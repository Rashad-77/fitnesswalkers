@extends('layouts.member')
@section('title', 'Create meal plan')
@section('content')
@if ($issues)
  <h1>We cannot make a plan for you</h1>
  <section class="card">
    @foreach ((array) $issues as $i)<div class="alert err">{{ $i }}</div>@endforeach
    <p>A plan made by a program would not be safe or right for you. Please speak to a doctor or dietitian, or ask the gym owner for advice.</p>
    <div class="row"><a class="btn" href="{{ route('member.diet.create', ['edit' => 1]) }}">Change my answers</a><a class="btn ghost" href="{{ route('member.diet') }}">Back to Food</a></div>
  </section>
@else
  <a class="small" href="{{ route('member.diet') }}">&lsaquo; {{ $hasPlan ? 'Back to my plan' : 'Back' }}</a>
  @include('partials.diet_wizard', ['action' => route('member.diet.generate'), 'previewUrl' => route('member.diet.preview')])
@endif
@endsection
