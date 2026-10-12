@extends('layouts.admin')
@section('title', 'Gym profile')
@section('content')
<h1>Gym profile</h1>
<div class="grid g2">
<form class="card" method="post" action="{{ route('admin.settings.save') }}">@csrf @method('PUT')
  <h2>Gym details</h2><p class="small muted">Shown on receipts and in the member app. Items marked <span class="b dummy">sample</span> are still placeholders.</p>
  @foreach ($fields as $k => $label)
    <div class="f"><label>{{ $label }} {!! in_array($k, $dummy) ? '<span class="b dummy">sample</span>' : '' !!}</label>
      @if ($k === 'about_text')<textarea name="{{ $k }}" rows="4">{{ old($k, \App\Models\Setting::get($k)) }}</textarea>@else<input type="text" name="{{ $k }}" value="{{ old($k, \App\Models\Setting::get($k)) }}">@endif</div>
  @endforeach
  <button class="btn">Save</button>
</form>

<div class="card" id="photos"><h2>Website photos</h2>
  <p class="small muted">The public home page has spots for your photos. Put a photo in the <code>public/img/gym</code> folder, named as below (jpg, png or webp), and it appears on the website by itself. Keep each file under about 400 KB.</p>
  @php $slots = gym_slots(); $have = collect($slots)->filter(fn ($v, $k) => gym_img($k))->count(); @endphp
  <p class="small"><b>{{ $have }} of {{ count($slots) }}</b> spots have a photo.</p>
  <div class="tw"><table><tr><th>Spot</th><th>File name</th><th>Shape</th><th></th></tr>
    @foreach ($slots as $k => [$what, $shape])
      <tr><td>{{ $what }}</td><td><code>{{ $k }}.jpg</code></td><td class="small muted">{{ $shape }}</td><td>{!! gym_img($k) ? '<span class="b done">added</span>' : '<span class="b pending">empty</span>' !!}</td></tr>
    @endforeach
  </table></div></div>
</div>
@endsection
