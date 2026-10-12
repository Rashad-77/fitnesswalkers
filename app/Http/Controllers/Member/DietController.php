<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\{DietPlan, Food, Member};
use App\Services\DietEngine;
use Illuminate\Http\Request;

/**
 * The member's meal plan. It is built around the approved workout plan, so the workout plan has to exist first.
 * Questionnaire -> 7-day plan (main course, dishes, dessert, water) -> change any food to fit the calorie target.
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
        $day = max(1, min(7, (int) $r->query('day', 1)));
        $alts = [];
        if ($plan && $plan->source === 'app' && ($plan->plan['blocked'] ?? true) === false) {
            foreach ($plan->plan['days'][$day - 1]['meals'] as $meal) foreach ($meal['items'] as $it) {
                $alts[$it['grp']] ??= [];
                foreach (DietEngine::alternatives($plan->inputs, $it) as $f) {
                    $alts[$it['grp']][$f->id] = ['id' => $f->id, 'n' => $f->name, 's' => $f->sub, 'm' => $f->mealTagList(), 'k' => $f->kcal_serving, 'p' => $f->protein_serving, 'q' => $f->qty(1)];
                }
                $cur = Food::find($it['food_id']);
                if ($cur) $alts[$it['grp']][$cur->id] ??= ['id' => $cur->id, 'n' => $cur->name, 's' => $cur->sub, 'm' => $cur->mealTagList(), 'k' => $cur->kcal_serving, 'p' => $cur->protein_serving, 'q' => $cur->qty(1)];
            }
            $alts = array_map('array_values', $alts);
        }
        return view('member.diet', ['m' => $m, 'plan' => $plan, 'day' => $day, 'alts' => $alts, 'workout' => $workout, 'waiting' => $this->waiting($m)]);
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
            'form' => $r->session()->get('diet_form') ?? ($r->boolean('edit') ? $existing?->inputs : null), 'usuals' => $this->usualFoods(),
            'workout' => $this->workoutContext($m),
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
        $plan = DietEngine::generate($in);
        if ($plan['blocked']) {
            return redirect()->route('member.diet.create')->with('diet_issues', $plan['issues'])->with('diet_form', $in);
        }
        $wc = $this->workoutContext($m);
        $plan['water'] = $this->water($in);
        $plan['tips'] = $this->tips($in);
        $plan['schedule'] = $this->schedule($wc);
        $plan['workout'] = $wc;
        $m->dietPlans()->update(['active' => false]);
        DietPlan::create(['member_id' => $m->id, 'source' => 'app', 'title' => 'My meal plan', 'inputs' => $in, 'plan' => $plan, 'active' => true]);
        return redirect()->route('member.diet')->with('ok', 'Your meal plan is ready. Calories and protein are estimates.');
    }

    public function shopping()
    {
        $m = auth()->user()->member;
        $plan = $m->dietPlans()->where('active', true)->where('source', 'app')->first();
        abort_unless($plan, 404);
        return view('member.shopping', ['plan' => $plan, 'm' => $m]);
    }

    /** Re-roll one meal with different foods. */
    public function swap(Request $r)
    {
        $m = auth()->user()->member;
        $d = $r->validate(['day' => 'required|integer|between:0,6', 'meal' => 'required|integer|min:0|max:4']);
        $dp = $m->dietPlans()->where('active', true)->where('source', 'app')->firstOrFail();
        abort_unless(isset($dp->plan['days'][$d['day']]['meals'][$d['meal']]), 404);
        $dp->update(['plan' => $this->keep($dp->plan, DietEngine::swapMeal($dp->inputs, $dp->plan, $d['day'], $d['meal']))]);
        return redirect()->route('member.diet', ['day' => $d['day'] + 1])->with('ok', 'Meal swapped.');
    }

    /** Replace one food with another from the same group. */
    public function item(Request $r)
    {
        $m = auth()->user()->member;
        $d = $r->validate(['day' => 'required|integer|between:0,6', 'meal' => 'required|integer|min:0|max:4', 'item' => 'required|integer|min:0|max:30', 'food_id' => 'required|integer']);
        $dp = $m->dietPlans()->where('active', true)->where('source', 'app')->firstOrFail();
        $plan = DietEngine::swapItem($dp->inputs, $dp->plan, $d['day'], $d['meal'], $d['item'], $d['food_id']);
        $n = (int) $r->input('servings', 0);
        if ($n >= 1 && $n <= 8) $plan = DietEngine::resizeItem($plan, $d['day'], $d['meal'], $d['item'], $n);   // eat more or less of it
        $dp->update(['plan' => $this->keep($dp->plan, $plan)]);
        return redirect()->route('member.diet', ['day' => $d['day'] + 1])->with('ok', 'Saved. Your day total is updated.');
    }

    /* ------------------------------------------------------------------ helpers */

    /** The extras we store beside the engine's plan survive a swap. */
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

    /** Which of the 7 plan days are workout days, spread through the week (rest days between). Each workout day names the workout plan day it matches. */
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
        if (($in['best_shape'] ?? '') === 'teen' || ($in['best_shape'] ?? '') === 'long_ago') $t[] = 'Your best shape was a while ago, so go at a steady pace. Slow and steady is what lasts.';
        $t[] = 'Have a glass of water before each meal, and keep a bottle with you while you train.';
        return $t;
    }

    /** Foods a member can tap as "what I usually eat": the staples, by meal. */
    private function usualFoods(): array
    {
        $foods = Food::where('active', true)->whereIn('grp', ['cereal', 'protein', 'pulse', 'egg'])->orderBy('name')->get();
        $pick = fn (string $tag, array $grps) => $foods->filter(fn ($f) => in_array($f->grp, $grps, true) && in_array($tag, $f->mealTagList(), true))->values();
        return ['breakfast' => $pick('breakfast', ['cereal', 'egg', 'pulse']), 'lunch' => $pick('main', ['cereal', 'protein', 'pulse']), 'dinner' => $pick('main', ['cereal', 'protein', 'pulse'])];
    }

    /** The questionnaire answers as the engine wants them. $strict = validate (a real submit); otherwise just read what is there (the live preview). */
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
            'usual_breakfast' => 'array', 'usual_lunch' => 'array', 'usual_dinner' => 'array',
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
        $d['usuals'] = ['breakfast' => array_map('intval', (array) ($d['usual_breakfast'] ?? [])), 'lunch' => array_map('intval', (array) ($d['usual_lunch'] ?? [])), 'dinner' => array_map('intval', (array) ($d['usual_dinner'] ?? []))];
        unset($d['understand'], $d['usual_breakfast'], $d['usual_lunch'], $d['usual_dinner']);
        return $d;
    }
}
