@extends('layouts.admin')
@section('title', 'My account')
@section('content')
<h1>My account</h1>
<form class="card mt" style="max-width:560px" method="post" action="{{ route('admin.settings.account') }}">@csrf @method('PUT')
  <h2>Owner login {!! auth()->user()->is_dummy ? '<span class="b dummy">sample</span>' : '' !!}</h2>
  @if (auth()->user()->is_dummy)<div class="alert warn">You are using the sample login (<code>owner@fitnesswalkers.test</code> / <code>password</code>). Change it now.</div>@endif
  <div class="f"><label>Your name</label><input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required></div>
  <div class="f"><label>Email (what you sign in with)</label><input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required></div>
  <div class="f"><label>Phone</label><input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}"></div>
  <div class="f"><label>New password (leave blank to keep)</label><input type="password" name="password" autocomplete="new-password"></div>
  <div class="f"><label>Repeat new password</label><input type="password" name="password_confirmation" autocomplete="new-password"></div>
  <button class="btn dark">Save</button>
</form>
@endsection
