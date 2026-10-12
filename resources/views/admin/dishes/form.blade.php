@extends('layouts.admin')
@section('title', $dish->exists ? 'Edit dish' : 'Add dish')
@section('content')
<h1>{{ $dish->exists ? 'Edit '.$dish->name : 'Add a dish' }}</h1>
@php
  $rows = old('items', $dish->exists ? $dish->items->map(fn ($i) => ['food_id' => $i->food_id, 'servings' => $i->servings + 0, 'role' => $i->role, 'scales' => $i->scales])->all() : [['food_id' => '', 'servings' => 1, 'role' => 'main', 'scales' => 1]]);
  $sl = old('slots', $dish->slotList()); $tg = old('tags', $dish->tagList());
@endphp
<form class="card mt" method="post" action="{{ $dish->exists ? route('admin.dishes.update', $dish) : route('admin.dishes.store') }}">@csrf @if ($dish->exists) @method('PUT') @endif
  @foreach ($errors->all() as $e)<div class="alert warn">{{ $e }}</div>@endforeach
  <div class="grid g3">
    <div class="f"><label>Name</label><input type="text" name="name" value="{{ old('name', $dish->name) }}" required placeholder="Red rice with fish curry and mallum"></div>
    <div class="f"><label>Local name (optional)</label><input type="text" name="local_name" value="{{ old('local_name', $dish->local_name) }}"></div>
    <div class="f"><label>Kind</label><select name="kind">@foreach ($kinds as $k => $v)<option value="{{ $k }}" @selected(old('kind', $dish->kind) === $k)>{{ $v }}</option>@endforeach</select></div>
  </div>
  <div class="f checks"><label style="display:inline">Eaten at:</label> @foreach ($slots as $k => $v)<label><input type="checkbox" name="slots[]" value="{{ $k }}" @checked(in_array($k, $sl))> {{ $v }}</label>@endforeach
    <label style="margin-left:1rem;display:inline">Tags:</label> @foreach ($tags as $k => $v)<label><input type="checkbox" name="tags[]" value="{{ $k }}" @checked(in_array($k, $tg))> {{ $v }}</label>@endforeach</div>
  <div class="f"><label>Minutes to make</label><input type="number" name="prep_minutes" value="{{ old('prep_minutes', $dish->prep_minutes) }}" style="max-width:8rem"></div>

  <h2 class="mt">What is in it</h2>
  <p class="small muted">One row per food. Amounts are in servings of that food (a serving is shown in the list). "Grows with the portion" means the amount gets bigger when the member picks a bigger portion, usually the rice and the main protein. Tick it off for things that stay the same, like a spoon of oil.</p>
  <div id="rows">
    @foreach ($rows as $n => $row)
      <div class="row dishrow" style="gap:.5rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:.5rem">
        <div class="f" style="flex:2;min-width:14rem;margin:0"><label>Food</label><select name="items[{{ $n }}][food_id]" required><option value="">Choose a food</option>
          @foreach ($foods as $f)<option value="{{ $f->id }}" @selected((int) ($row['food_id'] ?? 0) === $f->id)>{{ $f->name }} ({{ rtrim(rtrim(number_format($f->per_serving, 2), '0'), '.') }} {{ $f->unit }}, {{ $f->kcal_serving }} kcal)</option>@endforeach</select></div>
        <div class="f" style="width:7rem;margin:0"><label>Servings</label><input type="number" step="0.25" min="0.25" name="items[{{ $n }}][servings]" value="{{ $row['servings'] ?? 1 }}" required></div>
        <div class="f" style="width:9rem;margin:0"><label>Part of the meal</label><select name="items[{{ $n }}][role]">@foreach (['main' => 'Main course', 'dish' => 'Dishes', 'dessert' => 'Dessert'] as $k => $v)<option value="{{ $k }}" @selected(($row['role'] ?? 'main') === $k)>{{ $v }}</option>@endforeach</select></div>
        <label class="small" style="padding-bottom:.6rem"><input type="checkbox" name="items[{{ $n }}][scales]" value="1" @checked(! empty($row['scales']))> Grows with the portion</label>
        <button type="button" class="btn ghost sm" data-del style="margin-bottom:.3rem">Remove</button>
      </div>
    @endforeach
  </div>
  <button type="button" class="btn ghost sm" id="addrow">+ Add a food</button>

  <div class="f mt"><label>How to make it (one step per line)</label><textarea name="method" rows="5">{{ old('method', $dish->method) }}</textarea></div>
  <div class="row"><button class="btn">Save</button><a class="btn ghost" href="{{ route('admin.dishes.index') }}">Cancel</a></div>
</form>
<script>
(function () {
  var rows = document.getElementById('rows');
  function renum() { Array.prototype.forEach.call(rows.querySelectorAll('.dishrow'), function (r, n) { Array.prototype.forEach.call(r.querySelectorAll('[name^="items["]'), function (e) { e.name = e.name.replace(/items\[\d+\]/, 'items[' + n + ']'); }); }); }
  document.getElementById('addrow').addEventListener('click', function () {
    var last = rows.querySelector('.dishrow:last-child'), c = last.cloneNode(true);
    Array.prototype.forEach.call(c.querySelectorAll('select,input'), function (e) { if (e.type === 'checkbox') e.checked = true; else if (e.tagName === 'SELECT') e.selectedIndex = 0; else e.value = e.name.indexOf('servings') > -1 ? 1 : ''; });
    c.querySelector('[name$="[role]"]').value = 'dish'; rows.appendChild(c); renum();
  });
  rows.addEventListener('click', function (e) { if (e.target.hasAttribute('data-del') && rows.children.length > 1) { e.target.closest('.dishrow').remove(); renum(); } });
})();
</script>
@endsection
