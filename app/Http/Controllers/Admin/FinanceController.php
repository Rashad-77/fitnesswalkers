<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Attendance, Expense, Member, Membership, Payment};
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Finances > Reports (analytics): Money, Members and Attendance, each with charts. */
class FinanceController extends Controller
{
    public const TABS = ['money' => 'Money', 'members' => 'Members', 'attendance' => 'Attendance'];

    public function index(Request $r)
    {
        $tab = array_key_exists($r->query('tab'), self::TABS) ? $r->query('tab') : 'money';
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $r->query('month')) ? $r->query('month') : now()->format('Y-m');   // a bad month falls back to this month
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        return view('admin.finance', ['tab' => $tab, 'tabs' => self::TABS, 'month' => $month, 'start' => $start] + match ($tab) {
            'members' => $this->members($start),
            'attendance' => $this->attendance(),
            default => $this->money($start),
        });
    }

    /* ---------------------------------------------------------------- money */

    private function money(Carbon $start): array
    {
        $end = $start->copy()->endOfMonth();
        $payments = Payment::with('membership.plan')->whereBetween('paid_on', [$start->toDateString(), $end->toDateString()])->get();
        $expenses = Expense::whereBetween('spent_on', [$start->toDateString(), $end->toDateString()])->get();

        $trend = ['labels' => [], 'income' => [], 'expenses' => [], 'profit' => []];           // the 12 months ending at the chosen month
        for ($i = 11; $i >= 0; $i--) {
            $s = $start->copy()->subMonths($i)->startOfMonth(); $e = $s->copy()->endOfMonth();
            $in = (float) Payment::whereBetween('paid_on', [$s->toDateString(), $e->toDateString()])->sum('amount');
            $ex = (float) Expense::whereBetween('spent_on', [$s->toDateString(), $e->toDateString()])->sum('amount');
            $trend['labels'][] = $s->format('M'); $trend['income'][] = $in; $trend['expenses'][] = $ex; $trend['profit'][] = $in - $ex;
        }

        // income so far through the month, day by day, against the month before
        $days = $start->isSameMonth(now()) ? now()->day : $start->daysInMonth;
        $prev = $start->copy()->subMonthNoOverflow();
        $cum = function (Carbon $m, int $n) {
            $by = Payment::whereBetween('paid_on', [$m->copy()->startOfMonth()->toDateString(), $m->copy()->endOfMonth()->toDateString()])->get()->groupBy(fn ($p) => (int) $p->paid_on->format('j'))->map->sum('amount');
            $run = 0; $out = [];
            for ($d = 1; $d <= $n; $d++) { $run += (float) ($by[$d] ?? 0); $out[] = $run; }
            return $out;
        };
        $through = ['labels' => range(1, max($days, min($start->daysInMonth, $prev->daysInMonth))), 'this' => $cum($start, $days), 'last' => $cum($prev, min($start->daysInMonth, $prev->daysInMonth))];

        $members = Member::with(['user', 'currentMembership.plan', 'memberships.payments'])->get();
        return [
            'income' => (float) $payments->sum('amount'), 'spent' => (float) $expenses->sum('amount'), 'trend' => $trend, 'through' => $through,
            'prevIncome' => array_sum($through['last']) ? (float) end($through['last']) : 0.0,
            'byPlan' => $payments->groupBy(fn ($p) => $p->membership?->plan?->name ?? 'Other / unassigned')->map->sum('amount')->sortDesc(),
            'byMethod' => $payments->groupBy('method')->map->sum('amount'),
            'byCategory' => $expenses->groupBy('category')->map->sum('amount')->sortDesc(),
            'owing' => $members->filter(fn ($m) => $m->balanceDue() > 0)->sortByDesc(fn ($m) => $m->balanceDue())->values(),
        ];
    }

    /* -------------------------------------------------------------- members */

    private function members(Carbon $start): array
    {
        $members = Member::with(['user', 'currentMembership.plan'])->get();
        $statuses = $members->groupBy(fn ($m) => $m->status())->map->count();

        $over = ['labels' => [], 'data' => []];                                              // members with a live membership at the end of each of the last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $e = now()->startOfMonth()->subMonths($i)->endOfMonth();
            if ($e->isFuture()) $e = now();
            $over['labels'][] = $e->format('M');
            $over['data'][] = Membership::where('starts_on', '<=', $e->toDateString())->where('ends_on', '>=', $e->toDateString())->distinct('member_id')->count('member_id');
        }

        // renewal rate: of the memberships that ended in the last 6 months, how many were followed by another one (starting up to 14 days after)
        $ended = Membership::where('ends_on', '>=', now()->subMonths(6)->toDateString())->where('ends_on', '<', today()->toDateString())->get();
        $renewed = $ended->filter(fn ($m) => Membership::where('member_id', $m->member_id)->where('id', '!=', $m->id)->where('starts_on', '>', $m->starts_on->toDateString())
            ->where('starts_on', '<=', $m->ends_on->copy()->addDays(14)->toDateString())->exists())->count();

        $age = ['Under 18' => 0, '18 to 24' => 0, '25 to 34' => 0, '35 to 44' => 0, '45 to 54' => 0, '55 and over' => 0, 'Not known' => 0];
        foreach ($members as $m) {
            $a = $m->age();
            $age[$a === null ? 'Not known' : ($a < 18 ? 'Under 18' : ($a < 25 ? '18 to 24' : ($a < 35 ? '25 to 34' : ($a < 45 ? '35 to 44' : ($a < 55 ? '45 to 54' : '55 and over')))))]++;
        }

        return [
            'activeNow' => ($statuses['active'] ?? 0) + ($statuses['expiring'] ?? 0), 'statuses' => $statuses, 'over' => $over,
            'inUse' => $members->filter(fn ($m) => in_array($m->status(), ['active', 'expiring']))->groupBy(fn ($m) => $m->currentMembership?->plan?->name ?? 'None')->map->count()->sortDesc(),
            'renewalRate' => $ended->count() ? (int) round($renewed / $ended->count() * 100) : null, 'endedCount' => $ended->count(), 'renewedCount' => $renewed,
            'endingSoon' => $members->filter(fn ($m) => $m->status() === 'expiring')->sortBy(fn ($m) => $m->daysLeft())->values(),
            'ages' => array_filter($age), 'total' => $members->count(),
            'lapsed' => $members->filter(fn ($m) => $m->status() === 'expired' && $m->daysLeft() >= -90)->sortByDesc(fn ($m) => $m->daysLeft())->values(),
        ];
    }

    /* ----------------------------------------------------------- attendance */

    private function attendance(): array
    {
        $from = now()->startOfWeek()->subWeeks(11);
        $rows = Attendance::where('visited_on', '>=', $from->toDateString())->get();
        $by = $rows->groupBy(fn ($a) => $a->visited_on->toDateString())->map->count();

        $grid = []; $perWeek = ['labels' => [], 'data' => []];
        for ($w = 0; $w < 12; $w++) {
            $ws = $from->copy()->addWeeks($w); $cells = [];
            for ($d = 0; $d < 7; $d++) { $day = $ws->copy()->addDays($d); $cells[] = ['date' => $day, 'n' => $day->isFuture() ? null : (int) ($by[$day->toDateString()] ?? 0)]; }
            $grid[] = ['label' => $ws->format('j M'), 'cells' => $cells];
            $perWeek['labels'][] = $ws->format('j M'); $perWeek['data'][] = array_sum(array_map(fn ($c) => (int) $c['n'], $cells));
        }
        $max = max(1, collect($grid)->flatMap(fn ($g) => array_column($g['cells'], 'n'))->max());

        $weekdays = array_fill(0, 7, 0);
        foreach ($rows as $a) $weekdays[(int) $a->visited_on->format('N') - 1]++;

        $members = Member::with('user')->get();
        $last28 = $rows->where('visited_on', '>=', now()->subDays(28)->startOfDay());
        $perMember = $last28->groupBy('member_id')->map->count();
        $buckets = ['Not at all' => 0, 'Under once a week' => 0, '1 to 2 a week' => 0, '3 to 4 a week' => 0, '5 or more a week' => 0];
        foreach ($members->filter(fn ($m) => in_array($m->status(), ['active', 'expiring'])) as $m) {
            $w = ($perMember[$m->id] ?? 0) / 4;
            $buckets[$w == 0 ? 'Not at all' : ($w < 1 ? 'Under once a week' : ($w < 3 ? '1 to 2 a week' : ($w < 5 ? '3 to 4 a week' : '5 or more a week')))]++;
        }
        $cut = now()->subDays(config('fitness.quiet_days'))->toDateString();
        return [
            'grid' => $grid, 'gridMax' => $max, 'perWeek' => $perWeek, 'weekdays' => $weekdays, 'buckets' => $buckets, 'total12' => $rows->count(),
            'regular' => $members->map(fn ($m) => ['m' => $m, 'n' => $perMember[$m->id] ?? 0])->filter(fn ($x) => $x['n'] > 0)->sortByDesc('n')->take(8)->values(),
            'notSeen' => $members->filter(fn ($m) => in_array($m->status(), ['active', 'expiring']) && (! ($l = $m->lastVisit()) || $l < $cut))->values(),
            'quietDays' => config('fitness.quiet_days'),
        ];
    }
}
