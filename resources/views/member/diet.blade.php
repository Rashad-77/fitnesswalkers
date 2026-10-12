@extends('layouts.member')
@section('title', 'Food')
@section('content')
<h1>Meal plan</h1>
@if ($plan && $plan->source === 'owner')
  <div class="card mb"><div class="row between"><h2 style="margin:0">{{ $plan->title }}</h2><span class="b approved">from the owner</span></div>
    <pre class="plain mt">{{ $plan->owner_text }}</pre></div>
  <p class="disc">This plan was written for you by the gym owner.@if ($workout) You can also make your own plan from a few questions. It replaces the owner's plan on your screen.@endif</p>
  @if ($workout)<a class="btn ghost" href="{{ route('member.diet.create') }}">Make my own plan</a>@endif
@elseif ($plan)
  <div class="row between mb"><span class="b active">made for you</span><span class="row"><a class="btn ghost sm" href="{{ route('member.diet.shopping') }}">Shopping list</a><a class="btn ghost sm" href="{{ route('member.diet.create', ['edit' => 1]) }}">Redo the survey</a></span></div>
  @include('partials.diet_plan', ['plan' => $plan->plan, 'day' => $day, 'swappable' => true, 'tabsUrl' => fn ($i) => route('member.diet', ['day' => $i]), 'alts' => $alts])
  <p class="disc">Plan made {{ $plan->created_at->format('d M Y') }}. The owner can also see your plan.</p>
  @pushOnce('scripts', 'planner-js')<script src="/js/planner.js"></script>@endPushOnce
@elseif (! $workout)
  <div class="card lockcard">
    <span class="lockic">@include('partials.i', ['n' => 'dumbbell'])</span>
    <h2>First, your workout plan</h2>
    <p>Your meal plan is built around your workouts, so it can be made once the owner has approved your workout plan.</p>
    <p class="small"><b>{{ $waiting }}</b></p>
    <ol class="steps small"><li>Request your workout plan.</li><li>Pay your membership, so the owner can approve it.</li><li>Come back here and answer a few questions about you and your food.</li></ol>
    <a class="btn" href="{{ route('member.workout') }}">Go to my workout plan</a>
  </div>
@else
  <div class="card lockcard ready">
    <span class="lockic">@include('partials.i', ['n' => 'food'])</span>
    <h2>Get your meal plan</h2>
    <p>Answer a few questions about you, your body and what you like to eat. We build a 7 day plan around your workout plan, with a main course, dishes, dessert and water for every meal.</p>
    <ul class="ticks2"><li>Your workout plan: <b>{{ $workout->name }}</b></li><li>Takes about 3 minutes</li><li>Change any food to fit your calorie target</li></ul>
    <a class="btn" href="{{ route('member.diet.create') }}">Start the questions</a>
  </div>
@endif
@endsection
