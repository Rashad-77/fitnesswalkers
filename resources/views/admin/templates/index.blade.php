@extends('layouts.admin')
@section('title', 'Workout templates')
@section('content')
<div class="row between mb"><h1>Workout templates</h1><a class="btn" href="{{ route('admin.templates.create') }}">+ New template</a></div>
<div class="card"><div class="tw"><table>
  <tr><th>Name</th><th>Goal</th><th>Level</th><th>Days</th><th>Exercises</th><th></th></tr>
  @forelse ($templates as $t)
    <tr class="{{ $t->is_dummy ? 'dummy' : '' }}"><td>{{ $t->name }} {!! dummy_tag($t) !!}</td><td>{{ str_replace('_', ' ', $t->goal) }}</td><td>{{ $t->level }}</td><td>{{ $t->days_per_week }}</td><td>{{ $t->items_count }}</td>
      <td class="right nowrap"><a class="btn ghost sm" href="{{ route('admin.templates.edit', $t) }}">Edit</a>
        <form class="inline" method="post" action="{{ route('admin.templates.destroy', $t) }}">@csrf @method('DELETE')<button class="btn ghost sm" data-confirm="Delete this template?">Delete</button></form></td></tr>
  @empty <tr><td colspan="6" class="empty">No templates yet.</td></tr> @endforelse
</table></div></div>
@endsection
