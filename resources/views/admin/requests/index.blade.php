@extends('layouts.admin')
@section('title', 'Workout requests')
@section('content')
<h1>Workout plan requests</h1>
<div class="card mb"><h2>Waiting for you ({{ $pending->count() }})</h2><div class="tw"><table>
  <tr><th>Member</th><th>Goal</th><th>Level</th><th>Days/week</th><th>Asked</th><th></th></tr>
  @forelse ($pending as $r)
    <tr class="{{ $r->is_dummy ? 'dummy' : '' }}"><td>{{ $r->member->user->name }}</td><td>{{ str_replace('_', ' ', $r->goal) }}</td><td>{{ $r->level }}</td><td>{{ $r->days_per_week }}</td><td>{{ $r->created_at->diffForHumans() }}</td>
      <td class="right"><a class="btn sm" href="{{ route('admin.requests.show', $r) }}">Build plan</a></td></tr>
  @empty <tr><td colspan="6" class="empty">No requests waiting.</td></tr> @endforelse
</table></div></div>
<div class="card"><h2>History</h2><div class="tw"><table>
  <tr><th>Member</th><th>Goal</th><th>Status</th><th>Date</th></tr>
  @forelse ($history as $r)<tr><td>{{ $r->member->user->name }}</td><td>{{ str_replace('_', ' ', $r->goal) }}</td><td><span class="b {{ $r->status }}">{{ $r->status }}</span></td><td>{{ $r->updated_at->format('d M Y') }}</td></tr>
  @empty <tr><td colspan="4" class="empty">Nothing yet.</td></tr> @endforelse
</table></div></div>
@endsection
