{{-- $spec: array for public/js/charts.js. $h: height in px. $label: accessible description. --}}
<div class="chartbox" style="height:{{ $h ?? 260 }}px"><canvas data-chart="{{ json_encode($spec, JSON_UNESCAPED_UNICODE) }}" role="img" aria-label="{{ $label ?? 'Chart' }}"></canvas></div>
@pushOnce('scripts')
<script src="/vendor/chart.umd.min.js"></script>
<script src="/js/charts.js"></script>
@endPushOnce
