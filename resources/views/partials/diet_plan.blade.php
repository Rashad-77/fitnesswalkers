{{-- $plan (array from DietGenerator), $day (1-7), $swappable (bool), $tabsUrl (fn day => url), $priceDate --}}
@php $d = $plan['days'][$day - 1]; $t = $plan['targets']; @endphp
@foreach ($plan['warnings'] ?? [] as $w)<div class="alert warn">{{ $w }}</div>@endforeach
<div class="grid g4 mb">
  <div class="stat dark"><div class="n">{{ $t['kcal'] }}</div><div class="l">Calories a day (target)</div></div>
  <div class="stat"><div class="n">{{ $t['protein'] }} g</div><div class="l">Protein a day (target)</div></div>
  <div class="stat lime"><div class="n">{{ rs($plan['avg_day_cost']) }}</div><div class="l">Average cost a day</div></div>
  <div class="stat"><div class="n">{{ rs($plan['week_cost']) }}</div><div class="l">Cost for the week</div></div>
</div>
<div class="tabsbar">@for ($i = 1; $i <= 7; $i++)<a class="{{ $i === $day ? 'on' : '' }}" href="{{ $tabsUrl($i) }}">Day {{ $i }}</a>@endfor</div>
<div class="row between small mb"><span><strong>Day {{ $day }}</strong>: {{ $d['kcal'] }} kcal · {{ $d['protein'] }} g protein</span><strong>{{ rs($d['cost']) }}</strong></div>
@foreach ($d['meals'] as $mi => $meal)
  <div class="meal">
    <div class="h"><span>{{ $meal['name'] }}</span><span>{{ rs($meal['cost']) }}</span></div>
    <div class="small muted">{{ $meal['kcal'] }} kcal · {{ $meal['protein'] }} g protein</div>
    <ul class="list small">@foreach ($meal['items'] as $it)<li class="row between" style="padding:.25rem 0"><span>{{ $it['name'] }} <span class="muted">{{ $it['grams'] }} g</span></span><span class="muted">{{ rs($it['cost']) }}</span></li>@endforeach</ul>
    @if ($swappable)
      <form method="post" action="{{ route('member.diet.swap') }}">@csrf<input type="hidden" name="day" value="{{ $day - 1 }}"><input type="hidden" name="meal" value="{{ $mi }}"><button class="btn ghost sm">↻ Swap this meal</button></form>
    @endif
  </div>
@endforeach
<p class="disc">Prices are estimates{{ $priceDate ? ' (food prices last updated '.\Carbon\Carbon::parse($priceDate)->format('d M Y').')' : '' }} and change with the market. Calories and protein are guides, not medical advice. If you have a medical condition, are pregnant, or are under 18, follow your doctor's or the gym owner's advice instead.</p>
