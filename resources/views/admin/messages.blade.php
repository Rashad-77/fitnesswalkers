@extends('layouts.admin')
@section('title', 'Messages')
@section('content')
<div class="pagehead"><div><h1>Messages</h1><p>The WhatsApp messages you send to members. Change the wording here and every reminder, receipt and welcome uses it.</p></div></div>
<div class="alert info">Words in <b>{curly brackets}</b> are filled in for each member, for example {name} becomes their first name. Tap a word under a message to add it where your cursor is.</div>
<form method="post" action="{{ route('admin.messages.save') }}" id="msgform" data-noguard>@csrf @method('PUT')
@foreach ($groups as $group => $keys)
  <h2 class="mt2" style="margin-bottom:.8rem">{{ $group }}</h2>
  <div class="msggrid">
  @foreach ($keys as $k)
    @php [$title, $when, $default, $allowed] = $all[$k]; $current = \App\Support\Messages::body($k); $custom = $current !== $default; @endphp
    <section class="card msgcard" data-sample="{{ json_encode($sample) }}">
      <div class="row between" style="align-items:flex-start"><div><h3 style="margin:0">{{ $title }} @if ($custom)<span class="b pending">Edited</span>@endif</h3><p class="small muted" style="margin:.1rem 0 .6rem">{{ $when }}</p></div></div>
      <label class="sr" for="m-{{ $k }}">Message text for {{ $title }}</label>
      <textarea id="m-{{ $k }}" name="msg[{{ $k }}]" rows="4" maxlength="700">{{ old('msg.'.$k, $current) }}</textarea>
      <div class="vchips" aria-label="Words you can add">@foreach ($allowed as $v)<button type="button" class="vchip" data-ins="{{ '{'.$v.'}' }}" title="{{ $vars[$v] }}">{{ '{'.$v.'}' }}</button>@endforeach</div>
      <div class="wabubble" aria-live="polite"><span class="wa-text"></span><span class="wa-time">now</span></div>
      <div class="row between small muted" style="margin-top:.5rem"><span class="count"></span>
        @if ($custom)<button class="btn ghost sm" type="submit" formaction="{{ route('admin.messages.reset') }}" name="reset" value="{{ $k }}" data-confirm="Go back to the original wording?">Use original wording</button>@endif</div>
    </section>
  @endforeach
  </div>
@endforeach
<div class="savebar"><button class="btn">Save messages</button><span class="small muted">Preview uses example details, so you can check how each one reads.</span></div>
</form>
@push('scripts')
<script>
(function () {
  document.querySelectorAll('.msgcard').forEach(function (c) {
    var ta = c.querySelector('textarea'), out = c.querySelector('.wa-text'), cnt = c.querySelector('.count'), sample = JSON.parse(c.getAttribute('data-sample'));
    function draw() {
      out.textContent = ta.value.replace(/\{(\w+)\}/g, function (m, k) { return sample[k] !== undefined ? sample[k] : m; });
      cnt.textContent = ta.value.length + ' characters';
    }
    c.querySelectorAll('.vchip').forEach(function (b) {
      b.addEventListener('click', function () {
        var t = b.getAttribute('data-ins'), s = ta.selectionStart == null ? ta.value.length : ta.selectionStart, e = ta.selectionEnd == null ? s : ta.selectionEnd;
        ta.value = ta.value.slice(0, s) + t + ta.value.slice(e); ta.focus(); ta.selectionStart = ta.selectionEnd = s + t.length; draw();
      });
    });
    ta.addEventListener('input', draw); draw();
  });
})();
</script>
@endpush
@endsection
