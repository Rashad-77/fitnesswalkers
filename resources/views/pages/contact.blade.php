@extends('layouts.public')
@section('title', 'Contact us')
@section('content')
@php
  $phone = \App\Models\Setting::get('gym_phone'); $addr = \App\Models\Setting::get('gym_address');
  $digits = preg_replace('/\D+/', '', (string) $phone);
  $wa = $digits ? 'https://wa.me/'.(str_starts_with($digits, '0') ? '94'.substr($digits, 1) : $digits).'?text='.rawurlencode('Hi, I would like to know about joining '.\App\Models\Setting::get('gym_name', 'FitnessWalkers').'.') : null;
@endphp
<section class="sec"><div class="wrap">
  <h1 style="margin:0 0 .3rem">Contact us</h1>
  <p class="lead">The quickest way to reach us is WhatsApp or a phone call. You are welcome to walk in during opening hours.</p>
  <div class="cgrid">
    <div class="c"><h3>Phone</h3><a href="tel:{{ $digits }}">{{ $phone }}</a></div>
    <div class="c"><h3>WhatsApp</h3>@if ($wa)<a class="btn sm" href="{{ $wa }}">Message us on WhatsApp</a>@endif</div>
    <div class="c"><h3>Email</h3><a href="mailto:{{ \App\Models\Setting::get('gym_email') }}">{{ \App\Models\Setting::get('gym_email') }}</a></div>
    <div class="c"><h3>Address</h3>{{ $addr }}<br><a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode((string) $addr) }}"><b>Open in Google Maps &rarr;</b></a></div>
    <div class="c"><h3>Opening hours</h3>{{ \App\Models\Setting::get('gym_hours') }}</div>
  </div>
  <h2 style="margin:2rem 0 .3rem">Membership enquiry</h2>
  <p class="muted">Leave your details and we will contact you. We use them only to reply to your enquiry.</p>
  @if (session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
  @if ($errors->any())<div class="alert warn">{{ $errors->first() }}</div>@endif
  <form class="card" method="post" action="{{ route('enquiry.store') }}" style="max-width:640px">@csrf
    <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="grid g2">
      <div class="f"><label for="eq-name">Your name *</label><input id="eq-name" type="text" name="name" value="{{ old('name') }}" required></div>
      <div class="f"><label for="eq-phone">Phone or WhatsApp *</label><input id="eq-phone" type="tel" name="phone" value="{{ old('phone') }}" required placeholder="077 123 4567"></div>
      <div class="f"><label for="eq-email">Email (optional)</label><input id="eq-email" type="email" name="email" value="{{ old('email') }}"></div>
      <div class="f"><label for="eq-int">Area of interest</label><select id="eq-int" name="interest"><option value="">Choose one</option>@foreach (['Weight management', 'Muscle building', 'General fitness', 'Pricing information'] as $o)<option @selected(old('interest') === $o)>{{ $o }}</option>@endforeach</select></div>
    </div>
    <div class="f"><label for="eq-msg">Additional information</label><textarea id="eq-msg" name="message" rows="3">{{ old('message') }}</textarea></div>
    <button class="btn">Submit enquiry</button>
  </form>
</div></section>
@endsection
