@extends('layouts.admin')
@section('title', 'Create meal plan')
@section('content')
<h1>Create meal plan</h1>
<form class="card mt" method="post" action="{{ route('admin.diets.store') }}">@csrf
  <div class="f"><label>Member</label><select name="member_id" required style="max-width:380px">@foreach ($members as $m)<option value="{{ $m->id }}" @selected(old('member_id', $selected) == $m->id)>{{ $m->user->name }} ({{ $m->member_no }})</option>@endforeach</select></div>
  <div class="f"><label>Title</label><input type="text" name="title" value="{{ old('title', 'Meal plan') }}" required style="max-width:380px"></div>
  <div class="f"><label>The plan (write it any way you like: meals, quantities, notes)</label><textarea name="owner_text" style="min-height:260px" required>{{ old('owner_text') }}</textarea></div>
  <button class="btn">Save and show to member</button> <a class="btn ghost" href="{{ route('admin.diets.index') }}">Cancel</a>
</form>
@endsection
