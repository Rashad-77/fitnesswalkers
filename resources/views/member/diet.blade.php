@extends('layouts.member')
@section('title', 'Food')
@section('content')
<h1>Meal plan</h1>
@if ($plan && $plan->source === 'owner')
  <div class="card mb"><div class="row between"><h2 style="margin:0">{{ $plan->title }}</h2><span class="b approved">from the owner</span></div>
    <pre class="plain mt">{{ $plan->owner_text }}</pre></div>
  <p class="disc">This plan was written for you by the gym owner.@if ($workout) You can also make your own plan from a few questions. It replaces the owner's plan on your screen.@endif</p>
  @if ($workout)<a class="btn ghost" href="{{ route('member.diet.create') }}">Make my own plan</a>@endif
@elseif ($plan && (($plan->plan['format'] ?? '') !== 'dishes'))
  <div class="card lockcard"><span class="lockic">@include('partials.i', ['n' => 'food'])</span><h2>Your meal plan has a new look</h2>
    <p>We now plan with real dishes and show how each meal fits your calories, protein, carbs and fat. Answer the questions again to get yours.</p>
    @if ($workout)<a class="btn" href="{{ route('member.diet.create', ['edit' => 1]) }}">Make my new plan</a>@else<p class="small"><b>{{ $waiting }}</b></p><a class="btn" href="{{ route('member.workout') }}">Go to my workout plan</a>@endif</div>
@elseif ($plan)
  <div class="row between mb"><span class="b active">made for you</span><span class="row"><a class="btn ghost sm" href="{{ route('member.diet.shopping') }}">Shopping list</a><a class="btn ghost sm" href="{{ route('member.diet.create', ['edit' => 1]) }}">Redo the survey</a></span></div>

  @php $ov = $plan->inputs['override'] ?? null; $base = $limits['base']; @endphp
  <details class="card mb tgtedit" @if ($errors->any()) open @endif><summary><b>Adjust my targets</b> <span class="muted small">{{ $ownerSet ? 'set by your trainer' : ($ov ? 'changed by you' : 'from your survey') }}</span></summary>
    @if ($ownerSet)<p class="small muted" style="margin:.7rem 0 0">Your trainer set your targets. Ask them if you want a change.</p>
    @else
    <p class="small muted" style="margin:.7rem 0">Your survey gave <b>{{ number_format($base['kcal']) }} kcal</b>, <b>{{ $base['protein'] }} g</b> protein. You can move them inside a safe range, and your whole week is rebuilt around the new numbers. Carbs are what is left.</p>
    <form method="post" action="{{ route('member.diet.targets') }}" id="tgtform">@csrf
      <div class="grid g3">
        <div class="f"><label for="t-k">Calories a day</label><input id="t-k" type="number" name="kcal" value="{{ old('kcal', $plan->plan['targets']['kcal']) }}" min="{{ $limits['kcal'][0] }}" max="{{ $limits['kcal'][1] }}" required><p class="small muted">Safe range {{ number_format($limits['kcal'][0]) }} to {{ number_format($limits['kcal'][1]) }}</p></div>
        <div class="f"><label for="t-p">Protein, g</label><input id="t-p" type="number" name="protein" value="{{ old('protein', $plan->plan['targets']['protein']) }}" min="{{ $limits['protein'][0] }}" max="{{ $limits['protein'][1] }}" required><p class="small muted">Range {{ $limits['protein'][0] }} to {{ $limits['protein'][1] }}</p></div>
        <div class="f"><label for="t-f">Fat, % of calories</label><input id="t-f" type="number" name="fat_pct" value="{{ old('fat_pct', $plan->plan['targets']['fat_pct']) }}" min="{{ $limits['fat_pct'][0] }}" max="{{ $limits['fat_pct'][1] }}" required><p class="small muted">Range {{ $limits['fat_pct'][0] }} to {{ $limits['fat_pct'][1] }}%</p></div>
      </div>
      <p class="small" id="t-carbs" style="margin:0 0 .7rem"></p>
      <div class="row"><button class="btn">Save and rebuild my week</button>@if ($ov)<button class="btn ghost" form="tgtreset">Back to my survey numbers</button>@endif</div>
    </form>
    @if ($ov)<form id="tgtreset" method="post" action="{{ route('member.diet.targets.reset') }}">@csrf</form>@endif
    <script>(function(){var k=document.getElementById('t-k'),p=document.getElementById('t-p'),f=document.getElementById('t-f'),o=document.getElementById('t-carbs');if(!k)return;function u(){var c=(k.value-4*p.value-9*Math.round(k.value*f.value/100/9))/4;o.innerHTML='That gives about <b>'+Math.round(c)+' g</b> of carbs'+(c<{{ $limits['carbs_min'] }}?' <span style="color:#d4413a">(too low, the minimum is {{ $limits['carbs_min'] }} g)</span>':'')+'.';}[k,p,f].forEach(function(e){e.addEventListener('input',u)});u();})();</script>
    @endif
  </details>

  @include('partials.diet_plan', ['plan' => $plan->plan, 'day' => $day, 'swappable' => true, 'tabsUrl' => fn ($i) => route('member.diet', ['day' => $i]), 'ui' => $ui])

  <div class="card mb" id="checkin"><h2>Weekly check-in</h2>
    @if ($due)
      <p class="small muted" style="margin-top:0">Tell us your weight and how the week went. If your weight is not moving at the pace your goal needs, we adjust your calories a little.</p>
      <form method="post" action="{{ route('member.diet.checkin') }}">@csrf
        <div class="f"><label for="c-w">Your weight today, in kg</label><input id="c-w" type="number" step="0.1" name="weight" value="{{ old('weight', $lastWeight) }}" required min="25" max="300" style="max-width:200px"></div>
        <label>How well did you follow the plan?</label>
        <div class="seg" style="margin-bottom:.8rem">@foreach (['most' => 'Most of it', 'some' => 'Some of it', 'little' => 'Hardly'] as $v => $l)<label><input type="radio" name="followed" value="{{ $v }}" @checked(old('followed', 'most') === $v)><span>{{ $l }}</span></label>@endforeach</div>
        <div class="grid g2"><div><label>Hunger</label><div class="seg" style="margin-bottom:.8rem">@foreach (['low' => 'Not hungry', 'ok' => 'Fine', 'high' => 'Very hungry'] as $v => $l)<label><input type="radio" name="hunger" value="{{ $v }}" @checked(old('hunger', 'ok') === $v)><span>{{ $l }}</span></label>@endforeach</div></div>
          <div><label>Energy</label><div class="seg" style="margin-bottom:.8rem">@foreach (['low' => 'Low', 'ok' => 'Fine', 'high' => 'High'] as $v => $l)<label><input type="radio" name="energy" value="{{ $v }}" @checked(old('energy', 'ok') === $v)><span>{{ $l }}</span></label>@endforeach</div></div></div>
        <div class="f"><label for="c-n">Anything to tell your trainer? (optional)</label><input id="c-n" type="text" name="note" maxlength="300" value="{{ old('note') }}"></div>
        <button class="btn">Save check-in</button>
      </form>
    @else
      <p class="small" style="margin-top:0">Your next check-in is on <b>{{ $dueOn->format('l, d M') }}</b>.</p>
    @endif
    @if ($checkins->count())<div class="mt"><h3 class="small" style="text-transform:uppercase;letter-spacing:.08em;color:var(--mut)">Recent</h3>
      @foreach ($checkins as $c)<div class="arow"><span class="adot"></span><span><b>{{ $c->week_of->format('d M') }} · {{ rtrim(rtrim(number_format($c->weight_kg, 1), '0'), '.') }} kg</b><small>{{ $c->message }}</small></span></div>@endforeach</div>@endif
  </div>
  <p class="disc">Plan made {{ $plan->created_at->format('d M Y') }}. The owner can also see your plan.</p>
  @pushOnce('scripts', 'diet-js')<script src="/js/diet.js"></script>@endPushOnce
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
