@extends('layouts.admin')
@section('title', 'Renewals')
@section('content')
<h1>Renewals</h1>
<p class="muted">Members who asked to renew, and memberships that are ending or have just ended.</p>
@if ($requests->count())
<div class="card mb"><h2>Members who asked to renew</h2>
  @foreach ($requests as $q)
    <div class="row between" style="padding:.5rem 0;border-bottom:1px solid #eef0ea">
      <span><a href="{{ route('admin.members.show', $q->member) }}">{{ $q->member->user->name }}</a> <span class="muted small">wants {{ $q->plan?->name ?? 'to renew' }} · {{ $q->created_at->diffForHumans() }}</span></span>
      <span class="row"><a class="btn sm" href="{{ route('admin.members.show', [$q->member, 'tab' => 'payments']) }}">Renew</a>
      <form method="post" action="{{ route('admin.renewals.done', $q) }}">@csrf<button class="btn ghost sm">Mark complete</button></form></span>
    </div>
  @endforeach
</div>
@endif
@foreach ($groups as $kind => $g)
<div class="card mb"><h2>{{ $g['title'] }} <span class="muted small">({{ $g['rows']->count() }})</span></h2>
  @forelse ($g['rows'] as $r)
    <div class="row between" style="padding:.5rem 0;border-bottom:1px solid #eef0ea">
      <span><a href="{{ route('admin.members.show', $r['m']) }}">{{ $r['m']->user->name }}</a>
        <span class="muted small">· {{ $r['detail'] }}</span></span>
      <a class="btn sm" href="{{ route('admin.members.show', [$r['m'], 'tab' => 'payments']) }}">Renew</a>
    </div>
  @empty <div class="empty">{{ $g['empty'] }}</div> @endforelse
</div>
@endforeach
@endsection
