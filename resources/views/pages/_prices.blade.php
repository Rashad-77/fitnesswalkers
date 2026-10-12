{{-- $plans, optional $compact (home page: a tagline instead of the full feature list) --}}
@if ($plans->isEmpty())
  <p class="muted">Prices coming soon. Please contact us.</p>
@else
<div class="prices">
  @foreach ($plans as $p)
    @php $features = $p->featureList(); $saving = $p->savingVsMonthly(); $months = $p->duration_days / 30; @endphp
    <div class="price {{ $p->featured ? 'best' : '' }}" data-reveal style="--d:{{ $loop->index * .08 }}s">
      @if ($p->featured)<span class="tag">Most popular</span>@endif
      <h3>{{ $p->name }}</h3>
      @if ($p->tagline)<p class="ptag">{{ $p->tagline }}</p>@endif
      <div class="amt">{{ rs($p->price) }}</div>
      <div class="per">for {{ $p->duration_days }} days @if ($months > 1.5) &middot; about {{ rs($p->perMonth()) }} a month @endif @if ($saving)<span class="save">Save {{ rs($saving) }}</span>@endif</div>
      @if (empty($compact))
        <ul>@forelse ($features as $f)<li>{{ $f }}</li>@empty<li>Use of all gym equipment</li><li>Workout plan from the trainer</li><li>Member page on your phone</li>@endforelse</ul>
      @else<div style="flex:1"></div>@endif
      <a class="btn block {{ $p->featured ? '' : 'ghost' }}" href="{{ route('contact', ['plan' => $p->slug()]) }}">Choose {{ $p->name }}</a>
    </div>
  @endforeach
</div>
@endif
