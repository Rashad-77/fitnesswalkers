@extends('layouts.admin')
@section('title', 'Data export')
@section('content')
<h1>Data export</h1>
<p class="muted">Download your data as spreadsheet files (open them in Excel). Do this regularly and keep a copy somewhere safe, for example Google Drive.</p>
<div class="card"><div class="grid g3">
  @foreach ($types as $k => $label)<a class="btn dark" href="{{ route('admin.export.csv', $k) }}">⇩ {{ $label }}</a>@endforeach
</div></div>
<div class="alert info mt">For a full backup of the database, also use "Backup" or "phpMyAdmin → Export" in your hosting control panel (cPanel).</div>
@endsection
