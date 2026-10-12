@extends('layouts.member')
@section('title', 'Announcements')
@section('content')
<h1>Announcements</h1>
@forelse ($items as $a)<div class="card mb"><strong>{{ $a->title }}</strong><div class="small muted">{{ $a->published_at->format('d M Y') }}</div><p>{{ $a->body }}</p></div>
@empty<div class="empty">Nothing yet.</div>@endforelse
@endsection
