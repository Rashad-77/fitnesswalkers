@extends('layouts.admin')
@section('title', 'Add enquiry')
@section('content')
<h1>Add enquiry</h1>
<p class="muted">Record a walk-in or phone enquiry so it can be followed up.</p>
<form class="card mt" method="post" action="{{ route('admin.enquiries.store') }}">@csrf
  <div class="grid g2">
    <div class="f"><label>Name *</label><input type="text" name="name" value="{{ old('name') }}" required></div>
    <div class="f"><label>Phone *</label><input type="tel" name="phone" value="{{ old('phone') }}" required></div>
    <div class="f"><label>Email</label><input type="email" name="email" value="{{ old('email') }}"></div>
    <div class="f"><label>Enquiry source</label><select name="source">@foreach (['walk-in' => 'Walk-in', 'phone' => 'Phone / WhatsApp', 'other' => 'Other'] as $k => $l)<option value="{{ $k }}" @selected(old('source') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="f"><label>Interested in</label><input type="text" name="interest" value="{{ old('interest') }}" placeholder="Weight loss, monthly package..."></div>
  </div>
  <div class="f"><label>Note</label><textarea name="message">{{ old('message') }}</textarea></div>
  <div class="row"><button class="btn">Save</button><a class="btn ghost" href="{{ route('admin.enquiries.index') }}">Cancel</a></div>
</form>
@endsection
