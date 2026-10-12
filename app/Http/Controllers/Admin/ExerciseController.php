<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Equipment, Exercise};
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public const GROUPS = ['Chest', 'Back', 'Shoulders', 'Arms', 'Legs', 'Core', 'Cardio'];

    public function index(Request $r)
    {
        $all = Exercise::with('equipment')->when($r->query('group'), fn ($q, $g) => $q->where('muscle_group', $g))
            ->when($r->query('q'), fn ($q, $s) => $q->where('name', 'like', "%$s%"))->orderBy('muscle_group')->orderBy('name')->get();
        $missing = Exercise::all()->filter(fn ($e) => ! $e->mediaUrl())->count();
        $items = $r->query('tutorial') === 'missing' ? $all->filter(fn ($e) => ! $e->mediaUrl())->values() : $all;
        return view('admin.exercises.index', ['items' => $items, 'groups' => self::GROUPS, 'group' => $r->query('group'), 'q' => $r->query('q'),
            'missing' => $missing, 'total' => Exercise::count(), 'onlyMissing' => $r->query('tutorial') === 'missing']);
    }

    public function create()
    {
        return view('admin.exercises.form', ['ex' => new Exercise(), 'equipment' => Equipment::orderBy('name')->get(), 'groups' => self::GROUPS]);
    }

    public function store(Request $r)
    {
        $ex = Exercise::create($this->data($r, new Exercise()));
        AuditLog::record('Added exercise', $ex->name);
        return redirect()->route('admin.exercises.index')->with('ok', 'Exercise saved.');
    }

    public function edit(Exercise $exercise)
    {
        return view('admin.exercises.form', ['ex' => $exercise, 'equipment' => Equipment::orderBy('name')->get(), 'groups' => self::GROUPS]);
    }

    public function update(Request $r, Exercise $exercise)
    {
        $exercise->update($this->data($r, $exercise) + ['is_dummy' => false]);
        AuditLog::record('Edited exercise', $exercise->name);
        return redirect()->route('admin.exercises.index')->with('ok', 'Exercise updated.');
    }

    public function destroy(Exercise $exercise)
    {
        $this->removeClip($exercise);
        AuditLog::record('Deleted exercise', $exercise->name);
        $exercise->delete();
        return back()->with('ok', 'Exercise deleted (and removed from any templates and plans).');
    }

    private function data(Request $r, Exercise $ex): array
    {
        $d = $r->validate([
            'name' => 'required|max:120', 'muscle_group' => 'required|in:'.implode(',', self::GROUPS),
            'equipment_id' => 'nullable|exists:equipment,id', 'steps' => 'required|string|max:4000', 'mistakes' => 'nullable|string|max:2000',
            'clip_url' => 'nullable|url|max:500', 'clip' => 'nullable|file|mimes:mp4,webm,gif,webp|max:20480',
        ]);
        unset($d['clip']);
        if ($r->hasFile('clip')) {
            if (env('VERCEL')) {   // Vercel's disk is temporary, so an uploaded file would vanish
                throw \Illuminate\Validation\ValidationException::withMessages(['clip' => 'File uploads are not available on this hosting. Paste a link to the GIF instead, or add the file to public/img/exercises named after the exercise.']);
            }
            $this->removeClip($ex);
            $name = bin2hex(random_bytes(8)).'.'.$r->file('clip')->getClientOriginalExtension();
            $r->file('clip')->move(public_path('uploads/clips'), $name);
            $d['clip_url'] = '/uploads/clips/'.$name;
        } elseif (! $r->filled('clip_url')) {
            $d['clip_url'] = $ex->clip_url; // keep what is there
        }
        if ($r->boolean('remove_clip')) { $this->removeClip($ex); $d['clip_url'] = null; }
        return $d;
    }

    private function removeClip(Exercise $ex): void
    {
        if ($ex->clip_url && str_starts_with($ex->clip_url, '/uploads/clips/')) {
            @unlink(public_path($ex->clip_url));
        }
    }
}
