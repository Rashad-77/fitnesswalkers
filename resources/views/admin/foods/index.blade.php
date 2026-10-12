@extends('layouts.admin')
@section('title', 'Food prices')
@section('content')
<div class="row between mb"><h1>Food prices</h1></div>
<div class="card mb"><h2>Keeping prices up to date</h2>
  <p class="small">Prices are in Rs. per kg of the food <em>as eaten</em> (e.g. cooked rice). Every price shows a "last updated" date to members and is labelled an estimate. {{ $oldest ? 'Oldest price here: '.\Carbon\Carbon::parse($oldest)->format('d M Y').'.' : '' }}</p>
  <div class="grid g2">
    <form method="post" action="{{ route('admin.foods.import') }}" enctype="multipart/form-data">@csrf
      <label>Upload a price list (CSV: name, price per kg)</label><div class="row"><input type="file" name="file" accept=".csv,.txt" required style="flex:1"><button class="btn dark">Import</button></div>
      <p class="small muted">Names must match exactly. Changes bigger than 40% are rejected as a safety check. Locked prices are skipped.</p></form>
    <div><label>Automatic price feed</label>
      @if ($feedConfigured)<form method="post" action="{{ route('admin.foods.feed') }}">@csrf<button class="btn dark">Run price update now</button></form><p class="small muted">Also runs weekly on its own.</p>
      @else<p class="small muted">No price feed connected yet. No free live feed of Sri Lankan food prices exists, so for now update prices below or upload a CSV. A feed link can be added later in the server settings.</p>@endif</div>
  </div>
</div>
<div class="card mb"><h2>{{ $edit ? 'Edit '.$edit->name : 'Add a food' }}</h2>
  <form method="post" action="{{ $edit ? route('admin.foods.update', $edit) : route('admin.foods.store') }}">@csrf @if ($edit) @method('PUT') @endif
    @php $f = $edit; $al = $f ? $f->allergenList() : []; $tg = $f ? explode(',', $f->meal_tags) : ['main']; @endphp
    <div class="grid g4">
      <div class="f"><label>Name</label><input type="text" name="name" value="{{ old('name', $f->name ?? '') }}" required></div>
      <div class="f"><label>Type</label><select name="category">@foreach ($cats as $c)<option @selected(old('category', $f->category ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
      <div class="f"><label>Diet</label><select name="diet_type">@foreach ($types as $c)<option value="{{ $c }}" @selected(old('diet_type', $f->diet_type ?? '') === $c)>{{ str_replace('_', '-', $c) }}</option>@endforeach</select></div>
      <div class="f"><label>Price Rs. per kg</label><input type="number" step="0.01" min="1" name="price_per_kg" value="{{ old('price_per_kg', $f->price_per_kg ?? '') }}" required></div>
      <div class="f"><label>kcal per 100 g</label><input type="number" step="0.1" name="kcal_100g" value="{{ old('kcal_100g', $f->kcal_100g ?? '') }}" required></div>
      <div class="f"><label>Protein g</label><input type="number" step="0.1" name="protein_100g" value="{{ old('protein_100g', $f->protein_100g ?? '') }}" required></div>
      <div class="f"><label>Carbs g</label><input type="number" step="0.1" name="carbs_100g" value="{{ old('carbs_100g', $f->carbs_100g ?? '') }}" required></div>
      <div class="f"><label>Fat g</label><input type="number" step="0.1" name="fat_100g" value="{{ old('fat_100g', $f->fat_100g ?? '') }}" required></div>
    </div>
    <div class="f checks"><label style="display:inline">Contains:</label> @foreach (\App\Services\DietGenerator::ALLERGENS as $a)<label><input type="checkbox" name="allergens[]" value="{{ $a }}" @checked(in_array($a, $al))> {{ $a }}</label>@endforeach</div>
    <div class="f checks"><label style="display:inline">Used in:</label> @foreach (['breakfast', 'main', 'snack'] as $a)<label><input type="checkbox" name="meal_tags[]" value="{{ $a }}" @checked(in_array($a, $tg))> {{ $a }} meals</label>@endforeach
      <label><input type="checkbox" name="price_locked" value="1" @checked($f?->price_locked)> Lock price (automatic updates skip it)</label></div>
    <button class="btn">{{ $edit ? 'Save changes' : 'Add food' }}</button> @if ($edit)<a class="btn ghost" href="{{ route('admin.foods.index') }}">Cancel</a>@endif
  </form></div>
<form class="filters" method="get"><select name="category"><option value="">All types</option>@foreach ($cats as $c)<option @selected($category === $c)>{{ $c }}</option>@endforeach</select><button class="btn dark">Filter</button></form>
<div class="card"><div class="tw"><table>
  <tr><th>Food</th><th>Type</th><th>kcal</th><th>Protein</th><th>Contains</th><th>Price per kg</th><th>Updated</th><th></th></tr>
  @foreach ($foods as $f)
    <tr class="{{ $f->is_dummy ? 'dummy' : '' }}"><td>{{ $f->name }} {!! dummy_tag($f) !!}<div class="small muted">{{ str_replace('_', '-', $f->diet_type) }} · {{ $f->meal_tags }}</div></td><td>{{ $f->category }}</td><td>{{ $f->kcal_100g }}</td><td>{{ $f->protein_100g }}</td><td>{{ $f->allergens ?: '-' }}</td>
      <td><form method="post" action="{{ route('admin.foods.update', $f) }}" class="row" style="flex-wrap:nowrap">@csrf @method('PUT')<input type="hidden" name="quick" value="1">
        <input type="number" step="0.01" min="1" name="price_per_kg" value="{{ $f->price_per_kg }}" style="width:100px"><label class="checks" style="margin:0" title="Lock price"><input type="checkbox" name="price_locked" value="1" @checked($f->price_locked)>🔒</label><button class="btn ghost sm">Save</button></form></td>
      <td class="nowrap small">{{ $f->price_updated_on?->format('d M Y') ?? '-' }}</td>
      <td class="right nowrap"><a class="btn ghost sm" href="{{ route('admin.foods.index', ['edit' => $f->id]) }}">Edit</a>
        <form class="inline" method="post" action="{{ route('admin.foods.destroy', $f) }}">@csrf @method('DELETE')<button class="btn ghost sm" data-confirm="Delete {{ $f->name }}?">Delete</button></form></td></tr>
  @endforeach
</table></div></div>
@endsection
