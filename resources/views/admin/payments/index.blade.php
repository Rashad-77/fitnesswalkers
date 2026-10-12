@extends('layouts.admin')
@section('title', 'Payments')
@section('content')
<div class="row between mb"><h1>Payments</h1><a class="btn ghost" href="{{ route('admin.payments.daily') }}">Daily cash summary</a></div>
<div class="card mb" id="record"><h2>Record a payment</h2>
  <form method="post" action="{{ route('admin.payments.store') }}">@csrf
    <div class="grid g4">
      <div class="f"><label>Member</label><select name="member_id" required><option value="">Choose...</option>@foreach ($members as $m)<option value="{{ $m->id }}" @selected(old('member_id', $prefillMember) == $m->id)>{{ $m->user->name }} ({{ $m->member_no }})</option>@endforeach</select></div>
      <div class="f"><label>Pay towards</label><select name="membership_id"><option value="">General payment</option>@foreach ($owing as $o)<option value="{{ $o->id }}">{{ $o->member->user->name }} - {{ $o->plan->name }} due {{ rs($o->balance()) }}</option>@endforeach</select></div>
      <div class="f"><label>Amount (Rs.)</label><input type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" required></div>
      <div class="f"><label>Method</label><select name="method">@foreach (['cash','bank','card'] as $mt)<option value="{{ $mt }}">{{ ucfirst($mt) }}</option>@endforeach</select></div>
      <div class="f"><label>Date</label><input type="date" name="paid_on" value="{{ old('paid_on', today()->toDateString()) }}" max="{{ today()->toDateString() }}"></div>
      <div class="f"><label>Note</label><input type="text" name="note" value="{{ old('note') }}"></div>
    </div>
    <button class="btn dark">Save and open receipt</button>
  </form>
</div>
<form class="filters" method="get">
  <input type="date" name="from" value="{{ $from }}"><input type="date" name="to" value="{{ $to }}">
  <select name="method"><option value="">All methods</option>@foreach (['cash','bank','card'] as $mt)<option value="{{ $mt }}" @selected($method === $mt)>{{ ucfirst($mt) }}</option>@endforeach</select>
  <select name="member_id"><option value="">All members</option>@foreach ($members as $m)<option value="{{ $m->id }}" @selected($memberId == $m->id)>{{ $m->user->name }}</option>@endforeach</select>
  <button class="btn dark">Filter</button>
</form>
<div class="card"><div class="tw"><table>
  <tr><th>Receipt</th><th>Date</th><th>Member</th><th>For</th><th>Method</th><th class="right">Amount</th></tr>
  @forelse ($payments as $p)
    <tr class="{{ $p->is_dummy ? 'dummy' : '' }}"><td><a href="{{ route('admin.payments.show', $p) }}">{{ $p->receipt_no }}</a></td><td class="nowrap">{{ $p->paid_on->format('d M Y') }}</td>
      <td><a href="{{ route('admin.members.show', $p->member) }}">{{ $p->member->user->name }}</a></td><td>{{ $p->membership?->plan?->name ?? ($p->note ?: '-') }}</td><td>{{ $p->method }}</td><td class="right">{{ rs($p->amount) }}</td></tr>
  @empty <tr><td colspan="6" class="empty">No payments in this period.</td></tr> @endforelse
  @if ($payments->count())<tr><td colspan="5" class="right"><strong>Total</strong></td><td class="right"><strong>{{ rs($payments->sum('amount')) }}</strong></td></tr>@endif
</table></div></div>
@endsection
