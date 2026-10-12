@extends('layouts.admin')
@section('title', 'Receipt '.$payment->receipt_no)
@section('content')
@php use App\Models\Setting; @endphp
<div class="row noprint mb"><button class="btn" onclick="window.print()">Print receipt</button>
  @if ($wa = $payment->member->whatsappUrl('Receipt '.$payment->receipt_no.' from '.Setting::get('gym_name', 'FitnessWalkers').': '.rs($payment->amount).' received on '.$payment->paid_on->format('d M Y').'. Thank you!'))
    <a class="btn ghost" target="_blank" rel="noopener" href="{{ $wa }}">Send on WhatsApp</a>@endif
  <a class="btn ghost" href="{{ route('admin.members.show', $payment->member) }}">Back to member</a></div>
<div class="receipt">
  <div class="center"><span class="rlogo">@include('partials.logo', ['light' => true])</span><br>
    <strong>{{ Setting::get('gym_name', 'FitnessWalkers') }}</strong><br>
    <span class="small muted">{{ Setting::get('gym_address') }}<br>{{ Setting::get('gym_phone') }}</span></div>
  <hr><h2 class="center rtitle">Payment receipt</h2>
  <table>
    <tr><td>Receipt no.</td><td class="right"><strong>{{ $payment->receipt_no }}</strong></td></tr>
    <tr><td>Date</td><td class="right">{{ $payment->paid_on->format('d M Y') }}</td></tr>
    <tr><td>Member</td><td class="right">{{ $payment->member->user->name }} ({{ $payment->member->member_no }})</td></tr>
    @if ($payment->membership)<tr><td>For</td><td class="right">{{ $payment->membership->plan->name }} ({{ $payment->membership->starts_on->format('d M') }} - {{ $payment->membership->ends_on->format('d M Y') }})</td></tr>@endif
    <tr><td>Method</td><td class="right">{{ ucfirst($payment->method) }}</td></tr>
    <tr class="ramt"><td><strong>Amount received</strong></td><td class="right"><strong style="font-size:1.2rem">{{ rs($payment->amount, true) }}</strong></td></tr>
    @if ($payment->membership)
      <tr><td>Package price</td><td class="right">{{ rs($payment->membership->price, true) }}</td></tr>
      <tr><td>Total paid so far</td><td class="right">{{ rs($payment->membership->paid(), true) }}</td></tr>
      <tr><td>Balance due</td><td class="right">{{ rs($payment->membership->balance(), true) }}</td></tr>
    @endif
    @if ($payment->note)<tr><td>Note</td><td class="right">{{ $payment->note }}</td></tr>@endif
  </table>
  <p class="small muted center mt">{{ Setting::get('receipt_footer') }}</p>
</div>
@endsection
