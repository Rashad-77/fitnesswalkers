@extends('layouts.public')
@section('title', 'Terms and conditions')
@section('content')
@php $gym = \App\Models\Setting::get('gym_name', 'FitnessWalkers'); @endphp
<section class="pagehero slim">@include('partials.lanes', ['class' => 'lanes'])<div class="wrap"><p class="eyebrow">Legal</p><h1>Terms and conditions</h1><p>The ground rules for training with us.</p></div></section>
<section class="sec light"><div class="wrap"><div class="legal"><div class="prose">
  <div class="draftnote"><b>Draft template.</b> The owner should read each point, change anything that is not how the gym really works, and have it checked before the site goes live.</div>

  <h2>Membership</h2>
  <p>Membership is personal and cannot be shared or transferred. It starts and ends on the dates shown on your member page.</p>

  <h2>Fees and payment</h2>
  <p>Fees are paid at the gym, in cash or by bank transfer, and you receive a numbered receipt. Part payments are allowed, and the balance must be paid by the date agreed with the owner. Fees are not refundable.</p>

  <h2>Freezing a membership</h2>
  <p>The owner may freeze a membership in special cases, such as illness or travel. Please ask before you stop coming.</p>

  <h2>Health and safety</h2>
  <p>You confirm that you are fit to exercise. Tell the trainer about any injury, medical condition or pregnancy before you start. Workout and diet plans are general guidance and are not medical advice. If something hurts or feels wrong, stop and tell the trainer.</p>

  <h2>Using the gym</h2>
  <p>Please use the equipment properly, put weights back, wipe machines after use, and respect other members and staff. Report broken equipment instead of using it.</p>

  <h2>Personal belongings</h2>
  <p>Please look after your own belongings. The gym is not responsible for lost or stolen items.</p>

  <h2>Member page</h2>
  <p>Keep your password private. Food prices shown in diet plans are estimates and may differ from shop prices.</p>

  <h2>Ending membership</h2>
  <p>The owner may end a membership without refund if these terms are broken or other members are put at risk.</p>

  <h2>Changes</h2>
  <p>We may update these terms. The latest version is always on this page. Questions? <a href="{{ route('contact') }}">Contact us</a>.</p>
</div></div></div></section>
@endsection
