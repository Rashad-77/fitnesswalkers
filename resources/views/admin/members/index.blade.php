@extends('layouts.admin')
@section('title', 'Members')
@section('content')
<div class="row between mb"><h1>Members</h1><a class="btn" href="{{ route('admin.members.create') }}">+ Add member</a></div>
<div class="chips">
  @foreach ($filters as $k => $label)
    <a class="chip {{ $filter === $k ? 'on' : '' }}" href="{{ route('admin.members.index', array_filter(['filter' => $k === 'all' ? null : $k, 'q' => $q])) }}">{{ $label }} <span class="n">{{ $counts[$k] }}</span></a>
  @endforeach
</div>
<form class="filters" method="get">
  @if ($filter !== 'all')<input type="hidden" name="filter" value="{{ $filter }}">@endif
  <input type="search" name="q" value="{{ $q }}" placeholder="Search name, phone or member number">
  <button class="btn dark">Search</button>
</form>
<div class="card"><div class="tw"><table>
  @php
    $th = fn ($key, $label, $cls = '') => '<th class="'.$cls.'"><a class="sortlink '.($sort === $key ? 'on' : '').'" href="'.e(route('admin.members.index', array_filter(['filter' => $filter === 'all' ? null : $filter, 'q' => $q, 'sort' => $key, 'dir' => ($sort === $key && $dir === 'asc') ? 'desc' : ($key === 'balance' || $key === 'visit' ? 'desc' : 'asc')]))).'">'.$label.($sort === $key ? ($dir === 'asc' ? ' &#9650;' : ' &#9660;') : '').'</a></th>';
  @endphp
  <tr>{!! $th('no', 'No.') !!}{!! $th('name', 'Name') !!}{!! $th('phone', 'Phone') !!}{!! $th('package', 'Package') !!}{!! $th('status', 'Status') !!}{!! $th('ends', 'Ends') !!}{!! $th('visit', 'Last visit') !!}{!! $th('balance', 'Balance due', 'right') !!}</tr>
  @forelse ($members as $m)
    @php $lv = $last[$m->id] ?? null; @endphp
    <tr class="{{ $m->is_dummy ? 'dummy' : '' }}">
      <td class="nowrap">{{ $m->member_no }}</td>
      <td><a href="{{ route('admin.members.show', $m) }}">{{ $m->user->name }}</a></td>
      <td class="nowrap">{{ $m->user->phone }}</td>
      <td>{{ $m->currentMembership?->plan->name ?? '-' }}</td>
      <td><span class="b {{ $m->status() }}">{{ $m->status() === 'none' ? 'no package' : $m->status() }}</span></td>
      <td class="nowrap">{{ $m->currentMembership?->ends_on->format('d M Y') ?? '-' }}</td>
      <td class="nowrap">{{ $lv ? \Carbon\Carbon::parse($lv)->diffForHumans() : 'never' }}</td>
      <td class="right">{{ $m->balanceDue() > 0 ? rs($m->balanceDue()) : '-' }}</td>
    </tr>
  @empty <tr><td colspan="8" class="empty">No members match.</td></tr> @endforelse
</table></div></div>
@endsection
