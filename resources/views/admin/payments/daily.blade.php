@extends('layouts.admin')
@section('title', 'Daily cash summary')
@section('content')
<div class="row between mb"><h1>Daily summary</h1>
  <form method="get" class="row"><input type="date" name="date" value="{{ $date }}" style="width:auto"><button class="btn dark">Show</button><button type="button" class="btn ghost" onclick="window.print()">Print</button></form></div>
<div class="grid g4 mb">
  <div class="stat lime"><div class="n">{{ rs($payments->sum('amount')) }}</div><div class="l">Total on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div></div>
  @foreach (['cash','bank','card'] as $mt)<div class="stat"><div class="n">{{ rs($byMethod[$mt] ?? 0) }}</div><div class="l">{{ ucfirst($mt) }}</div></div>@endforeach
</div>
<div class="card"><div class="tw"><table>
  <tr><th>Receipt</th><th>Member</th><th>For</th><th>Method</th><th class="right">Amount</th></tr>
  @forelse ($payments as $p)<tr><td><a href="{{ route('admin.payments.show', $p) }}">{{ $p->receipt_no }}</a></td><td>{{ $p->member->user->name }}</td><td>{{ $p->membership?->plan?->name ?? '-' }}</td><td>{{ $p->method }}</td><td class="right">{{ rs($p->amount) }}</td></tr>
  @empty <tr><td colspan="5" class="empty">No payments on this day.</td></tr> @endforelse
</table></div></div>
@endsection
