@extends('layouts.admin')
@section('title', 'Exercise library')
@section('content')
<div class="row between mb"><h1>Exercise library</h1><a class="btn" href="{{ route('admin.exercises.create') }}">+ Add exercise</a></div>
<p class="muted">Members only see exercises whose equipment is working. Add a short looping clip (MP4/WebM, up to 20 MB) to each exercise.</p>
<form class="filters" method="get"><input type="search" name="q" value="{{ $q }}" placeholder="Search..."><select name="group"><option value="">All muscle groups</option>@foreach ($groups as $g)<option @selected($group === $g)>{{ $g }}</option>@endforeach</select><button class="btn dark">Filter</button></form>
<div class="card"><div class="tw"><table>
  <tr><th>Exercise</th><th>Muscle</th><th>Equipment</th><th>Clip</th><th></th></tr>
  @forelse ($items as $e)
    <tr class="{{ $e->is_dummy ? 'dummy' : '' }}"><td>{{ $e->name }} {!! dummy_tag($e) !!}</td><td>{{ $e->muscle_group }}</td>
      <td>{{ $e->equipment?->name ?? 'none needed' }} @if ($e->equipment?->out_of_order)<span class="b out">out of order</span>@endif</td>
      <td>{!! $e->clip_url ? '<span class="b done">added</span>' : '<span class="b pending">missing</span>' !!}</td>
      <td class="right nowrap"><a class="btn ghost sm" href="{{ route('admin.exercises.edit', $e) }}">Edit</a>
        <form class="inline" method="post" action="{{ route('admin.exercises.destroy', $e) }}">@csrf @method('DELETE')<button class="btn ghost sm" data-confirm="Delete {{ $e->name }}? It will be removed from plans and templates.">Delete</button></form></td></tr>
  @empty <tr><td colspan="5" class="empty">No exercises found.</td></tr> @endforelse
</table></div></div>
@endsection
