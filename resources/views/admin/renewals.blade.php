@extends('layouts.admin')
@section('title', 'Reminders')
@section('content')
<h1>Reminders</h1>
<p class="muted">Members to contact today. Use WhatsApp to open a prepared message, send it, then select <strong>Mark sent</strong> to keep a record.</p>
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
        <span class="muted small">· {{ $r['detail'] }}</span>
        @if ($r['sent'])<span class="b done">sent {{ $r['sent']->isToday() ? 'today' : $r['sent']->diffForHumans() }}</span>@endif</span>
      <span class="row">
        @if ($r['wa'])<a class="btn sm" target="_blank" rel="noopener" href="{{ $r['wa'] }}">WhatsApp</a>@else<span class="muted small">no phone</span>@endif
        <form method="post" action="{{ route('admin.reminders.sent') }}">@csrf
          <input type="hidden" name="member_id" value="{{ $r['m']->id }}"><input type="hidden" name="kind" value="{{ $kind }}">
          <button class="btn ghost sm">Mark sent</button></form>
      </span>
    </div>
  @empty <div class="empty">{{ $g['empty'] }}</div> @endforelse
</div>
@endforeach
@endsection
