@php $gym = \App\Models\Setting::get('gym_name', 'FitnessWalkers'); @endphp
<!doctype html><html lang="en"><head>@include('partials.head')<title>Sign in - {{ $gym }}</title></head>
<body><div class="login">
  <div class="brandside">
    @include('partials.lanes', ['class' => 'lanes'])
    <a href="{{ route('home') }}" aria-label="{{ $gym }} home">@include('partials.logo')</a>
    <div><h2>Welcome <em>back.</em></h2><p>Your membership, your plan and your progress, all in one place. Members and the owner sign in on the same page.</p></div>
    <p class="small" style="margin:0">&copy; {{ date('Y') }} {{ $gym }}</p>
  </div>
  <div class="formside"><div class="box">
    <div class="show-sm"><a href="{{ route('home') }}" aria-label="{{ $gym }} home">@include('partials.logo', ['light' => true])</a></div>
    <h1>Sign in</h1><p class="muted small">Use the email and password the gym gave you.</p>
    @include('partials.flash')
    <form method="post" action="{{ route('login.post') }}">@csrf
      <div class="f"><label for="l-email">Email</label><input id="l-email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
      <div class="f"><label for="l-pass">Password</label><input id="l-pass" type="password" name="password" required autocomplete="current-password"></div>
      <div class="f checks"><label><input type="checkbox" name="remember" value="1"> Keep me signed in</label></div>
      <button class="btn block" style="height:52px">Sign in</button>
    </form>
    <p class="small muted center mt">Forgot your password? Ask the gym owner to reset it.</p>
    <p class="small center" style="margin-bottom:0"><a href="{{ route('home') }}">&larr; Back to the website</a></p>
  </div></div>
</div><script src="/js/app.js"></script></body></html>
