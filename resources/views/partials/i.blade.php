{{-- Inline icon: @include('partials.i', ['n' => 'arrow']). Stroke icons, they inherit the text colour. --}}
@php
  $paths = [
    'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
    'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
    'plan' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1M9 11h6M9 15h4"/>',
    'dumbbell' => '<path d="M6.5 6.5v11M17.5 6.5v11M3 9v6M21 9v6M6.5 12h11"/>',
    'food' => '<path d="M4 3v8a3 3 0 0 0 3 3v7M7 3v6M10 3v8a3 3 0 0 1-3 3M18 21V3c-2 1-3 4-3 8h3"/>',
    'phone' => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
    'call' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
    'chat' => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
    'pin' => '<path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'chart' => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
    'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
    'heart' => '<path d="M12 20s-7-4.6-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.4-7 10-7 10z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'bolt' => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
  ];
@endphp
<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$n] ?? '' !!}</svg>
