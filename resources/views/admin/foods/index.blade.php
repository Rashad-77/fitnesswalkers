@extends('layouts.admin')
@section('title', 'Foods')
@section('content')
<div class="row between mb"><div><h1>Foods</h1><p class="muted" style="margin:0">The Sri Lankan food list behind members' meal plans. Switch off anything members cannot buy, or add a local food.</p></div>
  <a class="btn" href="#form">+ Add a food</a></div>
<div class="alert info">Calories and protein are <b>about</b> values for one household serving. Change any that you know better. Foods that are switched off are never put into new plans.@if ($off) <b>{{ $off }}</b> {{ $off === 1 ? 'food is' : 'foods are' }} off now.@endif</div>

<form class="filters" method="get"><input type="search" name="q" value="{{ $q }}" placeholder="Search foods">
  <select name="group"><option value="">All food groups</option>@foreach ($groups as $k => $v)<option value="{{ $k }}" @selected($group === $k)>{{ $v }} ({{ $counts[$k] ?? 0 }})</option>@endforeach</select><button class="btn dark">Filter</button></form>

<div class="card mb"><div class="tw"><table>
  <tr><th>Food</th><th>Group</th><th>One serving</th><th class="right">kcal</th><th class="right">Protein</th><th>Notes</th><th class="center">On</th><th></th></tr>
  @forelse ($foods as $f)
    <tr class="{{ $f->active ? '' : 'foodoff' }}"><td><b>{{ $f->name }}</b>{!! dummy_tag($f) !!}@if ($f->local_name)<div class="small muted">{{ $f->local_name }}</div>@endif</td>
      <td class="small">{{ \App\Models\Food::SHORT[$f->grp] ?? $f->grp }}@if ($f->sub)<div class="muted">{{ $f->sub }}</div>@endif</td>
      <td class="small nowrap">{{ $f->qty(1) }}@if ($f->serving_g) <span class="muted">({{ rtrim(rtrim(number_format($f->serving_g, 1), '0'), '.') }} g)</span>@endif</td>
      <td class="right">{{ $f->kcal_serving }}</td><td class="right">{{ $f->protein_serving }} g</td>
      <td class="small muted">{{ \App\Models\Food::DIETS[$f->diet] ?? $f->diet }}@if ($f->allergenList()) · {{ implode(', ', $f->allergenList()) }}@endif · {{ implode(', ', $f->mealTagList()) }}</td>
      <td class="center"><form method="post" action="{{ route('admin.foods.update', $f) }}">@csrf @method('PUT')<input type="hidden" name="toggle" value="1"><button class="switch {{ $f->active ? 'on' : '' }}" title="{{ $f->active ? 'Switch off' : 'Switch on' }}" aria-label="{{ $f->active ? 'Switch off' : 'Switch on' }} {{ $f->name }}"><i></i></button></form></td>
      <td class="right nowrap"><a class="btn ghost sm" href="{{ route('admin.foods.index', ['edit' => $f->id]) }}#form">Edit</a>
        <form class="inline" method="post" action="{{ route('admin.foods.destroy', $f) }}">@csrf @method('DELETE')<button class="btn ghost sm" data-confirm="Delete {{ $f->name }}?">Delete</button></form></td></tr>
  @empty <tr><td colspan="8" class="empty">No foods match.</td></tr> @endforelse
</table></div></div>

<div class="card" id="form"><h2>{{ $edit ? 'Edit '.$edit->name : 'Add a food' }}</h2>
  <form method="post" action="{{ $edit ? route('admin.foods.update', $edit) : route('admin.foods.store') }}">@csrf @if ($edit) @method('PUT') @endif
    @php $f = $edit; $al = $f ? $f->allergenList() : []; $tg = $f ? $f->mealTagList() : ['main']; $tags = $f ? $f->tagList() : []; @endphp
    <div class="grid g4">
      <div class="f"><label>Name</label><input type="text" name="name" value="{{ old('name', $f->name ?? '') }}" required></div>
      <div class="f"><label>Local name (optional)</label><input type="text" name="local_name" value="{{ old('local_name', $f->local_name ?? '') }}"></div>
      <div class="f"><label>Food group</label><select name="grp">@foreach ($groups as $k => $v)<option value="{{ $k }}" @selected(old('grp', $f->grp ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
      <div class="f"><label>Kind (optional)</label><input type="text" name="sub" value="{{ old('sub', $f->sub ?? '') }}" placeholder="fish, poultry, leaf..."></div>
      <div class="f"><label>One serving is</label><input type="number" step="0.01" name="per_serving" value="{{ old('per_serving', $f->per_serving ?? '') }}" required placeholder="e.g. 0.5"></div>
      <div class="f"><label>Unit</label><input type="text" name="unit" value="{{ old('unit', $f->unit ?? '') }}" required placeholder="cup"></div>
      <div class="f"><label>Unit, plural</label><input type="text" name="unit_plural" value="{{ old('unit_plural', $f->unit_plural ?? '') }}" placeholder="cups"></div>
      <div class="f"><label>Grams per serving</label><input type="number" step="0.1" name="serving_g" value="{{ old('serving_g', $f->serving_g ?? '') }}"></div>
      <div class="f"><label>Calories per serving</label><input type="number" name="kcal_serving" value="{{ old('kcal_serving', $f->kcal_serving ?? '') }}" required></div>
      <div class="f"><label>Protein g per serving</label><input type="number" step="0.1" name="protein_serving" value="{{ old('protein_serving', $f->protein_serving ?? '') }}" required></div>
      <div class="f"><label>Suits</label><select name="diet">@foreach ($diets as $k => $v)<option value="{{ $k }}" @selected(old('diet', $f->diet ?? 'vegan') === $k)>{{ $v }}</option>@endforeach</select></div>
      <div class="f"><label>Note (optional)</label><input type="text" name="note" value="{{ old('note', $f->note ?? '') }}"></div>
    </div>
    <div class="f checks"><label style="display:inline">Contains:</label> @foreach (\App\Models\Food::ALLERGENS as $a)<label><input type="checkbox" name="allergens[]" value="{{ $a }}" @checked(in_array($a, $al))> {{ $a }}</label>@endforeach</div>
    <div class="f checks"><label style="display:inline">Used in:</label> @foreach ($meal_tags as $k => $v)<label><input type="checkbox" name="meal_tags[]" value="{{ $k }}" @checked(in_array($k, $tg))> {{ $v }}</label>@endforeach
      <label style="margin-left:1rem"><input type="checkbox" name="tags[]" value="beef" @checked(in_array('beef', $tags))> beef</label><label><input type="checkbox" name="tags[]" value="pork" @checked(in_array('pork', $tags))> pork</label>
      <label style="margin-left:1rem"><input type="checkbox" name="whole_grain" value="1" @checked($f?->whole_grain)> whole grain</label><label><input type="checkbox" name="limited" value="1" @checked($f?->limited)> eat less of it</label></div>
    <button class="btn">{{ $edit ? 'Save changes' : 'Add food' }}</button> @if ($edit)<a class="btn ghost" href="{{ route('admin.foods.index') }}">Cancel</a>@endif
  </form></div>
@endsection
