<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, DietCheckin, DietPlan, Member};
use App\Services\{DietCheckins, DietEngine, DishPlanner};
use Illuminate\Http\Request;

class DietController extends Controller
{
    public function index()
    {
        $plans = DietPlan::with('member.user')->latest()->get();
        $flags = $plans->where('active', true)->mapWithKeys(fn ($p) => [$p->id => DietCheckins::flags($p->member)]);
        return view('admin.diets.index', ['plans' => $plans, 'flags' => $flags]);
    }

    public function show(DietPlan $diet)
    {
        $diet->load('member.user');
        $dishes = $diet->source === 'app' && ($diet->plan['format'] ?? '') === 'dishes';
        return view('admin.diets.show', ['diet' => $diet, 'dishes' => $dishes,
            'limits' => $dishes ? DietEngine::targetLimits($diet->inputs, true) : null,
            'flags' => $dishes && $diet->active ? DietCheckins::flags($diet->member) : [],
            'checkins' => DietCheckin::where('diet_plan_id', $diet->id)->latest('week_of')->latest('id')->take(8)->get()]);
    }

    /** The owner sets the member's targets inside a wider band than the member gets. The member can no longer change them. */
    public function targets(Request $r, DietPlan $diet)
    {
        abort_unless($diet->source === 'app' && ($diet->plan['format'] ?? '') === 'dishes', 404);
        $in = $diet->inputs; $lim = DietEngine::targetLimits($in, true);
        $d = $r->validate([
            'kcal' => "required|integer|between:{$lim['kcal'][0]},{$lim['kcal'][1]}", 'protein' => "required|integer|between:{$lim['protein'][0]},{$lim['protein'][1]}",
            'fat_pct' => "required|integer|between:{$lim['fat_pct'][0]},{$lim['fat_pct'][1]}",
        ]);
        $carbs = ($d['kcal'] - 4 * $d['protein'] - 9 * round($d['kcal'] * $d['fat_pct'] / 100 / 9)) / 4;
        if ($carbs < $lim['carbs_min']) return back()->withErrors(['kcal' => 'Those numbers leave under '.$lim['carbs_min'].' g of carbs. Lower the protein or fat, or raise the calories.'])->withInput();
        $in['override'] = ['kcal' => (int) $d['kcal'], 'protein' => (int) $d['protein'], 'fat_pct' => (int) $d['fat_pct']]; $in['override_by'] = 'owner';
        return $this->rebuild($diet, $in, 'Targets set. The week was rebuilt, and the member can no longer change them.');
    }

    /** Hand the targets back: back to the survey numbers, and the member may adjust them again. */
    public function resetTargets(DietPlan $diet)
    {
        abort_unless($diet->source === 'app' && ($diet->plan['format'] ?? '') === 'dishes', 404);
        $in = $diet->inputs; unset($in['override'], $in['override_by']);
        return $this->rebuild($diet, $in, 'Targets are back to the survey numbers.');
    }

    private function rebuild(DietPlan $diet, array $in, string $msg)
    {
        $plan = DishPlanner::generate($in);
        if (! empty($plan['blocked'])) return back()->with('warn', 'These numbers cannot make a plan: '.implode(' ', $plan['issues']));
        foreach (['water', 'tips', 'schedule', 'workout'] as $k) if (isset($diet->plan[$k])) $plan[$k] = $diet->plan[$k];
        $diet->update(['inputs' => $in, 'plan' => $plan]);
        AuditLog::record('Set meal plan targets', $diet->member->member_no, ($in['override']['kcal'] ?? 'survey').' kcal');
        return redirect()->route('admin.diets.show', $diet)->with('ok', $msg);
    }

    public function create(Request $r)
    {
        return view('admin.diets.create', [
            'members' => Member::with('user')->get()->sortBy(fn ($m) => $m->user->name)->values(), 'selected' => $r->query('member'),
        ]);
    }

    /** An owner-written plan replaces whatever plan the member was using (the app plan stays in history). */
    public function store(Request $r)
    {
        $d = $r->validate(['member_id' => 'required|exists:members,id', 'title' => 'required|max:120', 'owner_text' => 'required|max:6000']);
        DietPlan::where('member_id', $d['member_id'])->update(['active' => false]);
        $p = DietPlan::create($d + ['source' => 'owner', 'active' => true]);
        AuditLog::record('Wrote diet plan for member', Member::find($d['member_id'])->member_no, $d['title']);
        return redirect()->route('admin.diets.show', $p)->with('ok', 'Saved. The member now sees this plan instead of any app-made plan.');
    }

    public function destroy(DietPlan $diet)
    {
        AuditLog::record('Deleted diet plan', $diet->member->member_no, $diet->title);
        $diet->delete();
        return redirect()->route('admin.diets.index')->with('ok', 'Diet plan deleted.');
    }
}
