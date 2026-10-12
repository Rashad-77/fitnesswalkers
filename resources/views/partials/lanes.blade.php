{{-- Running-track lane lines. The lead lane draws itself once on load. --}}
<svg class="{{ $class ?? 'lanes' }}" viewBox="0 0 900 560" aria-hidden="true" focusable="false">
  @foreach ([0, 1, 2, 3, 4, 5] as $i)
    <rect x="{{ 20 + $i * 46 }}" y="{{ 20 + $i * 46 }}" width="{{ 860 - $i * 92 }}" height="{{ 520 - $i * 92 }}" rx="{{ 260 - $i * 46 }}" @if ($i === 2) class="lead" @endif/>
  @endforeach
</svg>
