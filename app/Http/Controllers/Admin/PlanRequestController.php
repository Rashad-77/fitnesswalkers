<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Exercise, PlanRequest, WorkoutPlan, WorkoutPlanItem, WorkoutTemplate};
use Illuminate\Http\Request;

class PlanRequestController extends Controller
{
    public function index()
    {
        return view('admin.requests.index', [
            'pending' => PlanRequest::with('member.user')->where('status', 'pending')->oldest()->get(),
            'history' => PlanRequest::with('member.user')->where('status', '!=', 'pending')->latest()->take(30)->get(),
        ]);
    }

    public function show(PlanRequest $planRequest)
    {
        $planRequest->load(['member.user', 'plan.items.exercise']);
        $suggested = WorkoutTemplate::where('goal', $planRequest->goal)->orderByRaw('level = ? desc', [$planRequest->level])->get();
        return view('admin.requests.show', [
            'due' => $planRequest->member->balanceDue(), 'req' => $planRequest, 'templates' => WorkoutTemplate::orderBy('name')->get(), 'suggested' => $suggested,
            'exercises' => Exercise::available()->orderBy('muscle_group')->orderBy('name')->get(),
        ]);
    }

    public function draft(Request $r, PlanRequest $planRequest)
    {
        $d = $r->validate(['template_id' => ['required', fn ($a, $v, $fail) => $v === 'blank' || WorkoutTemplate::whereKey($v)->exists() ?: $fail('Pick a template.')]]);
        $planRequest->plan?->delete();
        if ($d['template_id'] === 'blank') {                                  // start from an empty plan and pick every exercise yourself
            WorkoutPlan::create(['member_id' => $planRequest->member_id, 'plan_request_id' => $planRequest->id, 'name' => 'Plan for '.$planRequest->member->user->name, 'status' => 'draft']);
            AuditLog::record('Started a blank workout plan', $planRequest->member->member_no);
            return back()->with('ok', 'Blank plan started. Choose the exercises, sets and reps below, then approve.');
        }
        $tpl = WorkoutTemplate::with('items')->find($d['template_id']);
        $plan = WorkoutPlan::create(['member_id' => $planRequest->member_id, 'plan_request_id' => $planRequest->id, 'name' => $tpl->name, 'status' => 'draft']);
        foreach ($tpl->items as $it) {
            WorkoutPlanItem::create(['workout_plan_id' => $plan->id, 'day_no' => $it->day_no, 'exercise_id' => $it->exercise_id, 'sets' => $it->sets, 'reps' => $it->reps]);
        }
        AuditLog::record('Drafted workout plan from template', $planRequest->member->member_no, $tpl->name);
        return back()->with('ok', 'Draft created from the template. Set the sets and reps below, then approve.');
    }

    public function updatePlan(Request $r, PlanRequest $planRequest)
    {
        $plan = $planRequest->plan;
        abort_unless($plan, 404);
        $r->validate(['name' => 'required|max:120']);
        $plan->update(['name' => $r->input('name')]);
        $plan->items()->delete();
        foreach (PlanItems::clean($r->input('items', [])) as $it) {
            WorkoutPlanItem::create($it + ['workout_plan_id' => $plan->id]);
        }
        return back()->with('ok', 'Plan saved. Members only see it after you approve it.');
    }

    public function approve(Request $r, PlanRequest $planRequest)
    {
        $plan = $planRequest->plan;
        if (! $plan || $plan->items()->count() === 0) return back()->with('warn', 'Add exercises to the plan before approving.');
        $due = $planRequest->member->balanceDue();                          // plans are approved after payment
        if ($due > 0 && ! $r->boolean('override')) return back()->with('warn', 'This member still owes '.rs($due).'. Record their payment first, or tick "approve anyway".');
        if ($due > 0) AuditLog::record('Approved plan before payment', $planRequest->member->member_no, rs($due).' still due');
        WorkoutPlan::where('member_id', $planRequest->member_id)->where('status', 'approved')->update(['status' => 'archived']);
        $plan->update(['status' => 'approved', 'approved_at' => now(), 'is_dummy' => false]);
        $planRequest->update(['status' => 'approved', 'is_dummy' => false]);
        AuditLog::record('Approved workout plan', $planRequest->member->member_no, $plan->name);
        return redirect()->route('admin.requests.index')->with('ok', 'Approved. The member can now see their plan.');
    }

    public function reject(Request $r, PlanRequest $planRequest)
    {
        $d = $r->validate(['admin_note' => 'required|max:500']);
        $planRequest->update(['status' => 'rejected', 'admin_note' => $d['admin_note']]);
        AuditLog::record('Rejected plan request', $planRequest->member->member_no, $d['admin_note']);
        return redirect()->route('admin.requests.index')->with('ok', 'Request declined. The member sees your note.');
    }
}
