@extends('layouts.admin')
@section('title', $tpl->exists ? 'Edit template' : 'New template')
@section('content')
<h1>{{ $tpl->exists ? 'Edit template' : 'New template' }}</h1>
<form class="card mt" method="post" action="{{ $tpl->exists ? route('admin.templates.update', $tpl) : route('admin.templates.store') }}">@csrf @if ($tpl->exists) @method('PUT') @endif
  <div class="grid g4">
    <div class="f"><label>Name</label><input type="text" name="name" value="{{ old('name', $tpl->name) }}" required></div>
    <div class="f"><label>Goal</label><select name="goal">@foreach (['general' => 'General fitness', 'fat_loss' => 'Fat loss', 'muscle_gain' => 'Muscle gain'] as $k => $v)<option value="{{ $k }}" @selected(old('goal', $tpl->goal) === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="f"><label>Level</label><select name="level">@foreach (['beginner', 'intermediate'] as $v)<option @selected(old('level', $tpl->level) === $v)>{{ $v }}</option>@endforeach</select></div>
    <div class="f"><label>Days per week</label><input type="number" min="1" max="7" name="days_per_week" value="{{ old('days_per_week', $tpl->days_per_week) }}"></div>
  </div>
  @include('partials.plan_items', ['items' => $tpl->exists ? $tpl->items : collect(), 'exercises' => $exercises])
  <div class="mt row"><button class="btn">Save template</button><a class="btn ghost" href="{{ route('admin.templates.index') }}">Cancel</a></div>
</form>
@endsection
