@if (session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
@if (session('warn'))<div class="alert warn">{{ session('warn') }}</div>@endif
@if ($errors->any())
  <div class="alert err">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif
