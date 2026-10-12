@extends('layouts.admin')
@section('title', 'Financial reports')
@section('content')
<div class="row between mb"><h1>Financial reports</h1>
  <form method="get" class="row"><input type="month" name="month" value="{{ $month }}" style="width:auto"><button class="btn dark">Show</button></form></div>
<div class="grid g3 mb">
  <div class="stat dark"><div class="n">{{ rs($income) }}</div><div class="l">Income in {{ $start->format('F Y') }}</div></div>
  <div class="stat"><div class="n">{{ rs($spent) }}</div><div class="l">Expenses</div></div>
  <div class="stat lime"><div class="n">{{ rs($income - $spent) }}</div><div class="l">Profit</div></div>
</div>
<div class="card mb"><h2>Last 6 months</h2>
  @include('partials.chart_bars', ['labels' => $labels, 'series' => [['name' => 'Income', 'color' => '#7cb82f', 'data' => $incSeries], ['name' => 'Expenses', 'color' => '#2c333c', 'data' => $expSeries]]])</div>
<div class="grid g3">
  <div class="card"><h2>Income by package</h2>@forelse ($byPlan as $k => $v)<div class="row between small"><span>{{ $k }}</span><strong>{{ rs($v) }}</strong></div>@empty<div class="empty">No income.</div>@endforelse</div>
  <div class="card"><h2>Income by method</h2>@forelse ($byMethod as $k => $v)<div class="row between small"><span>{{ ucfirst($k) }}</span><strong>{{ rs($v) }}</strong></div>@empty<div class="empty">No income.</div>@endforelse</div>
  <div class="card"><h2>Expenses by category</h2>@forelse ($byCategory as $k => $v)<div class="row between small"><span>{{ $k }}</span><strong>{{ rs($v) }}</strong></div>@empty<div class="empty">No expenses.</div>@endforelse</div>
</div>
@endsection
