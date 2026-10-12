@extends('layouts.public')
@section('title', 'Home')
@section('content')
@php
  $S = fn ($k, $d = '') => \App\Models\Setting::get($k, $d);
  $tag = trim($S('tagline', 'Train with purpose.'));
  $words = preg_split('/\s+/', $tag); $last = array_pop($words);
  $digits = preg_replace('/\D+/', '', (string) $S('gym_phone'));
  $about = array_values(array_filter(preg_split('/\n\s*\n/', (string) $S('about_text', '')), 'strlen'));
  $owner = $S('owner_name', 'The owner');
@endphp

<section class="phero">
  @include('partials.lanes', ['class' => 'hero-lanes'])
  <div class="wrap"><div class="grid2">
    <div>
      <p class="eyebrow" data-reveal>A gym where the owner is your trainer</p>
      <h1 data-reveal style="--d:.08s">{{ implode(' ', $words) }} <em>{{ $last }}</em></h1>
      <p class="sub" data-reveal style="--d:.16s">Personal training from the owner himself, a plan made for you, and a simple app to track your membership, workouts and progress.</p>
      <div class="row" data-reveal style="--d:.24s">
        <a class="btn" href="{{ route('contact') }}">Become a member @include('partials.i', ['n' => 'arrow'])</a>
        <a class="btn ghost" href="{{ route('pricing') }}">See pricing</a>
      </div>
      <div class="heroproof" data-reveal style="--d:.32s">
        <div>@include('partials.i', ['n' => 'user'])<p style="margin:0"><b>Owner-led</b><span>Your trainer knows your name</span></p></div>
        <div>@include('partials.i', ['n' => 'plan'])<p style="margin:0"><b>Plans made for you</b><span>Built around your goal</span></p></div>
        <div>@include('partials.i', ['n' => 'phone'])<p style="margin:0"><b>Track on your phone</b><span>Dates, workouts, progress</span></p></div>
      </div>
    </div>

    <div class="devices" aria-hidden="true" data-reveal style="--d:.2s">
      <div class="phone p2"><div class="scr">
        <div class="mk-h">Progress<b>Your weight</b></div>
        <svg class="mk-svg" viewBox="0 0 200 110" fill="none"><defs><linearGradient id="gA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b8f03c" stop-opacity=".35"/><stop offset="1" stop-color="#b8f03c" stop-opacity="0"/></linearGradient></defs>
          <path d="M0 22H200M0 55H200M0 88H200" stroke="#ffffff" stroke-opacity=".08"/>
          <path d="M5 30C30 34 45 44 70 48S110 62 135 66 175 78 195 84V110H5Z" fill="url(#gA)"/>
          <path d="M5 30C30 34 45 44 70 48S110 62 135 66 175 78 195 84" stroke="#b8f03c" stroke-width="3" stroke-linecap="round"/><circle cx="195" cy="84" r="5" fill="#b8f03c"/></svg>
        <div class="mk-card"><div class="mk-big">-4.2 <small>kg this quarter</small></div><div class="mk-bar"><i style="width:82%"></i></div></div>
        <div class="mk-row">Goal weight<span>68 kg</span></div><div class="mk-row">Consistency<span class="mk-chip">12 visits</span></div>
        <div class="mk-tabs"><span>Home</span><span>Workout</span><span>Food</span><span class="on">Progress</span></div>
      </div></div>
      <div class="phone p1"><div class="scr">
        <div class="mk-h">Welcome,<b>Kasun</b></div>
        <div class="mk-card"><div class="mk-big">18 <small>days left</small></div><div class="mk-bar"><i></i></div></div>
        <div class="mk-card" style="margin-top:.7rem"><div style="font-size:.74rem;color:#8fb09f">Today &middot; Upper body</div>
          <div class="mk-row">Bench press<span>3 &times; 10</span></div><div class="mk-row">Seated row<span>3 &times; 12</span></div><div class="mk-row">Shoulder press<span>3 &times; 10</span></div></div>
        <div class="mk-tabs"><span class="on">Home</span><span>Workout</span><span>Food</span><span>Progress</span></div>
      </div></div>
      <div class="glass g1"><span class="dot">@include('partials.i', ['n' => 'check'])</span><div><b>Checked in</b>Have a good workout!</div></div>
      <div class="glass g2"><span class="dot">@include('partials.i', ['n' => 'bolt'])</span><div><b>Plan approved</b>by your trainer</div></div>
    </div>
  </div></div>
  <span class="scrollcue" aria-hidden="true"></span>
</section>

<div class="marquee-wrap" aria-hidden="true"><div class="marquee"><div class="track">
  @for ($i = 0; $i < 2; $i++)@foreach (['Strength', 'Cardio', 'Coaching', 'Nutrition', 'Progress', 'Community'] as $w)<span>{{ $w }}</span>@endforeach @endfor
  @for ($i = 0; $i < 2; $i++)@foreach (['Strength', 'Cardio', 'Coaching', 'Nutrition', 'Progress', 'Community'] as $w)<span>{{ $w }}</span>@endforeach @endfor
</div></div></div>

<section class="sec light" style="padding-top:6rem"><div class="wrap">
  <div class="sechead">
    <div><p class="eyebrow dk" data-reveal>Why train with us</p><h2 data-reveal style="margin:0">A small gym<br>on purpose.</h2></div>
    <p class="lead" data-reveal>You are never just a number. Everything here is built around knowing you and helping you get where you are going.</p>
  </div>
  <div class="bento">
    <div class="c big" data-reveal>@include('partials.lanes', ['class' => 'lanes'])<span class="fi">@include('partials.i', ['n' => 'user'])</span><h3>Personal attention</h3><p>Your trainer is the owner. He knows your name, your goal and your progress, and every plan is checked and approved by him personally.</p></div>
    <div class="c" data-reveal style="--d:.08s"><span class="fi">@include('partials.i', ['n' => 'plan'])</span><h3>A plan made for you</h3><p>Workout plans built around your goal and the equipment we have.</p></div>
    <div class="c" data-reveal><span class="fi">@include('partials.i', ['n' => 'food'])</span><h3>Simple food guidance</h3><p>A meal plan with Sri Lankan foods, in cups and spoons you already use at home.</p></div>
    <div class="c lime" data-reveal style="--d:.08s"><span class="fi">@include('partials.i', ['n' => 'phone'])</span><h3>Your own member page</h3><p>See your membership dates, today's workout and your progress on your phone.</p></div>
    <div class="c" data-reveal style="--d:.16s"><span class="fi">@include('partials.i', ['n' => 'chart'])</span><h3>See your progress</h3><p>Weight, measurements and personal bests, drawn as simple charts so you can see it working.</p></div>
  </div>
</div></section>

<section class="sec deep"><div class="wrap">
  <div class="center" style="max-width:640px;margin:0 auto 4rem"><p class="eyebrow" data-reveal>How it works</p><h2 data-reveal>Three steps to your first session.</h2></div>
  <div class="steps3">
    <div class="stp" data-reveal><div class="num">1</div><h3>Say hello</h3><p>Message us on WhatsApp, call, or walk in during opening hours. No pressure.</p></div>
    <div class="stp" data-reveal style="--d:.1s"><div class="num">2</div><h3>Get your plan</h3><p>Your trainer sets up your membership and builds a workout and meal plan around your goal.</p></div>
    <div class="stp" data-reveal style="--d:.2s"><div class="num">3</div><h3>Train and track</h3><p>Scan the code at the door to check in, follow your plan and watch your progress build.</p></div>
  </div>
</div></section>

<section class="sec dark" style="overflow:hidden"><div class="wrap"><div class="showcase">
  <div>
    <p class="eyebrow" data-reveal>The member app</p>
    <h2 data-reveal>Your gym, in your pocket.</h2>
    <p class="lead" style="margin-bottom:0" data-reveal>No download needed. Open your member page on your phone and everything is there.</p>
    <ul class="ticks" data-reveal>
      <li>@include('partials.i', ['n' => 'check'])<span><b>Know where you stand</b>Your membership dates and check-in status at a glance.</span></li>
      <li>@include('partials.i', ['n' => 'check'])<span><b>Follow your workout</b>Step-by-step exercise guides, with sets and reps to log.</span></li>
      <li>@include('partials.i', ['n' => 'check'])<span><b>Eat with a plan</b>Seven days of Sri Lankan meals and a shopping list you can send on WhatsApp.</span></li>
      <li>@include('partials.i', ['n' => 'check'])<span><b>Watch it work</b>Charts for weight, measurements and your strength.</span></li>
    </ul>
    <a class="btn" href="{{ route('login') }}" data-reveal>Member sign in @include('partials.i', ['n' => 'arrow'])</a>
  </div>
  <div class="devices" aria-hidden="true" data-reveal>
    <div class="phone p2"><div class="scr">
      <div class="mk-h">Your meal plan<b>Day 1</b></div>
      <div class="mk-card"><div class="mk-big">2,150 <small>kcal target</small></div></div>
      <div class="mk-row">Breakfast<span>String hoppers, dhal</span></div><div class="mk-row">Lunch<span>Rice, fish, mallum</span></div><div class="mk-row">Snack<span>Banana, curd</span></div><div class="mk-row">Dinner<span>Roti, chicken curry</span></div>
      <div class="mk-tabs"><span>Home</span><span>Workout</span><span class="on">Food</span><span>Progress</span></div>
    </div></div>
    <div class="phone p1"><div class="scr">
      <div class="mk-h">Workout<b>Upper body</b></div>
      <div class="mk-card"><div class="mk-row" style="padding-top:0">Bench press<span class="mk-chip">3 &times; 10</span></div><div class="mk-row">Incline dumbbell press<span>3 &times; 12</span></div><div class="mk-row">Lat pulldown<span>3 &times; 10</span></div><div class="mk-row">Triceps pushdown<span>3 &times; 15</span></div></div>
      <div class="mk-card" style="margin-top:.7rem"><div style="font-size:.74rem;color:#8fb09f">Last time</div><div class="mk-big" style="font-size:1.4rem">40 <small>kg</small></div></div>
      <div class="mk-tabs"><span>Home</span><span class="on">Workout</span><span>Food</span><span>Progress</span></div>
    </div></div>
  </div>
</div></div></section>

<section class="sec light"><div class="wrap">
  <div class="sechead">
    <div><p class="eyebrow dk" data-reveal>Membership</p><h2 data-reveal style="margin:0">Simple prices.</h2></div>
    <p class="lead" data-reveal>Pay at the gym. No online payment needed.</p>
  </div>
  @include('pages._prices', ['plans' => $plans])
  <p style="margin-top:2rem" data-reveal><a href="{{ route('pricing') }}"><b>Full pricing details &rarr;</b></a></p>
</div></section>

@if ($about)
<section class="sec white"><div class="wrap"><div class="quote">
  <div class="portrait" data-reveal>@include('partials.lanes', ['class' => 'lanes'])<span class="mono">{{ strtoupper(mb_substr($owner, 0, 1)) }}</span><div class="cap"><b>{{ $owner }}</b>Owner and head trainer</div></div>
  <div class="txt" data-reveal style="--d:.1s"><p class="eyebrow dk" style="margin-bottom:1.3rem">About us</p>
    @foreach (array_slice($about, 0, 2) as $para)<p>{{ $para }}</p>@endforeach
    <p style="margin-top:1.6rem"><a class="btn ghost" href="{{ route('about') }}">More about us @include('partials.i', ['n' => 'arrow'])</a></p></div>
</div></div></section>
@endif

<section class="sec deep"><div class="wrap">
  <div class="sechead"><div><p class="eyebrow" data-reveal>Visit us</p><h2 data-reveal style="margin:0">Come and see<br>for yourself.</h2></div></div>
  <div class="visit">
    <div class="vc" data-reveal><span class="fi">@include('partials.i', ['n' => 'pin'])</span><h3>Address</h3><div>{{ $S('gym_address') }}</div></div>
    <div class="vc" data-reveal style="--d:.08s"><span class="fi">@include('partials.i', ['n' => 'clock'])</span><h3>Opening hours</h3><div>{{ $S('gym_hours') }}</div></div>
    <div class="vc" data-reveal style="--d:.16s"><span class="fi">@include('partials.i', ['n' => 'chat'])</span><h3>Call or WhatsApp</h3><a href="tel:{{ $digits }}">{{ $S('gym_phone') }}</a></div>
  </div>
  <div class="ctaband" data-reveal>@include('partials.lanes', ['class' => 'lanes'])
    <h2>Ready to walk in?</h2><p>Leave your details and we will get back to you, or just come by.</p>
    <div class="row"><a class="btn" href="{{ route('contact') }}">Contact us @include('partials.i', ['n' => 'arrow'])</a><a class="btn ghost" href="{{ route('pricing') }}">See pricing</a></div>
  </div>
</div></section>
@endsection
