@extends('layouts.public')
@section('title', 'Home')
@section('content')
@php $tag = \App\Models\Setting::get('tagline', 'Train with purpose.'); @endphp
<section class="phero"><div class="wrap">
  <h1>{{ $tag }}</h1>
  <p>Personal training from the owner himself, a plan made for you, and a simple app to track your membership, workouts and progress.</p>
  <div class="row">
    <a class="btn" href="{{ route('contact') }}">Become a member</a>
    <a class="btn herobtn" href="{{ route('pricing') }}">See pricing</a>
  </div>
</div></section>

<section class="sec"><div class="wrap">
  <h2>Why train with us</h2><p class="lead">A small gym on purpose. You are never just a number.</p>
  <div class="feat">
    <div class="c"><b>Personal attention</b>Your trainer is the owner. He knows your name, your goal and your progress.</div>
    <div class="c"><b>A plan made for you</b>Workout plans built around your goal and the equipment we have.</div>
    <div class="c"><b>Simple food guidance</b>A sample meal plan with Sri Lankan foods and rough costs in Rs.</div>
    <div class="c"><b>Your own member page</b>See your membership dates, today's workout and your progress on your phone.</div>
  </div>
</div></section>

<section class="sec alt"><div class="wrap">
  <h2>Membership prices</h2><p class="lead">Pay at the gym. No online payment needed.</p>
  @include('pages._prices', ['plans' => $plans])
  <p style="margin-top:1rem"><a href="{{ route('pricing') }}"><b>Full pricing details &rarr;</b></a></p>
</div></section>

<section class="sec"><div class="wrap">
  <h2>About us</h2>
  <div class="prose">@foreach (preg_split('/\n\s*\n/', \App\Models\Setting::get('about_text', '')) as $para)<p>{{ $para }}</p>@endforeach</div>
  <p><a href="{{ route('about') }}"><b>More about us &rarr;</b></a></p>
</div></section>

<section class="sec alt"><div class="wrap">
  <h2>Visit us</h2>
  <div class="cgrid">
    <div class="c"><h3>Address</h3>{{ \App\Models\Setting::get('gym_address') }}</div>
    <div class="c"><h3>Opening hours</h3>{{ \App\Models\Setting::get('gym_hours') }}</div>
    <div class="c"><h3>Call or WhatsApp</h3>{{ \App\Models\Setting::get('gym_phone') }}</div>
  </div>
  <p style="margin-top:1rem"><a class="btn" href="{{ route('contact') }}">Contact us</a></p>
</div></section>
@endsection
