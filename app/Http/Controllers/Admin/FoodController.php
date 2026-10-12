<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Food};
use Illuminate\Http\Request;

/** Library > Foods: the catalogue the meal planner draws from. The owner can switch a food off if members cannot buy it, edit it, or add a local one. */
class FoodController extends Controller
{
    public function index(Request $r)
    {
        $grp = $r->query('group'); $q = trim((string) $r->query('q'));
        $foods = Food::when($grp, fn ($x, $g) => $x->where('grp', $g))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('local_name', 'like', "%$q%")))
            ->orderBy('grp')->orderBy('name')->get();
        return view('admin.foods.index', [
            'foods' => $foods, 'group' => $grp, 'q' => $q, 'groups' => Food::SHORT, 'diets' => Food::DIETS, 'meal_tags' => Food::MEAL_TAGS,
            'counts' => Food::selectRaw('grp, count(*) as n')->groupBy('grp')->pluck('n', 'grp'), 'off' => Food::where('active', false)->count(),
            'edit' => $r->query('edit') ? Food::find($r->query('edit')) : null,
        ]);
    }

    public function store(Request $r)
    {
        $f = Food::create($this->data($r) + ['active' => true]);
        AuditLog::record('Added food', $f->name);
        return redirect()->route('admin.foods.index')->with('ok', 'Food added.');
    }

    public function update(Request $r, Food $food)
    {
        if ($r->has('toggle')) {                         // the on/off switch in the table
            $food->update(['active' => ! $food->active]);
            AuditLog::record($food->active ? 'Switched food on' : 'Switched food off', $food->name);
            return back()->with('ok', $food->name.($food->active ? ' is on again.' : ' is switched off. Members will not be given it in new plans.'));
        }
        $food->update($this->data($r) + ['is_dummy' => false]);
        AuditLog::record('Edited food', $food->name);
        return redirect()->route('admin.foods.index')->with('ok', $food->name.' updated.');
    }

    public function destroy(Food $food)
    {
        AuditLog::record('Deleted food', $food->name);
        $food->delete();
        return back()->with('ok', 'Food deleted. Existing member plans keep their saved copy.');
    }

    private function data(Request $r): array
    {
        $d = $r->validate([
            'name' => 'required|max:120', 'local_name' => 'nullable|max:120', 'grp' => 'required|in:'.implode(',', array_keys(Food::SHORT)), 'sub' => 'nullable|max:40',
            'per_serving' => 'required|numeric|min:0.01|max:1000', 'unit' => 'required|max:30', 'unit_plural' => 'nullable|max:30', 'serving_g' => 'nullable|numeric|min:0|max:2000',
            'kcal_serving' => 'required|integer|min:0|max:2000', 'protein_serving' => 'required|numeric|min:0|max:200',
            'diet' => 'required|in:'.implode(',', array_keys(Food::DIETS)), 'allergens' => 'array', 'allergens.*' => 'in:'.implode(',', Food::ALLERGENS),
            'tags' => 'array', 'tags.*' => 'in:beef,pork', 'meal_tags' => 'array', 'meal_tags.*' => 'in:breakfast,main,snack', 'note' => 'nullable|max:200',
        ]);
        $d['allergens'] = implode(',', $d['allergens'] ?? []) ?: null;
        $d['tags'] = implode(',', $d['tags'] ?? []) ?: null;
        $d['meal_tags'] = implode(',', $d['meal_tags'] ?? ['main']);
        $d['whole_grain'] = $r->boolean('whole_grain'); $d['limited'] = $r->boolean('limited');
        $d['sub'] = $d['sub'] ?: null; $d['unit_plural'] = $d['unit_plural'] ?: null; $d['local_name'] = $d['local_name'] ?: null;
        return $d;
    }
}
