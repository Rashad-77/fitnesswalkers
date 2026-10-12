@extends('layouts.admin')
@section('title', 'Build plan')
@section('content')
<h1>Workout plan for {{ $req->member->user->name }}</h1>
<div class="card mb">
  <p><strong>Wants:</strong> {{ str_replace('_', ' ', $req->goal) }} · {{ $req->level }} · {{ $req->days_per_week }} days a week
    · Age {{ $req->member->age() ?? '?' }} · {{ $req->member->sex ?: '' }}</p>
  @if ($req->notes)<p><strong>Their note:</strong> {{ $req->notes }}</p>@endif
  @if ($req->member->notes)<p class="alert warn"><strong>Your notes on this member:</strong> {{ $req->member->notes }}</p>@endif
  <p class="small muted">Status: <span class="b {{ $req->status }}">{{ $req->status }}</span>. The member cannot see anything until you approve.</p>
</div>
@if ($req->status === 'pending')
<div class="card mb"><h2>1. Start from a template</h2>
  <form method="post" action="{{ route('admin.requests.draft', $req) }}" class="row">@csrf
    <select name="template_id" style="max-width:380px">@foreach ($suggested as $t)<option value="{{ $t->id }}">Suggested: {{ $t->name }}</option>@endforeach
      @foreach ($templates->whereNotIn('id', $suggested->pluck('id')) as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
    <button class="btn dark">{{ $req->plan ? 'Replace draft with this template' : 'Create draft' }}</button></form>
</div>
@if ($req->plan)
<form class="card mb" method="post" action="{{ route('admin.requests.update', $req) }}">@csrf @method('PUT')
  <h2>2. Edit the draft</h2>
  <div class="f"><label>Plan name</label><input type="text" name="name" value="{{ old('name', $req->plan->name) }}" required style="max-width:420px"></div>
  @include('partials.plan_items', ['items' => $req->plan->items, 'exercises' => $exercises])
  <div class="mt"><button class="btn dark">Save draft</button></div>
</form>
<div class="card mb"><h2>3. Approve or decline</h2>
  <div class="row">
    <form method="post" action="{{ route('admin.requests.approve', $req) }}">@csrf<button class="btn" data-confirm="Approve and show this plan to the member?">Approve and show to member</button></form>
  </div>
  <form method="post" action="{{ route('admin.requests.reject', $req) }}" class="mt">@csrf
    <div class="f"><label>Or decline with a note for the member</label><input type="text" name="admin_note" placeholder="e.g. Please come in so I can check your form first"></div>
    <button class="btn ghost">Decline request</button></form>
</div>
@endif
@else
  @if ($req->plan)<div class="card"><h2>{{ $req->plan->name }}</h2>@foreach ($req->plan->items->groupBy('day_no') as $d => $its)<p><strong>Day {{ $d }}:</strong> {{ $its->map(fn ($i) => $i->exercise->name.' '.$i->sets.'x'.$i->reps)->implode(', ') }}</p>@endforeach</div>@endif
  @if ($req->admin_note)<div class="alert info">Note sent to member: {{ $req->admin_note }}</div>@endif
@endif
@endsection
