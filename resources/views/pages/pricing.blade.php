@extends('layouts.public')
@section('title', 'Pricing')
@section('content')
<section class="sec"><div class="wrap">
  <h1 style="margin:0 0 .3rem">Pricing</h1>
  <p class="lead">Simple memberships. Pay at the gym by cash or bank transfer, and you get a numbered receipt.</p>
  @include('pages._prices', ['plans' => $plans])
  <div class="prose" style="margin-top:1.6rem">
    <h2>Important information</h2>
    <ul>
      <li>Prices are in Sri Lankan rupees (Rs.).</li>
      <li>You can pay in part and finish later. The gym keeps track of what is left to pay.</li>
      <li>Fees are not refundable. See the <a href="{{ route('terms') }}">terms and conditions</a>.</li>
      <li>Not sure which plan to pick? <a href="{{ route('contact') }}">Ask us</a>.</li>
    </ul>
  </div>
</div></section>
@endsection
