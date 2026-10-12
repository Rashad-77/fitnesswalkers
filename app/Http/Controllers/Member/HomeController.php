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
        $days = $m->workoutLogs()->where('logged_on', '>=', now()->subDays(28))->distinct()->count('logged_on');
        $thisWeek = $m->workoutLogs()->where('logged_on', '>=', now()->startOfWeek())->distinct()->count('logged_on');
        return view('member.home', [
            'm' => $m, 'plan' => $plan, 'planDays' => $plan ? $plan->items->pluck('day_no')->unique()->count() : 0,
            'days28' => $days, 'thisWeek' => $thisWeek,
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
