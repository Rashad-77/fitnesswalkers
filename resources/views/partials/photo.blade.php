{{-- A photo spot on the website. $slot (see gym_slots()), $class, $alt. Shows the photo if one was added, otherwise a tidy placeholder. --}}
@php $src = gym_img($slot); $label = gym_slots()[$slot][0] ?? ''; @endphp
<figure class="photo {{ $class ?? '' }} {{ $src ? 'has' : 'empty' }}">
  @if ($src)<img src="{{ $src }}" alt="{{ $alt ?? $label }}" loading="lazy" decoding="async">
  @else<div class="ph" role="img" aria-label="{{ $alt ?? $label }}">@include('partials.lanes', ['class' => 'lanes'])<span>@include('partials.i', ['n' => $icon ?? 'dumbbell'])</span></div>@endif
  @isset($caption)<figcaption>{{ $caption }}</figcaption>@endisset
</figure>
