@extends('layouts.admin')
@section('title', 'Announcements')
@section('content')
<div class="row between mb"><h1>Announcements</h1><a class="btn" href="{{ route('admin.announcements.create') }}">+ New announcement</a></div>
<div class="card">
  @forelse ($items as $a)
    <div style="padding:.7rem 0;border-bottom:1px solid #eef0ea"><div class="row between"><strong>{{ $a->title }} {!! dummy_tag($a) !!}</strong><span class="muted small">{{ $a->published_at->format('d M Y') }}</span></div>
      <p class="small">{{ $a->body }}</p>
      <a class="btn ghost sm" href="{{ route('admin.announcements.edit', $a) }}">Edit</a>
      <form class="inline" method="post" action="{{ route('admin.announcements.destroy', $a) }}">@csrf @method('DELETE')<button class="btn ghost sm" data-confirm="Delete?">Delete</button></form></div>
  @empty <div class="empty">No announcements.</div> @endforelse
</div>
@endsection
