@extends('layouts.admin')
@section('title', 'Expenses')
@section('content')
<div class="row between mb"><h1>Expenses</h1><a class="btn" href="{{ route('admin.expenses.create') }}">+ Add expense</a></div>
<form class="filters" method="get"><input type="date" name="from" value="{{ $from }}"><input type="date" name="to" value="{{ $to }}">
  <select name="category"><option value="">All categories</option>@foreach ($cats as $c)<option @selected($category === $c)>{{ $c }}</option>@endforeach</select><button class="btn dark">Filter</button></form>
<div class="card"><div class="tw"><table>
  <tr><th>Date</th><th>Category</th><th>Description</th><th class="right">Amount</th><th></th></tr>
  @forelse ($items as $e)
    <tr class="{{ $e->is_dummy ? 'dummy' : '' }}"><td class="nowrap">{{ $e->spent_on->format('d M Y') }}</td><td>{{ $e->category }}</td><td>{{ $e->description }}</td><td class="right">{{ rs($e->amount) }}</td>
      <td class="right nowrap"><a class="btn ghost sm" href="{{ route('admin.expenses.edit', $e) }}">Edit</a>
      <form class="inline" method="post" action="{{ route('admin.expenses.destroy', $e) }}">@csrf @method('DELETE')<button class="btn ghost sm" data-confirm="Delete this expense?">Delete</button></form></td></tr>
  @empty <tr><td colspan="5" class="empty">No expenses in this period.</td></tr> @endforelse
  @if ($items->count())<tr><td colspan="3" class="right"><strong>Total</strong></td><td class="right"><strong>{{ rs($items->sum('amount')) }}</strong></td><td></td></tr>@endif
</table></div></div>
@endsection
