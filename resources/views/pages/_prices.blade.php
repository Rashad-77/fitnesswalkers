@php $mid = $plans->count() > 1 ? $plans->values()[intdiv($plans->count(), 2)]->id : null; @endphp
@if ($plans->isEmpty())
  <p class="muted">Prices coming soon. Please contact us.</p>
@else
<div class="prices">
  @foreach ($plans as $p)
    @php
      $months = max(1, round($p->duration_days / 30));
      $monthly = $p->price / $months;
    @endphp
    <div class="price {{ $p->id === $mid ? 'best' : '' }}" data-reveal style="--d:{{ $loop->index * .08 }}s">
      @if ($p->id === $mid)<span class="tag">Popular</span>@endif
      <h3>{{ $p->name }}</h3>
      <div class="amt">{{ rs($p->price) }}</div>
      <div class="per">for {{ $p->duration_days }} days @if ($months > 1) &middot; about {{ rs(round($monthly)) }} a month @endif</div>
      <ul><li>Use of all gym equipment</li><li>Workout plan from the trainer</li><li>Member page on your phone</li></ul>
      <a class="btn block {{ $p->id === $mid ? '' : 'ghost' }}" href="{{ route('contact') }}">Ask about joining</a>
    </div>
  @endforeach
</div>
@endif
