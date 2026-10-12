@extends('layouts.public')
@section('title', 'About us')
@section('content')
<section class="sec"><div class="wrap">
  <h1 style="margin:0 0 .6rem">About us</h1>
  <div class="prose">
    @foreach (preg_split('/\n\s*\n/', \App\Models\Setting::get('about_text', '')) as $para)<p>{{ $para }}</p>@endforeach
  </div>
</div></section>
<section class="sec alt"><div class="wrap">
  <h2>Your trainer</h2>
  <div class="prose"><p><b>{{ \App\Models\Setting::get('owner_name', 'The owner') }}</b>, owner and head trainer of {{ \App\Models\Setting::get('gym_name', 'FitnessWalkers') }}. Every member's plan is checked and approved by him personally.</p></div>
  <h2 style="margin-top:1.6rem">What you will find here</h2>
  <div class="feat">
    <div class="c"><b>Machines and free weights</b>Strength machines, barbells, dumbbells and a pull-up bar.</div>
    <div class="c"><b>Cardio</b>Treadmills and bikes to warm up or burn calories.</div>
    <div class="c"><b>Clear guidance</b>Step-by-step exercise instructions in your member page.</div>
  </div>
  <p style="margin-top:1.4rem"><a class="btn" href="{{ route('contact') }}">Visit us</a></p>
</div></section>
@endsection
