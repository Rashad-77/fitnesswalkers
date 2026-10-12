<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\{Announcement, Plan, RenewalRequest, Setting};
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $m = auth()->user()->member->load(['user', 'currentMembership.plan']);
        $plan = $m->approvedPlan();
        $weekStart = now()->startOfWeek();
        $trained = $m->workoutLogs()->where('logged_on', '>=', $weekStart)->pluck('logged_on')->map(fn ($d) => $d->toDateString())->unique();
        $week = collect(range(0, 6))->map(fn ($i) => ['label' => ['M', 'T', 'W', 'T', 'F', 'S', 'S'][$i], 'done' => $trained->contains($weekStart->copy()->addDays($i)->toDateString()),
            'today' => $weekStart->copy()->addDays($i)->isToday(), 'future' => $weekStart->copy()->addDays($i)->isFuture()])->all();
        $planDays = $plan ? $plan->items->pluck('day_no')->unique()->sort()->values() : collect();

        // The next workout day: the one after the day of the last exercise logged, or Day 1.
        $next = null;
        if ($plan && $planDays->isNotEmpty()) {
            $last = $m->workoutLogs()->latest('logged_on')->latest('id')->first();
            $lastDay = $last ? $plan->items->firstWhere('exercise_id', $last->exercise_id)?->day_no : null;
            $next = $lastDay ? ($planDays->first(fn ($d) => $d > $lastDay) ?? $planDays->first()) : $planDays->first();
            $next = ['day' => $next, 'count' => $plan->items->where('day_no', $next)->count()];
        }

        $diet = $m->dietPlans()->where('active', true)->where('source', 'app')->first();
        $meals = null;
        if ($diet && ($diet->plan['format'] ?? '') === 'dishes' && empty($diet->plan['blocked'])) {
            $d = $diet->plan['days'][now()->dayOfWeekIso - 1];
            $meals = ['t' => $diet->plan['targets'], 'kcal' => $d['kcal'], 'water' => $diet->plan['water']['litres'] ?? null,
                'list' => collect($d['meals'])->map(fn ($x) => ['slot' => $x['name'], 'name' => $x['dish'], 'kcal' => $x['kcal'] ?? null])->all()];
        }
        $logs = $m->bodyLogs()->latest('logged_on')->take(2)->get();

        return view('member.home', [
            'm' => $m, 'plan' => $plan, 'next' => $next, 'week' => $week, 'weekDone' => $trained->count(), 'planDays' => $planDays->count(),
            'meals' => $meals, 'hasDiet' => (bool) $diet, 'weight' => $logs->first()?->weight_kg, 'weightChange' => $logs->count() === 2 ? round($logs[0]->weight_kg - $logs[1]->weight_kg, 1) : null,
            'announcements' => Announcement::where('published_at', '<=', now())->orderByDesc('published_at')->take(2)->get(),
            'pendingReq' => $m->planRequests()->where('status', 'pending')->first(),
            'checkedIn' => $m->checkedInToday(), 'visits' => $m->visitsThisMonth(),
        ]);
    }

    public function more()
    {
        return view('member.more', ['m' => auth()->user()->member->load('currentMembership.plan', 'memberships.payments'), 'gym' => Setting::get('gym_name', 'FitnessWalkers'), 'phone' => Setting::get('gym_phone'), 'address' => Setting::get('gym_address'), 'hours' => Setting::get('gym_hours')]);
    }

    /** Opened by scanning the QR code at the gym door. Records one visit per day. */
    public function checkin(Request $r)
    {
        if (! hash_equals(Setting::checkinCode(), (string) $r->query('code'))) {
            return redirect()->route('member.home')->with('warn', 'That check-in code is not valid. Ask at the front desk.');
        }
        $m = auth()->user()->member;
        if (in_array($m->status(), ['expired', 'none', 'frozen'])) {
            return redirect()->route('member.home')->with('warn', 'Your membership is not active, so we could not check you in. Please see the front desk.');
        }
        $new = \App\Models\Attendance::record($m, 'member');
        return redirect()->route('member.home')->with('ok', $new ? 'You are checked in. Have a good workout!' : 'You are already checked in today.');
    }

    public function membership()
    {
        $m = auth()->user()->member->load(['currentMembership.plan', 'memberships.plan', 'memberships.payments', 'payments.membership.plan']);
        return view('member.membership', ['m' => $m, 'plans' => Plan::where('active', true)->orderBy('duration_days')->get(), 'pending' => $m->renewalRequests()->where('status', 'pending')->first()]);
    }

    public function renew(Request $r)
    {
        $m = auth()->user()->member;
        $d = $r->validate(['plan_id' => 'nullable|exists:plans,id']);
        if ($m->renewalRequests()->where('status', 'pending')->exists()) return back()->with('warn', 'You already asked to renew. The gym will contact you.');
        RenewalRequest::create(['member_id' => $m->id, 'plan_id' => $d['plan_id'] ?? null]);
        return back()->with('ok', 'Renewal request sent. Please pay at the gym or by bank transfer, and the owner will update your membership.');
    }

    public function announcements()
    {
        return view('member.announcements', ['items' => Announcement::where('published_at', '<=', now())->orderByDesc('published_at')->take(30)->get()]);
    }
}
