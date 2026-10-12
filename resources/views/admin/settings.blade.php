@extends('layouts.admin')
@section('title', 'Gym profile')
@section('content')
<h1>Gym profile</h1>
<div class="grid g2">
<form class="card" method="post" action="{{ route('admin.settings.save') }}">@csrf @method('PUT')
  <h2>Gym details</h2><p class="small muted">Shown on receipts and in the member app. Items marked <span class="b dummy">sample</span> are still placeholders.</p>
  @foreach ($fields as $k => $label)
    <div class="f"><label>{{ $label }} {!! in_array($k, $dummy) ? '<span class="b dummy">sample</span>' : '' !!}</label>
      @if ($k === 'about_text')<textarea name="{{ $k }}" rows="4">{{ old($k, \App\Models\Setting::get($k)) }}</textarea>@else<input type="text" name="{{ $k }}" value="{{ old($k, \App\Models\Setting::get($k)) }}">@endif</div>
  @endforeach
  <button class="btn">Save</button>
</form>

</div>
@endsection
