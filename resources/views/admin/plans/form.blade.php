@extends('layouts.admin')
@section('title', $plan->exists ? 'Edit package' : 'Add package')
@section('content')
<h1>{{ $plan->exists ? 'Edit package' : 'Add package' }}</h1>
<form class="card mt" style="max-width:520px" method="post" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}">@csrf @if ($plan->exists) @method('PUT') @endif
  <div class="f"><label>Name</label><input type="text" name="name" value="{{ old('name', $plan->name) }}" required placeholder="Monthly"></div>
  <div class="f"><label>Length (days)</label><input type="number" name="duration_days" value="{{ old('duration_days', $plan->duration_days) }}" required placeholder="30"></div>
  <div class="f"><label>Price (Rs.)</label><input type="number" step="0.01" name="price" value="{{ old('price', $plan->price) }}" required></div>
  <div class="f checks"><label><input type="checkbox" name="active" value="1" @checked(old('active', $plan->active ?? true))> On sale (shown when adding a package to a member)</label></div>
  <button class="btn">Save</button> <a class="btn ghost" href="{{ route('admin.plans.index') }}">Cancel</a>
</form>
@endsection
