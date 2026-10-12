@extends('layouts.public')
@section('title', 'Privacy policy')
@section('content')
@php $gym = \App\Models\Setting::get('gym_name', 'FitnessWalkers'); @endphp
<section class="sec"><div class="wrap"><div class="prose">
  <h1 style="margin:0 0 .3rem">Privacy policy</h1>
  <div class="draftnote"><b>Draft template.</b> This is a plain starting text. The owner should read it, change anything that is not true for the gym, and have it checked before the site goes live.</div>

  <h2>What we collect</h2>
  <p>When you join {{ $gym }}, we keep your name, phone number, email, date of birth, height and weight. We also keep your membership dates, payments, workout plans, workout logs, body measurements and goals. We record the days you visit the gym (when you scan the check-in code or the owner checks you in). If you ask for a meal plan, we keep the answers you give about your goal, diet type and allergies.</p>
  <p>If you send us an enquiry through this website, we keep your name, phone number, email (if given) and message so we can reply to you.</p>

  <h2>Why we collect it</h2>
  <p>To run your membership, record payments, build safe workout and food plans for you, and contact you about renewals and announcements.</p>

  <h2>Health information</h2>
  <p>When you join we ask a few health questions, and you agree to the gym terms. We keep your answers and the date you agreed. Any medical condition, injury or allergy you tell us about is used only to keep your plan safe. It is seen by the owner and trainer, not shared with other members.</p>

  <h2>Who can see your information</h2>
  <p>The owner and trainer can see all member records. You can see only your own. We do not sell your information. We do not share it with others unless the law requires it.</p>

  <h2>Messages</h2>
  <p>We may contact you by phone or WhatsApp about your membership. Tell us if you would rather not receive them.</p>

  <h2>Keeping it safe</h2>
  <p>Member pages need a password. Passwords are stored in scrambled form. Please keep yours private and tell us if you think someone else knows it.</p>

  <h2>Your choices</h2>
  <p>You can ask us to show you, correct or delete your information at any time by contacting the gym. Some payment records may need to be kept for accounting.</p>

  <h2>Contact</h2>
  <p>Questions about this policy: <a href="{{ route('contact') }}">contact us</a>.</p>
</div></div></section>
@endsection
