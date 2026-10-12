@php
  $gym = \App\Models\Setting::get('gym_name', 'FitnessWalkers');
  $dummyTotal = \Illuminate\Support\Facades\Cache::remember('dummy_total', 30, fn () => \App\Support\Dummy::total());
  $sections = \App\Support\AdminNav::sections();
  $cur = \App\Support\AdminNav::current($sections);
  $tabs = $sections[$cur]['tabs'];
@endphp
<!doctype html><html lang="en"><head>@include('partials.head')<title>@yield('title', 'Admin') - {{ $gym }}</title></head>
<body>
<div class="shell">
  <aside class="side">
    <a class="brand" href="{{ route('admin.dashboard') }}"><img src="/img/logo-banner.png" alt="{{ $gym }}"></a>
    @foreach ($sections as $key => $s)
      <a class="nav {{ $cur === $key ? 'on' : '' }}" href="{{ route($s['route']) }}">{!! \App\Support\AdminNav::icon($s['icon']) !!}{{ $s['label'] }}</a>
    @endforeach
    <div class="sidefoot"><a href="{{ route('home') }}" target="_blank" rel="noopener">View website &nearr;</a></div>
  </aside>
  <div class="main">
    <div class="top">
      <button class="menu-btn" data-toggle-menu aria-label="Menu">{!! \App\Support\AdminNav::icon('menu') !!}</button>
      <form class="topsearch" method="get" action="{{ route('admin.members.index') }}" role="search">
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
    @if (count($tabs) > 1)
      <nav class="subnav" aria-label="{{ $sections[$cur]['label'] }}">
        @foreach ($tabs as $t)
          <a class="{{ request()->routeIs(...$t[2]) ? 'on' : '' }}" href="{{ route($t[1]) }}">{{ $t[0] }}@if (! empty($t[3]))<span class="cnt">{{ $t[3] }}</span>@endif</a>
        @endforeach
      </nav>
    @endif
    @if ($dummyTotal > 0 && $cur === 'today')
      <div class="dummybar">This is sample data ({{ $dummyTotal }} rows). <a href="{{ route('admin.placeholders') }}">See what to replace</a></div>
    @endif
    <div class="page">
      @include('partials.flash')
      @yield('content')
    </div>
  </div>
</div>
<script src="/js/app.js"></script>
</body></html>
