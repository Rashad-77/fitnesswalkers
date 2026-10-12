@extends('layouts.public')
@section('title', 'Pricing')
@section('content')
<section class="pagehero">@include('partials.lanes', ['class' => 'lanes'])<div class="wrap">
  <p class="eyebrow" data-reveal>Pricing</p><h1 data-reveal style="--d:.08s">Simple<br>memberships.</h1>
  <p data-reveal style="--d:.16s">Pay at the gym by cash or bank transfer, and you get a numbered receipt.</p>
</div></section>

<section class="sec light"><div class="wrap">
  @include('pages._prices', ['plans' => $plans])
  <div class="infocard" style="margin-top:3.5rem;max-width:760px" data-reveal>
    <h3 style="font-size:1.35rem;margin-bottom:1rem">Good to know</h3>
    <ul class="infolist">
      <li>@include('partials.i', ['n' => 'check'])<span>Prices are in Sri Lankan rupees (Rs.).</span></li>
      <li>@include('partials.i', ['n' => 'check'])<span>You can pay in part and finish later. The gym keeps track of what is left to pay.</span></li>
      <li>@include('partials.i', ['n' => 'check'])<span>Fees are not refundable. See the <a href="{{ route('terms') }}">terms and conditions</a>.</span></li>
      <li>@include('partials.i', ['n' => 'check'])<span>Not sure which plan to pick? <a href="{{ route('contact') }}">Ask us</a>.</span></li>
    </ul>
  </div>
</div></section>
@endsection
