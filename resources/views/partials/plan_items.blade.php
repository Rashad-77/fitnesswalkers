{{-- $items: collection of {day_no, exercise_id, sets, reps}; $exercises: Exercise collection --}}
@php $rows = $items->map(fn ($i) => ['day_no' => $i->day_no, 'exercise_id' => $i->exercise_id, 'sets' => $i->sets, 'reps' => $i->reps])->values()->all();
     for ($k = 0; $k < 3; $k++) $rows[] = ['day_no' => collect($rows)->max('day_no') ?: 1, 'exercise_id' => '', 'sets' => 3, 'reps' => '10']; @endphp
<div class="tw"><table id="items">
  <tr><th>Day</th><th>Exercise</th><th>Sets</th><th>Reps / time</th></tr>
  @foreach ($rows as $i => $row)
    <tr class="item-row">
      <td style="width:70px"><input type="number" min="1" max="7" name="items[{{ $i }}][day_no]" value="{{ $row['day_no'] }}"></td>
      <td><select name="items[{{ $i }}][exercise_id]"><option value="">- empty -</option>
        @foreach ($exercises->groupBy('muscle_group') as $g => $list)<optgroup label="{{ $g }}">@foreach ($list as $e)<option value="{{ $e->id }}" @selected($row['exercise_id'] == $e->id)>{{ $e->name }}{{ $e->isAvailable() ? '' : ' (equipment out of order)' }}</option>@endforeach</optgroup>@endforeach</select></td>
      <td style="width:80px"><input type="number" min="1" max="10" name="items[{{ $i }}][sets]" value="{{ $row['sets'] }}"></td>
      <td style="width:110px"><input type="text" name="items[{{ $i }}][reps]" value="{{ $row['reps'] }}"></td>
    </tr>
  @endforeach
</table></div>
<button type="button" class="btn ghost sm mt" id="add-row">+ Add row</button>
<p class="small muted">Leave a row's exercise empty to remove it. Day numbers group exercises into workout days.</p>
<script>
document.getElementById('add-row').addEventListener('click', function () {
  var rows = document.querySelectorAll('#items .item-row'), last = rows[rows.length - 1], n = rows.length, c = last.cloneNode(true);
  c.querySelectorAll('input,select').forEach(function (el) { el.name = el.name.replace(/items\[\d+\]/, 'items[' + n + ']'); if (el.tagName === 'SELECT') el.value = ''; });
  last.parentNode.appendChild(c);
});
</script>
