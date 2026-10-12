@extends('layouts.admin')
@section('title', 'Reports')
@section('content')
@php
  $G = '#5fa31f'; $D = '#143a2c'; $B = '#5a98d6'; $A = '#e0a02a'; $R = '#d4413a';
  $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
@endphp
<div class="row between mb"><div><h1>Reports</h1><p class="muted" style="margin:0">How the gym is doing: money, members and attendance.</p></div>
  @if ($tab === 'money')<form method="get" class="row"><input type="hidden" name="tab" value="money"><input type="month" name="month" value="{{ $month }}" style="width:auto"><button class="btn dark">Show</button></form>@endif</div>
<div class="ptabs">@foreach ($tabs as $k => $label)<a class="{{ $tab === $k ? 'on' : '' }}" href="{{ route('admin.finance', ['tab' => $k]) }}">{{ $label }}</a>@endforeach</div>

@if ($tab === 'money')
  @php $diff = $prevIncome > 0 ? round(($income - $prevIncome) / $prevIncome * 100) : null; @endphp
  <div class="grid g3 mb">
    <div class="stat dark"><div class="n">{{ rs($income) }}</div><div class="l">Income in {{ $start->format('F Y') }}</div></div>
    <div class="stat"><div class="n">{{ rs($spent) }}</div><div class="l">Expenses</div></div>
    <div class="stat lime"><div class="n">{{ rs($income - $spent) }}</div><div class="l">Profit @if ($diff !== null) · {{ $diff >= 0 ? '+' : '' }}{{ $diff }}% income vs last month @endif</div></div>
  </div>
  <div class="card mb"><h2>Income, expenses and profit <span class="muted small">12 months to {{ $start->format('M Y') }}</span></h2>
    @include('partials.chart', ['h' => 280, 'label' => 'Income, expenses and profit by month', 'spec' => ['type' => 'bar', 'format' => 'rs', 'labels' => $trend['labels'], 'datasets' => [
      ['label' => 'Income', 'data' => $trend['income'], 'color' => $G], ['label' => 'Expenses', 'data' => $trend['expenses'], 'color' => $D],
      ['label' => 'Profit', 'type' => 'line', 'data' => $trend['profit'], 'color' => $A, 'fill' => false, 'points' => true]]]])</div>
  <div class="grid g2 mb">
    <div class="card"><h2>Income through the month <span class="muted small">against the month before</span></h2>
      @include('partials.chart', ['h' => 240, 'label' => 'Income through the month', 'spec' => ['type' => 'line', 'format' => 'rs', 'labels' => $through['labels'], 'datasets' => [
        ['label' => $start->format('M'), 'data' => $through['this'], 'color' => $G], ['label' => $start->copy()->subMonthNoOverflow()->format('M'), 'data' => $through['last'], 'color' => '#9aa8a0', 'fill' => false, 'dash' => true]]]])</div>
    <div class="card"><h2>Who owes money <span class="muted small">{{ $owing->count() }} {{ \Illuminate\Support\Str::plural('member', $owing->count()) }} · {{ rs($owing->sum(fn ($m) => $m->balanceDue())) }}</span></h2>
      @forelse ($owing->take(7) as $m)<div class="row between small" style="padding:.4rem 0;border-bottom:1px solid #eef2e9"><a href="{{ route('admin.members.show', [$m, 'tab' => 'payments']) }}">{{ $m->user->name }}</a><strong>{{ rs($m->balanceDue()) }}</strong></div>
      @empty<div class="empty">Everyone is paid up.</div>@endforelse</div>
  </div>
  <div class="grid g3">
    <div class="card"><h2>Income by package</h2>@if ($byPlan->count())@include('partials.chart', ['h' => 220, 'label' => 'Income by package', 'spec' => ['type' => 'doughnut', 'format' => 'rs', 'labels' => $byPlan->keys()->all(), 'colors' => [$G, $D, $B, $A, '#9aa8a0'], 'datasets' => [['data' => $byPlan->values()->all()]], 'centre' => ['text' => rs($income), 'sub' => 'this month']]])@else<div class="empty">No income.</div>@endif</div>
    <div class="card"><h2>Income by method</h2>@if ($byMethod->count())@include('partials.chart', ['h' => 220, 'label' => 'Income by method', 'spec' => ['type' => 'doughnut', 'format' => 'rs', 'labels' => $byMethod->keys()->map(fn ($k) => ucfirst($k))->all(), 'colors' => [$G, $D, $B], 'datasets' => [['data' => $byMethod->values()->all()]]]])@else<div class="empty">No income.</div>@endif</div>
    <div class="card"><h2>Expenses by category</h2>@if ($byCategory->count())@include('partials.chart', ['h' => 220, 'label' => 'Expenses by category', 'spec' => ['type' => 'hbar', 'format' => 'rs', 'legend' => false, 'labels' => $byCategory->keys()->all(), 'datasets' => [['data' => $byCategory->values()->all(), 'color' => $D]]]])@else<div class="empty">No expenses.</div>@endif</div>
  </div>

@elseif ($tab === 'members')
  <div class="grid g4 mb">
    <div class="stat dark"><div class="n">{{ $activeNow }}</div><div class="l">Members now</div></div>
    <div class="stat lime"><div class="n">{{ $renewalRate === null ? '-' : $renewalRate.'%' }}</div><div class="l">Renewal rate · {{ $renewedCount }} of {{ $endedCount }} that ended in 6 months came back</div></div>
    <div class="stat"><div class="n">{{ $endingSoon->count() }}</div><div class="l">Ending in the next 7 days</div></div>
    <div class="stat"><div class="n">{{ $lapsed->count() }}</div><div class="l">Lapsed in the last 90 days</div></div>
  </div>
  <div class="card mb"><h2>Members over the year</h2>
    @include('partials.chart', ['h' => 260, 'label' => 'Members with a live membership at the end of each month', 'spec' => ['type' => 'line', 'format' => 'num', 'labels' => $over['labels'], 'legend' => false, 'datasets' => [['label' => 'Members', 'data' => $over['data'], 'color' => $G, 'points' => true]]]])</div>
  <div class="grid g3 mb">
    <div class="card"><h2>Status mix</h2>@php $mix = collect(['active' => [$G, 'Active'], 'expiring' => [$A, 'Ending soon'], 'expired' => [$R, 'Ended'], 'frozen' => [$B, 'Frozen'], 'none' => ['#9aa8a0', 'No package']])->filter(fn ($v, $k) => ($statuses[$k] ?? 0) > 0); @endphp
      @include('partials.chart', ['h' => 230, 'label' => 'Members by status', 'spec' => ['type' => 'doughnut', 'format' => 'num', 'labels' => $mix->map(fn ($v) => $v[1])->values()->all(), 'colors' => $mix->map(fn ($v) => $v[0])->values()->all(), 'datasets' => [['data' => $mix->keys()->map(fn ($k) => (int) $statuses[$k])->all()]], 'centre' => ['text' => (string) $total, 'sub' => 'members']]])</div>
    <div class="card"><h2>Packages in use</h2>@if ($inUse->count())@include('partials.chart', ['h' => 230, 'label' => 'Packages in use', 'spec' => ['type' => 'hbar', 'format' => 'num', 'legend' => false, 'labels' => $inUse->keys()->all(), 'datasets' => [['data' => $inUse->values()->all(), 'color' => $D]]]])@else<div class="empty">No active packages.</div>@endif</div>
    <div class="card"><h2>Age groups</h2>@include('partials.chart', ['h' => 230, 'label' => 'Members by age group', 'spec' => ['type' => 'bar', 'format' => 'num', 'legend' => false, 'labels' => array_keys($ages), 'datasets' => [['data' => array_values($ages), 'color' => $G]]]])</div>
  </div>
  <div class="grid g2">
    <div class="card"><h2>Packages ending soon</h2>@forelse ($endingSoon as $m)<div class="row between small" style="padding:.45rem 0;border-bottom:1px solid #eef2e9"><span><a href="{{ route('admin.members.show', $m) }}">{{ $m->user->name }}</a> <span class="muted">{{ $m->currentMembership?->plan->name }}</span></span><span class="b pending">{{ $m->daysLeft() === 0 ? 'today' : $m->daysLeft().' days' }}</span></div>@empty<div class="empty">Nothing ending this week.</div>@endforelse</div>
    <div class="card"><h2>Lapsed members <span class="muted small">ended in the last 90 days</span></h2>@forelse ($lapsed->take(8) as $m)<div class="row between small" style="padding:.45rem 0;border-bottom:1px solid #eef2e9"><a href="{{ route('admin.members.show', [$m, 'tab' => 'payments']) }}">{{ $m->user->name }}</a><span class="muted">ended {{ abs($m->daysLeft()) }} days ago</span></div>@empty<div class="empty">Nobody has lapsed.</div>@endforelse</div>
  </div>

@else
  <div class="grid g3 mb">
    <div class="stat dark"><div class="n">{{ $total12 }}</div><div class="l">Check-ins in 12 weeks</div></div>
    <div class="stat"><div class="n">{{ round($total12 / 12) }}</div><div class="l">A week on average</div></div>
    <div class="stat {{ $notSeen->count() ? '' : 'lime' }}"><div class="n">{{ $notSeen->count() }}</div><div class="l">Active members not seen in {{ $quietDays }} days</div></div>
  </div>
  <div class="card mb"><h2>When people come in <span class="muted small">last 12 weeks, darker is busier</span></h2>
    <div class="heatwrap"><div class="heat"><div class="hrow hhead"><span></span>@foreach ($names as $n)<b>{{ $n }}</b>@endforeach</div>
      @foreach ($grid as $g)<div class="hrow"><span>{{ $g['label'] }}</span>@foreach ($g['cells'] as $c)<i class="hc {{ $c['n'] === null ? 'future' : '' }}" style="{{ $c['n'] ? 'background:rgba(95,163,31,'.round(.16 + .84 * $c['n'] / $gridMax, 2).');color:'.($c['n'] / $gridMax > .55 ? '#fff' : '#27430f') : '' }}" title="{{ $c['date']->format('D j M') }}: {{ $c['n'] ?? 'not yet' }}">{{ $c['n'] ?: '' }}</i>@endforeach</div>@endforeach</div></div></div>
  <div class="grid g2 mb">
    <div class="card"><h2>Check-ins per week</h2>@include('partials.chart', ['h' => 240, 'label' => 'Check-ins per week', 'spec' => ['type' => 'line', 'format' => 'num', 'legend' => false, 'labels' => $perWeek['labels'], 'datasets' => [['label' => 'Check-ins', 'data' => $perWeek['data'], 'color' => $G, 'points' => true]]]])</div>
    <div class="card"><h2>Busiest weekdays</h2>@include('partials.chart', ['h' => 240, 'label' => 'Check-ins by weekday', 'spec' => ['type' => 'bar', 'format' => 'num', 'legend' => false, 'labels' => $names, 'datasets' => [['label' => 'Check-ins', 'data' => $weekdays, 'color' => $D]]]])</div>
  </div>
  <div class="grid g3">
    <div class="card"><h2>How often members train <span class="muted small">last 4 weeks</span></h2>@include('partials.chart', ['h' => 230, 'label' => 'How often active members train', 'spec' => ['type' => 'hbar', 'format' => 'num', 'legend' => false, 'labels' => array_keys($buckets), 'datasets' => [['data' => array_values($buckets), 'color' => $G]]]])</div>
    <div class="card"><h2>Most regular <span class="muted small">last 30 days</span></h2>@forelse ($regular as $x)<div class="row between small" style="padding:.45rem 0;border-bottom:1px solid #eef2e9"><a href="{{ route('admin.members.show', $x['m']) }}">{{ $x['m']->user->name }}</a><strong>{{ $x['n'] }} visits</strong></div>@empty<div class="empty">No check-ins yet.</div>@endforelse</div>
    <div class="card"><h2>Not seen lately <span class="muted small">{{ $quietDays }}+ days</span></h2>@forelse ($notSeen->take(8) as $m)@php $l = $m->lastVisit(); @endphp<div class="row between small" style="padding:.45rem 0;border-bottom:1px solid #eef2e9"><a href="{{ route('admin.members.show', $m) }}">{{ $m->user->name }}</a><span class="muted">{{ $l ? \Carbon\Carbon::parse($l)->diffForHumans() : 'never' }}</span></div>@empty<div class="empty">Everyone has been in lately.</div>@endforelse</div>
  </div>
@endif
@endsection
