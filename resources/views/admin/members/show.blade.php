@extends('layouts.admin')
@section('title', $member->user->name)
@section('content')
@php
  $ms = $member->currentMembership; $st = $member->status();
  $tabs = ['overview' => 'Overview', 'payments' => 'Payments', 'workout' => 'Workout', 'meals' => 'Meals', 'progress' => 'Progress'];
@endphp
<div class="row between mb">
  <div><h1>{{ $member->user->name }} {!! dummy_tag($member) !!}</h1>
    <span class="muted">{{ $member->member_no }} · {{ $member->user->phone ?: 'no phone' }} · {{ $member->user->email }}</span></div>
  <div class="row">
    @if ($wa)<a class="btn ghost" target="_blank" rel="noopener" href="{{ $wa }}">WhatsApp</a>@endif
    <form method="post" action="{{ route('admin.members.checkin', $member) }}">@csrf<button class="btn">{{ $member->checkedInToday() ? 'Checked in today' : 'Check in' }}</button></form>
    <a class="btn ghost" href="{{ route('admin.members.edit', $member) }}">Edit</a>
    <form method="post" action="{{ route('admin.members.freeze', $member) }}">@csrf<button class="btn ghost">{{ $member->frozen ? 'Unfreeze' : 'Freeze' }}</button></form>
  </div>
</div>
<div class="grid g4 mb">
  <div class="stat"><div class="n"><span class="b {{ $st }}">{{ $st === 'none' ? 'no package' : $st }}</span></div><div class="l">Status</div></div>
  <div class="stat"><div class="n">{{ $ms ? ($member->daysLeft() < 0 ? 'Expired' : $member->daysLeft().' days') : '-' }}</div><div class="l">{{ $ms ? 'Ends '.$ms->ends_on->format('d M Y').' · '.$ms->plan->name : 'No package yet' }}</div></div>
  <div class="stat {{ $member->balanceDue() > 0 ? 'warn' : '' }}"><div class="n">{{ rs($member->balanceDue()) }}</div><div class="l">Balance due</div></div>
  <div class="stat"><div class="n">{{ $lastVisit ? \Carbon\Carbon::parse($lastVisit)->diffForHumans() : 'never' }}</div><div class="l">Last visit · {{ $member->visitsThisMonth() }} this month</div></div>
</div>
<div class="ptabs">
  @foreach ($tabs as $k => $label)<a class="{{ $tab === $k ? 'on' : '' }}" href="{{ route('admin.members.show', [$member, 'tab' => $k === 'overview' ? null : $k]) }}">{{ $label }}</a>@endforeach
</div>

@if ($tab === 'overview')
<div class="grid g2">
  <div class="card"><h2>Details</h2>
    <p class="small">Sex: {{ $member->sex ?: '-' }} · Age: {{ $member->age() ?? '-' }} · Height: {{ $member->height_cm ? $member->height_cm.' cm' : '-' }}</p>
    <p class="small">Joined: {{ $member->joined_on->format('d M Y') }}<br>Address: {{ $member->address ?: '-' }}<br>Emergency contact: {{ $member->emergency_contact ?: '-' }}</p>
    <p class="small"><strong>Notes:</strong> {{ $member->notes ?: '-' }}</p>
    <h3 class="mt">Reset password</h3>
    <form method="post" action="{{ route('admin.members.password', $member) }}" class="row">@csrf
      <input type="text" name="password" placeholder="New password (8+)" minlength="8" required style="flex:1"><button class="btn ghost">Reset</button></form>
    <form class="mt" method="post" action="{{ route('admin.members.destroy', $member) }}" data-confirm="Delete {{ $member->user->name }} and ALL their records? This cannot be undone.">@csrf @method('DELETE')<button class="btn red">Delete member</button></form>
  </div>
  <div class="card"><h2>Health form and terms</h2>
    <p>Terms: @if ($member->waiver_accepted_at)<span class="b active">agreed {{ $member->waiver_accepted_at->format('d M Y') }}</span>@else<span class="b pending">not recorded</span>@endif</p>
    @if ($member->health_answers)
      @if ($member->health_flag)<div class="alert warn">Some answers were "yes". Check with them before hard training.</div>@endif
      @foreach ($questions as $k => $text)<div class="small" style="padding:.2rem 0">{{ ($member->health_answers[$k] ?? false) ? 'Yes' : 'No' }} · {{ $text }}</div>@endforeach
      @if ($member->health_notes)<p class="small mt"><strong>Notes:</strong> {{ $member->health_notes }}</p>@endif
    @else
      <p class="muted small">No health form on file. <a href="{{ route('admin.members.edit', $member) }}">Complete the form</a>.</p>
    @endif
  </div>
</div>

@elseif ($tab === 'payments')
<div class="grid g2">
  <div class="card"><h2>Add or renew a package</h2>
    <form method="post" action="{{ route('admin.members.memberships.store', $member) }}">@csrf
      <div class="grid g2">
        <div class="f"><label>Package</label><select name="plan_id" required>@foreach ($plans as $p)<option value="{{ $p->id }}">{{ $p->name }} - {{ rs($p->price) }}</option>@endforeach</select></div>
        <div class="f"><label>Starts on</label><input type="date" name="starts_on" value="{{ $ms && $ms->ends_on->isFuture() ? $ms->ends_on->copy()->addDay()->toDateString() : today()->toDateString() }}" required></div>
        <div class="f"><label>Price (change for a discount)</label><input type="number" step="0.01" name="price" placeholder="Package price"></div>
        <div class="f"><label>Paid now (Rs.)</label><input type="number" step="0.01" min="0" name="initial_payment"></div>
      </div>
      <div class="f"><label>Paid by</label><select name="method">@foreach (['cash','bank','card'] as $mt)<option value="{{ $mt }}">{{ ucfirst($mt) }}</option>@endforeach</select></div>
      <button class="btn">Add package</button>
    </form>
  </div>
  <div class="card"><h2>Record a payment</h2>
    @php $open = $member->memberships->filter(fn ($x) => $x->balance() > 0); @endphp
    <form method="post" action="{{ route('admin.payments.store') }}">@csrf
      <input type="hidden" name="member_id" value="{{ $member->id }}">
      <div class="f"><label>For</label><select name="membership_id"><option value="">General payment</option>@foreach ($member->memberships as $x)<option value="{{ $x->id }}" @selected($open->first()?->id === $x->id)>{{ $x->plan->name }} {{ $x->starts_on->format('d M Y') }} - balance {{ rs($x->balance()) }}</option>@endforeach</select></div>
      <div class="grid g2">
        <div class="f"><label>Amount (Rs.)</label><input type="number" step="0.01" min="1" name="amount" required></div>
        <div class="f"><label>Method</label><select name="method">@foreach (['cash','bank','card'] as $mt)<option value="{{ $mt }}">{{ ucfirst($mt) }}</option>@endforeach</select></div>
        <div class="f"><label>Date</label><input type="date" name="paid_on" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}"></div>
        <div class="f"><label>Note</label><input type="text" name="note"></div>
      </div>
      <button class="btn dark">Save and print receipt</button>
    </form>
  </div>
</div>
<div class="grid g2 mt">
  <div class="card"><h2>Packages</h2><div class="tw"><table>
    <tr><th>Package</th><th>From</th><th>To</th><th class="right">Price</th><th class="right">Paid</th></tr>
    @forelse ($member->memberships as $x)<tr><td>{{ $x->plan->name }}</td><td class="nowrap">{{ $x->starts_on->format('d M Y') }}</td><td class="nowrap">{{ $x->ends_on->format('d M Y') }}</td><td class="right">{{ rs($x->price) }}</td><td class="right">{{ rs($x->paid()) }}</td></tr>
    @empty <tr><td colspan="5" class="empty">None yet.</td></tr> @endforelse
  </table></div></div>
  <div class="card"><h2>Payments</h2><div class="tw"><table>
    <tr><th>Receipt</th><th>Date</th><th>Method</th><th class="right">Amount</th></tr>
    @forelse ($member->payments as $p)<tr><td><a href="{{ route('admin.payments.show', $p) }}">{{ $p->receipt_no }}</a></td><td class="nowrap">{{ $p->paid_on->format('d M Y') }}</td><td>{{ $p->method }}</td><td class="right">{{ rs($p->amount) }}</td></tr>
    @empty <tr><td colspan="4" class="empty">No payments yet.</td></tr> @endforelse
  </table></div></div>
</div>

@elseif ($tab === 'workout')
@php $ap = $member->workoutPlans->firstWhere('status', 'approved'); @endphp
<div class="card"><h2>Workout plan</h2>
  <p>{!! $ap ? '<span class="b approved">approved</span> '.e($ap->name) : '<span class="muted">No approved plan yet.</span>' !!}</p>
  @forelse ($member->planRequests->where('status', 'pending') as $rq)
    <p class="small">They asked for a plan {{ $rq->created_at->diffForHumans() }}. <a class="btn sm" href="{{ route('admin.requests.show', $rq) }}">Build plan</a></p>
  @empty @unless ($ap)<p class="small muted">They have not asked for one. You can build one from <a href="{{ route('admin.templates.index') }}">a template</a>.</p>@endunless @endforelse
  @if ($member->health_flag)<div class="alert warn">Their health form has "yes" answers. Keep it gentle until you have spoken with them.</div>@endif
</div>

@elseif ($tab === 'meals')
@php $dp = $member->dietPlans->firstWhere('active', true); @endphp
<div class="card"><h2>Meal plan</h2>
  <p class="small">{!! $dp ? '<a href="'.route('admin.diets.show', $dp).'">'.e($dp->title).'</a> <span class="muted">('.e($dp->source).')</span>' : '<span class="muted">No meal plan yet.</span>' !!}</p>
  <a class="btn" href="{{ route('admin.diets.create', ['member' => $member->id]) }}">Create meal plan</a>
</div>

@else
<div class="grid g2">
  <div class="card"><h2>Measurements</h2>
    @forelse ($bodyLogs as $b)<div class="small" style="padding:.2rem 0">{{ $b->logged_on->format('d M') }}: {{ $b->weight_kg }} kg{{ $b->bmi ? ' · BMI '.$b->bmi : '' }}{{ $b->waist_cm ? ' · waist '.$b->waist_cm.' cm' : '' }}</div>@empty <div class="small muted">None logged.</div> @endforelse
    <h3 class="mt">Goals</h3>
    @forelse ($goals as $g)<div class="small">{{ $g->done ? 'Done · ' : '' }}{{ $g->title }}{{ $g->target_value ? ' ('.rtrim(rtrim($g->target_value, '0'), '.').' '.$g->unit.')' : '' }}</div>@empty <div class="small muted">No goals set.</div> @endforelse
  </div>
  <div class="card"><h2>Recent visits</h2>
    @forelse ($visits as $v)<div class="small" style="padding:.2rem 0">{{ $v->visited_on->format('D d M') }} <span class="muted">· {{ $v->source === 'member' ? 'scanned the QR' : 'checked in by you' }}</span></div>@empty <div class="small muted">No visits recorded.</div> @endforelse
  </div>
</div>
@endif
@endsection
