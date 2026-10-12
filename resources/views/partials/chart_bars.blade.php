{{-- $labels: string[], $series: [ ['name'=>..,'color'=>..,'data'=>float[]], ... ] --}}
@php
  $max = max(1, collect($series)->flatMap(fn ($s) => $s['data'])->max());
  $n = count($labels); $k = count($series);
  $W = 420; $H = 180; $pad = 24; $bw = ($W - $pad * 2) / max(1, $n);
  $gw = $bw * .78; $w1 = $gw / $k;
@endphp
<svg class="chart" viewBox="0 0 {{ $W }} {{ $H + 22 }}" role="img" aria-label="Bar chart">
  @foreach ([0, .5, 1] as $g)
    <line x1="{{ $pad }}" x2="{{ $W - $pad }}" y1="{{ $H - $g * ($H - 10) }}" y2="{{ $H - $g * ($H - 10) }}" stroke="#e3e6df"/>
  @endforeach
  @foreach ($labels as $i => $lab)
    @foreach ($series as $j => $s)
      @php $h = ($s['data'][$i] / $max) * ($H - 10); @endphp
      <rect x="{{ $pad + $i * $bw + ($bw - $gw) / 2 + $j * $w1 }}" y="{{ $H - $h }}" width="{{ max(2, $w1 - 2) }}" height="{{ $h }}" rx="3" fill="{{ $s['color'] }}"><title>{{ $s['name'] }} {{ $lab }}: {{ rs($s['data'][$i]) }}</title></rect>
    @endforeach
    <text x="{{ $pad + $i * $bw + $bw / 2 }}" y="{{ $H + 14 }}" text-anchor="middle">{{ $lab }}</text>
  @endforeach
</svg>
<div class="small muted row">@foreach ($series as $s)<span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:{{ $s['color'] }}"></span> {{ $s['name'] }}</span>@endforeach <span>(max {{ rs($max) }})</span></div>
