@extends('layouts.member')
@section('title', 'Shopping list')
@section('content')
@php
  $p = $plan->plan; $by = collect($p['shopping'])->groupBy('grp');
  $text = "Shopping list for my week:\n".collect($p['shopping'])->map(fn ($s) => '- '.$s['qty'].' '.$s['name'])->implode("\n");
@endphp
<a class="small" href="{{ route('member.diet') }}">&lsaquo; Back to plan</a>
<div class="row between"><h1>Shopping list</h1><span class="row noprint"><button class="btn ghost sm" onclick="window.print()">Print</button><a class="btn sm" target="_blank" rel="noopener" href="https://wa.me/?text={{ rawurlencode($text) }}">Send on WhatsApp</a></span></div>
<p class="muted small">Everything for the 7 days, as eaten (for example cooked rice). Tick things off as you buy them.</p>
@foreach (\App\Services\DietEngine::GROUPS as $g)@if ($by->has($g))
  <div class="card mb"><h2>{{ \App\Models\Food::SHORT[$g] }}</h2>
    <ul class="list shop">@foreach ($by[$g] as $s)<li><label class="shoprow"><input type="checkbox" data-shop="{{ $s['food_id'] }}"><span><b>{{ $s['qty'] }}</b> {{ $s['name'] }}@if ($s['grams']) <span class="muted small">(about {{ $s['grams'] >= 1000 ? round($s['grams'] / 1000, 2).' kg' : $s['grams'].' g' }})</span>@endif</span></label></li>@endforeach</ul></div>
@endif @endforeach
<script>
(function () {
  var key = 'shop-{{ $plan->id }}', saved = {};
  try { saved = JSON.parse(localStorage.getItem(key) || '{}'); } catch (e) {}
  document.querySelectorAll('[data-shop]').forEach(function (c) {
    c.checked = !!saved[c.dataset.shop];
    c.addEventListener('change', function () { saved[c.dataset.shop] = c.checked; try { localStorage.setItem(key, JSON.stringify(saved)); } catch (e) {} });
  });
})();
</script>
@endsection
