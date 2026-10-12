@extends('layouts.admin')
@section('title', $member->exists ? 'Edit member' : 'Add member')
@section('content')
<h1>{{ $member->exists ? 'Edit '.$member->user->name : 'Add member' }}</h1>
<form class="card mt" method="post" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}">
  @csrf @if ($member->exists) @method('PUT') @endif
  @if ($enquiry)<input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}"><div class="alert">Adding <strong>{{ $enquiry->name }}</strong> from your enquiries list. Their details are filled in.</div>@endif
  <div class="grid g2">
    <div class="f"><label>Full name *</label><input type="text" name="name" value="{{ old('name', $member->user->name ?? $enquiry?->name ?? '') }}" required></div>
    <div class="f"><label>Phone (used for WhatsApp)</label><input type="tel" name="phone" value="{{ old('phone', $member->user->phone ?? $enquiry?->phone ?? '') }}" placeholder="077 123 4567"></div>
    <div class="f"><label>Email (their login) *</label><input type="email" name="email" value="{{ old('email', $member->user->email ?? $enquiry?->email ?? '') }}" required></div>
    @unless ($member->exists)<div class="f"><label>First password * (8+ characters)</label><input type="text" name="password" value="{{ old('password') }}" required></div>@endunless
    <div class="f"><label>Sex</label><select name="sex"><option value="">-</option>@foreach (['male','female'] as $s)<option value="{{ $s }}" @selected(old('sex', $member->sex) === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <div class="f"><label>Date of birth</label><input type="date" name="dob" value="{{ old('dob', $member->dob?->toDateString()) }}"></div>
    <div class="f"><label>Height (cm)</label><input type="number" step="0.1" name="height_cm" value="{{ old('height_cm', $member->height_cm) }}"></div>
    <div class="f"><label>Joined on *</label><input type="date" name="joined_on" value="{{ old('joined_on', $member->joined_on?->toDateString()) }}" required></div>
    <div class="f"><label>Address</label><input type="text" name="address" value="{{ old('address', $member->address) }}"></div>
    <div class="f"><label>Emergency contact</label><input type="text" name="emergency_contact" value="{{ old('emergency_contact', $member->emergency_contact) }}"></div>
  </div>
  <div class="f"><label>Notes (anything to remember)</label><textarea name="notes">{{ old('notes', $member->notes) }}</textarea></div>
  <h2 class="mt">Health form</h2>
  <p class="small muted">Ask these out loud when they join and tick "yes" where it applies.</p>
  <input type="hidden" name="health_form" value="1">
  <div class="checks" style="display:flex;flex-direction:column;gap:.45rem">
  @foreach (config('fitness.health_questions') as $k => $text)
    <label><input type="checkbox" name="health[{{ $k }}]" value="1" @checked(old("health.$k", $member->health_answers[$k] ?? false))> {{ $text }}</label>
  @endforeach
  </div>
  <div class="f mt"><label>Health notes (injuries, medicines, doctor's advice)</label><textarea name="health_notes">{{ old('health_notes', $member->health_notes) }}</textarea></div>
  <label class="mt" style="display:flex;gap:.5rem;align-items:flex-start"><input type="checkbox" name="waiver" value="1" @checked(old('waiver', (bool) $member->waiver_accepted_at))>
    <span>They have read and agreed to the gym <a href="{{ route('terms') }}" target="_blank">terms</a>: they train at their own risk and the answers above are true.
      @if ($member->waiver_accepted_at)<span class="muted small">(agreed {{ $member->waiver_accepted_at->format('d M Y') }})</span>@endif</span></label>
  @unless ($member->exists)
    <h2 class="mt">Start a package now (optional)</h2>
    <div class="grid g4">
      <div class="f"><label>Package</label><select name="plan_id"><option value="">Add later</option>@foreach ($plans as $p)<option value="{{ $p->id }}" @selected(old('plan_id') == $p->id)>{{ $p->name }} - {{ rs($p->price) }}</option>@endforeach</select></div>
      <div class="f"><label>Starts on</label><input type="date" name="starts_on" value="{{ old('starts_on', today()->toDateString()) }}"></div>
      <div class="f"><label>Paid now (Rs.)</label><input type="number" step="0.01" min="0" name="initial_payment" value="{{ old('initial_payment') }}"></div>
      <div class="f"><label>Paid by</label><select name="method">@foreach (['cash','bank','card'] as $mt)<option value="{{ $mt }}">{{ ucfirst($mt) }}</option>@endforeach</select></div>
    </div>
  @endunless
  <div class="row"><button class="btn">Save</button><a class="btn ghost" href="{{ $member->exists ? route('admin.members.show', $member) : route('admin.members.index') }}">Cancel</a></div>
</form>
@endsection
