@extends('layouts.member')
@section('title', 'Home')
@section('content')
@php $ms = $m->currentMembership; $st = $m->status(); $left = $m->daysLeft(); @endphp
<p class="muted" style="margin:0">Welcome,</p><h1 style="margin-bottom:.8rem">{{ explode(' ', $m->user->name)[0] }}</h1>

<div class="card mb"><h2>Today</h2>
  @if ($plan)
    @php $days = $plan->items->pluck('day_no')->unique()->values(); @endphp
    <p class="small" style="margin-top:0">Your plan: <strong>{{ $plan->name }}</strong></p>
    <div class="row">@foreach ($days as $d)<a class="btn {{ $loop->first ? '' : 'ghost' }}" href="{{ route('member.workout.day', $d) }}">Day {{ $d }}</a>@endforeach</div>
  @elseif ($pendingReq)
    <p style="margin-top:0">Your workout plan request is with the owner. You will see it here once it is approved.</p>
  @else
    <p style="margin-top:0">You do not have a workout plan yet.</p><a class="btn" href="{{ route('member.workout') }}">Request your workout plan</a>
  @endif
  <div class="small" style="margin-top:.8rem;padding-top:.7rem;border-top:1px solid #eef0ea">
    @if ($checkedIn)<span class="b active">Checked in today</span> Have a good workout!
    @else Scan the code at the gym door to check in.@endif
    <span class="muted">{{ $visits }} {{ $visits === 1 ? 'visit' : 'visits' }} this month.</span>
  </div>
</div>

<div class="hero mb">
  <div class="small" style="color:#9aa4b0">Your membership @if ($ms)· {{ $ms->plan->name }}@endif</div>
  @if (! $ms)<div class="big">No plan yet</div><p class="small" style="color:#cfd5dd">Visit the gym to start a membership.</p>
  @elseif ($st === 'frozen')<div class="big">Frozen</div>
  @elseif ($left < 0)<div class="big" style="color:#ff8a80">Expired</div><p class="small" style="color:#cfd5dd">Ended {{ $ms->ends_on->format('d M Y') }}</p>
  @else<div class="big">{{ $left }} <span style="font-size:1rem">days left</span></div>
    <div class="bar mt"><i style="width:{{ min(100, max(3, $left / max(1, $ms->plan->duration_days) * 100)) }}%"></i></div>
    <p class="small" style="color:#cfd5dd;margin-top:.5rem">Ends {{ $ms->ends_on->format('d M Y') }}</p>@endif
  @if (in_array($st, ['expiring', 'expired']))<a class="btn mt" href="{{ route('member.membership') }}">Renew membership</a>@endif
</div>
@if ($m->balanceDue() > 0)<div class="alert warn">You have {{ rs($m->balanceDue()) }} still to pay. <a href="{{ route('member.membership') }}">See details</a></div>@endif

<div class="grid g2 mb" style="grid-template-columns:1fr 1fr">
  <div class="stat"><div class="n">{{ $thisWeek }}{{ $planDays ? '/'.$planDays : '' }}</div><div class="l">Workouts this week</div></div>
  <div class="stat"><div class="n">{{ $days28 }}</div><div class="l">Workout days (last 4 weeks)</div></div>
</div>

@if ($announcements->count())
<div class="card"><h2>Announcements <a class="small" href="{{ route('member.announcements') }}">All</a></h2>
  @foreach ($announcements as $a)<div style="padding:.4rem 0"><strong>{{ $a->title }}</strong><div class="small muted">{{ $a->published_at->format('d M') }}</div><div class="small">{{ $a->body }}</div></div>@endforeach</div>
@endif
@endsection
