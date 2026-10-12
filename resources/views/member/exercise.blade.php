@extends('layouts.member')
@section('title', $ex->name)
@section('content')
<a class="small" href="javascript:history.back()">‹ Back</a>
<h1>{{ $ex->name }}</h1>
<p><span class="b">{{ $ex->muscle_group }}</span> @if ($ex->equipment)<span class="b">{{ $ex->equipment->name }}</span>@else<span class="b">no equipment</span>@endif</p>
<div class="mb">@include('partials.exercise_media', ['ex' => $ex])</div>
<div class="card mb"><h2>How to do it</h2><ol class="steps">@foreach (array_filter(array_map('trim', preg_split('/\R/', $ex->steps))) as $line)<li>{{ $line }}</li>@endforeach</ol></div>
@if ($ex->mistakes)<div class="card mb"><h2>Common mistakes</h2><p>{{ $ex->mistakes }}</p></div>@endif
<p class="disc">Start light and focus on form. Stop if something hurts and ask the owner to check your technique.</p>
@endsection
