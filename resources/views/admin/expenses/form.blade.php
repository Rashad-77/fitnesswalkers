@extends('layouts.admin')
@section('title', $expense->exists ? 'Edit expense' : 'Add expense')
@section('content')
<h1>{{ $expense->exists ? 'Edit expense' : 'Add expense' }}</h1>
<form class="card mt" style="max-width:520px" method="post" action="{{ $expense->exists ? route('admin.expenses.update', $expense) : route('admin.expenses.store') }}">@csrf @if ($expense->exists) @method('PUT') @endif
  <div class="f"><label>Category</label><select name="category">@foreach ($cats as $c)<option @selected(old('category', $expense->category) === $c)>{{ $c }}</option>@endforeach</select></div>
  <div class="f"><label>Amount (Rs.)</label><input type="number" step="0.01" min="1" name="amount" value="{{ old('amount', $expense->amount) }}" required></div>
  <div class="f"><label>Date</label><input type="date" name="spent_on" value="{{ old('spent_on', $expense->spent_on?->toDateString()) }}" required></div>
  <div class="f"><label>Description</label><input type="text" name="description" value="{{ old('description', $expense->description) }}"></div>
  <button class="btn">Save</button> <a class="btn ghost" href="{{ route('admin.expenses.index') }}">Cancel</a>
</form>
@endsection
