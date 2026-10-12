@extends('layouts.admin')
@section('title', $diet->title)
@section('content')
<div class="row between mb"><div><h1>{{ $diet->title }}</h1><span class="muted">{{ $diet->member->user->name }} · {{ $diet->source === 'owner' ? 'written by you' : 'made by the app' }} · {{ $diet->created_at->format('d M Y') }}</span></div>
  <div class="row"><a class="btn" href="{{ route('admin.diets.create', ['member' => $diet->member_id]) }}">Override with my own plan</a>
    <form method="post" action="{{ route('admin.diets.destroy', $diet) }}">@csrf @method('DELETE')<button class="btn ghost" data-confirm="Delete this plan?">Delete</button></form></div></div>
@if ($diet->source === 'owner')
  <div class="card"><pre class="plain">{{ $diet->owner_text }}</pre></div>
@else
  @if ($diet->inputs)@php $in = $diet->inputs; @endphp<div class="card mb small"><strong>Survey answers:</strong> {{ $in['sex'] ?? '' }}, {{ $in['age'] ?? '' }} years, {{ $in['height'] ?? '' }} cm, {{ $in['weight'] ?? '' }} kg · wants to {{ strtolower(\App\Http\Controllers\Member\DietController::ACHIEVEMENTS[$in['achievement'] ?? ''] ?? ($in['goal'] ?? '')) }} · body now {{ $in['current_body'] ?? '-' }}, goal {{ $in['body_goal'] ?? '-' }} · metabolism {{ $in['metabolism'] ?? '-' }} · {{ $in['train_days'] ?? '-' }} workouts a week · {{ $in['meals'] ?? '-' }} meals · {{ \App\Services\DietEngine::PATTERNS[$in['pattern'] ?? 'everything'] ?? '' }}@if (! empty($in['allergies'])) · allergies: {{ implode(', ', $in['allergies']) }}@endif</div>@endif
  @if ($dishes)
    @php $tg = $diet->plan['targets']; $ov = $diet->inputs['override'] ?? null; $byOwner = ($diet->inputs['override_by'] ?? null) === 'owner'; @endphp
    @foreach ($flags as $f)<div class="alert warn">{{ $f['text'] }}</div>@endforeach
    <details class="card mb tgtedit" @if ($errors->any()) open @endif><summary><b>Set this member's targets</b> <span class="muted small">{{ $byOwner ? 'set by you' : ($ov ? 'changed by the member' : 'from the survey') }}</span></summary>
      <p class="small muted" style="margin:.7rem 0">The survey gave <b>{{ number_format($limits['base']['kcal']) }} kcal</b> and <b>{{ $limits['base']['protein'] }} g</b> protein. You can go wider than the member can. Once you save, the member can no longer change their targets.</p>
      <form method="post" action="{{ route('admin.diets.targets', $diet) }}">@csrf
        <div class="grid g3">
          <div class="f"><label for="t-k">Calories a day</label><input id="t-k" type="number" name="kcal" value="{{ old('kcal', $tg['kcal']) }}" min="{{ $limits['kcal'][0] }}" max="{{ $limits['kcal'][1] }}" required><p class="small muted">{{ number_format($limits['kcal'][0]) }} to {{ number_format($limits['kcal'][1]) }}</p></div>
          <div class="f"><label for="t-p">Protein, g</label><input id="t-p" type="number" name="protein" value="{{ old('protein', $tg['protein']) }}" min="{{ $limits['protein'][0] }}" max="{{ $limits['protein'][1] }}" required><p class="small muted">{{ $limits['protein'][0] }} to {{ $limits['protein'][1] }}</p></div>
          <div class="f"><label for="t-f">Fat, % of calories</label><input id="t-f" type="number" name="fat_pct" value="{{ old('fat_pct', $tg['fat_pct']) }}" min="{{ $limits['fat_pct'][0] }}" max="{{ $limits['fat_pct'][1] }}" required><p class="small muted">{{ $limits['fat_pct'][0] }} to {{ $limits['fat_pct'][1] }}</p></div>
        </div>
        <div class="row"><button class="btn">Save and rebuild the week</button>@if ($ov)<button class="btn ghost" form="tgtreset">Back to survey numbers</button>@endif</div>
      </form>
      @if ($ov)<form method="post" action="{{ route('admin.diets.targets.reset', $diet) }}" id="tgtreset">@csrf</form>@endif
    </details>
  @endif
  @include('partials.diet_plan', ['plan' => $diet->plan, 'day' => max(1, min(7, (int) request('day', 1))), 'swappable' => false, 'ui' => null, 'tabsUrl' => fn ($i) => route('admin.diets.show', [$diet, 'day' => $i])])
  @if ($dishes)
    <div class="card mt"><h2 style="margin:0 0 .6rem">Weekly check-ins</h2>
      @forelse ($checkins as $c)
        <p class="small" style="margin:.3rem 0"><b>{{ $c->week_of->format('d M') }}</b> · {{ $c->weight_kg }} kg · followed the plan {{ $c->followed }} · hunger {{ $c->hunger }} · energy {{ $c->energy }} · {{ $c->action }} ({{ number_format($c->kcal_before) }} → {{ number_format($c->kcal_after) }} kcal)@if ($c->note) <br><span class="muted">"{{ $c->note }}"</span>@endif</p>
      @empty <p class="muted small">No check-ins yet.</p> @endforelse
    </div>
  @endif
@endif
@endsection
