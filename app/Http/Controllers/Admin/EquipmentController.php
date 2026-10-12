<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Equipment};
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public const CATEGORIES = ['machine', 'free weights', 'bodyweight', 'cardio'];

    public function index()
    {
        return view('admin.equipment.index', ['items' => Equipment::withCount('exercises')->orderBy('category')->orderBy('name')->get(), 'cats' => self::CATEGORIES]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required|max:100', 'category' => 'required|in:'.implode(',', self::CATEGORIES)]);
        Equipment::create($d);
        AuditLog::record('Added equipment', $d['name']);
        return back()->with('ok', 'Equipment added.');
    }

    public function update(Request $r, Equipment $equipment)
    {
        $d = $r->validate(['name' => 'sometimes|required|max:100', 'category' => 'sometimes|in:'.implode(',', self::CATEGORIES)]);
        $equipment->update($d + ['out_of_order' => $r->boolean('out_of_order'), 'is_dummy' => false]);
        AuditLog::record($equipment->out_of_order ? 'Marked out of order' : 'Marked working', $equipment->name);
        return back()->with('ok', $equipment->name.' updated. Exercises for out-of-order equipment are hidden from members.');
    }

    public function destroy(Equipment $equipment)
    {
        AuditLog::record('Deleted equipment', $equipment->name);
        $equipment->delete();
        return back()->with('ok', 'Equipment deleted. Its exercises now count as "no equipment needed", so check them.');
    }
}
