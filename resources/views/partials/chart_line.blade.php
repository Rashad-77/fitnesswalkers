{{-- $points: [label => value], optional $unit --}}
@php
  $vals = array_values($points); $labs = array_keys($points); $n = count($vals);
  $W = 420; $H = 170; $pad = 28;
  $min = $n ? min($vals) : 0; $max = $n ? max($vals) : 1;
  if ($max == $min) { $max += 1; $min -= 1; }
  $x = fn ($i) => $n > 1 ? $pad + $i * (($W - $pad * 2) / ($n - 1)) : $W / 2;
  $y = fn ($v) => $H - 18 - (($v - $min) / ($max - $min)) * ($H - 40);
  $path = collect($vals)->map(fn ($v, $i) => ($i ? 'L' : 'M').round($x($i), 1).' '.round($y($v), 1))->implode(' ');
@endphp
@if ($n < 2)
  <div class="empty small">Log at least two entries to see a chart.</div>
@else
<svg class="chart" viewBox="0 0 {{ $W }} {{ $H + 8 }}" role="img" aria-label="Line chart">
  <line x1="{{ $pad }}" x2="{{ $W - $pad }}" y1="{{ $H - 18 }}" y2="{{ $H - 18 }}" stroke="#e3e6df"/>
  <path d="{{ $path }}" fill="none" stroke="#7cb82f" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/>
  @foreach ($vals as $i => $v)
    <circle cx="{{ $x($i) }}" cy="{{ $y($v) }}" r="3.5" fill="#14171c"><title>{{ $labs[$i] }}: {{ $v }}{{ $unit ?? '' }}</title></circle>
  @endforeach
  <text x="{{ $pad }}" y="{{ $H - 4 }}">{{ $labs[0] }}</text>
  <text x="{{ $W - $pad }}" y="{{ $H - 4 }}" text-anchor="end">{{ $labs[$n - 1] }}</text>
  <text x="{{ $pad }}" y="12">{{ round(max($vals), 1) }}{{ $unit ?? '' }}</text>
  <text x="{{ $pad }}" y="{{ $H - 22 }}">{{ round(min($vals), 1) }}{{ $unit ?? '' }}</text>
</svg>
@endif
