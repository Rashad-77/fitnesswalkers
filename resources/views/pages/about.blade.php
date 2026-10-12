@extends('layouts.public')
@section('title', 'About us')
@section('content')
@php
  $S = fn ($k, $d = '') => \App\Models\Setting::get($k, $d);
  $owner = $S('owner_name', 'The owner'); $gym = $S('gym_name', 'FitnessWalkers');
  $about = array_values(array_filter(preg_split('/\n\s*\n/', (string) $S('about_text', '')), 'strlen'));
@endphp
<section class="pagehero">@include('partials.lanes', ['class' => 'lanes'])<div class="wrap">
  <p class="eyebrow" data-reveal>About us</p><h1 data-reveal style="--d:.08s">A small gym,<br>on purpose.</h1>
  <p data-reveal style="--d:.16s">Who we are, who will train you, and what you will find when you walk in.</p>
</div></section>

<section class="sec white"><div class="wrap"><div class="quote">
  <div class="portrait" data-reveal>@include('partials.lanes', ['class' => 'lanes'])<span class="mono">{{ strtoupper(mb_substr($owner, 0, 1)) }}</span><div class="cap"><b>{{ $owner }}</b>Owner and head trainer of {{ $gym }}</div></div>
  <div class="txt" data-reveal style="--d:.1s">
    @foreach ($about as $para)<p>{{ $para }}</p>@endforeach
    <p style="font-family:var(--body);font-size:1.05rem;font-weight:500;color:var(--mut)"><b style="color:var(--ink)">Your trainer.</b> Every member's plan is checked and approved by {{ $owner }} personally.</p>
  </div>
</div></div></section>

<section class="sec light"><div class="wrap">
  <div class="sechead"><div><p class="eyebrow dk" data-reveal>The gym</p><h2 data-reveal style="margin:0">What you will find here.</h2></div></div>
  <div class="cgrid">
    <div class="c" data-reveal><span class="fi">@include('partials.i', ['n' => 'dumbbell'])</span><h3>Machines and free weights</h3><p>Strength machines, barbells, dumbbells and a pull-up bar.</p></div>
    <div class="c" data-reveal style="--d:.08s"><span class="fi">@include('partials.i', ['n' => 'heart'])</span><h3>Cardio</h3><p>Treadmills and bikes to warm up or burn calories.</p></div>
    <div class="c" data-reveal style="--d:.16s"><span class="fi">@include('partials.i', ['n' => 'plan'])</span><h3>Clear guidance</h3><p>Step-by-step exercise instructions in your member page.</p></div>
  </div>
  <div class="ctaband" data-reveal>@include('partials.lanes', ['class' => 'lanes'])
    <h2>Come and say hello.</h2><p>Walk in during opening hours or send us a message. We would love to meet you.</p>
    <div class="row"><a class="btn" href="{{ route('contact') }}">Visit us @include('partials.i', ['n' => 'arrow'])</a><a class="btn ghost" href="{{ route('pricing') }}">See pricing</a></div>
  </div>
</div></section>
@endsection
