@extends('layouts.admin')
@section('title', 'Dishes')
@section('content')
<div class="row between mb"><div><h1>Dishes</h1><p class="muted" style="margin:0">The meals members are given, such as rice and curry. Each is built from the Foods list, and the planner scales the amounts to fit a member's day.</p></div>
  <a class="btn" href="{{ route('admin.dishes.create') }}">+ Add a dish</a></div>
<div class="alert info">Calories and nutrients below are for one normal portion, worked out from the foods in the dish. Switch a dish off and it is never put into new plans.@if ($off) <b>{{ $off }}</b> {{ $off === 1 ? 'dish is' : 'dishes are' }} off now.@endif</div>
<form class="filters" method="get"><input type="search" name="q" value="{{ $q }}" placeholder="Search dishes">
  <select name="kind"><option value="">All kinds</option>@foreach (\App\Models\Dish::KINDS as $k => $v)<option value="{{ $k }}" @selected($kind === $k)>{{ $v }}</option>@endforeach</select><button class="btn dark">Filter</button></form>
<div class="card"><div class="tw"><table>
  <tr><th>Dish</th><th>Kind</th><th>Eaten at</th><th class="right">kcal</th><th class="right">Protein</th><th class="right">Carbs</th><th class="right">Fat</th><th>Notes</th><th class="center">On</th><th></th></tr>
  @forelse ($dishes as $d)
    <tr class="{{ $d->active ? '' : 'foodoff' }}"><td><b>{{ $d->name }}</b>{!! dummy_tag($d) !!}@if ($d->local_name)<div class="small muted">{{ $d->local_name }}</div>@endif</td>
      <td class="small">{{ \App\Models\Dish::KINDS[$d->kind] ?? $d->kind }}</td><td class="small">{{ implode(', ', $d->slotList()) }}</td>
      @if ($d->one)<td class="right">{{ $d->one['kcal'] }}</td><td class="right">{{ $d->one['protein'] }} g</td><td class="right">{{ $d->one['carbs'] }} g</td><td class="right">{{ $d->one['fat'] }} g</td>
      @else<td colspan="4" class="small muted">A food in this dish was deleted</td>@endif
      <td class="small muted">{{ implode(', ', $d->tagList()) }}{{ $d->needList() ? ' · needs '.implode(', ', $d->needList()) : '' }}</td>
      <td class="center"><form method="post" action="{{ route('admin.dishes.update', $d) }}">@csrf @method('PUT')<input type="hidden" name="toggle" value="1"><button class="switch {{ $d->active ? 'on' : '' }}" aria-label="{{ $d->active ? 'Switch off' : 'Switch on' }}"></button></form></td>
      <td class="right nowrap"><a class="btn ghost sm" href="{{ route('admin.dishes.edit', $d) }}">Edit</a>
        <form class="inline" method="post" action="{{ route('admin.dishes.destroy', $d) }}">@csrf @method('DELETE')<button class="btn ghost sm" data-confirm="Delete {{ $d->name }}?">Delete</button></form></td></tr>
  @empty <tr><td colspan="10" class="empty">No dishes match.</td></tr> @endforelse
</table></div></div>
@endsection
