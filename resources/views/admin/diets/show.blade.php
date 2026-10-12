@extends('layouts.admin')
@section('title', $diet->title)
@section('content')
<div class="row between mb"><div><h1>{{ $diet->title }}</h1><span class="muted">{{ $diet->member->user->name }} · {{ $diet->source === 'owner' ? 'written by you' : 'made by the app' }} · {{ $diet->created_at->format('d M Y') }}</span></div>
  <div class="row"><a class="btn" href="{{ route('admin.diets.create', ['member' => $diet->member_id]) }}">Override with my own plan</a>
    <form method="post" action="{{ route('admin.diets.destroy', $diet) }}">@csrf @method('DELETE')<button class="btn ghost" data-confirm="Delete this plan?">Delete</button></form></div></div>
@if ($diet->source === 'owner')
  <div class="card"><pre class="plain">{{ $diet->owner_text }}</pre></div>
@else
  @if ($diet->inputs)@php $in = $diet->inputs; @endphp<div class="card mb small"><strong>Survey answers:</strong> {{ $in['sex'] ?? '' }}, {{ $in['age'] ?? '' }} years, {{ $in['height'] ?? '' }} cm, {{ $in['weight'] ?? '' }} kg · wants to {{ strtolower(\App\Http\Controllers\Member\DietController::ACHIEVEMENTS[$in['achievement'] ?? ''] ?? ($in['goal'] ?? '')) }} · body now {{ $in['current_body'] ?? '-' }}, goal {{ $in['body_goal'] ?? '-' }} · metabolism {{ $in['metabolism'] ?? '-' }} · {{ $in['train_days'] ?? '-' }} workouts a week · {{ $in['meals'] ?? '-' }} meals · {{ \App\Services\DietEngine::PATTERNS[$in['pattern'] ?? 'everything'] ?? '' }}@if (! empty($in['allergies'])) · allergies: {{ implode(', ', $in['allergies']) }}@endif</div>@endif
  @include('partials.diet_plan', ['plan' => $diet->plan, 'day' => max(1, min(7, (int) request('day', 1))), 'swappable' => false, 'tabsUrl' => fn ($i) => route('admin.diets.show', [$diet, 'day' => $i]), 'alts' => []])
@endif
@endsection
