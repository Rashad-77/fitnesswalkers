@extends('layouts.admin')
@section('title', $a->exists ? 'Edit announcement' : 'New announcement')
@section('content')
<h1>{{ $a->exists ? 'Edit announcement' : 'New announcement' }}</h1>
<form class="card mt" style="max-width:640px" method="post" action="{{ $a->exists ? route('admin.announcements.update', $a) : route('admin.announcements.store') }}">@csrf @if ($a->exists) @method('PUT') @endif
  <div class="f"><label>Title</label><input type="text" name="title" value="{{ old('title', $a->title) }}" required></div>
  <div class="f"><label>Message</label><textarea name="body" required>{{ old('body', $a->body) }}</textarea></div>
  <button class="btn">{{ $a->exists ? 'Save' : 'Post to members' }}</button> <a class="btn ghost" href="{{ route('admin.announcements.index') }}">Cancel</a>
</form>
@endsection
