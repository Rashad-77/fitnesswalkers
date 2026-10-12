@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.member')
@section('title', 'Change password')
@section('content')
<h1>Change password</h1>
<div class="card mt" style="max-width:420px">
  <form method="post" action="{{ route('password.update') }}">@csrf @method('PUT')
    <div class="f"><label>Current password</label><input type="password" name="current_password" required></div>
    <div class="f"><label>New password (8+ characters)</label><input type="password" name="password" required></div>
    <div class="f"><label>Repeat new password</label><input type="password" name="password_confirmation" required></div>
    <button class="btn">Save password</button>
  </form>
</div>
@endsection
