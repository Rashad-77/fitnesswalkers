@extends('layouts.member')
@section('title', 'Food')
@section('content')
<h1>Meal plan</h1>
@if ($plan && $plan->source === 'owner')
  <div class="card mb"><div class="row between"><h2 style="margin:0">{{ $plan->title }}</h2><span class="b approved">from the owner</span></div>
    <pre class="plain mt">{{ $plan->owner_text }}</pre></div>
  <p class="disc">This plan was written for you by the gym owner. For a plan with prices and a shopping list, you can make one with the questions below. It replaces the owner's plan on your screen.</p>
@elseif ($plan)
  <div class="row between mb"><span class="b active">made by the app</span><a class="btn ghost sm" href="{{ route('member.diet.shopping') }}">🛒 Shopping list</a></div>
  @include('partials.diet_plan', ['plan' => $plan->plan, 'day' => $day, 'swappable' => true, 'tabsUrl' => fn ($i) => route('member.diet', ['day' => $i]), 'priceDate' => $priceDate])
  <p class="disc">Plan made {{ $plan->created_at->format('d M Y') }}. Want a different one? Answer the questions below again. The owner can also see and change your plan.</p>
@endif

@foreach ((array) $issues as $i)<div class="alert err">{{ $i }}</div>@endforeach
@if ($issues)<div class="alert info">Please speak to the gym owner. He can write a plan that suits you safely.</div>@endif

<div class="card mt"><h2>{{ $plan ? 'Create a new plan' : 'Create my plan' }}</h2>
  <p class="small muted">Answer a few questions and the app builds a 7-day meal plan with prices in rupees that fits your budget.</p>
  @php $f = fn ($k, $d = null) => old($k, $form[$k] ?? $d); @endphp
  <form method="post" action="{{ route('member.diet.generate') }}">@csrf
    <div class="f"><label>Your goal</label><select name="goal">@foreach (['lose' => 'Lose weight', 'maintain' => 'Stay the same', 'gain' => 'Gain weight / muscle'] as $k => $v)<option value="{{ $k }}" @selected($f('goal', 'maintain') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="grid g2" style="grid-template-columns:1fr 1fr">
      <div class="f"><label>Age</label><input type="number" name="age" value="{{ $f('age', $defaults['age']) }}" required></div>
      <div class="f"><label>Sex</label><select name="sex">@foreach (['male', 'female'] as $s)<option value="{{ $s }}" @selected($f('sex', $defaults['sex']) === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
      <div class="f"><label>Height (cm)</label><input type="number" step="0.1" name="height" value="{{ $f('height', $defaults['height']) }}" required></div>
      <div class="f"><label>Weight (kg)</label><input type="number" step="0.1" name="weight" value="{{ $f('weight', $defaults['weight']) }}" required></div>
    </div>
    <div class="f"><label>How active are you?</label><select name="activity">@foreach (['sedentary' => 'Mostly sitting, little exercise', 'light' => 'Light (1-3 workouts a week)', 'moderate' => 'Moderate (3-5 workouts a week)', 'active' => 'Very active (6-7 workouts or a physical job)'] as $k => $v)<option value="{{ $k }}" @selected($f('activity', 'moderate') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="f"><label>What do you eat?</label><select name="diet_type">@foreach (['non_veg' => 'Everything (meat, fish, eggs)', 'vegetarian' => 'Vegetarian (eggs and dairy ok)', 'vegan' => 'Vegan (plants only)'] as $k => $v)<option value="{{ $k }}" @selected($f('diet_type', 'non_veg') === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="f checks"><label style="display:block">Allergies (tick any)</label>@foreach ($allergens as $a)<label><input type="checkbox" name="allergies[]" value="{{ $a }}" @checked(in_array($a, (array) $f('allergies', [])))> {{ $a }}</label>@endforeach</div>
    <div class="grid g2" style="grid-template-columns:1fr 1fr">
      <div class="f"><label>Meals a day</label><select name="meals">@foreach ([3, 4, 5] as $n)<option @selected((int) $f('meals', 3) === $n)>{{ $n }}</option>@endforeach</select></div>
      <div class="f"><label>Food budget a day (Rs.)</label><input type="number" name="budget" min="100" value="{{ $f('budget', 800) }}" required></div>
    </div>
    <div class="f checks"><label style="display:block">Safety check</label>
      <label><input type="checkbox" name="pregnant" value="1"> I'm pregnant or breastfeeding</label><br>
      <label><input type="checkbox" name="medical" value="1"> I have a medical condition or injury (e.g. diabetes, heart, kidney, eating disorder)</label></div>
    <button class="btn block">Build my plan</button>
  </form>
  <p class="disc">This is a general guide, not medical advice. Under-18s, pregnant members, people with medical conditions and people with a very low BMI are asked to speak to the owner or a doctor instead of using an app-made plan.</p>
</div>
@endsection
