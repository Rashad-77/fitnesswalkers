@extends('layouts.member')
@section('title', 'Membership')
@section('content')
@php $ms = $m->currentMembership; @endphp
<h1>My membership</h1>
<div class="card mb">
  @if ($ms)<p><strong>{{ $ms->plan->name }}</strong> <span class="b {{ $m->status() }}">{{ $m->status() }}</span><br>
    <span class="small muted">{{ $ms->starts_on->format('d M Y') }} to {{ $ms->ends_on->format('d M Y') }} @if ($m->daysLeft() >= 0)· {{ $m->daysLeft() }} days left @endif</span></p>
  @else<p>No membership yet.</p>@endif
  @if ($m->balanceDue() > 0)<p class="alert warn">Balance to pay: <strong>{{ rs($m->balanceDue()) }}</strong></p>@endif
  <h3 class="mt">Request a renewal</h3>
  @if ($pending)<p class="alert info">Request sent {{ $pending->created_at->diffForHumans() }}. The gym will contact you.</p>
  @else
  <form method="post" action="{{ route('member.renew') }}">@csrf
    <div class="f"><select name="plan_id"><option value="">Same as before</option>@foreach ($plans as $p)<option value="{{ $p->id }}">{{ $p->name }} - {{ rs($p->price) }}</option>@endforeach</select></div>
    <button class="btn block">Request renewal</button>
    <p class="small muted">Payments are made at the gym or by bank transfer, not in the app. The owner updates your membership once paid.</p>
  </form>@endif
</div>
<div class="card"><h2>Payments</h2>
  @forelse ($m->payments as $p)<div class="row between" style="padding:.4rem 0;border-bottom:1px solid #eef0ea"><span>{{ $p->paid_on->format('d M Y') }} <span class="muted small">{{ $p->receipt_no }} · {{ $p->method }}</span></span><strong>{{ rs($p->amount) }}</strong></div>
  @empty<div class="empty">No payments yet.</div>@endforelse
</div>
@endsection
