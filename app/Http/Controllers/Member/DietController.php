<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\{DietCheckin, DietPlan, Food, Member};
use App\Services\{DietCheckins, DietEngine, DishPlanner};
use Illuminate\Http\Request;

/**
 * The member's meal plan. It is built around the approved workout plan, so the workout plan has to exist first.
 * Survey -> daily targets (calories, protein, carbs, fat) -> a week of dishes with amounts -> change any dish or portion and see how
 * it moves the day against the targets -> a weekly check-in that nudges the calories.
 */
class DietController extends Controller
{
    public const ACHIEVEMENTS = ['lose' => 'Lose weight', 'tone' => 'Lose fat and tone up', 'muscle' => 'Build muscle', 'gain' => 'Gain weight', 'maintain' => 'Stay the same'];
    public const CAUSES = ['sleep' => 'Not enough sleep', 'stress' => 'Stress', 'emotional' => 'Eating when upset or bored', 'irregular' => 'Skipping or irregular meals',
        'desk' => 'Sitting most of the day', 'medication' => 'A medical condition or medication'];

    public function index(Request $r)
    {
        $m = auth()->user()->member;
        $workout = $m->approvedPlan();
        $plan = $m->dietPlans()->where('active', true)->first();
        $day = max(1, min(7, (int) $r->query('day', now()->dayOfWeekIso)));
        $tab = in_array($r->query('tab'), ['targets', 'checkin'], true) ? $r->query('tab') : 'meals';
        $data = ['m' => $m, 'plan' => $plan, 'day' => $day, 'tab' => $tab, 'workout' => $workout, 'waiting' => $this->waiting($m)];
        if ($plan && $plan->source === 'app') {
            $p = $plan->plan;
            if (($p['format'] ?? '') === 'dishes' && empty($p['blocked'])) {
                $in = $plan->inputs; $d = $p['days'][$day - 1];
                if ($tab !== 'meals') $d = ['meals' => []];
                $opts = []; $dess = DishPlanner::options($in, 'lunch', 'dessert');
                foreach ($d['meals'] as $mt) { $k = str_starts_with($mt['slot'], 'snack') ? 'snack' : $mt['slot']; $opts[$k] ??= DishPlanner::options($in, $mt['slot'], $k === 'snack' ? 'snack' : 'meal'); }
                $data += ['ui' => ['t' => $p['targets'], 'meals' => collect($d['meals'])->map(fn ($mt, $i) => ['mi' => $i, 'slot' => $mt['slot'], 'dish' => $mt['dish_id'], 'scale' => $mt['scale'],
                    'des' => $mt['dessert']['dish_id'] ?? null, 'dscale' => $mt['dessert']['scale'] ?? 1, 'km' => $mt['dish_totals'], 'kd' => $mt['dessert']['totals'] ?? ['kcal' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0]])->values()->all(), 'opts' => $opts, 'dess' => $dess],
                    'limits' => DietEngine::targetLimits($in), 'ownerSet' => ($in['override_by'] ?? null) === 'owner', 'due' => DietCheckins::isDue($plan), 'dueOn' => DietCheckins::dueOn($plan),
                    'checkins' => DietCheckin::where('member_id', $m->id)->latest('week_of')->latest('id')->take(4)->get(), 'lastWeight' => $m->bodyLogs()->latest('logged_on')->first()?->weight_kg ?? $in['weight']];
            }
        }
        return view('member.diet', $data);
    }

    /** The questionnaire, one question to a screen. */
    public function create(Request $r)
    {
        $m = auth()->user()->member;
        if (! $m->approvedPlan()) return redirect()->route('member.diet');
        $last = $m->bodyLogs()->latest('logged_on')->first();
        $existing = $m->dietPlans()->where('active', true)->where('source', 'app')->first();
        return view('member.diet_create', [
            'issues' => session('diet_issues'), 'hasPlan' => (bool) $existing, 'allergens' => Food::ALLERGENS, 'achievements' => self::ACHIEVEMENTS, 'causes' => self::CAUSES,
            'form' => $r->session()->get('diet_form') ?? ($r->boolean('edit') ? $existing?->inputs : null), 'workout' => $this->workoutContext($m),
            'defaults' => ['age' => $m->age(), 'sex' => $m->sex, 'height' => $m->height_cm, 'weight' => $last?->weight_kg],
        ]);
    }

    /** Live numbers on the last screen of the questionnaire. */
    public function preview(Request $r)
    {
        $m = auth()->user()->member;
        if (! $m->approvedPlan()) return response()->json(['incomplete' => true]);
        $in = $this->answers($r, $m, false);
        foreach (['age', 'sex', 'height', 'weight'] as $k) if (empty($in[$k])) return response()->json(['incomplete' => true]);
        $s = DietEngine::summary($in);
        $water = $this->water($in);
        return response()->json($s['blocked'] ? ['blocked' => true, 'issues' => $s['issues'], 'targets' => $s['targets']]
            : ['blocked' => false, 'targets' => $s['targets'], 'servings' => $s['servings'], 'notes' => array_merge($s['notes'], $this->tips($in)), 'warnings' => $s['warnings'], 'water' => $water]);
    }

    public function generate(Request $r)
    {
        $m = auth()->user()->member;
        abort_unless($m->approvedPlan(), 403);
        $in = $this->answers($r, $m, true);
        $plan = DishPlanner::generate($in);
        if ($plan['blocked']) {
            return redirect()->route('member.diet.create')->with('diet_issues', $plan['issues'])->with('diet_form', $in);
        }
        $wc = $this->workoutContext($m);
        $plan['water'] = $this->water($in); $plan['tips'] = $this->tips($in); $plan['schedule'] = $this->schedule($wc); $plan['workout'] = $wc;
        $m->dietPlans()->update(['active' => false]);
        DietPlan::create(['member_id' => $m->id, 'source' => 'app', 'title' => 'My meal plan', 'inputs' => $in, 'plan' => $plan, 'active' => true]);
        return redirect()->route('member.diet')->with('ok', 'Your meal plan is ready. Calories and nutrients are estimates.');
    }

    /** Change a dish, its portion or its dessert for one meal. */
    public function dish(Request $r)
    {
        $dp = $this->activePlan();
        $d = $r->validate(['day' => 'required|integer|between:0,6', 'meal' => 'required|integer|min:0|max:4', 'dish_id' => 'nullable|integer', 'scale' => 'nullable|numeric', 'part' => 'nullable|in:main,dessert', 'drop' => 'nullable|boolean']);
        $plan = $dp->plan; $part = $d['part'] ?? 'main';
        abort_unless(isset($plan['days'][$d['day']]['meals'][$d['meal']]), 404);
        if ($r->boolean('drop')) $plan = DishPlanner::dropDessert($plan, $d['day'], $d['meal']);
        elseif (! empty($d['dish_id'])) $plan = DishPlanner::swapDish($dp->inputs, $plan, $d['day'], $d['meal'], (int) $d['dish_id'], isset($d['scale']) ? (float) $d['scale'] : null, $part);
        elseif (isset($d['scale'])) $plan = DishPlanner::setScale($dp->inputs, $plan, $d['day'], $d['meal'], (float) $d['scale'], $part);
        $dp->update(['plan' => $this->keep($dp->plan, $plan)]);
        return redirect()->route('member.diet', ['day' => $d['day'] + 1])->with('ok', 'Saved. Your day is updated.');
    }

    /** Surprise me: a different dish for the meal. */
    public function reroll(Request $r)
    {
        $dp = $this->activePlan();
        $d = $r->validate(['day' => 'required|integer|between:0,6', 'meal' => 'required|integer|min:0|max:4']);
        abort_unless(isset($dp->plan['days'][$d['day']]['meals'][$d['meal']]), 404);
        $dp->update(['plan' => $this->keep($dp->plan, DishPlanner::reroll($dp->inputs, $dp->plan, $d['day'], $d['meal']))]);
        return redirect()->route('member.diet', ['day' => $d['day'] + 1])->with('ok', 'Here is another dish.');
    }

    /** The member adjusts their own calories, protein and fat, inside safe limits. The plan is rebuilt around them. */
    public function targets(Request $r)
    {
        $dp = $this->activePlan();
        $in = $dp->inputs;
        if (($in['override_by'] ?? null) === 'owner') return back()->with('warn', 'Your trainer set your targets, so ask them if you want a change.');
        $lim = DietEngine::targetLimits($in);
        $d = $r->validate([
            'kcal' => "required|integer|between:{$lim['kcal'][0]},{$lim['kcal'][1]}", 'protein' => "required|integer|between:{$lim['protein'][0]},{$lim['protein'][1]}",
            'fat_pct' => "required|integer|between:{$lim['fat_pct'][0]},{$lim['fat_pct'][1]}",
        ], [
            'kcal.between' => "Calories must be between {$lim['kcal'][0]} and {$lim['kcal'][1]}, which is a safe range for your body and goal.",
            'protein.between' => "Protein must be between {$lim['protein'][0]} and {$lim['protein'][1]} g a day.",
            'fat_pct.between' => "Fat should be between {$lim['fat_pct'][0]}% and {$lim['fat_pct'][1]}% of your calories.",
        ]);
        $carbs = ($d['kcal'] - 4 * $d['protein'] - 9 * round($d['kcal'] * $d['fat_pct'] / 100 / 9)) / 4;
        if ($carbs < $lim['carbs_min']) return back()->withErrors(['kcal' => 'With those numbers you would get under '.$lim['carbs_min'].' g of carbs, which is too low. Lower the protein or fat, or raise the calories.'])->withInput();
        $in['override'] = ['kcal' => (int) $d['kcal'], 'protein' => (int) $d['protein'], 'fat_pct' => (int) $d['fat_pct']]; unset($in['override_by']);
        return $this->rebuild($dp, $in, 'Your targets are saved and your week is rebuilt around them.');
    }

    /** Back to what the survey gave. */
    public function resetTargets()
    {
        $dp = $this->activePlan(); $in = $dp->inputs;
        if (($in['override_by'] ?? null) === 'owner') return back()->with('warn', 'Your trainer set your targets, so ask them if you want a change.');
        unset($in['override']);
        return $this->rebuild($dp, $in, 'Back to the targets from your survey.');
    }

    /** The weekly check-in: weight and how the week went. The calories move a small step if the weight is not on pace. */
    public function checkin(Request $r)
    {
        $m = auth()->user()->member; $dp = $this->activePlan();
        $d = $r->validate(['weight' => 'required|numeric|between:25,300', 'followed' => 'required|in:most,some,little', 'hunger' => 'required|in:low,ok,high', 'energy' => 'required|in:low,ok,high', 'note' => 'nullable|max:300']);
        if (! DietCheckins::isDue($dp) && DietCheckin::where('diet_plan_id', $dp->id)->whereDate('week_of', today())->exists()) return back()->with('warn', 'You already checked in today.');
        $c = DietCheckins::record($m, $dp, $d);
        return redirect()->route('member.diet', ['tab' => 'checkin'])->with($c->action === 'hold' ? 'warn' : 'ok', $c->message);
    }

    /* ------------------------------------------------------------------ helpers */

    private function activePlan(): DietPlan
    {
        $m = auth()->user()->member;
        $dp = $m->dietPlans()->where('active', true)->where('source', 'app')->firstOrFail();
        abort_unless(($dp->plan['format'] ?? '') === 'dishes', 404);
        return $dp;
    }

    /** Rebuild the week from changed inputs, keeping the extras stored beside the plan. */
    private function rebuild(DietPlan $dp, array $in, string $message)
    {
        $plan = DishPlanner::generate($in);
        if (! empty($plan['blocked'])) return back()->with('warn', 'These numbers cannot make a plan: '.implode(' ', $plan['issues']));
        $dp->update(['inputs' => $in, 'plan' => $this->keep($dp->plan, $plan)]);
        return redirect()->route('member.diet')->with('ok', $message);
    }

    /** The extras we store beside the planner's plan survive a change. */
    private function keep(array $old, array $new): array
    {
        foreach (['water', 'tips', 'schedule', 'workout'] as $k) if (isset($old[$k])) $new[$k] = $old[$k];
        return $new;
    }

    /** What the member is waiting on, in plain words. */
    private function waiting(Member $m): ?string
    {
        if ($m->approvedPlan()) return null;
        if ($m->planRequests()->where('status', 'pending')->exists()) return $m->balanceDue() > 0 ? 'Your workout plan is waiting for your payment, then the owner will approve it.' : 'Your workout plan request is with the owner.';
        return 'You have not asked for a workout plan yet.';
    }

    /** Days a week and minutes a session, read from the approved workout plan. */
    private function workoutContext(Member $m): array
    {
        $plan = $m->approvedPlan();
        $days = $plan ? $plan->items->groupBy('day_no') : collect();
        $mins = $days->isEmpty() ? 45 : (int) max(30, min(120, round($days->map(fn ($it) => 10 + $it->sum(fn ($i) => max(1, (int) $i->sets) * 2.5))->avg() / 5) * 5));
        return ['name' => $plan?->name, 'days' => max(1, min(6, $days->count())), 'minutes' => $mins,
            'day_nos' => $days->keys()->map(fn ($k) => (int) $k)->values()->all(),
            'focus' => $days->map(fn ($it) => $it->map(fn ($i) => $i->exercise?->muscle_group)->filter()->unique()->take(2)->implode(' and '))->values()->all()];
    }

    /** Which of the 7 plan days are workout days, spread through the week (rest days between). */
    private function schedule(array $wc): array
    {
        $pattern = [1 => [2], 2 => [0, 3], 3 => [0, 2, 4], 4 => [0, 1, 3, 4], 5 => [0, 1, 2, 3, 4], 6 => [0, 1, 2, 3, 4, 5]][$wc['days']] ?? [0, 2, 4];
        $out = []; $n = 0;
        for ($d = 0; $d < 7; $d++) {
            if (in_array($d, $pattern, true)) { $k = $n % max(1, count($wc['day_nos'])); $out[] = ['workout' => true, 'plan_day' => $wc['day_nos'][$k] ?? ($k + 1), 'focus' => $wc['focus'][$k] ?? '']; $n++; }
            else $out[] = ['workout' => false];
        }
        return $out;
    }

    /** Daily water: about 35 ml per kg of body weight, and more when training. */
    private function water(array $in): array
    {
        $litres = round(((float) ($in['weight'] ?? 60) * 0.035 + ((int) ($in['train_days'] ?? 0) > 0 ? 0.5 : 0)) * 2) / 2;
        $litres = max(2.0, min(4.5, $litres));
        return ['litres' => $litres, 'glasses' => (int) ceil($litres * 4)];
    }

    /** Short, practical notes from the answers. */
    private function tips(array $in): array
    {
        $causes = (array) ($in['causes'] ?? []);
        $t = [];
        if (in_array('sleep', $causes, true)) $t[] = 'Poor sleep makes hunger harder to manage. Aim for 7 to 8 hours, and your plan will be easier to follow.';
        if (in_array('stress', $causes, true)) $t[] = 'Stress can push weight the wrong way. Your workouts help, and so does eating regular meals instead of skipping them.';
        if (in_array('emotional', $causes, true)) $t[] = 'If you eat when upset or bored, keep a fruit or a cup of curd ready. The snack in your plan is there for exactly that.';
        if (in_array('irregular', $causes, true)) $t[] = 'Try to eat at the same times each day. Your plan spreads food over '.($in['meals'] ?? 3).' meals so you do not get too hungry.';
        if (in_array($in['best_shape'] ?? '', ['teen', 'long_ago'], true)) $t[] = 'Your best shape was a while ago, so go at a steady pace. Slow and steady is what lasts.';
        $t[] = 'Have a glass of water before each meal, and keep a bottle with you while you train.';
        return $t;
    }

    /** The questionnaire answers as the planner wants them. $strict = validate (a real submit); otherwise just read what is there (the live preview). */
    private function answers(Request $r, Member $m, bool $strict): array
    {
        $rules = [
            'sex' => 'required|in:male,female', 'age' => 'required|integer|between:10,90', 'height' => 'required|numeric|between:100,250', 'weight' => 'required|numeric|between:25,300',
            'achievement' => 'required|in:'.implode(',', array_keys(self::ACHIEVEMENTS)), 'pace' => 'nullable|in:gentle,steady', 'target_weight' => 'nullable|numeric|between:25,300',
            'current_body' => 'required|in:lean,average,soft,heavy', 'body_goal' => 'required|in:lean,toned,athletic,muscular,healthy',
            'metabolism' => 'required|in:slow,average,fast', 'best_shape' => 'required|in:never,recent,few_years,long_ago,teen',
            'causes' => 'array', 'causes.*' => 'in:'.implode(',', array_keys(self::CAUSES)), 'job' => 'required|in:sitting,standing,heavy',
            'meals' => 'required|integer|between:3,5', 'pattern' => 'required|in:'.implode(',', array_keys(DietEngine::PATTERNS)),
            'allergies' => 'array', 'allergies.*' => 'in:'.implode(',', Food::ALLERGENS),
        ];
        if ($strict) $rules['understand'] = 'accepted';
        $d = $strict ? $r->validate($rules, ['understand.accepted' => 'Please tick the box to say you understand.']) : $r->only(array_keys($rules));

        $wc = $this->workoutContext($m);
        $achieve = $d['achievement'] ?? 'maintain';
        $d['goal'] = ['lose' => 'lose', 'tone' => 'lose', 'muscle' => 'gain', 'gain' => 'gain', 'maintain' => 'maintain'][$achieve] ?? 'maintain';
        if ($achieve === 'tone' || $achieve === 'muscle') $d['pace'] = 'gentle';
        $d['pace'] ??= 'gentle';
        $d['allergies'] = array_values(array_filter((array) ($d['allergies'] ?? []), 'is_string'));
        $d['causes'] = array_values(array_filter((array) ($d['causes'] ?? []), 'is_string'));
        foreach (['no_beef', 'no_pork', 'pregnant', 'medical', 'eating_disorder'] as $k) $d[$k] = $r->boolean($k);
        if (in_array('medication', $d['causes'], true)) $d['medical'] = true;
        $d['train_days'] = $wc['days']; $d['train_minutes'] = $wc['minutes'];                // from the workout plan, not asked
        unset($d['understand']);
        return $d;
    }
}
