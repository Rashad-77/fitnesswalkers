@php
  $gym = \App\Models\Setting::get('gym_name', 'FitnessWalkers');
  $nav = [['home','Home'],['about','About us'],['pricing','Pricing'],['contact','Contact us']];
  $u = auth()->user();
@endphp
<!doctype html><html lang="en"><head>@include('partials.head')<title>@yield('title', $gym) - {{ $gym }}</title>
<meta name="description" content="@yield('desc', $gym.' - a friendly local gym with personal training.')">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:type" content="website"><meta property="og:site_name" content="{{ $gym }}"><meta property="og:title" content="@yield('title', $gym) - {{ $gym }}"><meta property="og:description" content="@yield('desc', $gym.' - a friendly local gym with personal training.')"><meta property="og:url" content="{{ url()->current() }}"><meta property="og:image" content="{{ asset('img/icon-192.png') }}"></head>
<body class="pub">
<header class="phead" id="phead"><div class="in">
  <a href="{{ route('home') }}" aria-label="{{ $gym }} home">@include('partials.logo')</a>
  <button class="pnav-toggle" id="pnav-toggle" type="button" aria-label="Menu" aria-expanded="false">@include('partials.i', ['n' => 'menu'])</button>
  <nav id="pnav">
    @foreach ($nav as $n)<a class="{{ request()->routeIs($n[0]) ? 'on' : '' }}" href="{{ route($n[0]) }}">{{ $n[1] }}</a>@endforeach
    @if ($u)<a class="cta" href="{{ route('dashboard') }}">My dashboard</a>
    @else<a class="cta" href="{{ route('login') }}">Sign in</a>@endif
  </nav>
</div></header>
<main>@yield('content')</main>
<footer class="pfoot">
  <div class="in">
    <div>@include('partials.logo')<p class="blurb">A small gym on purpose. Personal training from the owner, plans made for you, and a simple app to track it all.</p></div>
    <div><h4>Visit</h4><a>{{ \App\Models\Setting::get('gym_address') }}</a><a href="tel:{{ preg_replace('/\D+/', '', (string) \App\Models\Setting::get('gym_phone')) }}">{{ \App\Models\Setting::get('gym_phone') }}</a></div>
    <div><h4>Opening hours</h4><a>{{ \App\Models\Setting::get('gym_hours') }}</a></div>
    <div><h4>Explore</h4>
      <a href="{{ route('about') }}">About us</a><a href="{{ route('pricing') }}">Pricing</a><a href="{{ route('contact') }}">Contact us</a>
      <a href="{{ route('privacy') }}">Privacy policy</a><a href="{{ route('terms') }}">Terms and conditions</a>
    </div>
  </div>
  <div class="copy"><span>&copy; {{ date('Y') }} {{ $gym }}. All rights reserved.</span><span>Train with purpose.</span></div>
  <div class="bigword" aria-hidden="true">{{ strtoupper($gym) }}</div>
</footer>
<script src="/js/app.js"></script>
</body></html>
