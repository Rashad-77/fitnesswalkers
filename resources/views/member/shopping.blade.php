@extends('layouts.member')
@section('title', 'Shopping list')
@section('content')
@php $p = $plan->plan; @endphp
<a class="small" href="{{ route('member.diet') }}">‹ Back to plan</a>
<div class="row between"><h1>Shopping list</h1><button class="btn ghost sm noprint" onclick="window.print()">Print</button></div>
<p class="muted small">Everything for the 7 days. Amounts are the food as eaten (e.g. cooked rice).</p>
<div class="card"><table>
  <tr><th>Item</th><th class="right">Amount</th><th class="right">Est. cost</th></tr>
  @foreach ($p['shopping'] as $s)<tr><td>{{ $s['name'] }}</td><td class="right nowrap">{{ $s['grams'] >= 1000 ? round($s['grams'] / 1000, 2).' kg' : $s['grams'].' g' }}</td><td class="right">{{ rs($s['cost']) }}</td></tr>@endforeach
  <tr><td colspan="2" class="right"><strong>Week total</strong></td><td class="right"><strong>{{ rs($p['week_cost']) }}</strong></td></tr>
</table></div>
<p class="disc">Prices are estimates{{ $priceDate ? ' (last updated '.\Carbon\Carbon::parse($priceDate)->format('d M Y').')' : '' }}.</p>
@endsection
