<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\{DietPlan, Food};
use App\Services\DietGenerator;
use Illuminate\Http\Request;

class DietController extends Controller
{
    public function index(Request $r)
    {
        $m = auth()->user()->member;
        $plan = $m->dietPlans()->where('active', true)->first();
        $lastBody = $m->bodyLogs()->latest('logged_on')->first();
        return view('member.diet', [
            'm' => $m, 'plan' => $plan, 'day' => max(1, min(7, (int) $r->query('day', 1))), 'form' => $r->session()->get('diet_form'),
            'issues' => session('diet_issues'),
            'defaults' => ['age' => $m->age(), 'sex' => $m->sex, 'height' => $m->height_cm, 'weight' => $lastBody?->weight_kg],
            'allergens' => DietGenerator::ALLERGENS, 'priceDate' => Food::min('price_updated_on'),
        ]);
    }

    public function generate(Request $r)
    {
        $m = auth()->user()->member;
        $d = $r->validate([
            'goal' => 'required|in:lose,maintain,gain', 'age' => 'required|integer|between:10,90', 'sex' => 'required|in:male,female',
            'height' => 'required|numeric|between:100,250', 'weight' => 'required|numeric|between:25,300',
            'activity' => 'required|in:sedentary,light,moderate,active', 'diet_type' => 'required|in:vegan,vegetarian,non_veg',
            'allergies' => 'array', 'allergies.*' => 'in:'.implode(',', DietGenerator::ALLERGENS),
            'meals' => 'required|integer|between:3,5', 'budget' => 'required|numeric|min:100|max:20000',
        ]);
        $d['pregnant'] = $r->boolean('pregnant');
        $d['medical'] = $r->boolean('medical');
        $d['allergies'] = $d['allergies'] ?? [];

        $plan = DietGenerator::generate($d);
        if ($plan['blocked']) {
            return redirect()->route('member.diet')->with('diet_issues', $plan['issues'])->with('diet_form', $d);
        }
        $m->dietPlans()->update(['active' => false]);
        DietPlan::create(['member_id' => $m->id, 'source' => 'app', 'title' => 'App-made plan', 'inputs' => $d, 'plan' => $plan, 'active' => true]);
        return redirect()->route('member.diet')->with('ok', 'Your plan is ready. Prices are estimates.');
    }

    public function shopping()
    {
        $m = auth()->user()->member;
        $plan = $m->dietPlans()->where('active', true)->where('source', 'app')->first();
        abort_unless($plan, 404);
        return view('member.shopping', ['plan' => $plan, 'priceDate' => Food::min('price_updated_on')]);
    }

    public function swap(Request $r)
    {
        $m = auth()->user()->member;
        $d = $r->validate(['day' => 'required|integer|between:0,6', 'meal' => 'required|integer|min:0|max:4']);
        $dp = $m->dietPlans()->where('active', true)->where('source', 'app')->firstOrFail();
        $plan = $dp->plan;
        if (! isset($plan['days'][$d['day']]['meals'][$d['meal']])) abort(404);
        $dp->update(['plan' => DietGenerator::swapMeal($dp->inputs, $plan, $d['day'], $d['meal'])]);
        return redirect()->route('member.diet', ['day' => $d['day'] + 1])->with('ok', 'Meal swapped.');
    }
}
