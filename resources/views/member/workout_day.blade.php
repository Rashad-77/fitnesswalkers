@extends('layouts.member')
@section('title', 'Day '.$day)
@section('content')
<a class="small" href="{{ route('member.workout') }}">‹ Back</a>
<h1>Day {{ $day }}</h1><p class="small muted">{{ $plan->name }}. Enter what you lifted, then save at the bottom.</p>
<form method="post" action="{{ route('member.workout.log') }}">@csrf
@foreach ($items as $it)
  @php $prev = $last[$it->exercise_id]; $todays = $today[$it->exercise_id] ?? collect(); @endphp
  <div class="card mb">
    <div class="row between"><h2 style="margin:0">@if ($it->exercise->isAvailable())<a href="{{ route('member.exercise', $it->exercise) }}">{{ $it->exercise->name }}</a>@else{{ $it->exercise->name }}@endif</h2><span class="b">{{ $it->sets }} x {{ $it->reps }}</span></div>
    @unless ($it->exercise->isAvailable())<div class="alert warn" style="margin-top:.5rem">⚠ This equipment is out of order right now. Swap it for another exercise below.</div>@endunless
    @if ($prev->count())<p class="small muted" style="margin:.3rem 0">Last time: {{ $prev->map(fn ($s) => $s->reps.'×'.rtrim(rtrim((string) $s->weight_kg, '0'), '.').'kg')->implode(', ') }}</p>@endif
    <div class="tw"><table>
      <tr><th>Set</th><th>Reps</th><th>Weight (kg)</th></tr>
      @for ($s = 1; $s <= $it->sets; $s++)
        @php $t = $todays->firstWhere('set_no', $s); $p = $prev->firstWhere('set_no', $s); @endphp
        <tr><td>{{ $s }}</td>
          <td><input type="number" inputmode="numeric" min="0" max="500" name="sets[{{ $it->exercise_id }}][{{ $s }}][reps]" value="{{ $t?->reps }}" placeholder="{{ $p?->reps }}"></td>
          <td><input type="number" inputmode="decimal" step="0.5" min="0" name="sets[{{ $it->exercise_id }}][{{ $s }}][weight]" value="{{ $t ? rtrim(rtrim((string) $t->weight_kg, '0'), '.') : '' }}" placeholder="{{ $p ? rtrim(rtrim((string) $p->weight_kg, '0'), '.') : '' }}"></td></tr>
      @endfor
    </table></div>
    @if ($alts[$it->id]->count())
    <details class="mt" @if (! $it->exercise->isAvailable()) open @endif><summary class="small">Can't do this one? Swap it</summary>
      <div class="row mt">
        <select name="exercise_id" form="swap-{{ $it->id }}" style="flex:1">@foreach ($alts[$it->id] as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
        <button class="btn ghost sm" form="swap-{{ $it->id }}" data-confirm="Swap this exercise in your plan? (Save any numbers you entered first.)">Swap</button></div></details>
    @endif
  </div>
@endforeach
<button class="btn block">Save workout</button>
</form>
@foreach ($items as $it)<form id="swap-{{ $it->id }}" method="post" action="{{ route('member.workout.swap', $it) }}">@csrf</form>@endforeach
@endsection
