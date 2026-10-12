@php
  $gym = \App\Models\Setting::get('gym_name', 'FitnessWalkers');
  $nav = [['home','Home'],['about','About us'],['pricing','Pricing'],['contact','Contact us']];
  $u = auth()->user();
@endphp
<!doctype html><html lang="en"><head>@include('partials.head')<title>@yield('title', $gym) - {{ $gym }}</title>
<meta name="description" content="@yield('desc', $gym.' - a friendly local gym with personal training.')"></head>
<body class="pub">
<header class="phead"><div class="in">
  <a href="{{ route('home') }}"><img src="/img/logo-banner.png" alt="{{ $gym }}"></a>
  <nav>
    @foreach ($nav as $n)<a class="{{ request()->routeIs($n[0]) ? 'on' : '' }}" href="{{ route($n[0]) }}">{{ $n[1] }}</a>@endforeach
    @if ($u)<a class="cta" href="{{ route('dashboard') }}">My dashboard</a>
    @else<a class="cta" href="{{ route('login') }}">Sign in</a>@endif
  </nav>
</div></header>
<main>@yield('content')</main>
<footer class="pfoot">
  <div class="in">
    <div><h4>{{ $gym }}</h4><div>{{ \App\Models\Setting::get('gym_address') }}</div><div>{{ \App\Models\Setting::get('gym_phone') }}</div></div>
    <div><h4>Opening hours</h4><div>{{ \App\Models\Setting::get('gym_hours') }}</div></div>
    <div><h4>Links</h4>
      <a href="{{ route('about') }}">About us</a><a href="{{ route('pricing') }}">Pricing</a><a href="{{ route('contact') }}">Contact us</a>
      <a href="{{ route('privacy') }}">Privacy policy</a><a href="{{ route('terms') }}">Terms and conditions</a>
    </div>
  </div>
  <div class="copy">&copy; {{ date('Y') }} {{ $gym }}. All rights reserved.</div>
</footer>
<script src="/js/app.js"></script>
</body></html>
