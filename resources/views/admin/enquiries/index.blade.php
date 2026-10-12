@extends('layouts.admin')
@section('title', 'Enquiries')
@section('content')
@php $open = ($counts['new'] ?? 0) + ($counts['contacted'] ?? 0); @endphp
<div class="row between mb"><h1>Enquiries</h1><a class="btn" href="{{ route('admin.enquiries.create') }}">+ Add enquiry</a></div>
<p class="muted">People who asked about joining, from the website or at the desk. Reply, then turn them into a member when they sign up.</p>
<div class="chips">
  <a class="chip {{ $filter === 'open' ? 'on' : '' }}" href="{{ route('admin.enquiries.index') }}">Open <span class="n">{{ $open }}</span></a>
  @foreach (\App\Models\Enquiry::STATUSES as $k => $label)
    <a class="chip {{ $filter === $k ? 'on' : '' }}" href="{{ route('admin.enquiries.index', ['status' => $k]) }}">{{ $label }} <span class="n">{{ $counts[$k] ?? 0 }}</span></a>
  @endforeach
</div>
<div class="card">
  @forelse ($enquiries as $e)
    <div style="padding:.7rem 0;border-bottom:1px solid #eef0ea" class="{{ $e->is_dummy ? 'dummy' : '' }}">
      <div class="row between">
        <span><strong>{{ $e->name }}</strong> {!! dummy_tag($e) !!}
          <span class="b {{ $e->status === 'new' ? 'pending' : ($e->status === 'joined' ? 'active' : ($e->status === 'lost' ? 'expired' : 'frozen')) }}">{{ \App\Models\Enquiry::STATUSES[$e->status] ?? $e->status }}</span>
          <span class="muted small">· {{ \App\Models\Enquiry::SOURCES[$e->source] ?? $e->source }} · {{ $e->created_at->diffForHumans() }}</span></span>
        <span class="row">
          @if ($wa = $e->whatsappUrl())<a class="btn sm" target="_blank" rel="noopener" href="{{ $wa }}">WhatsApp</a>@endif
          @if ($e->status !== 'joined')<a class="btn dark sm" href="{{ route('admin.members.create', ['enquiry' => $e->id]) }}">Convert to member</a>@elseif ($e->member)<a class="btn ghost sm" href="{{ route('admin.members.show', $e->member) }}">Open member</a>@endif
        </span>
      </div>
      <div class="small">{{ $e->phone }}{{ $e->email ? ' · '.$e->email : '' }}{{ $e->interest ? ' · interested in '.$e->interest : '' }}</div>
      @if ($e->message)<div class="small muted">"{{ $e->message }}"</div>@endif
      <form class="row mt" method="post" action="{{ route('admin.enquiries.update', $e) }}" style="gap:.4rem">@csrf @method('PUT')
        <select name="status" aria-label="Status" style="width:auto">@foreach (\App\Models\Enquiry::STATUSES as $k => $label)<option value="{{ $k }}" @selected($e->status === $k)>{{ $label }}</option>@endforeach</select>
        <input type="text" name="notes" value="{{ $e->notes }}" placeholder="Note (what did they say?)" style="flex:1">
        <button class="btn ghost sm">Save</button>
        <button class="btn ghost sm" form="del{{ $e->id }}">Delete</button>
      </form>
      <form id="del{{ $e->id }}" method="post" action="{{ route('admin.enquiries.destroy', $e) }}" data-confirm="Delete this enquiry?">@csrf @method('DELETE')</form>
    </div>
  @empty <div class="empty">No enquiries found. New enquiries from the website will appear here.</div> @endforelse
</div>
@endsection
