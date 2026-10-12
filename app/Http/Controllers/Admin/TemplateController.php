<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Exercise, WorkoutTemplate, WorkoutTemplateItem};
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index()
    {
        return view('admin.templates.index', ['templates' => WorkoutTemplate::withCount('items')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.templates.form', ['tpl' => new WorkoutTemplate(['days_per_week' => 3, 'goal' => 'general', 'level' => 'beginner']), 'exercises' => $this->exercises()]);
    }

    public function store(Request $r)
    {
        $tpl = WorkoutTemplate::create($this->data($r));
        $this->saveItems($tpl, $r);
        AuditLog::record('Added workout template', $tpl->name);
        return redirect()->route('admin.templates.index')->with('ok', 'Template saved.');
    }

    public function edit(WorkoutTemplate $template)
    {
        $template->load('items');
        return view('admin.templates.form', ['tpl' => $template, 'exercises' => $this->exercises()]);
    }

    public function update(Request $r, WorkoutTemplate $template)
    {
        $template->update($this->data($r) + ['is_dummy' => false]);
        $this->saveItems($template, $r);
        AuditLog::record('Edited workout template', $template->name);
        return redirect()->route('admin.templates.index')->with('ok', 'Template updated.');
    }

    public function destroy(WorkoutTemplate $template)
    {
        AuditLog::record('Deleted workout template', $template->name);
        $template->delete();
        return back()->with('ok', 'Template deleted.');
    }

    private function exercises()
    {
        return Exercise::orderBy('muscle_group')->orderBy('name')->get();
    }

    private function data(Request $r): array
    {
        return $r->validate(['name' => 'required|max:120', 'goal' => 'required|in:fat_loss,muscle_gain,general', 'level' => 'required|in:beginner,intermediate', 'days_per_week' => 'required|integer|between:1,7']);
    }

    private function saveItems(WorkoutTemplate $tpl, Request $r): void
    {
        $tpl->items()->delete();
        foreach (PlanItems::clean($r->input('items', [])) as $it) {
            WorkoutTemplateItem::create($it + ['workout_template_id' => $tpl->id]);
        }
    }
}
