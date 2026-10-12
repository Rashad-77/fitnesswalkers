{{-- A 7-day plan from DietEngine. $plan (array), $day (1-7), $swappable (bool), $tabsUrl (fn day => url), $alts (array|null, for the Change menu) --}}
@php
  use App\Services\DietEngine;
  $di = $day - 1; $d = $plan['days'][$di]; $t = $plan['targets'];
  $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; $long = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  $sched = $plan['schedule'][$di] ?? ['workout' => false];
  $water = $plan['water'] ?? ['litres' => 2.5, 'glasses' => 10];
  $pct = $t['kcal'] > 0 ? min(130, round($d['kcal'] / $t['kcal'] * 100)) : 0;
  $state = $d['kcal'] > $t['kcal'] * 1.05 ? ['over', 'A little over target'] : ($d['kcal'] < $t['kcal'] * 0.93 ? ['under', 'A little under target'] : ['ok', 'On target']);
  $course = fn ($grp) => $grp === 'cereal' ? 'main' : ($grp === 'fruit' ? 'dessert' : 'dishes');
  $heads = ['main' => 'Main course', 'dishes' => 'Dishes', 'dessert' => 'Dessert'];
  $glass = max(1, (int) round($water['glasses'] / max(1, count($d['meals']) + 1)));
@endphp
@foreach ($plan['warnings'] ?? [] as $w)<div class="alert warn">{{ $w }}</div>@endforeach
@isset($plan['workout']['name'])
  <div class="wnote mb"><b>Built around your workout plan</b><span>{{ $plan['workout']['name'] }}: {{ $plan['workout']['days'] }} workout {{ $plan['workout']['days'] === 1 ? 'day' : 'days' }} a week, about {{ $plan['workout']['minutes'] }} minutes each. Workout days are marked below.</span></div>
@endisset

<div class="grid g4 mb">
  <div class="stat dark"><div class="n">{{ number_format($t['kcal']) }}</div><div class="l">Calorie target a day</div></div>
  <div class="stat"><div class="n">{{ $t['protein'] }} g</div><div class="l">Protein a day (about)</div></div>
  <div class="stat lime"><div class="n">{{ $water['litres'] }} L</div><div class="l">Water a day · {{ $water['glasses'] }} glasses</div></div>
  <div class="stat"><div class="n">{{ $plan['meals_per_day'] }}</div><div class="l">Meals a day</div></div>
</div>

<div class="tabsbar daytabs">@for ($i = 1; $i <= 7; $i++)<a class="{{ $i === $day ? 'on' : '' }}" href="{{ $tabsUrl($i) }}">{{ $names[$i - 1] }}@if (($plan['schedule'][$i - 1]['workout'] ?? false))<i class="wdot" title="Workout day"></i>@endif</a>@endfor</div>

<div class="card mb dayhead">
  <div class="row between"><div><h2 style="margin:0">{{ $long[$di] }}</h2>
    <span class="muted small">@if ($sched['workout']) <span class="b active">Workout day</span> {{ isset($sched['plan_day']) ? 'Your plan Day '.$sched['plan_day'] : '' }}{{ ! empty($sched['focus']) ? ' · '.ucfirst($sched['focus']) : '' }} @else <span class="b none">Rest day</span> Let your body recover @endif</span></div>
    <div class="right"><div class="big">{{ number_format($d['kcal']) }} <span class="small muted">of {{ number_format($t['kcal']) }} kcal</span></div><span class="b {{ $state[0] === 'ok' ? 'active' : 'pending' }}">{{ $state[1] }}</span></div></div>
  <div class="bar mt kbar {{ $state[0] }}"><i style="width:{{ min(100, $pct) }}%"></i></div>
  <div class="small muted" style="margin-top:.4rem">{{ $d['protein'] }} g protein today @if ($sched['workout']) · eat your snack about an hour before training and sip water as you train @endif</div>
</div>

<div @if ($swappable) id="meals" data-item-url="{{ route('member.diet.item') }}" data-day="{{ $di }}" data-daykcal="{{ $d['kcal'] }}" data-target="{{ $t['kcal'] }}" @endif>
@foreach ($d['meals'] as $mi => $meal)
  @php
    $isSnack = str_starts_with($meal['slot'], 'snack'); $type = $isSnack ? 'snack' : ($meal['slot'] === 'breakfast' ? 'breakfast' : 'main');
    $by = ['main' => [], 'dishes' => [], 'dessert' => [], 'snack' => []];
    foreach ($meal['items'] as $ii => $it) $by[$isSnack ? 'snack' : $course($it['grp'])][] = [$ii, $it];
  @endphp
  <div class="meal">
    <div class="h"><span>{{ $meal['name'] }}</span><span class="kc">{{ $meal['kcal'] }} kcal</span></div>
    <div class="small muted">{{ $meal['protein'] }} g protein @if (! empty($meal['edited'])) · changed by you @endif</div>
    @foreach (($isSnack ? ['snack' => 'Snack'] : $heads) as $key => $head)
      @if ($by[$key])
        <div class="course"><div class="ch">{{ $head }}</div>
          <ul class="list small">
            @foreach ($by[$key] as [$ii, $it])
              <li><span class="it"><b>{{ $it['qty'] }}</b> {{ $it['name'] }}@if (! empty($it['local'])) <span class="muted">({{ $it['local'] }})</span>@endif</span>
                <span class="muted nowrap">{{ $it['kcal'] }} kcal
                  @if ($swappable)<button type="button" class="chg" data-grp="{{ $it['grp'] }}" data-sub="{{ $it['sub'] }}" data-food="{{ $it['food_id'] }}" data-type="{{ $type }}" data-meal="{{ $mi }}" data-item="{{ $ii }}" data-name="{{ $it['name'] }}" data-kcal="{{ $it['kcal'] }}" data-servings="{{ $it['servings'] }}">Change</button>@endif</span></li>
            @endforeach
          </ul></div>
      @endif
    @endforeach
    <div class="course water"><div class="ch">Water</div><div class="small">{{ $glass }} {{ $glass === 1 ? 'glass' : 'glasses' }} (about {{ $glass * 250 }} ml) with this {{ $isSnack ? 'snack' : 'meal' }}</div></div>
    @if ($swappable)
      <form method="post" action="{{ route('member.diet.swap') }}" style="margin-top:.6rem">@csrf<input type="hidden" name="day" value="{{ $di }}"><input type="hidden" name="meal" value="{{ $mi }}"><button class="btn ghost sm">&#8635; Swap this whole meal</button></form>
    @endif
  </div>
@endforeach
</div>
@if ($swappable)<script type="application/json" id="alts">{!! json_encode($alts ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
  <p class="small muted">Tap <b>Change</b> on any food. Each choice shows how it moves your day's calories, and the best fit for your target is marked.</p>@endif

<div class="card mb mt"><h2>Servings today and the guideline</h2>
  <div class="tw"><table><tr><th>Food group</th><th class="right">Today</th><th class="right">Guideline range</th></tr>
    @foreach (DietEngine::GROUPS as $g)@if (($d['groups'][$g] ?? 0) > 0 || ($plan['servings'][$g] ?? 0) > 0)
      <tr><td>{{ \App\Models\Food::SHORT[$g] }}</td><td class="right"><b>{{ $d['groups'][$g] ?? 0 }}</b></td><td class="right muted">{{ DietEngine::RANGE[$g][0] }} to {{ DietEngine::RANGE[$g][1] }}</td></tr>
    @endif @endforeach
  </table></div></div>

<div class="card mb"><h2>Your week in calories</h2>
  @include('partials.chart_bars', ['labels' => $names, 'series' => [['name' => 'Calories', 'color' => '#3b7a17', 'data' => array_map(fn ($x) => (float) $x['kcal'], $plan['days'])]]])
  <p class="small muted" style="margin:.4rem 0 0">Your target is {{ number_format($t['kcal']) }} calories a day. Average planned: {{ number_format($plan['avg_kcal']) }}.</p></div>

@if (! empty($plan['tips']) || ! empty($plan['notes']))
<div class="card mb"><h2>Good to know</h2><ul class="steps">@foreach (array_merge($plan['notes'] ?? [], $plan['tips'] ?? []) as $n)<li>{{ $n }}</li>@endforeach</ul></div>
@endif
<p class="disc">Calories and protein are estimates from typical values, not exact. This is a general guide made from the Food Based Dietary Guidelines for Sri Lanka, not medical advice. If you have a medical condition, talk to a doctor or dietitian.</p>
