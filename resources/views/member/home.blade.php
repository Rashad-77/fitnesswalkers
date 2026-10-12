@extends('layouts.member')
@section('title', 'Home')
@section('content')
@php
  use App\Support\AdminNav;
  $ms = $m->currentMembership; $st = $m->status(); $left = $m->daysLeft(); $first = explode(' ', $m->user->name)[0];
  $hour = (int) now()->format('G'); $hello = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
  $chip = match (true) { ! $ms => ['none', 'No membership'], $st === 'frozen' => ['pending', 'Frozen'], $left < 0 => ['expired', 'Expired'], $left <= 7 => ['expiring', $left.' '.($left === 1 ? 'day' : 'days').' left'], default => ['active', 'Active'] };
@endphp

<header class="mh-top">
  <div><p class="mh-date">{{ now()->format('l, j F') }}</p><h1>{{ $hello }}, {{ $first }}</h1></div>
  <a class="b {{ $chip[0] }}" href="{{ route('member.membership') }}">{{ $chip[1] }}</a>
</header>

@if ($m->balanceDue() > 0)<div class="alert warn">You still have {{ rs($m->balanceDue()) }} to pay. <a href="{{ route('member.membership') }}">See details</a></div>@endif

<div class="mh-grid">
{{-- 1. Today: the one thing to do next --}}
<section class="mh-hero" aria-label="Today">
  <div class="mh-row">
    <div class="mh-next">
      @if ($next)
        <span class="mh-k">Up next</span>
        <h2>Day {{ $next['day'] }}</h2>
        <p>{{ $plan->name }} · {{ $next['count'] }} {{ $next['count'] === 1 ? 'exercise' : 'exercises' }}</p>
      @elseif ($pendingReq)
        <span class="mh-k">Workout plan</span><h2>With the owner</h2><p>You will see it here as soon as it is approved.</p>
      @else
        <span class="mh-k">Workout plan</span><h2>Get your plan</h2><p>The owner builds it around your goal.</p>
      @endif
    </div>
    @if ($next)<a class="btn lime" href="{{ route('member.workout.day', $next['day']) }}">Start</a>
    @elseif (! $pendingReq)<a class="btn lime" href="{{ route('member.workout') }}">Request</a>@endif
  </div>

  <div class="mh-week" role="img" aria-label="{{ $weekDone }} workout {{ $weekDone === 1 ? 'day' : 'days' }} so far this week">
    @foreach ($week as $d)<span class="{{ $d['done'] ? 'done' : '' }} {{ $d['today'] ? 'today' : '' }} {{ $d['future'] ? 'future' : '' }}"><i>@if ($d['done']){!! AdminNav::icon('check') !!}@endif</i>{{ $d['label'] }}</span>@endforeach
  </div>
  <p class="mh-foot"><b>{{ $weekDone }}{{ $planDays ? ' of '.$planDays : '' }}</b> {{ $planDays ? 'workouts this week' : ($weekDone === 1 ? 'workout this week' : 'workouts this week') }}
    <span class="sep"></span>
    @if ($checkedIn)<span class="mh-ok">{!! AdminNav::icon('check') !!} Checked in today</span>@else Scan the code at the gym door to check in @endif</p>
</section>

{{-- 2. Numbers at a glance --}}
<section class="mh-stats" aria-label="At a glance">
  <a href="{{ route('member.progress') }}"><b>{{ $visits }}</b><span>{{ $visits === 1 ? 'visit' : 'visits' }} this month</span></a>
  <a href="{{ route('member.progress') }}"><b>{{ $weight ? rtrim(rtrim(number_format($weight, 1), '0'), '.') : '-' }}<small>{{ $weight ? ' kg' : '' }}</small></b>
    <span>@if ($weightChange !== null && $weightChange != 0)<em class="{{ $weightChange < 0 ? 'dn' : 'up' }}">{{ $weightChange > 0 ? '+' : '−' }}{{ number_format(abs($weightChange), 1) }} kg</em> since last @else Your weight @endif</span></a>
  <a href="{{ route('member.membership') }}"><b>{{ $ms && $left >= 0 && $st !== 'frozen' ? $left : '-' }}<small>{{ $ms && $left >= 0 && $st !== 'frozen' ? ' days' : '' }}</small></b><span>left on your plan</span></a>
</section>

<div class="mh-main">
{{-- 3. Food today --}}
<section class="mh-card" aria-label="Food today">
  <div class="mh-head"><h2>{!! AdminNav::icon('food') !!} Food today</h2><a href="{{ route('member.diet') }}">{{ $meals ? 'Open plan' : 'Set up' }} &rsaquo;</a></div>
  @if ($meals)
    <div class="mh-cal"><div><b>{{ number_format($meals['kcal']) }}</b> <span>of {{ number_format($meals['t']['kcal']) }} kcal planned</span></div>
      <div class="bar"><i style="width:{{ min(100, max(3, $meals['kcal'] / max(1, $meals['t']['kcal']) * 100)) }}%"></i></div>
      <p class="mh-macros"><span>Protein <b>{{ $meals['t']['protein'] }} g</b></span><span>Carbs <b>{{ $meals['t']['carbs'] }} g</b></span><span>Fat <b>{{ $meals['t']['fat'] }} g</b></span>@if ($meals['water'])<span>Water <b>{{ $meals['water'] }} L</b></span>@endif</p></div>
    <ul class="mh-meals">@foreach ($meals['list'] as $x)<li><span>{{ $x['slot'] }}</span><b>{{ $x['name'] }}</b>@if ($x['kcal'])<em>{{ $x['kcal'] }} kcal</em>@endif</li>@endforeach</ul>
  @elseif ($plan)
    <p class="mh-empty">Answer a few questions and get a week of meals with exact amounts, built around your workouts.</p><a class="btn" href="{{ route('member.diet') }}">Make my meal plan</a>
  @else
    <p class="mh-empty">Your meal plan is built around your workout plan, so get that first.</p>
  @endif
</section>

</div>
<div class="mh-side">
{{-- 4. Membership --}}
<section class="mh-card" aria-label="Membership">
  <div class="mh-head"><h2>{!! AdminNav::icon('card') !!} Membership</h2><a href="{{ route('member.membership') }}">Details &rsaquo;</a></div>
  @if (! $ms)<p class="mh-empty">Visit the gym to start a membership.</p>
  @elseif ($st === 'frozen')<p class="mh-empty">Your membership is frozen. Your days are saved for when it restarts.</p>
  @elseif ($left < 0)<p class="mh-empty">Your {{ $ms->plan->name }} membership ended on {{ $ms->ends_on->format('d M Y') }}.</p><a class="btn" href="{{ route('member.membership') }}">Renew</a>
  @else
    <div class="mh-cal"><div><b>{{ $ms->plan->name }}</b> <span>ends {{ $ms->ends_on->format('d M Y') }}</span></div>
      <div class="bar"><i style="width:{{ min(100, max(3, $left / max(1, $ms->plan->duration_days) * 100)) }}%"></i></div></div>
    @if (in_array($st, ['expiring', 'expired']))<a class="btn" href="{{ route('member.membership') }}">Renew membership</a>@endif
  @endif
</section>

@if ($announcements->count())
<section class="mh-card" aria-label="News">
  <div class="mh-head"><h2>{!! AdminNav::icon('megaphone') !!} From the gym</h2><a href="{{ route('member.announcements') }}">All &rsaquo;</a></div>
  @foreach ($announcements as $a)<article class="mh-news"><b>{{ $a->title }}</b><time>{{ $a->published_at->format('d M') }}</time><p>{{ $a->body }}</p></article>@endforeach
</section>
@endif
</div>
</div>
@endsection
