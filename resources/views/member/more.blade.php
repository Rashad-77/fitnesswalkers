@extends('layouts.member')
@section('title', 'Account')
@section('content')
@php $ms = $m->currentMembership; $st = $m->status(); @endphp
<h1>{{ $m->user->name }}</h1>
<p class="muted small" style="margin-top:0">{{ $m->member_no }} · {{ $m->user->email }}</p>
<a class="card mb" href="{{ route('member.membership') }}" style="display:block;color:inherit;text-decoration:none">
  <div class="row between"><div><div class="small muted">Membership</div>
    <strong>{{ $ms ? $ms->plan->name : 'No membership yet' }}</strong>
    @if ($ms)<div class="small muted">{{ $st === 'frozen' ? 'Frozen' : ($m->daysLeft() < 0 ? 'Expired '.$ms->ends_on->format('d M Y') : $m->daysLeft().' days left') }}</div>@endif</div>
    <span class="b {{ $st }}">{{ $st === 'none' ? 'no plan' : $st }}</span></div>
  @if ($m->balanceDue() > 0)<div class="small" style="color:#8a5a06;margin-top:.4rem">Still to pay: {{ rs($m->balanceDue()) }}</div>@endif
  <div class="small" style="margin-top:.5rem">Payments and renewal &rarr;</div>
</a>
<div class="card mb"><ul class="list">
  <li><a href="{{ route('member.announcements') }}">Announcements</a></li>
  <li><a href="{{ route('password.edit') }}">Change my password</a></li>
</ul></div>
<div class="card mb"><h2>{{ $gym }}</h2>
  <p class="small" style="margin:0">{{ $address }}<br>{{ $phone }}<br>{{ $hours }}</p></div>
<form method="post" action="{{ route('logout') }}">@csrf<button class="btn ghost block">Sign out</button></form>
<p class="small muted center mt">Tip: use your browser's "Add to Home Screen" to put this app on your phone.</p>
@endsection
