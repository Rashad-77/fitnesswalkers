<!doctype html><html lang="en"><head>@include('partials.head')<title>Sign in - {{ \App\Models\Setting::get('gym_name', 'FitnessWalkers') }}</title></head>
<body><div class="login"><div class="box">
  <img src="/img/logo-banner.png" alt="FitnessWalkers">
  <h1>Sign in</h1><p class="muted small">Members and the owner use the same page.</p>
  @include('partials.flash')
  <form method="post" action="{{ route('login.post') }}">@csrf
    <div class="f"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
    <div class="f"><label>Password</label><input type="password" name="password" required autocomplete="current-password"></div>
    <div class="f checks"><label><input type="checkbox" name="remember" value="1"> Keep me signed in</label></div>
    <button class="btn block">Sign in</button>
  </form>
  <p class="small muted center mt">Forgot your password? Ask the gym owner to reset it.</p>
</div></div><script src="/js/app.js"></script></body></html>
