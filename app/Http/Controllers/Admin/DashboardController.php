<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Attendance, AuditLog, Enquiry, Equipment, Expense, Member, Payment, PlanRequest, RenewalRequest};

/**
 * The "Today" page. One prioritised to-do list instead of the same lists repeated, then how the gym is doing:
 * the month against last month, this week's attendance, the health of memberships, and what changed recently.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $members = Member::with(['user', 'currentMembership.plan', 'memberships.payments'])->get();
        $monthStart = now()->startOfMonth();
        $lastStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastSame = $lastStart->copy()->addDays(now()->day - 1)->endOfDay();          // last month, up to the same day of the month

        $income = (float) Payment::where('paid_on', '>=', $monthStart)->sum('amount');
        $incomeLast = (float) Payment::whereBetween('paid_on', [$lastStart->toDateString(), $lastSame->toDateString()])->sum('amount');
        $spent = (float) Expense::where('spent_on', '>=', $monthStart)->sum('amount');
        $owing = $members->filter(fn ($m) => $m->balanceDue() > 0)->sortByDesc(fn ($m) => $m->balanceDue())->values();
        $status = $members->groupBy(fn ($m) => $m->status())->map->count();

        $hereToday = Attendance::with('member.user')->whereDate('visited_on', today()->toDateString())->latest('id')->get();
        $week = collect(range(6, 0))->map(fn ($i) => today()->subDays($i))->map(fn ($d) => [
            'label' => $d->format('D'), 'date' => $d,
            'n' => Attendance::whereDate('visited_on', $d->toDateString())->count(),
        ]);

        // last 6 months of income and expenses
        $labels = []; $inc = []; $exp = [];
        for ($i = 5; $i >= 0; $i--) {
            $s = now()->startOfMonth()->subMonths($i); $e = $s->copy()->endOfMonth();
            $labels[] = $s->format('M');
            $inc[] = (float) Payment::whereBetween('paid_on', [$s->toDateString(), $e->toDateString()])->sum('amount');
            $exp[] = (float) Expense::whereBetween('spent_on', [$s->toDateString(), $e->toDateString()])->sum('amount');
        }

        $hour = (int) now()->format('G');
        $newThisMonth = $members->where('joined_on', '>=', $monthStart)->count();

        return view('admin.dashboard', [
            'greeting' => $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening'),
            'tasks' => $this->tasks($members, $owing),
            'activeCount' => ($status['active'] ?? 0) + ($status['expiring'] ?? 0), 'newThisMonth' => $newThisMonth,
            'income' => $income, 'incomeLast' => $incomeLast, 'spent' => $spent,
            'delta' => $incomeLast > 0 ? round(($income - $incomeLast) / $incomeLast * 100) : null,
            'owed' => $owing->sum(fn ($m) => $m->balanceDue()), 'owingCount' => $owing->count(),
            'todayCash' => (float) Payment::whereDate('paid_on', today())->sum('amount'),
            'hereToday' => $hereToday, 'week' => $week, 'weekAvg' => round($week->avg('n')),
            'status' => $status, 'total' => max(1, $members->count()),
            'chart' => ['labels' => $labels, 'series' => [['name' => 'Income', 'color' => '#84cc16', 'data' => $inc], ['name' => 'Expenses', 'color' => '#143a2c', 'data' => $exp]]],
            'activity' => AuditLog::with('user')->latest('id')->take(8)->get(),
            'names' => $members->map(fn ($m) => $m->user->name.' ('.$m->member_no.')')->sort()->values(),
        ]);
    }

    /** Everything that needs the owner, most urgent first. Each is one row with one button. */
    private function tasks($members, $owing)
    {
        $t = collect();
        foreach (RenewalRequest::with(['member.user', 'plan'])->where('status', 'pending')->latest()->get() as $q) {
            $t->push(['score' => 88, 'kind' => 'renew', 'icon' => 'refresh', 'title' => $q->member->user->name.' asked to renew', 'sub' => ($q->plan?->name ?? 'Membership').' · '.$q->created_at->diffForHumans(),
                'url' => route('admin.members.show', [$q->member, 'tab' => 'payments']), 'cta' => 'Renew']);
        }
        foreach ($members->filter(fn ($m) => $m->status() === 'expiring')->sortBy(fn ($m) => $m->daysLeft()) as $m) {
            $d = $m->daysLeft();
            $t->push(['score' => $d <= 2 ? 90 - $d : 58 - $d, 'kind' => 'expiring', 'icon' => 'calendar', 'title' => $m->user->name.($d === 0 ? ' expires today' : ' expires in '.$d.' '.\Illuminate\Support\Str::plural('day', $d)),
                'sub' => $m->currentMembership?->plan?->name.' · ends '.$m->currentMembership?->ends_on->format('d M'), 'url' => route('admin.members.show', [$m, 'tab' => 'payments']), 'cta' => 'Renew']);
        }
        foreach (PlanRequest::with('member.user')->where('status', 'pending')->latest()->get() as $q) {
            $due = $q->member->balanceDue();
            $t->push(['score' => $due > 0 ? 55 : 80, 'kind' => 'request', 'icon' => 'clipboard', 'title' => $q->member->user->name.' wants a workout plan',
                'sub' => ($due > 0 ? 'Payment due '.rs($due).' first' : 'Paid, ready to build').' · '.$q->created_at->diffForHumans(), 'tag' => $due > 0 ? ['pending', 'Waiting for payment'] : ['active', 'Paid'],
                'url' => route('admin.requests.show', $q), 'cta' => 'Build plan']);
        }
        foreach ($owing->take(6) as $m) {
            $t->push(['score' => 48 + min(30, (int) ($m->balanceDue() / 1000)), 'kind' => 'owing', 'icon' => 'card', 'title' => $m->user->name.' owes '.rs($m->balanceDue()),
                'sub' => $m->currentMembership?->plan?->name.' membership', 'url' => route('admin.members.show', [$m, 'tab' => 'payments']), 'cta' => 'Record payment']);
        }
        foreach (Enquiry::where('status', 'new')->latest()->get() as $e) {
            $t->push(['score' => $e->created_at->lt(now()->subDay()) ? 72 : 66, 'kind' => 'enquiry', 'icon' => 'inbox', 'title' => 'New enquiry from '.$e->name,
                'sub' => ($e->interest ?: (\App\Models\Enquiry::SOURCES[$e->source] ?? $e->source)).' · '.$e->created_at->diffForHumans(), 'url' => $e->whatsappUrl() ?: route('admin.enquiries.index'),
                'external' => (bool) $e->whatsappUrl(), 'cta' => $e->whatsappUrl() ? 'Reply' : 'Open']);
        }
        foreach (Equipment::where('out_of_order', true)->get() as $q) {
            $t->push(['score' => 40, 'kind' => 'equipment', 'icon' => 'wrench', 'title' => $q->name.' is out of order', 'sub' => 'Members cannot be given exercises that need it', 'url' => route('admin.equipment.index'), 'cta' => 'Open']);
        }
        return $t->sortByDesc('score')->values();
    }
}
