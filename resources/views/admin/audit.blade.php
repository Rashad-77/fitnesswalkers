@extends('layouts.admin')
@section('title', 'Activity log')
@section('content')
<h1>Activity log</h1><p class="muted">Who changed what and when. Payments, prices, packages and members are logged here.</p>
<div class="card"><div class="tw"><table>
  <tr><th>When</th><th>Who</th><th>What</th><th>About</th><th>Details</th></tr>
  @forelse ($logs as $l)<tr><td class="nowrap small">{{ $l->created_at->format('d M Y H:i') }}</td><td>{{ $l->user?->name ?? 'system' }}</td><td>{{ $l->action }}</td><td>{{ $l->subject }}</td><td class="small">{{ $l->details }}</td></tr>
  @empty <tr><td colspan="5" class="empty">Nothing recorded yet.</td></tr> @endforelse
</table></div>{{ $logs->links() }}</div>
@endsection
