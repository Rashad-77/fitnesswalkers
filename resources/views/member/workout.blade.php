@extends('layouts.member')
@section('title', 'Workout')
@section('content')
<h1>Workout</h1>
@if ($plan)
  <div class="card mb"><h2>{{ $plan->name }}</h2>
    <p class="small muted">Approved {{ $plan->approved_at?->format('d M Y') }} · {{ $thisWeek }} workout day(s) logged this week</p>
    @foreach ($days as $d => $items)
      <a href="{{ route('member.workout.day', $d) }}" class="row between" style="padding:.7rem 0;border-bottom:1px solid #eef0ea;color:inherit;text-decoration:none">
        <span><strong>Day {{ $d }}</strong><br><span class="small muted">{{ $items->map(fn ($i) => $i->exercise->name)->take(4)->implode(' · ') }}{{ $items->count() > 4 ? ' ...' : '' }}</span></span><span>›</span></a>
    @endforeach
  </div>
@elseif ($pending)
  <div class="card mb"><h2>Request sent</h2>
    @if ($due > 0)<div class="alert warn"><b>Waiting for payment.</b> You still owe {{ rs($due) }}. Pay at the gym, then the owner can approve your plan.</div>@endif
    <p>The owner is putting your plan together with the sets and reps for you. You'll see it here once he approves it. Your meal plan unlocks after that.</p>
    <p class="small muted">{{ str_replace('_', ' ', $pending->goal) }} · {{ $pending->level }} · {{ $pending->days_per_week }} days a week</p></div>
@else
  @if ($lastRequest?->status === 'rejected')<div class="alert warn"><strong>Your last request was declined:</strong> {{ $lastRequest->admin_note }}</div>@endif
  <div class="card mb"><h2>Get a workout plan</h2>
    @if ($canRequest)
    <p class="small muted">The owner builds a plan just for you, with the sets and reps, and approves it after your payment. Then you can get your meal plan.</p>
    <form method="post" action="{{ route('member.workout.request') }}">@csrf
      <div class="f"><label>Your goal</label><select name="goal"><option value="general">General fitness</option><option value="fat_loss">Lose fat</option><option value="muscle_gain">Build muscle</option></select></div>
      <div class="f"><label>Experience</label><select name="level"><option value="beginner">Beginner</option><option value="intermediate">Intermediate</option></select></div>
      <div class="f"><label>Days a week you can train</label><select name="days_per_week">@foreach ([2,3,4,5,6] as $n)<option @selected($n === 3)>{{ $n }}</option>@endforeach</select></div>
      <div class="f"><label>Anything the owner should know? (injuries, preferences)</label><textarea name="notes" maxlength="500"></textarea></div>
      <button class="btn block">Submit request</button></form>
    @else<p>Your membership needs to be active to request a plan. <a href="{{ route('member.membership') }}">Renew</a></p>@endif
  </div>
@endif
<div class="card"><h2>Exercise guide</h2>
  <p class="small muted">How to do each exercise on the gym's equipment.</p>
  <div class="tabsbar"><a class="{{ ! $group ? 'on' : '' }}" href="{{ route('member.workout') }}#guide">All</a>@foreach ($groups as $g)<a class="{{ $group === $g ? 'on' : '' }}" href="{{ route('member.workout', ['group' => $g]) }}#guide">{{ $g }}</a>@endforeach</div>
  <ul class="list" id="guide">@foreach ($library as $e)<li><a href="{{ route('member.exercise', $e) }}">{{ $e->name }}</a> <span class="small muted">· {{ $e->muscle_group }}{{ $e->equipment ? ' · '.$e->equipment->name : '' }}</span></li>@endforeach</ul>
</div>
@endsection
