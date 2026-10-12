{{-- A 7-day dish plan from DishPlanner. $plan (array), $day (1-7), $swappable (bool), $tabsUrl (fn day => url), $ui (array|null, for the Change panel) --}}
@php
  use App\Services\{DietEngine, DishPlanner};
  $di = $day - 1; $d = $plan['days'][$di]; $t = $plan['targets'];
  $names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; $long = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  $sched = $plan['schedule'][$di] ?? ['workout' => false];
  $water = $plan['water'] ?? ['litres' => 2.5, 'glasses' => 10];
  $fit = DishPlanner::fit(['kcal' => $d['kcal'], 'protein' => $d['protein'], 'carbs' => $d['carbs'], 'fat' => $d['fat']], $t);
  $glass = max(1, (int) round($water['glasses'] / max(1, count($d['meals']) + 1)));
  $macroLabel = ['kcal' => 'Calories', 'protein' => 'Protein', 'carbs' => 'Carbs', 'fat' => 'Fat']; $unit = ['kcal' => 'kcal', 'protein' => 'g', 'carbs' => 'g', 'fat' => 'g'];
  $problems = collect($fit)->filter(fn ($x, $k) => $k !== 'all' && $x['state'] !== 'ok')->map(fn ($x, $k) => $macroLabel[$k].' '.abs($x['diff']).' '.$unit[$k].($x['diff'] > 0 ? ' over' : ' under'))->values();
@endphp
@foreach ($plan['warnings'] ?? [] as $w)<div class="alert warn">{{ $w }}</div>@endforeach
@isset($plan['workout']['name'])
  <div class="wnote mb"><b>Built around your workout plan</b><span>{{ $plan['workout']['name'] }}: {{ $plan['workout']['days'] }} workout {{ $plan['workout']['days'] === 1 ? 'day' : 'days' }} a week, about {{ $plan['workout']['minutes'] }} minutes each. Workout days are marked below.</span></div>
@endisset

<div class="grid g4 mb tgt">
  <div class="stat dark"><div class="n">{{ number_format($t['kcal']) }}</div><div class="l">Calories a day</div></div>
  <div class="stat"><div class="n">{{ $t['protein'] }} g</div><div class="l">Protein</div></div>
  <div class="stat"><div class="n">{{ $t['carbs'] }} g</div><div class="l">Carbs</div></div>
  <div class="stat"><div class="n">{{ $t['fat'] }} g</div><div class="l">Fat</div></div>
</div>

<div class="tabsbar daytabs">@for ($i = 1; $i <= 7; $i++)<a class="{{ $i === $day ? 'on' : '' }}" href="{{ $tabsUrl($i) }}">{{ $names[$i - 1] }}@if (($plan['schedule'][$i - 1]['workout'] ?? false))<i class="wdot" title="Workout day"></i>@endif</a>@endfor</div>

<div class="card mb dayhead">
  <div class="row between"><div><h2 style="margin:0">{{ $long[$di] }}</h2>
    <span class="muted small">@if ($sched['workout']) <span class="b active">Workout day</span> {{ isset($sched['plan_day']) ? 'Your plan Day '.$sched['plan_day'] : '' }}{{ ! empty($sched['focus']) ? ' · '.ucfirst($sched['focus']) : '' }} @else <span class="b none">Rest day</span> Let your body recover @endif</span></div>
    <div class="right"><span class="verdict {{ $fit['all'] }}">@if ($fit['all'] === 'ok') &#10003; On target @elseif ($fit['all'] === 'near') &#9888; Close to target @else &#10007; Off target @endif</span></div></div>
  <div class="fitgrid mt">
    @foreach ($macroLabel as $k => $label)
      @php $x = $fit[$k]; @endphp
      <div class="fitrow {{ $x['state'] }}"><div class="fl"><span>{{ $label }}</span><b>{{ number_format($x['got']) }} <small>of {{ number_format($x['target']) }} {{ $unit[$k] }}</small></b></div>
        <div class="fbar"><i style="width:{{ min(100, $x['pct']) }}%"></i><u style="left:{{ min(100, round(100 / max(100, $x['pct']) * 100)) }}%"></u></div></div>
    @endforeach
  </div>
  @if ($problems->isNotEmpty())<p class="small" style="margin:.7rem 0 0">{{ $problems->implode(', ') }}. @if ($swappable) Change a dish or portion below to bring it back. @endif</p>
  @else<p class="small muted" style="margin:.7rem 0 0">All four are close to your targets.@if ($sched['workout']) Eat your snack about an hour before training and sip water as you train.@endif</p>@endif
</div>

@foreach ($d['meals'] as $mi => $meal)
  @php $isSnack = str_starts_with($meal['slot'], 'snack'); $mains = collect($meal['items'])->where('role', 'main'); $dishes = collect($meal['items'])->where('role', '!=', 'main'); @endphp
  <div class="meal dmeal" data-mi="{{ $mi }}">
    <div class="h"><span>{{ $meal['name'] }}</span><span class="kc">{{ $meal['kcal'] }} kcal</span></div>
    <div class="dname"><b>{{ $meal['dish'] }}</b>@if ($meal['local'])<span class="muted small"> · {{ $meal['local'] }}</span>@endif</div>
    <div class="chips2">@foreach ($meal['tags'] as $tg)<span class="b {{ $tg === 'quick' ? 'active' : 'none' }}">{{ ['quick' => 'Quick', 'batch' => 'Cook once, eat twice', 'weekend' => 'Weekend'][$tg] ?? $tg }}</span>@endforeach<span class="b none">about {{ $meal['prep'] }} min</span>
      <span class="b none">P {{ round($meal['protein']) }} g · C {{ round($meal['carbs']) }} g · F {{ round($meal['fat']) }} g</span></div>

    @if ($mains->count())<div class="course"><div class="ch">Main course</div><ul class="list small">@foreach ($mains as $it)<li><span class="it"><b>{{ $it['qty'] }}</b> {{ $it['name'] }}@if ($it['g'] && ! str_ends_with($it['qty'], ' g')) <span class="muted">(about {{ $it['g'] }} g)</span>@endif</span><span class="muted nowrap">{{ $it['kcal'] }} kcal</span></li>@endforeach</ul></div>@endif
    @if ($dishes->count())<div class="course"><div class="ch">{{ $isSnack ? 'Snack' : 'Dishes' }}</div><ul class="list small">@foreach ($dishes as $it)<li><span class="it"><b>{{ $it['qty'] }}</b> {{ $it['name'] }}@if ($it['g'] && ! str_ends_with($it['qty'], ' g')) <span class="muted">(about {{ $it['g'] }} g)</span>@endif</span><span class="muted nowrap">{{ $it['kcal'] }} kcal</span></li>@endforeach</ul></div>@endif
    @if (! empty($meal['dessert']))
      <div class="course"><div class="ch">Dessert</div><ul class="list small">@foreach ($meal['dessert']['items'] as $it)<li><span class="it"><b>{{ $it['qty'] }}</b> {{ $it['name'] }}</span><span class="muted nowrap">{{ $it['kcal'] }} kcal</span></li>@endforeach</ul>
        @if ($swappable)<div class="row" style="gap:.4rem"><button type="button" class="chg" data-open="dessert" data-mi="{{ $mi }}">Change dessert</button>
          <form method="post" action="{{ route('member.diet.dish') }}" class="inline">@csrf<input type="hidden" name="day" value="{{ $di }}"><input type="hidden" name="meal" value="{{ $mi }}"><input type="hidden" name="drop" value="1"><button class="chg">No dessert</button></form></div>@endif</div>
    @endif
    <div class="course water"><div class="ch">Water</div><div class="small">{{ $glass }} {{ $glass === 1 ? 'glass' : 'glasses' }} (about {{ $glass * 250 }} ml) with this {{ $isSnack ? 'snack' : 'meal' }}</div></div>
    @if ($meal['method'])<details class="howto"><summary class="small">How to make it</summary><p class="small muted" style="margin:.4rem 0 0">{{ $meal['method'] }}</p></details>@endif

    @if ($swappable)
      <div class="mactions">
        <span class="portion"><form method="post" action="{{ route('member.diet.dish') }}" class="inline">@csrf<input type="hidden" name="day" value="{{ $di }}"><input type="hidden" name="meal" value="{{ $mi }}"><input type="hidden" name="scale" value="{{ collect(DishPlanner::SCALES)->filter(fn ($s) => $s < $meal['scale'])->last() ?? $meal['scale'] }}"><button class="chg" aria-label="Smaller portion" @disabled($meal['scale'] <= 0.5)>&minus;</button></form>
          <b>{{ rtrim(rtrim(number_format($meal['scale'], 2), '0'), '.') }}&times; portion</b>
          <form method="post" action="{{ route('member.diet.dish') }}" class="inline">@csrf<input type="hidden" name="day" value="{{ $di }}"><input type="hidden" name="meal" value="{{ $mi }}"><input type="hidden" name="scale" value="{{ collect(DishPlanner::SCALES)->first(fn ($s) => $s > $meal['scale']) ?? $meal['scale'] }}"><button class="chg" aria-label="Bigger portion" @disabled($meal['scale'] >= 2.5)>+</button></form></span>
        <button type="button" class="chg" data-open="main" data-mi="{{ $mi }}">Change dish</button>
        <form method="post" action="{{ route('member.diet.reroll') }}" class="inline">@csrf<input type="hidden" name="day" value="{{ $di }}"><input type="hidden" name="meal" value="{{ $mi }}"><button class="chg">&#8635; Surprise me</button></form>
        @if (empty($meal['dessert']) && ! $isSnack)<button type="button" class="chg" data-open="dessert" data-mi="{{ $mi }}">+ Add dessert</button>@endif
      </div>
      <div class="panelslot" data-slot="{{ $mi }}"></div>
    @endif
  </div>
@endforeach
@if ($swappable && $ui)<script type="application/json" id="dietui">{!! json_encode($ui + ['day' => $di, 'url' => route('member.diet.dish')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
  <p class="small muted">Tap <b>Change dish</b> to see every option and how it moves your day against your targets. The best fit is marked.</p>@endif

<div class="card mb mt"><h2>Food groups today and the guideline</h2>
  <div class="tw"><table><tr><th>Food group</th><th class="right">Servings today</th><th class="right">Guideline range</th></tr>
    @foreach (DietEngine::GROUPS as $g)<tr><td>{{ \App\Models\Food::SHORT[$g] }}</td><td class="right"><b>{{ $d['groups'][$g] ?? 0 }}</b></td><td class="right muted">{{ DietEngine::RANGE[$g][0] }} to {{ DietEngine::RANGE[$g][1] }}</td></tr>@endforeach
  </table></div><p class="small muted" style="margin:.5rem 0 0">Dishes are built from the foods in Sri Lanka's dietary guidelines. The ranges are a guide, and a day outside one is fine if your targets are met.</p></div>

<div class="card mb"><h2>Your week in calories</h2>
  @include('partials.chart_bars', ['unit' => 'kcal', 'labels' => $names, 'series' => [['name' => 'Calories', 'color' => '#3b7a17', 'data' => array_map(fn ($x) => (float) $x['kcal'], $plan['days'])]]])
  <p class="small muted" style="margin:.4rem 0 0">Your target is {{ number_format($t['kcal']) }} calories a day. Average planned: {{ number_format($plan['avg_kcal']) }}.</p></div>

@if (! empty($plan['tips']) || ! empty($plan['notes']))
<div class="card mb"><h2>Good to know</h2><ul class="steps">@foreach (array_merge($plan['notes'] ?? [], $plan['tips'] ?? []) as $n)<li>{{ $n }}</li>@endforeach</ul></div>
@endif
<p class="disc">Calories, protein, carbs and fat are estimates from typical values, not exact. This is a general guide made from the Food Based Dietary Guidelines for Sri Lanka, not medical advice. If you have a medical condition, talk to a doctor or dietitian.</p>
