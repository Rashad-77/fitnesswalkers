@extends('layouts.admin')
@section('title', 'Meal plans')
@section('content')
<div class="row between mb"><h1>Meal plans</h1><a class="btn" href="{{ route('admin.diets.create') }}">+ Create meal plan</a></div>
<p class="muted">Every app-made plan is visible here. Write a plan yourself and it replaces the app's plan for that member.</p>
<div class="card"><div class="tw"><table>
  <tr><th>Member</th><th>Plan</th><th>Made by</th><th>Calories/day</th><th>Needs a look</th><th>Active</th><th>Date</th></tr>
  @forelse ($plans as $p)
    <tr class="{{ $p->is_dummy ? 'dummy' : '' }}"><td>{{ $p->member->user->name }}</td><td><a href="{{ route('admin.diets.show', $p) }}">{{ $p->title }}</a></td><td>{{ $p->source === 'owner' ? 'You' : 'App' }}</td>
      <td>{{ $p->source === 'app' && ! empty($p->plan['targets']['kcal']) ? number_format($p->plan['targets']['kcal']).' kcal' : '-' }}</td>
      <td>@forelse ($flags[$p->id] ?? [] as $f)<span class="b due">{{ $f['text'] }}</span> @empty - @endforelse</td><td>{!! $p->active ? '<span class="b active">active</span>' : '<span class="b none">old</span>' !!}</td><td>{{ $p->created_at->format('d M Y') }}</td></tr>
  @empty <tr><td colspan="7" class="empty">No meal plans yet.</td></tr> @endforelse
</table></div></div>
@endsection
