@extends('layouts.admin')
@section('title', 'Packages')
@section('content')
<div class="row between mb"><h1>Packages</h1><a class="btn" href="{{ route('admin.plans.create') }}">+ Add package</a></div>
<div class="card"><div class="tw"><table>
  <tr><th>Name</th><th>Length</th><th class="right">Price</th><th class="right">Per month</th><th class="right">Members</th><th>Status</th><th></th></tr>
  @foreach ($plans as $p)
    <tr class="{{ $p->is_dummy ? 'dummy' : '' }}"><td>{{ $p->name }} @if ($p->featured)<span class="b active">most popular</span>@endif {!! dummy_tag($p) !!}</td><td>{{ $p->duration_days }} days</td><td class="right">{{ rs($p->price) }}</td><td class="right muted">{{ rs($p->perMonth()) }}</td><td class="right">{{ $counts[$p->id] ?? 0 }}</td>
      <td><span class="b {{ $p->active ? 'active' : 'none' }}">{{ $p->active ? 'on sale' : 'hidden' }}</span></td>
      <td class="right nowrap"><a class="btn ghost sm" href="{{ route('admin.plans.edit', $p) }}">Edit</a>
        <form class="inline" method="post" action="{{ route('admin.plans.destroy', $p) }}">@csrf @method('DELETE')<button class="btn ghost sm" data-confirm="Delete this package?">Delete</button></form></td></tr>
  @endforeach
</table></div></div>
@endsection
