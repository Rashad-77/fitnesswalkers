@extends('layouts.public')
@section('title', 'Home')
@section('content')
@php
  $S = fn ($k, $d = '') => \App\Models\Setting::get($k, $d);
  $tag = trim($S('tagline', 'Train with purpose.'));
  $words = preg_split('/\s+/', $tag); $last = array_pop($words);
  $digits = preg_replace('/\D+/', '', (string) $S('gym_phone'));
  $about = array_values(array_filter(preg_split('/\n\s*\n/', (string) $S('about_text', '')), 'strlen'));
  $owner = $S('owner_name', 'The owner'); $gymName = $S('gym_name', 'FitnessWalkers');
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
    <div class="collage" data-reveal style="--d:.2s">
      @include('partials.photo', ['slot' => 'hero-1', 'class' => 'c1', 'alt' => 'Training at '.$gymName])
      @include('partials.photo', ['slot' => 'hero-2', 'class' => 'c2', 'alt' => 'Inside '.$gymName, 'icon' => 'heart'])
      @include('partials.photo', ['slot' => 'hero-3', 'class' => 'c3', 'alt' => 'Members at '.$gymName, 'icon' => 'bolt'])
      <div class="glass g1" aria-hidden="true"><span class="dot">@include('partials.i', ['n' => 'check'])</span><div><b>Plan approved</b>by your trainer</div></div>
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
    <div><p class="eyebrow dk" data-reveal>Inside the gym</p><h2 data-reveal style="margin:0">Take a look<br>around.</h2></div>
    <p class="lead" data-reveal>Strength machines, free weights and cardio, with a trainer who is on the floor with you.</p>
  </div>
  <div class="gallery">
    @include('partials.photo', ['slot' => 'floor', 'class' => 'g-big', 'caption' => 'The training floor', 'alt' => 'The training floor at '.$gymName])
    @include('partials.photo', ['slot' => 'free-weights', 'class' => 'g-a', 'caption' => 'Free weights', 'icon' => 'dumbbell'])
    @include('partials.photo', ['slot' => 'machines', 'class' => 'g-b', 'caption' => 'Strength machines', 'icon' => 'layers'])
    @include('partials.photo', ['slot' => 'cardio', 'class' => 'g-c', 'caption' => 'Cardio', 'icon' => 'heart'])
    @include('partials.photo', ['slot' => 'community', 'class' => 'g-d', 'caption' => 'Training together', 'icon' => 'user'])
  </div>
</div></section>

<section class="sec white"><div class="wrap"><div class="quote">
  @include('partials.photo', ['slot' => 'trainer', 'class' => 'portrait', 'alt' => $owner, 'icon' => 'user', 'caption' => $owner.' · Owner and head trainer'])
  <div class="txt" data-reveal style="--d:.1s"><p class="eyebrow dk" style="margin-bottom:1.3rem">Meet your trainer</p>
    @if ($about)@foreach (array_slice($about, 0, 2) as $para)<p>{{ $para }}</p>@endforeach
    @else<p>Your trainer is the owner. He knows your name, your goal and your progress.</p>@endif
    <ul class="infolist" style="margin:1.4rem 0 1.6rem">
      <li>@include('partials.i', ['n' => 'check'])<span>Every member's plan is checked and approved by {{ $owner }} personally.</span></li>
      <li>@include('partials.i', ['n' => 'check'])<span>Sets and reps are set for you, not copied from a template.</span></li>
    </ul>
    <p style="margin:0"><a class="btn ghost" href="{{ route('about') }}">More about us @include('partials.i', ['n' => 'arrow'])</a></p></div>
</div></div></section>

<section class="sec deep"><div class="wrap">
  <div class="sechead"><div><p class="eyebrow" data-reveal>Programs</p><h2 data-reveal style="margin:0">Train for<br>what you want.</h2></div>
    <p class="lead" data-reveal>Tell us your goal and we build the plan around it, workouts and meals together.</p></div>
  <div class="programs">
    <a class="prog" href="{{ route('contact') }}" data-reveal>@include('partials.photo', ['slot' => 'program-weight', 'alt' => 'Weight management', 'icon' => 'heart'])<div><h3>Weight management</h3><p>Steady fat loss with workouts and a meal plan that fits your calories.</p><span class="more">Ask about it @include('partials.i', ['n' => 'arrow'])</span></div></a>
    <a class="prog" href="{{ route('contact') }}" data-reveal style="--d:.08s">@include('partials.photo', ['slot' => 'program-muscle', 'alt' => 'Muscle building', 'icon' => 'dumbbell'])<div><h3>Muscle building</h3><p>Progressive training and enough protein, with sets and reps chosen for you.</p><span class="more">Ask about it @include('partials.i', ['n' => 'arrow'])</span></div></a>
    <a class="prog" href="{{ route('contact') }}" data-reveal style="--d:.16s">@include('partials.photo', ['slot' => 'program-fitness', 'alt' => 'General fitness', 'icon' => 'bolt'])<div><h3>General fitness</h3><p>Move well, get stronger and feel good, at a pace that suits you.</p><span class="more">Ask about it @include('partials.i', ['n' => 'arrow'])</span></div></a>
  </div>
</div></section>

<section class="sec light"><div class="wrap">
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
  @include('pages._prices', ['plans' => $plans, 'compact' => true])
  <p style="margin-top:2rem" data-reveal><a href="{{ route('pricing') }}"><b>Full pricing details &rarr;</b></a></p>
</div></section>

<section class="sec deep"><div class="wrap">
  <div class="visitwrap">
    <div>
      <p class="eyebrow" data-reveal>Visit us</p><h2 data-reveal>Come and see<br>for yourself.</h2>
      <div class="visit one">
        <div class="vc" data-reveal><span class="fi">@include('partials.i', ['n' => 'pin'])</span><h3>Address</h3><div>{{ $S('gym_address') }}</div></div>
        <div class="vc" data-reveal style="--d:.08s"><span class="fi">@include('partials.i', ['n' => 'clock'])</span><h3>Opening hours</h3><div>{{ $S('gym_hours') }}</div></div>
        <div class="vc" data-reveal style="--d:.16s"><span class="fi">@include('partials.i', ['n' => 'chat'])</span><h3>Call or WhatsApp</h3><a href="tel:{{ $digits }}">{{ $S('gym_phone') }}</a></div>
      </div>
    </div>
    @include('partials.photo', ['slot' => 'visit', 'class' => 'visitphoto', 'alt' => 'Outside '.$gymName, 'icon' => 'pin'])
  </div>
  <div class="ctaband" data-reveal>@include('partials.lanes', ['class' => 'lanes'])
    <h2>Ready to walk in?</h2><p>Leave your details and we will get back to you, or just come by.</p>
    <div class="row"><a class="btn" href="{{ route('contact') }}">Contact us @include('partials.i', ['n' => 'arrow'])</a><a class="btn ghost" href="{{ route('pricing') }}">See pricing</a></div>
  </div>
</div></section>
@endsection
