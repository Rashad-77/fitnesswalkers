<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\{BodyLog, Exercise, Goal, WorkoutLog};
use App\Services\Bmi;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function bmi()
    {
        return redirect()->to(route('member.progress').'#bmi');
    }

    public function saveBmi(Request $r)
    {
        $m = auth()->user()->member;
        $d = $r->validate(['weight_kg' => 'required|numeric|between:20,300', 'height_cm' => 'required|numeric|between:100,250']);
        $bmi = Bmi::calc($d['weight_kg'], $d['height_cm']);
        BodyLog::updateOrCreate(['member_id' => $m->id, 'logged_on' => today()], ['weight_kg' => $d['weight_kg'], 'height_cm' => $d['height_cm'], 'bmi' => $bmi, 'is_dummy' => false]);
        if (! $m->height_cm) $m->update(['height_cm' => $d['height_cm']]);
        return redirect()->to(route('member.progress').'#bmi')->with('ok', 'BMI saved: '.$bmi.' ('.Bmi::category($bmi).').');
    }

    public function index(Request $r)
    {
        $m = auth()->user()->member;
        $body = $m->bodyLogs()->get();
        $lifts = WorkoutLog::where('member_id', $m->id)->pluck('exercise_id')->unique();
        $exercises = Exercise::whereIn('id', $lifts)->orderBy('name')->get();
        $exId = (int) ($r->query('exercise') ?: ($exercises->first()->id ?? 0));
        $liftSeries = WorkoutLog::where('member_id', $m->id)->where('exercise_id', $exId)->orderBy('logged_on')->get()
            ->groupBy(fn ($l) => $l->logged_on->toDateString())->map(fn ($g) => (float) $g->max('weight_kg'));
        // sessions per week for the last 8 weeks
        $weeks = [];
        for ($i = 7; $i >= 0; $i--) {
            $s = now()->startOfWeek()->subWeeks($i);
            $weeks[$s->format('d M')] = $m->workoutLogs()->whereBetween('logged_on', [$s, $s->copy()->endOfWeek()])->distinct()->count('logged_on');
        }
        return view('member.progress', [
            'm' => $m, 'body' => $body, 'goals' => $m->goals()->orderBy('done')->latest()->get(),
            'exercises' => $exercises, 'exId' => $exId, 'liftSeries' => $liftSeries, 'weeks' => $weeks,
            'latest' => $body->last(), 'first' => $body->first(),
            'lastBmi' => $m->bodyLogs()->whereNotNull('bmi')->latest('logged_on')->first(),
        ]);
    }

    public function saveBody(Request $r)
    {
        $m = auth()->user()->member;
        $d = $r->validate(['weight_kg' => 'required|numeric|between:20,300', 'waist_cm' => 'nullable|numeric|between:30,250', 'chest_cm' => 'nullable|numeric|between:30,250', 'arm_cm' => 'nullable|numeric|between:10,100']);
        $h = $m->height_cm ?: $m->bodyLogs()->whereNotNull('height_cm')->latest('logged_on')->value('height_cm');
        BodyLog::updateOrCreate(['member_id' => $m->id, 'logged_on' => today()], $d + ['height_cm' => $h, 'bmi' => $h ? Bmi::calc($d['weight_kg'], (float) $h) : null, 'is_dummy' => false]);
        return back()->with('ok', 'Measurements saved.');
    }

    public function saveGoal(Request $r)
    {
        $m = auth()->user()->member;
        $d = $r->validate(['title' => 'required|max:120', 'target_value' => 'nullable|numeric', 'unit' => 'nullable|max:10', 'target_date' => 'nullable|date|after:yesterday']);
        Goal::create($d + ['member_id' => $m->id]);
        return back()->with('ok', 'Goal added.');
    }

    public function toggleGoal(Goal $goal)
    {
        abort_unless($goal->member_id === auth()->user()->member->id, 403);
        $goal->update(['done' => ! $goal->done]);
        return back();
    }
}
