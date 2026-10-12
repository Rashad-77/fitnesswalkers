@php
  $gym = \App\Models\Setting::get('gym_name', 'FitnessWalkers');
  $svg = fn ($p) => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$p.'</svg>';
  $tabs = [
    ['member.home', 'Home', $svg('<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>'), 'member.home'],
    ['member.workout', 'Workout', $svg('<path d="M6.5 6.5v11M17.5 6.5v11M3 9v6M21 9v6M6.5 12h11"/>'), 'member.workout*'],
    ['member.diet', 'Food', $svg('<path d="M4 3v8a3 3 0 0 0 3 3v7M7 3v6M10 3v8a3 3 0 0 1-3 3M18 21V3c-2 1-3 4-3 8h3"/>'), 'member.diet*'],
    ['member.progress', 'Progress', $svg('<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>'), 'member.progress*'],
  ];
@endphp
<!doctype html><html lang="en"><head>@include('partials.head')<meta name="robots" content="noindex"><title>@yield('title', 'Home') - {{ $gym }}</title></head>
<body>
<div class="mshell">
  <div class="mtop"><a href="{{ route('member.home') }}">@include('partials.logo')</a>
    <a class="me {{ request()->routeIs('member.me', 'member.membership', 'member.announcements') ? 'on' : '' }}" href="{{ route('member.me') }}" aria-label="Me: membership, news and settings">
      {!! $svg('<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0 1 16 0v1"/>') !!}<span>Account</span></a></div>
  <div class="mpage">
    @include('partials.flash')
    @yield('content')
  </div>
  <nav class="tabs" aria-label="Main"><div class="in">
    @foreach ($tabs as $t)
      <a class="{{ request()->routeIs($t[3]) ? 'on' : '' }}" href="{{ route($t[0]) }}"><span class="ic">{!! $t[2] !!}</span>{{ $t[1] }}</a>
    @endforeach
  </div></nav>
</div>
<script src="/js/app.js"></script>
@stack('scripts')
</body></html>
