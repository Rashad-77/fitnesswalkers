@extends('layouts.admin')
@section('title', $ex->exists ? 'Edit exercise' : 'Add exercise')
@section('content')
<h1>{{ $ex->exists ? 'Edit '.$ex->name : 'Add exercise' }}</h1>
<form class="card mt" method="post" enctype="multipart/form-data" action="{{ $ex->exists ? route('admin.exercises.update', $ex) : route('admin.exercises.store') }}">@csrf @if ($ex->exists) @method('PUT') @endif
  <div class="grid g3">
    <div class="f"><label>Name</label><input type="text" name="name" value="{{ old('name', $ex->name) }}" required></div>
    <div class="f"><label>Muscle group</label><select name="muscle_group">@foreach ($groups as $g)<option @selected(old('muscle_group', $ex->muscle_group) === $g)>{{ $g }}</option>@endforeach</select></div>
    <div class="f"><label>Equipment needed</label><select name="equipment_id"><option value="">None (bodyweight)</option>@foreach ($equipment as $q)<option value="{{ $q->id }}" @selected(old('equipment_id', $ex->equipment_id) == $q->id)>{{ $q->name }}</option>@endforeach</select></div>
  </div>
  <div class="f"><label>How to do it (one step per line)</label><textarea name="steps" required>{{ old('steps', $ex->steps) }}</textarea></div>
  <div class="f"><label>Common mistakes</label><textarea name="mistakes">{{ old('mistakes', $ex->mistakes) }}</textarea></div>
  <div class="grid g2">
    <div class="f"><label>Upload a short clip (MP4 / WebM, max 20 MB)</label><input type="file" name="clip" accept="video/mp4,video/webm"></div>
    <div class="f"><label>...or paste a link to a clip</label><input type="url" name="clip_url" value="{{ old('clip_url', $ex->clip_url) }}" placeholder="https://..."></div>
  </div>
  @if ($ex->clip_url)<div class="f checks"><video class="vid" src="{{ $ex->clip_url }}" controls muted loop playsinline style="max-width:360px"></video><br><label><input type="checkbox" name="remove_clip" value="1"> Remove current clip</label></div>@endif
  <div class="row"><button class="btn">Save</button><a class="btn ghost" href="{{ route('admin.exercises.index') }}">Cancel</a></div>
</form>
@endsection
