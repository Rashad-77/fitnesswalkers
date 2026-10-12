<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Exercise, PlanRequest, WorkoutLog, WorkoutPlanItem};
use Illuminate\Http\Request;

class WorkoutController extends Controller
{
    public function index(Request $r)
    {
        $m = auth()->user()->member;
        $plan = $m->approvedPlan();
        $group = $r->query('group');
        $thisWeek = $m->workoutLogs()->where('logged_on', '>=', now()->startOfWeek())->distinct()->count('logged_on');
        return view('member.workout', [
            'm' => $m, 'plan' => $plan, 'days' => $plan ? $plan->items->groupBy('day_no') : collect(),
            'pending' => $m->planRequests()->where('status', 'pending')->first(),
            'lastRequest' => $m->planRequests()->first(), 'thisWeek' => $thisWeek,
            'library' => Exercise::available()->with('equipment')->when($group, fn ($q) => $q->where('muscle_group', $group))->orderBy('muscle_group')->orderBy('name')->get(),
            'groups' => \App\Http\Controllers\Admin\ExerciseController::GROUPS, 'group' => $group,
            'canRequest' => in_array($m->status(), ['active', 'expiring']),
        ]);
    }

    public function requestPlan(Request $r)
    {
        $m = auth()->user()->member;
        if (! in_array($m->status(), ['active', 'expiring'])) return back()->with('warn', 'Your membership needs to be active to request a plan.');
        if ($m->planRequests()->where('status', 'pending')->exists()) return back()->with('warn', 'You already have a request waiting for the owner.');
        $d = $r->validate(['goal' => 'required|in:fat_loss,muscle_gain,general', 'level' => 'required|in:beginner,intermediate', 'days_per_week' => 'required|integer|between:2,6', 'notes' => 'nullable|max:500']);
        PlanRequest::create($d + ['member_id' => $m->id]);
        return back()->with('ok', 'Request sent. The owner will build your plan and approve it. You will see it here.');
    }

    public function day(int $day)
    {
        $m = auth()->user()->member;
        $plan = $m->approvedPlan();
        abort_unless($plan, 404);
        $items = $plan->items->where('day_no', $day)->values();
        abort_if($items->isEmpty(), 404);
        $last = [];
        foreach ($items as $it) {
            $prev = WorkoutLog::where('member_id', $m->id)->where('exercise_id', $it->exercise_id)->where('logged_on', '<', today())->max('logged_on');
            $last[$it->exercise_id] = $prev ? WorkoutLog::where('member_id', $m->id)->where('exercise_id', $it->exercise_id)->whereDate('logged_on', $prev)->orderBy('set_no')->get() : collect();
        }
        $today = WorkoutLog::where('member_id', $m->id)->whereDate('logged_on', today())->get()->groupBy('exercise_id');
        $alts = [];
        foreach ($items as $it) {
            $alts[$it->id] = Exercise::available()->where('muscle_group', $it->exercise->muscle_group)
                ->whereNotIn('id', $plan->items->where('day_no', $day)->pluck('exercise_id'))->orderBy('name')->get();
        }
        return view('member.workout_day', compact('plan', 'items', 'day', 'last', 'today', 'alts'));
    }

    public function log(Request $r)
    {
        $m = auth()->user()->member;
        $plan = $m->approvedPlan();
        $allowed = $plan ? $plan->items->pluck('exercise_id')->all() : [];
        $r->validate(['sets' => 'required|array']);
        $saved = 0;
        foreach ($r->input('sets') as $exerciseId => $sets) {
            if (! in_array((int) $exerciseId, $allowed, true) || ! is_array($sets)) continue;
            $rows = [];
            foreach ($sets as $no => $row) {
                $reps = (int) ($row['reps'] ?? 0);
                if ($reps < 1 || $reps > 500) continue;
                $rows[] = ['member_id' => $m->id, 'exercise_id' => (int) $exerciseId, 'logged_on' => today(), 'set_no' => (int) $no,
                    'reps' => $reps, 'weight_kg' => max(0, min(1000, (float) ($row['weight'] ?? 0))), 'created_at' => now(), 'updated_at' => now()];
            }
            if ($rows) { // replace today's entry for this exercise so re-saving doesn't double count
                WorkoutLog::where('member_id', $m->id)->where('exercise_id', $exerciseId)->whereDate('logged_on', today())->delete();
                WorkoutLog::insert($rows);
                $saved += count($rows);
            }
        }
        return back()->with($saved ? 'ok' : 'warn', $saved ? "Saved $saved sets. Nice work!" : 'Enter reps for at least one set.');
    }

    public function swap(Request $r, WorkoutPlanItem $item)
    {
        $m = auth()->user()->member;
        $plan = $m->approvedPlan();
        abort_unless($plan && $item->workout_plan_id === $plan->id, 403);
        $d = $r->validate(['exercise_id' => 'required|exists:exercises,id']);
        $new = Exercise::available()->find($d['exercise_id']);
        if (! $new || $new->muscle_group !== $item->exercise->muscle_group) return back()->with('warn', 'Pick an exercise for the same muscle group.');
        $old = $item->exercise->name;
        $item->update(['exercise_id' => $new->id]);
        AuditLog::record('Member swapped exercise', $m->member_no, "$old -> {$new->name}");
        return back()->with('ok', "Swapped $old for {$new->name}.");
    }

    public function exercise(Exercise $exercise)
    {
        $exercise->load('equipment');
        abort_unless($exercise->isAvailable(), 404, 'This exercise is not available right now.');
        return view('member.exercise', ['ex' => $exercise]);
    }
}
