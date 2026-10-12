@extends('layouts.admin')
@section('title', $diet->title)
@section('content')
<div class="row between mb"><div><h1>{{ $diet->title }}</h1><span class="muted">{{ $diet->member->user->name }} · {{ $diet->source === 'owner' ? 'written by you' : 'made by the app' }} · {{ $diet->created_at->format('d M Y') }}</span></div>
  <div class="row"><a class="btn" href="{{ route('admin.diets.create', ['member' => $diet->member_id]) }}">Override with my own plan</a>
    <form method="post" action="{{ route('admin.diets.destroy', $diet) }}">@csrf @method('DELETE')<button class="btn ghost" data-confirm="Delete this plan?">Delete</button></form></div></div>
@if ($diet->source === 'owner')
  <div class="card"><pre class="plain">{{ $diet->owner_text }}</pre></div>
@else
  @if ($diet->inputs)<div class="card mb small"><strong>Answers given:</strong> goal {{ $diet->inputs['goal'] }}, age {{ $diet->inputs['age'] }}, {{ $diet->inputs['sex'] }}, {{ $diet->inputs['height'] }} cm, {{ $diet->inputs['weight'] }} kg, activity {{ $diet->inputs['activity'] }}, {{ str_replace('_', '-', $diet->inputs['diet_type']) }}, allergies: {{ implode(', ', $diet->inputs['allergies'] ?: ['none']) }}, {{ $diet->inputs['meals'] }} meals, budget {{ rs($diet->inputs['budget']) }}/day</div>@endif
  @include('partials.diet_plan', ['plan' => $diet->plan, 'day' => max(1, min(7, (int) request('day', 1))), 'swappable' => false, 'tabsUrl' => fn ($i) => route('admin.diets.show', [$diet, 'day' => $i]), 'priceDate' => \App\Models\Food::min('price_updated_on')])
@endif
@endsection
