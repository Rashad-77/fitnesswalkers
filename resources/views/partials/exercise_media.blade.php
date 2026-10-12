{{-- The tutorial GIF or video for an exercise. $ex, optional $class. Shows a friendly placeholder until one is added. --}}
@php $url = $ex->mediaUrl(); $kind = $ex->mediaKind(); @endphp
@if ($url && $kind === 'image')
  <img class="tutorial {{ $class ?? '' }}" src="{{ $url }}" alt="How to do {{ $ex->name }}" loading="lazy">
@elseif ($url)
  <video class="tutorial {{ $class ?? '' }}" src="{{ $url }}" autoplay loop muted playsinline controls preload="metadata" aria-label="How to do {{ $ex->name }}"></video>
@else
  <div class="clip-ph {{ $class ?? '' }}">@include('partials.i', ['n' => 'dumbbell']) <span>A short tutorial GIF for this exercise is coming soon.<br>Follow the steps for now.</span></div>
@endif
