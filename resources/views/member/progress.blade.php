@extends('layouts.member')
@section('title', 'Progress')
@section('content')
<h1>Progress</h1>
@if ($latest && $first && $body->count() > 1)
  @php $diff = round($latest->weight_kg - $first->weight_kg, 1); @endphp
  <div class="grid g2 mb" style="grid-template-columns:1fr 1fr">
    <div class="stat lime"><div class="n">{{ $latest->weight_kg }} kg</div><div class="l">Current weight</div></div>
    <div class="stat"><div class="n">{{ $diff > 0 ? '+' : '' }}{{ $diff }} kg</div><div class="l">Since {{ $first->logged_on->format('d M') }}</div></div>
  </div>
@endif
<div class="card mb"><h2>Weight</h2>@include('partials.chart_line', ['points' => $body->mapWithKeys(fn ($b) => [$b->logged_on->format('d M') => (float) $b->weight_kg])->all(), 'unit' => ' kg'])</div>
<div class="card mb"><h2>Log measurements</h2>
  <form method="post" action="{{ route('member.progress.body') }}">@csrf
    <div class="grid g2" style="grid-template-columns:1fr 1fr">
      <div class="f"><label>Weight (kg) *</label><input type="number" step="0.1" inputmode="decimal" name="weight_kg" required></div>
      <div class="f"><label>Waist (cm)</label><input type="number" step="0.1" inputmode="decimal" name="waist_cm"></div>
      <div class="f"><label>Chest (cm)</label><input type="number" step="0.1" inputmode="decimal" name="chest_cm"></div>
      <div class="f"><label>Arm (cm)</label><input type="number" step="0.1" inputmode="decimal" name="arm_cm"></div>
    </div><button class="btn block">Save</button></form>
  @if ($body->count())<details class="mt"><summary class="small">History</summary>
    @foreach ($body->reverse()->take(15) as $b)<div class="row between small" style="padding:.25rem 0"><span>{{ $b->logged_on->format('d M Y') }}</span><span>{{ $b->weight_kg }} kg{{ $b->waist_cm ? ' · waist '.$b->waist_cm : '' }}{{ $b->chest_cm ? ' · chest '.$b->chest_cm : '' }}{{ $b->arm_cm ? ' · arm '.$b->arm_cm : '' }}</span></div>@endforeach</details>@endif
</div>
<div class="card mb"><h2>Consistency (workout days per week)</h2>
  @include('partials.chart_bars', ['labels' => array_keys($weeks), 'series' => [['name' => 'Days', 'color' => '#7cb82f', 'data' => array_map('floatval', array_values($weeks))]]])</div>
<div class="card mb"><h2>Strength</h2>
  @if ($exercises->count())
    <form method="get" class="f"><select name="exercise" onchange="this.form.submit()">@foreach ($exercises as $e)<option value="{{ $e->id }}" @selected($e->id === $exId)>{{ $e->name }}</option>@endforeach</select></form>
    <div class="small muted">Heaviest weight each session (kg)</div>
    @include('partials.chart_line', ['points' => $liftSeries->all(), 'unit' => ' kg'])
  @else<div class="empty">Log workouts to see your lifts here.</div>@endif
</div>
<div class="card mb" id="bmi"><h2>BMI</h2>
  @php $h = old('height_cm', $lastBmi?->height_cm ?? $m->height_cm); $w = old('weight_kg', $lastBmi?->weight_kg ?? $latest?->weight_kg); @endphp
  @if ($lastBmi)
    <div class="center"><div class="small muted">Latest, {{ $lastBmi->logged_on->format('d M') }}</div>
      <div style="font-size:2.4rem;font-weight:800;line-height:1.1">{{ $lastBmi->bmi }}</div>
      <span class="b {{ $lastBmi->bmi < 18.5 ? 'pending' : ($lastBmi->bmi < 23 ? 'active' : 'expiring') }}">{{ \App\Services\Bmi::category((float) $lastBmi->bmi) }}</span></div>
  @endif
  <form method="post" action="{{ route('member.bmi.save') }}" class="mt">@csrf
    <div class="grid g2" style="grid-template-columns:1fr 1fr">
      <div class="f"><label>Weight (kg)</label><input type="number" step="0.1" inputmode="decimal" name="weight_kg" value="{{ $w }}" required></div>
      <div class="f"><label>Height (cm)</label><input type="number" step="0.1" inputmode="decimal" name="height_cm" value="{{ $h }}" required></div>
    </div><button class="btn ghost block">Calculate BMI</button></form>
  <p class="disc">BMI is only a rough guide. It cannot tell muscle from fat. The ranges used here are the Asian cut-offs (healthy 18.5 to 22.9). They do not apply to children and teenagers. If you are worried about your weight, talk to the owner or a doctor.</p>
</div>
<div class="card"><h2>Goals</h2>
  @foreach ($goals as $g)
    <div class="row between" style="padding:.4rem 0;border-bottom:1px solid #eef0ea">
      <span style="{{ $g->done ? 'text-decoration:line-through;color:#6b7380' : '' }}">{{ $g->title }}{{ $g->target_value ? ' ('.rtrim(rtrim((string) $g->target_value, '0'), '.').' '.$g->unit.')' : '' }}<br><span class="small muted">{{ $g->target_date ? 'by '.$g->target_date->format('d M Y') : '' }}</span></span>
      <form method="post" action="{{ route('member.progress.goal.toggle', $g) }}">@csrf<button class="btn ghost sm">{{ $g->done ? 'Undo' : '✓ Done' }}</button></form></div>
  @endforeach
  <form method="post" action="{{ route('member.progress.goal') }}" class="mt">@csrf
    <div class="f"><label>New goal</label><input type="text" name="title" placeholder="e.g. Reach 70 kg" required></div>
    <div class="grid g3" style="grid-template-columns:1fr 1fr 1fr"><div class="f"><label>Target</label><input type="number" step="0.1" name="target_value"></div>
      <div class="f"><label>Unit</label><input type="text" name="unit" placeholder="kg"></div><div class="f"><label>By</label><input type="date" name="target_date" min="{{ today()->toDateString() }}"></div></div>
    <button class="btn ghost block">Add goal</button></form>
</div>
@endsection
