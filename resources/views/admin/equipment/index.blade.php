@extends('layouts.admin')
@section('title', 'Equipment')
@section('content')
<h1>Equipment</h1>
<p class="muted">Tell the app what the gym has. If a machine breaks, tick "out of order": members stop seeing exercises that need it.</p>
<div class="card mb"><h2>Add equipment</h2>
  <form method="post" action="{{ route('admin.equipment.store') }}" class="row">@csrf
    <input type="text" name="name" placeholder="e.g. Cable Crossover" required style="flex:2;min-width:200px"><select name="category" style="flex:1">@foreach ($cats as $c)<option>{{ $c }}</option>@endforeach</select><button class="btn">Add</button></form></div>
<div class="card"><div class="tw"><table>
  <tr><th>Name</th><th>Type</th><th>Exercises</th><th>Out of order?</th><th></th></tr>
  @foreach ($items as $q)
    <tr class="{{ $q->is_dummy ? 'dummy' : '' }}"><td>{{ $q->name }} {!! dummy_tag($q) !!}</td><td>{{ $q->category }}</td><td>{{ $q->exercises_count }}</td>
      <td><form method="post" action="{{ route('admin.equipment.update', $q) }}" class="row">@csrf @method('PUT')
        <label class="checks" style="margin:0"><input type="checkbox" name="out_of_order" value="1" @checked($q->out_of_order) onchange="this.form.submit()"> {{ $q->out_of_order ? 'Out of order' : 'Working' }}</label></form></td>
      <td class="right"><form class="inline" method="post" action="{{ route('admin.equipment.destroy', $q) }}">@csrf @method('DELETE')<button class="btn ghost sm" data-confirm="Delete {{ $q->name }}?">Delete</button></form></td></tr>
  @endforeach
</table></div></div>
@endsection
