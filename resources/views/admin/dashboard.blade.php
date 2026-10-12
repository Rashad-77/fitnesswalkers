@extends('layouts.admin')
@section('title', 'Today')
@section('content')
@php
  $waiting = $expiring->count() + $owing->count() + $requests->count() + $enquiries->count() + $renewalRequests;
@endphp
<div class="row between mb">
  <div>
    <h1>{{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }}</h1>
    <span class="muted">{{ now()->format('l, j F') }} · {{ $activeCount }} active members · {{ $hereToday->count() }} in today
      · {{ $waiting ? $waiting.' '.\Illuminate\Support\Str::plural('item', $waiting).' require attention' : 'nothing is pending' }}</span>
  </div>
  <form class="quickin" method="post" action="{{ route('admin.checkin.quick') }}">@csrf
    <input list="people" name="who" placeholder="Check in a member..." required autocomplete="off" aria-label="Check in a member">
    <datalist id="people">@foreach ($names as $n)<option value="{{ $n }}">@endforeach</datalist>
    <button class="btn">Check in</button>
  </form>
</div>

<h2 class="mb">Requires attention</h2>
<div class="needgrid mb">
  <div class="card need"><div class="head"><h2>Expiring this week</h2><a href="{{ route('admin.renewals') }}">All</a></div>
    @forelse ($expiring->take(4) as $m)
      <div class="line"><span><a href="{{ route('admin.members.show', $m) }}">{{ $m->user->name }}</a>
        <span class="sub">{{ $m->daysLeft() === 0 ? 'ends today' : $m->daysLeft().' days left' }}</span></span>
</div>
    @empty <div class="none">No memberships expiring.</div> @endforelse
    @if ($expiring->count() > 4)<a class="small" href="{{ route('admin.renewals') }}">+{{ $expiring->count() - 4 }} more</a>@endif
  </div>
  <div class="card need"><div class="head"><h2>Outstanding balances</h2><a href="{{ route('admin.members.index', ['filter' => 'owes']) }}">All</a></div>
    @forelse ($owing->take(4) as $m)
      <div class="line"><span><a href="{{ route('admin.members.show', $m) }}">{{ $m->user->name }}</a><span class="sub">{{ rs($m->balanceDue()) }} due</span></span>
        <a class="btn ghost sm" href="{{ route('admin.members.show', [$m, 'tab' => 'payments']) }}">Record payment</a></div>
    @empty <div class="none">No outstanding balances.</div> @endforelse
    @if ($owing->count() > 4)<a class="small" href="{{ route('admin.members.index', ['filter' => 'owes']) }}">+{{ $owing->count() - 4 }} more</a>@endif
  </div>
  <div class="card need"><div class="head"><h2>Workout requests</h2><a href="{{ route('admin.requests.index') }}">All</a></div>
    @forelse ($requests->take(4) as $q)
      <div class="line"><span><a href="{{ route('admin.members.show', $q->member) }}">{{ $q->member->user->name }}</a><span class="sub">{{ $q->created_at->diffForHumans() }}</span></span>
        <a class="btn sm" href="{{ route('admin.requests.show', $q) }}">Build plan</a></div>
    @empty <div class="none">No requests waiting.</div> @endforelse
  </div>
  <div class="card need"><div class="head"><h2>New enquiries</h2><a href="{{ route('admin.enquiries.index') }}">All</a></div>
    @forelse ($enquiries->take(4) as $e)
      <div class="line"><span>{{ $e->name }}<span class="sub">{{ \App\Models\Enquiry::SOURCES[$e->source] ?? $e->source }} · {{ $e->created_at->diffForHumans() }}</span></span>
        @if ($wa = $e->whatsappUrl())<a class="btn sm" target="_blank" rel="noopener" href="{{ $wa }}">WhatsApp</a>@endif</div>
    @empty <div class="none">No new enquiries.</div> @endforelse
  </div>
</div>

<div class="card mb"><h2>Present today ({{ $hereToday->count() }})</h2>
  @forelse ($hereToday as $a)<a class="chip" href="{{ route('admin.members.show', $a->member) }}">{{ $a->member->user->name }}</a> @empty
    <div class="muted small">No check-ins yet today. Members scan the QR code at the door, or you can check them in above.</div>@endforelse
</div>

<h2 class="mb">This month</h2>
<div class="grid g4 mb">
  <div class="stat dark"><div class="n">{{ rs($income) }}</div><div class="l">Income</div></div>
  <div class="stat"><div class="n">{{ rs($spent) }}</div><div class="l">Expenses</div></div>
  <div class="stat"><div class="n" style="color:{{ $income - $spent >= 0 ? '#3b6212' : '#d6453d' }}">{{ rs($income - $spent) }}</div><div class="l">Profit</div></div>
  <div class="stat lime"><div class="n">{{ $newThisMonth }}</div><div class="l">New members</div></div>
</div>
<div class="grid g2">
  <div class="card"><h2>Income and expenses, last 6 months</h2>@include('partials.chart_bars', ['labels' => $chart['labels'], 'series' => $chart['series']])</div>
  <div class="card"><h2>Latest payments <a class="small" href="{{ route('admin.payments.index') }}">All</a></h2>
    @forelse ($recent as $p)
      <div class="row between" style="padding:.4rem 0;border-bottom:1px solid #eef0ea">
        <span><a href="{{ route('admin.payments.show', $p) }}">{{ $p->member->user->name }}</a> <span class="muted small">{{ $p->paid_on->format('d M') }} · {{ $p->method }}</span></span>
        <strong>{{ rs($p->amount) }}</strong></div>
    @empty <div class="empty">No payments yet.</div> @endforelse
    <p class="small muted mt">Cash taken today: <strong>{{ rs($todayCash) }}</strong> · <a href="{{ route('admin.payments.daily') }}">Daily summary</a></p>
    <h3 class="mt">Newest members</h3>
    @foreach ($newest as $m)<div class="small"><a href="{{ route('admin.members.show', $m) }}">{{ $m->user->name }}</a> <span class="muted">· {{ $m->joined_on->format('d M') }}</span></div>@endforeach
  </div>
</div>
@endsection
