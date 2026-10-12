@extends('layouts.admin')
@section('title', 'Today')
@section('content')
@php
  $first = explode(' ', auth()->user()->name)[0];
  $shown = $tasks->take(8); $more = $tasks->count() - $shown->count();
  $st = fn ($k) => (int) ($status[$k] ?? 0);
  $maxWeek = max(1, $week->max('n'));
  $labels = ['renew' => 'lime', 'expiring' => 'amber', 'request' => 'blue', 'owing' => 'red', 'enquiry' => 'violet', 'equipment' => 'grey'];
@endphp
<div class="dashhead">
  <div><h1>{{ $greeting }}, {{ $first }}</h1>
    <span class="muted">{{ now()->format('l, j F') }} · {{ $tasks->count() ? $tasks->count().' '.\Illuminate\Support\Str::plural('thing', $tasks->count()).' need you' : 'you are all caught up' }}</span></div>
  <form class="quickin" method="post" action="{{ route('admin.checkin.quick') }}">@csrf
    <input list="people" name="who" placeholder="Check in a member..." required autocomplete="off" aria-label="Check in a member">
    <datalist id="people">@foreach ($names as $n)<option value="{{ $n }}">@endforeach</datalist>
    <button class="btn">Check in</button>
  </form>
</div>

<div class="qa mb">
  <a class="qa-i" href="{{ route('admin.members.create') }}"><span>{!! \App\Support\AdminNav::icon('users') !!}</span><b>Add member</b><small>Sign someone up</small></a>
  <a class="qa-i" href="{{ route('admin.payments.index') }}#record"><span>{!! \App\Support\AdminNav::icon('card') !!}</span><b>Record payment</b><small>Cash, bank or card</small></a>
  <a class="qa-i" href="{{ route('admin.requests.index') }}"><span>{!! \App\Support\AdminNav::icon('clipboard') !!}</span><b>Workout plans</b><small>{{ $tasks->where('kind', 'request')->count() }} waiting</small></a>
  <a class="qa-i" href="{{ route('admin.expenses.create') }}"><span>{!! \App\Support\AdminNav::icon('receipt') !!}</span><b>Add expense</b><small>Rent, bills, repairs</small></a>
</div>

<div class="grid g4 mb kpis">
  <div class="stat dark"><div class="l">Active members</div><div class="n">{{ $activeCount }}</div><div class="d">{{ $newThisMonth ? '+'.$newThisMonth.' joined this month' : 'No new members yet this month' }}</div></div>
  <div class="stat"><div class="l">Income this month</div><div class="n">{{ rs($income) }}</div>
    <div class="d {{ $delta === null ? '' : ($delta >= 0 ? 'up' : 'down') }}">@if ($delta === null) {{ rs($todayCash) }} taken today @else {{ $delta >= 0 ? '▲' : '▼' }} {{ abs($delta) }}% vs this time last month @endif</div></div>
  <div class="stat"><div class="l">Here today</div><div class="n">{{ $hereToday->count() }}</div><div class="d">{{ $weekAvg }} a day on average this week</div></div>
  <div class="stat {{ $owed > 0 ? 'lime' : '' }}"><div class="l">Still owed to you</div><div class="n">{{ rs($owed) }}</div><div class="d">{{ $owingCount ? $owingCount.' '.\Illuminate\Support\Str::plural('member', $owingCount) : 'Everyone is paid up' }}</div></div>
</div>

<div class="dashgrid">
  <div class="card todo">
    <div class="row between"><h2 style="margin:0">Needs you</h2><span class="muted small">Most urgent first</span></div>
    @forelse ($shown as $t)
      <div class="trow">
        <span class="tic {{ $labels[$t['kind']] ?? 'grey' }}">{!! \App\Support\AdminNav::icon($t['icon']) !!}</span>
        <span class="tx"><b>{{ $t['title'] }}</b><small>{{ $t['sub'] }}@isset($t['tag']) <span class="b {{ $t['tag'][0] }}">{{ $t['tag'][1] }}</span>@endisset</small></span>
        <a class="btn sm {{ $loop->first ? '' : 'ghost' }}" href="{{ $t['url'] }}" @if (! empty($t['external'])) target="_blank" rel="noopener" @endif>{{ $t['cta'] }}</a>
      </div>
    @empty
      <div class="allclear"><span>{!! \App\Support\AdminNav::icon('check') !!}</span><b>You are all caught up</b><p class="muted small">No renewals, requests, balances or enquiries are waiting.</p></div>
    @endforelse
    @if ($more > 0)<p class="small muted" style="margin:.7rem 0 0">+{{ $more }} more. See <a href="{{ route('admin.renewals') }}">Renewals</a>, <a href="{{ route('admin.members.index', ['filter' => 'owes']) }}">balances</a>, <a href="{{ route('admin.enquiries.index') }}">Enquiries</a>.</p>@endif
  </div>

  <div class="stack-col">
    <div class="card"><h2>Memberships</h2>
      <div class="segbar" role="img" aria-label="Membership status">
        @foreach (['active' => 'lime', 'expiring' => 'amber', 'expired' => 'red', 'frozen' => 'blue'] as $k => $c)@if ($st($k))<i class="{{ $c }}" style="flex:{{ $st($k) }}" title="{{ ucfirst($k) }}: {{ $st($k) }}"></i>@endif @endforeach
      </div>
      <div class="legend">
        <a href="{{ route('admin.members.index', ['filter' => 'active']) }}"><i class="lime"></i>Active <b>{{ $st('active') }}</b></a>
        <a href="{{ route('admin.members.index', ['filter' => 'expiring']) }}"><i class="amber"></i>Ending soon <b>{{ $st('expiring') }}</b></a>
        <a href="{{ route('admin.members.index', ['filter' => 'expired']) }}"><i class="red"></i>Ended <b>{{ $st('expired') }}</b></a>
        <a href="{{ route('admin.members.index', ['filter' => 'frozen']) }}"><i class="blue"></i>Frozen <b>{{ $st('frozen') }}</b></a>
      </div>
    </div>
    <div class="card"><h2>Check-ins this week</h2>
      <div class="wbars">@foreach ($week as $d)<div class="wb {{ $d['date']->isToday() ? 'today' : '' }}"><i style="height:{{ max(4, $d['n'] / $maxWeek * 100) }}%"><em>{{ $d['n'] }}</em></i><span>{{ $d['label'] }}</span></div>@endforeach</div>
      @if ($hereToday->count())<div class="mt small muted">In today: @foreach ($hereToday->take(6) as $a)<a href="{{ route('admin.members.show', $a->member) }}">{{ explode(' ', $a->member->user->name)[0] }}</a>{{ ! $loop->last ? ', ' : '' }}@endforeach @if ($hereToday->count() > 6) and {{ $hereToday->count() - 6 }} more @endif</div>
      @else<div class="mt small muted">Nobody yet today. Members scan the QR code at the door, or check them in above.</div>@endif
    </div>
  </div>
</div>

<div class="grid g2 mt">
  <div class="card"><h2>Income and expenses <span class="muted small">last 6 months</span></h2>@include('partials.chart_bars', ['labels' => $chart['labels'], 'series' => $chart['series']])
    <p class="small muted" style="margin:.5rem 0 0">This month so far: <b>{{ rs($income - $spent) }}</b> profit after {{ rs($spent) }} of expenses. <a href="{{ route('admin.finance') }}">Full report</a></p></div>
  <div class="card"><h2>Recent activity</h2>
    @forelse ($activity as $a)<div class="arow"><span class="adot"></span><span><b>{{ $a->action }}</b>@if ($a->subject) <span class="muted">· {{ $a->subject }}</span>@endif<small>{{ $a->user?->name ?? 'System' }} · {{ $a->created_at->diffForHumans() }}</small></span></div>
    @empty <div class="empty">Nothing yet.</div> @endforelse
    <p class="small" style="margin:.6rem 0 0"><a href="{{ route('admin.audit') }}">Full activity log</a></p></div>
</div>
@endsection
