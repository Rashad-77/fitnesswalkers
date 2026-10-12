<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Member, Membership, Payment};
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $r)
    {
        $from = $r->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $r->query('to') ?: today()->toDateString();
        $payments = Payment::with(['member.user', 'membership.plan'])
            ->whereBetween('paid_on', [$from, $to])
            ->when($r->query('method'), fn ($q, $m) => $q->where('method', $m))
            ->when($r->query('member_id'), fn ($q, $m) => $q->where('member_id', $m))
            ->orderByDesc('paid_on')->orderByDesc('id')->get();
        return view('admin.payments.index', [
            'payments' => $payments, 'from' => $from, 'to' => $to,
            'members' => Member::with('user')->get()->sortBy(fn ($m) => $m->user->name)->values(),
            'method' => $r->query('method'), 'memberId' => $r->query('member_id'),
            'owing' => Membership::with(['member.user', 'plan'])->get()->filter(fn ($m) => $m->balance() > 0)->values(),
            'prefillMember' => $r->query('member'),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'member_id' => 'required|exists:members,id', 'membership_id' => 'nullable|exists:memberships,id',
            'amount' => 'required|numeric|min:1', 'method' => 'required|in:cash,bank,card',
            'paid_on' => 'required|date|before_or_equal:today', 'note' => 'nullable|max:255',
        ]);
        if (! empty($d['membership_id'])) {
            $ms = Membership::findOrFail($d['membership_id']);
            if ($ms->member_id != $d['member_id']) return back()->withInput()->withErrors(['membership_id' => 'That membership belongs to a different member.']);
            if ($d['amount'] > $ms->balance() + 0.001) {
                return back()->withInput()->withErrors(['amount' => 'That is more than the balance due (Rs. '.number_format($ms->balance(), 2).').']);
            }
        }
        $p = Payment::create($d + ['receipt_no' => Payment::nextReceiptNo()]);
        AuditLog::record('Recorded payment', $p->receipt_no, 'Rs. '.number_format($p->amount, 2).' '.$p->method);
        return redirect()->route('admin.payments.show', $p)->with('ok', 'Payment recorded.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['member.user', 'membership.plan']);
        return view('admin.payments.receipt', compact('payment'));
    }

    public function daily(Request $r)
    {
        $date = $r->query('date') ?: today()->toDateString();
        $payments = Payment::with(['member.user', 'membership.plan'])->whereDate('paid_on', $date)->orderBy('id')->get();
        return view('admin.payments.daily', ['payments' => $payments, 'date' => $date, 'byMethod' => $payments->groupBy('method')->map->sum('amount')]);
    }
}
