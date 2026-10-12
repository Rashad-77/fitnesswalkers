<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Expense};
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public const CATEGORIES = ['Rent', 'Electricity', 'Water', 'Salaries', 'Equipment', 'Equipment maintenance', 'Cleaning', 'Marketing', 'Other'];

    public function index(Request $r)
    {
        $from = $r->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $r->query('to') ?: today()->toDateString();
        $items = Expense::whereBetween('spent_on', [$from, $to])->when($r->query('category'), fn ($q, $c) => $q->where('category', $c))->orderByDesc('spent_on')->get();
        return view('admin.expenses.index', ['items' => $items, 'from' => $from, 'to' => $to, 'category' => $r->query('category'), 'cats' => self::CATEGORIES]);
    }

    public function create()
    {
        return view('admin.expenses.form', ['expense' => new Expense(['spent_on' => today()]), 'cats' => self::CATEGORIES]);
    }

    public function store(Request $r)
    {
        $e = Expense::create($this->data($r));
        AuditLog::record('Added expense', $e->category, 'Rs. '.number_format($e->amount, 2));
        return redirect()->route('admin.expenses.index')->with('ok', 'Expense saved.');
    }

    public function edit(Expense $expense)
    {
        return view('admin.expenses.form', ['expense' => $expense, 'cats' => self::CATEGORIES]);
    }

    public function update(Request $r, Expense $expense)
    {
        $expense->update($this->data($r) + ['is_dummy' => false]);
        AuditLog::record('Edited expense', $expense->category, 'Rs. '.number_format($expense->amount, 2));
        return redirect()->route('admin.expenses.index')->with('ok', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        AuditLog::record('Deleted expense', $expense->category, 'Rs. '.number_format($expense->amount, 2));
        $expense->delete();
        return back()->with('ok', 'Expense deleted.');
    }

    private function data(Request $r): array
    {
        return $r->validate(['category' => 'required|in:'.implode(',', self::CATEGORIES), 'amount' => 'required|numeric|min:1', 'spent_on' => 'required|date', 'description' => 'nullable|max:255']);
    }
}
