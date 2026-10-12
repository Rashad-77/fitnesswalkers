@extends('layouts.admin')
@section('title', 'Sample data')
@section('content')
<h1>Sample data</h1>
<p class="muted">Everything the app was filled with for testing is marked <span class="b dummy">sample</span> (and has a yellow edge in tables). <strong>When you edit a sample row and save it, it becomes real.</strong> The full list is also in the file <code>DUMMY_DATA.md</code>.</p>
<div class="grid g2">
<div class="card"><h2>What is still sample data</h2><table>
  @foreach ($labels as $t => $label)<tr><td>{{ $label }}</td><td class="right"><span class="b {{ ($counts[$t] ?? 0) ? 'dummy' : 'done' }}">{{ $counts[$t] ?? 0 }}</span></td></tr>@endforeach
</table></div>
<div>
  <div class="card mb"><h2>Settings still on sample values</h2>
    @forelse ($dummySettings as $k)<div class="small">• {{ $fields[$k] ?? $k }}</div>@empty<div class="small">All real. 👍</div>@endforelse
    <p class="small mt"><a href="{{ route('admin.settings') }}">Go to Gym profile</a></p></div>
  <div class="card mb"><h2>Remaining setup tasks</h2><ul class="small">
    <li>Record real exercise clips (the exercises have no video yet).</li>
    <li>Check every food price and nutrition value.</li>
    <li>Swap the logo file for a clean image (<code>public/img/logo-banner.png</code>).</li>
    <li>Replace sample package prices and lengths.</li></ul></div>
  <div class="card"><h2>Remove ALL sample data</h2>
    <p class="small">Deletes every sample member, payment, exercise, food and so on. Real data is kept. Do this when you are ready to go live. <strong>Back up first</strong> (Data export).</p>
    <form method="post" action="{{ route('admin.placeholders.purge') }}">@csrf
      <div class="f"><label>Type DELETE to confirm</label><input type="text" name="confirm" autocomplete="off"></div>
      <button class="btn red" data-confirm="This deletes all sample data. Continue?">Remove sample data</button></form></div>
</div></div>
@endsection
