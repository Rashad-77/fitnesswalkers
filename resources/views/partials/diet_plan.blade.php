{{-- The "Meals" view of a dish plan. $plan (array), $day (1-7), $swappable (bool), $tabsUrl (fn day => url), $ui (array|null, feeds the Change sheet) --}}
@php
  use App\Services\{DietEngine, DishPlanner};
  $di = $day - 1; $d = $plan['days'][$di]; $t = $plan['targets'];
  $monday = now()->startOfWeek();
  $long = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  $sched = $plan['schedule'][$di] ?? ['workout' => false];
  $water = $plan['water'] ?? ['litres' => 2.5, 'glasses' => 10];
  $unit = ['kcal' => 'kcal', 'protein' => 'g', 'carbs' => 'g', 'fat' => 'g']; $label = ['kcal' => 'Calories', 'protein' => 'Protein', 'carbs' => 'Carbs', 'fat' => 'Fat'];
  $fits = collect($plan['days'])->map(fn ($x) => DishPlanner::fit(['kcal' => $x['kcal'], 'protein' => $x['protein'], 'carbs' => $x['carbs'], 'fat' => $x['fat']], $t));
  $fit = $fits[$di];
  $problems = collect($fit)->filter(fn ($x, $k) => $k !== 'all' && $x['state'] !== 'ok')->map(fn ($x, $k) => $label[$k].' '.abs($x['diff']).' '.$unit[$k].($x['diff'] > 0 ? ' over' : ' under'))->values();
  $pct = min(100, round($d['kcal'] / max(1, $t['kcal']) * 100));
  $rings = ['ok' => 'ok', 'near' => 'near', 'off' => 'off'];
@endphp
@foreach ($plan['warnings'] ?? [] as $w)<div class="alert warn">{{ $w }}</div>@endforeach

<div class="mp-layout">
<div class="mp-aside">
{{-- the week --}}
<nav class="mp-week" aria-label="Day of the week">
  @for ($i = 1; $i <= 7; $i++)
    @php $dt = $monday->copy()->addDays($i - 1); $wk = $plan['schedule'][$i - 1]['workout'] ?? false; @endphp
    <a class="mp-day {{ $i === $day ? 'on' : '' }} {{ $dt->isToday() ? 'today' : '' }}" href="{{ $tabsUrl($i) }}" @if ($i === $day) aria-current="page" @endif>
      <small>{{ $dt->format('D') }}</small><b>{{ $dt->format('j') }}</b>
      <i class="mp-dot {{ $fits[$i - 1]['all'] }}" title="{{ ['ok' => 'On target', 'near' => 'Close to target', 'off' => 'Off target'][$fits[$i - 1]['all']] }}"></i>
      @if ($wk)<span class="mp-wk" title="Workout day">@include('partials.i', ['n' => 'dumbbell'])</span>@endif
    </a>
  @endfor
</nav>

{{-- how the day adds up --}}
<section class="mp-sum" aria-label="{{ $long[$di] }} against your targets">
  <div class="mp-ring {{ $fit['all'] }}">
    <svg viewBox="0 0 36 36" aria-hidden="true"><circle class="bg" cx="18" cy="18" r="15.915"/><circle class="fg" cx="18" cy="18" r="15.915" stroke-dasharray="{{ $pct }} 100"/></svg>
    <div><b>{{ number_format($d['kcal']) }}</b><span>of {{ number_format($t['kcal']) }} kcal</span></div>
  </div>
  <div class="mp-bars">
    @foreach (['protein', 'carbs', 'fat'] as $k)
      @php $x = $fit[$k]; @endphp
      <div class="mp-bar {{ $x['state'] }}"><span>{{ $label[$k] }}</span><div class="tr"><i style="width:{{ min(100, $x['pct']) }}%"></i></div><b>{{ number_format($x['got']) }}<small>/{{ number_format($x['target']) }} g</small></b></div>
    @endforeach
  </div>
  <div class="mp-line">
    <span class="mp-verdict {{ $fit['all'] }}">@if ($fit['all'] === 'ok') &#10003; On target @elseif ($fit['all'] === 'near') &#9888; Close @else &#10007; Off target @endif</span>
    <span class="mp-daytype">{{ $long[$di] }} · @if ($sched['workout'])<b>Workout day</b>{{ ! empty($sched['focus']) ? ', '.strtolower($sched['focus']) : '' }}@else Rest day @endif</span>
  </div>
  @if ($problems->isNotEmpty())<p class="mp-hint">{{ $problems->implode(', ') }}.@if ($swappable) Change a dish or portion to bring it back.@endif</p>@endif
</section>

</div>
<div class="mp-main">
{{-- the meals --}}
<div class="mp-meals">
@foreach ($d['meals'] as $mi => $meal)
  @php
    $isSnack = str_starts_with($meal['slot'], 'snack');
    $groups = [['Main course', collect($meal['items'])->where('role', 'main')], [$isSnack ? 'Snack' : 'Dishes', collect($meal['items'])->where('role', '!=', 'main')], ['Dessert', collect($meal['dessert']['items'] ?? [])]];
    $tagName = ['quick' => 'Quick', 'batch' => 'Cook once, eat twice', 'weekend' => 'Weekend'];
  @endphp
  <article class="mp-meal" data-mi="{{ $mi }}">
    <header><span class="mp-slot">{{ $meal['name'] }}</span><span class="mp-kcal">{{ $meal['kcal'] }} kcal</span></header>
    <h3>{{ $meal['dish'] }}@if ($meal['local'])<small>{{ $meal['local'] }}</small>@endif</h3>
    <p class="mp-meta"><span>{{ $meal['prep'] }} min</span><span>P {{ round($meal['protein']) }}</span><span>C {{ round($meal['carbs']) }}</span><span>F {{ round($meal['fat']) }}</span>@foreach ($meal['tags'] as $tg)<span class="tag">{{ $tagName[$tg] ?? $tg }}</span>@endforeach</p>

    <ul class="mp-items">
      @foreach ($groups as [$gname, $rows])
        @if ($rows->count())
          <li class="sub">{{ $gname }}</li>
          @foreach ($rows as $it)<li><span class="n">{{ $it['name'] }}</span><span class="a"><b>{{ $it['qty'] }}</b>@if ($it['g'] && ! str_ends_with($it['qty'], ' g')) <em>{{ $it['g'] }} g</em>@endif</span></li>@endforeach
        @endif
      @endforeach
    </ul>
    @if ($meal['method'])<details class="mp-how"><summary>How to make it</summary><p>{{ $meal['method'] }}</p></details>@endif

    @if ($swappable)
      <footer class="mp-act">
        <div class="mp-step" role="group" aria-label="Portion size">
          <form method="post" action="{{ route('member.diet.dish') }}">@csrf<input type="hidden" name="day" value="{{ $di }}"><input type="hidden" name="meal" value="{{ $mi }}"><input type="hidden" name="scale" value="{{ collect(DishPlanner::SCALES)->filter(fn ($s) => $s < $meal['scale'])->last() ?? $meal['scale'] }}"><button aria-label="Smaller portion" @disabled($meal['scale'] <= 0.5)>&minus;</button></form>
          <span>{{ rtrim(rtrim(number_format($meal['scale'], 2), '0'), '.') }}&times;</span>
          <form method="post" action="{{ route('member.diet.dish') }}">@csrf<input type="hidden" name="day" value="{{ $di }}"><input type="hidden" name="meal" value="{{ $mi }}"><input type="hidden" name="scale" value="{{ collect(DishPlanner::SCALES)->first(fn ($s) => $s > $meal['scale']) ?? $meal['scale'] }}"><button aria-label="Bigger portion" @disabled($meal['scale'] >= 2.5)>+</button></form>
        </div>
        <button type="button" class="btn lime sm" data-open="main" data-mi="{{ $mi }}">Change dish</button>
        <details class="mp-more"><summary aria-label="More options">&middot;&middot;&middot;</summary>
          <div class="menu">
            <form method="post" action="{{ route('member.diet.reroll') }}">@csrf<input type="hidden" name="day" value="{{ $di }}"><input type="hidden" name="meal" value="{{ $mi }}"><button>&#8635; Surprise me</button></form>
            @if (! $isSnack)
              <button type="button" data-open="dessert" data-mi="{{ $mi }}">{{ empty($meal['dessert']) ? '+ Add dessert' : 'Change dessert' }}</button>
              @if (! empty($meal['dessert']))<form method="post" action="{{ route('member.diet.dish') }}">@csrf<input type="hidden" name="day" value="{{ $di }}"><input type="hidden" name="meal" value="{{ $mi }}"><input type="hidden" name="drop" value="1"><button>Remove dessert</button></form>@endif
            @endif
          </div>
        </details>
      </footer>
    @endif
  </article>
@endforeach
</div>

<section class="mp-water" aria-label="Water">
  <div><b>Water</b><span>{{ $water['glasses'] }} glasses · about {{ $water['litres'] }} L today</span></div>
  <p aria-hidden="true">@for ($g = 0; $g < $water['glasses']; $g++)<i></i>@endfor</p>
</section>
</div>
</div>

@if ($swappable && $ui)
  <script type="application/json" id="dietui">{!! json_encode($ui + ['day' => $di, 'url' => route('member.diet.dish')], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
  <div class="sheet" id="sheet" hidden role="dialog" aria-modal="true" aria-labelledby="sheet-title"><div class="sheet-bg" data-cancel></div>
    <div class="sheet-body"><div class="sheet-grab"></div><h3 id="sheet-title">Change dish</h3><div id="sheet-slot"></div></div></div>
@endif

<details class="mp-fold"><summary>Food groups today</summary>
  <div class="tw"><table><tr><th>Food group</th><th class="right">Servings</th><th class="right">Guideline</th></tr>
    @foreach (DietEngine::GROUPS as $g)<tr><td>{{ \App\Models\Food::SHORT[$g] }}</td><td class="right"><b>{{ $d['groups'][$g] ?? 0 }}</b></td><td class="right muted">{{ DietEngine::RANGE[$g][0] }} to {{ DietEngine::RANGE[$g][1] }}</td></tr>@endforeach
  </table></div><p class="small muted">Dishes are built from the foods in Sri Lanka's dietary guidelines. A day outside a range is fine if your targets are met.</p></details>
@if (! empty($plan['tips']) || ! empty($plan['notes']))
<details class="mp-fold"><summary>Good to know</summary><ul class="steps">@foreach (array_merge($plan['notes'] ?? [], $plan['tips'] ?? []) as $n)<li>{{ $n }}</li>@endforeach</ul></details>
@endif
<p class="disc">Calories, protein, carbs and fat are estimates from typical values, not exact. This is a general guide made from the Food Based Dietary Guidelines for Sri Lanka, not medical advice. If you have a medical condition, talk to a doctor or dietitian.</p>
