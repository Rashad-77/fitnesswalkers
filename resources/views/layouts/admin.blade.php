@php
  $gym = \App\Models\Setting::get('gym_name', 'FitnessWalkers');
  $dummyTotal = \Illuminate\Support\Facades\Cache::remember('dummy_total', 30, fn () => \App\Support\Dummy::total());
  $groups = \App\Support\AdminNav::groups();
  $label = \App\Support\AdminNav::currentLabel($groups);
@endphp
<!doctype html><html lang="en"><head>@include('partials.head')<meta name="robots" content="noindex"><title>@yield('title', 'Admin') - {{ $gym }}</title></head>
<body>
<div class="shell">
  <aside class="side" id="side" aria-label="Main menu">
    <a class="brand" href="{{ route('admin.dashboard') }}">@include('partials.logo')<span class="brandtag">Owner area</span></a>
    <nav class="sidenav">
      @foreach ($groups as $g)
        <div class="sec">{{ $g['label'] }}</div>
        @foreach ($g['items'] as $i)
          <a class="nav {{ request()->routeIs(...$i[2]) ? 'on' : '' }}" href="{{ route($i[1]) }}" @if (request()->routeIs(...$i[2])) aria-current="page" @endif>{!! \App\Support\AdminNav::icon($i[3]) !!}<span>{{ $i[0] }}</span>@if (! empty($i[4]))<span class="cnt">{{ $i[4] }}</span>@endif</a>
        @endforeach
      @endforeach
    </nav>
    <div class="sidefoot">
      <a class="nav" href="{{ route('home') }}" target="_blank" rel="noopener">{!! \App\Support\AdminNav::icon('globe') !!}<span>View website</span></a>
      <form method="post" action="{{ route('logout') }}">@csrf<button class="nav">{!! \App\Support\AdminNav::icon('logout') !!}<span>Sign out</span></button></form>
    </div>
  </aside>
  <div class="scrim" data-toggle-menu></div>
  <div class="main">
    <div class="top">
      <button class="menu-btn" data-toggle-menu aria-label="Menu">{!! \App\Support\AdminNav::icon('menu') !!}</button>
      <span class="toptitle">{{ $label }}</span>
      <form class="topsearch" method="get" action="{{ route('admin.members.index') }}" role="search" data-search="{{ route('admin.search') }}">
        {!! \App\Support\AdminNav::icon('search') !!}
        <input type="search" name="q" placeholder="Find a member by name, phone or number" value="{{ request()->routeIs('admin.members.index') ? request('q') : '' }}" aria-label="Find a member">
      </form>
      <details class="menu newmenu">
        <summary class="btn">{!! \App\Support\AdminNav::icon('plus') !!}<span>New</span></summary>
        <div class="pop">
          <a href="{{ route('admin.members.create') }}">Add member</a>
          <a href="{{ route('admin.payments.index') }}#record">Record payment</a>
          <a href="{{ route('admin.expenses.create') }}">Add expense</a>
          <a href="{{ route('admin.enquiries.create') }}">Add enquiry</a>
          <a href="{{ route('admin.announcements.create') }}">Post announcement</a>
        </div>
      </details>
      <details class="menu who">
        <summary>{{ auth()->user()->name }}</summary>
        <div class="pop right">
          <a href="{{ route('admin.account') }}">My account</a>
          <a href="{{ route('password.edit') }}">Change password</a>
          <form method="post" action="{{ route('logout') }}">@csrf<button>Sign out</button></form>
        </div>
      </details>
    </div>
    @if ($dummyTotal > 0 && request()->routeIs('admin.dashboard'))
      <div class="dummybar">This is sample data ({{ $dummyTotal }} rows). <a href="{{ route('admin.placeholders') }}">See what to replace</a></div>
    @endif
    <div class="page">
      @include('partials.flash')
      @yield('content')
    </div>
  </div>
</div>
<script src="/js/app.js"></script>
@stack('scripts')
</body></html>
