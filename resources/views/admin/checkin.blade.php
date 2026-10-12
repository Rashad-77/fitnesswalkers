@extends('layouts.admin')
@section('title', 'Check-in QR')
@section('content')
<div class="row between mb noprint"><h1>Check-in QR</h1>
  <span class="row"><button class="btn" onclick="window.print()">Print poster</button>
  <form method="post" action="{{ route('admin.checkin.renew') }}" data-confirm="Generate a new code? The printed poster will stop working.">@csrf<button class="btn ghost">Generate new code</button></form></span></div>
<p class="muted noprint">Display this poster at the entrance. Members scan it with their phone, sign in, and are checked in for the day. If the poster is compromised, generate a new code.</p>
<div class="poster">
  <h1>{{ $gym }}</h1>
  <p>Scan to check in</p>
  <div class="qr">{!! $svg !!}</div>
  <p class="small muted">Use your phone camera. Sign in with your member email if asked.</p>
</div>
<p class="small muted noprint mt">Link inside the code: <code>{{ $url }}</code></p>
@endsection
