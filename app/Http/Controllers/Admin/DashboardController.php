<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Attendance, Enquiry, Expense, Member, Payment, PlanRequest, RenewalRequest};

/** The "Today" page: what needs the owner right now, then the month at a glance. */
class DashboardController extends Controller
{
    public function index()
    {
        $members = Member::with(['user', 'currentMembership.plan', 'memberships.payments'])->get();
        $monthStart = now()->startOfMonth();
        $income = (float) Payment::where('paid_on', '>=', $monthStart)->sum('amount');
        $spent = (float) Expense::where('spent_on', '>=', $monthStart)->sum('amount');

        $expiring = $members->filter(fn ($m) => $m->status() === 'expiring')->sortBy(fn ($m) => $m->daysLeft())->values();
        $owing = $members->filter(fn ($m) => $m->balanceDue() > 0)->sortByDesc(fn ($m) => $m->balanceDue())->values();
        $requests = PlanRequest::with('member.user')->where('status', 'pending')->latest()->get();
        $enquiries = Enquiry::whereIn('status', ['new'])->latest()->get();
        $renewalRequests = RenewalRequest::where('status', 'pending')->count();

        $hereToday = Attendance::with('member.user')->whereDate('visited_on', today()->toDateString())->latest('id')->get();

        // Last 6 months: income vs expenses
        $labels = []; $inc = []; $exp = [];
        for ($i = 5; $i >= 0; $i--) {
            $s = now()->startOfMonth()->subMonths($i); $e = $s->copy()->endOfMonth();
            $labels[] = $s->format('M');
            $inc[] = (float) Payment::whereBetween('paid_on', [$s->toDateString(), $e->toDateString()])->sum('amount');
            $exp[] = (float) Expense::whereBetween('spent_on', [$s->toDateString(), $e->toDateString()])->sum('amount');
        }

        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        return view('admin.dashboard', [
            'greeting' => $greeting,
            'activeCount' => $members->filter(fn ($m) => in_array($m->status(), ['active', 'expiring']))->count(),
            'expiring' => $expiring, 'owing' => $owing, 'requests' => $requests, 'enquiries' => $enquiries,
            'renewalRequests' => $renewalRequests, 'hereToday' => $hereToday,
            'income' => $income, 'spent' => $spent, 'owed' => $owing->sum(fn ($m) => $m->balanceDue()),
            'todayCash' => (float) Payment::whereDate('paid_on', today())->sum('amount'),
            'newThisMonth' => $members->where('joined_on', '>=', $monthStart)->count(),
            'chart' => ['labels' => $labels, 'series' => [
                ['name' => 'Income', 'color' => '#84cc16', 'data' => $inc],
                ['name' => 'Expenses', 'color' => '#2b303a', 'data' => $exp],
            ]],
            'recent' => Payment::with('member.user')->latest('paid_on')->latest('id')->take(5)->get(),
            'newest' => $members->sortByDesc('joined_on')->take(5)->values(),
            'names' => $members->map(fn ($m) => $m->user->name.' ('.$m->member_no.')')->sort()->values(),
        ]);
    }
}
