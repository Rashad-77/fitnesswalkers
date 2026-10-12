<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Expense, Payment};
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function index(Request $r)
    {
        $month = $r->query('month') ?: now()->format('Y-m');
        $start = \Carbon\Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $payments = Payment::with('membership.plan')->whereBetween('paid_on', [$start, $end])->get();
        $expenses = Expense::whereBetween('spent_on', [$start, $end])->get();

        $labels = $inc = $exp = [];
        for ($i = 5; $i >= 0; $i--) {
            $s = $start->copy()->subMonths($i)->startOfMonth();
            $e = $s->copy()->endOfMonth();
            $labels[] = $s->format('M');
            $inc[] = (float) Payment::whereBetween('paid_on', [$s, $e])->sum('amount');
            $exp[] = (float) Expense::whereBetween('spent_on', [$s, $e])->sum('amount');
        }
        return view('admin.finance', [
            'month' => $month, 'start' => $start,
            'income' => (float) $payments->sum('amount'), 'spent' => (float) $expenses->sum('amount'),
            'byPlan' => $payments->groupBy(fn ($p) => $p->membership?->plan?->name ?? 'Other / unassigned')->map->sum('amount')->sortDesc(),
            'byMethod' => $payments->groupBy('method')->map->sum('amount'),
            'byCategory' => $expenses->groupBy('category')->map->sum('amount')->sortDesc(),
            'labels' => $labels, 'incSeries' => $inc, 'expSeries' => $exp,
        ]);
    }
}
