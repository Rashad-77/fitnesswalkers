<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Dish, Food};
use App\Services\DishPlanner;
use Illuminate\Http\Request;

/** Library > Dishes: the meals members are given. Each dish is a list of catalogue foods with amounts; the planner scales them to fit a member's day. */
class DishController extends Controller
{
    public function index(Request $r)
    {
        $kind = $r->query('kind'); $q = trim((string) $r->query('q'));
        $dishes = Dish::with('items.food')->when($kind, fn ($x, $k) => $x->where('kind', $k))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('local_name', 'like', "%$q%")))
            ->orderBy('kind')->orderBy('name')->get()
            ->each(fn ($d) => $d->one = $d->items->every(fn ($i) => $i->food) ? DishPlanner::dishAt($d, 1.0) : null);
        return view('admin.dishes.index', ['dishes' => $dishes, 'kind' => $kind, 'q' => $q, 'off' => Dish::where('active', false)->count()]);
    }

    public function create() { return view('admin.dishes.form', $this->formData(new Dish(['kind' => 'meal', 'slots' => 'lunch,dinner', 'active' => true]))); }

    public function edit(Dish $dish) { return view('admin.dishes.form', $this->formData($dish->load('items'))); }

    public function store(Request $r)
    {
        [$d, $items] = $this->data($r);
        $dish = Dish::create($d + ['active' => true]);
        $this->save($dish, $items);
        AuditLog::record('Added dish', $dish->name);
        return redirect()->route('admin.dishes.index')->with('ok', 'Dish added.');
    }

    public function update(Request $r, Dish $dish)
    {
        if ($r->has('toggle')) {
            $dish->update(['active' => ! $dish->active]);
            AuditLog::record($dish->active ? 'Switched dish on' : 'Switched dish off', $dish->name);
            return back()->with('ok', $dish->name.($dish->active ? ' is on again.' : ' is switched off. Members will not be given it in new plans.'));
        }
        [$d, $items] = $this->data($r);
        $dish->update($d + ['is_dummy' => false]);
        $this->save($dish, $items);
        AuditLog::record('Edited dish', $dish->name);
        return redirect()->route('admin.dishes.index')->with('ok', $dish->name.' updated.');
    }

    public function destroy(Dish $dish)
    {
        AuditLog::record('Deleted dish', $dish->name);
        $dish->delete();
        return back()->with('ok', 'Dish deleted. Existing member plans keep their saved copy.');
    }

    private function formData(Dish $dish): array
    {
        return ['dish' => $dish, 'foods' => Food::where('active', true)->orderBy('grp')->orderBy('name')->get(['id', 'name', 'grp', 'per_serving', 'unit', 'unit_plural', 'kcal_serving']),
            'kinds' => Dish::KINDS, 'slots' => Dish::SLOTS, 'tags' => Dish::TAGS];
    }

    private function data(Request $r): array
    {
        $d = $r->validate([
            'name' => 'required|max:120', 'local_name' => 'nullable|max:120', 'kind' => 'required|in:'.implode(',', array_keys(Dish::KINDS)),
            'slots' => 'required|array|min:1', 'slots.*' => 'in:'.implode(',', array_keys(Dish::SLOTS)), 'tags' => 'array', 'tags.*' => 'in:'.implode(',', array_keys(Dish::TAGS)),
            'prep_minutes' => 'nullable|integer|min:0|max:600', 'method' => 'nullable|max:2000',
            'items' => 'required|array|min:1|max:12', 'items.*.food_id' => 'required|exists:foods,id', 'items.*.servings' => 'required|numeric|min:0.25|max:20',
            'items.*.role' => 'required|in:main,dish,dessert', 'items.*.scales' => 'nullable|boolean',
        ], ['items.required' => 'Add at least one food to the dish.', 'items.min' => 'Add at least one food to the dish.']);
        $items = collect($d['items'])->values()->map(fn ($i, $n) => ['food_id' => (int) $i['food_id'], 'servings' => (float) $i['servings'], 'role' => $i['role'], 'scales' => ! empty($i['scales']), 'sort' => $n])->all();
        unset($d['items']);
        $d['slots'] = implode(',', $d['slots']); $d['tags'] = implode(',', $d['tags'] ?? []) ?: null; $d['local_name'] = $d['local_name'] ?? null ?: null; $d['method'] = $d['method'] ?? null ?: null; $d['prep_minutes'] = (int) ($d['prep_minutes'] ?? 20);
        return [$d, $items];
    }

    private function save(Dish $dish, array $items): void
    {
        $dish->items()->delete();
        $dish->items()->createMany($items);
        $dish->refreshFromItems();
    }
}
