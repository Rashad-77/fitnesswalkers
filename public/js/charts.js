/* FitnessWalkers charts: declarative wrapper around Chart.js.
   Usage:  <div class="chartbox" style="height:260px"><canvas data-chart='{"type":"line","labels":[...],"datasets":[{"label":"Income","data":[...],"color":"#5fa31f"}],"format":"rs"}'></canvas></div>
   type: bar | line | hbar | doughnut ; per-dataset type "line" on a bar chart makes a combo. */
(function () {
  if (!window.Chart) return;
  var C = { ink: '#12201a', mut: '#5b6b62', line: '#e8eee3', canopy: '#0e2a20', fern: '#3b7a17' };
  Chart.defaults.font.family = '"Figtree", system-ui, -apple-system, "Segoe UI", sans-serif';
  Chart.defaults.font.size = 12;
  Chart.defaults.color = C.mut;
  Chart.defaults.maintainAspectRatio = false;
  Chart.defaults.animation.duration = 700;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.boxWidth = 8;
  Chart.defaults.plugins.legend.labels.boxHeight = 8;
  Chart.defaults.plugins.legend.labels.padding = 16;
  Object.assign(Chart.defaults.plugins.tooltip, {
    backgroundColor: C.canopy, titleColor: '#fff', bodyColor: '#dbe7df', padding: 12, cornerRadius: 10, boxPadding: 5,
    titleFont: { weight: '700', size: 13 }, displayColors: true, usePointStyle: true
  });
  if (window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches) Chart.defaults.animation = false;

  var fmt = {
    rs: function (v) { return 'Rs. ' + Math.round(v).toLocaleString('en-US'); },
    num: function (v) { return (Math.round(v * 10) / 10).toLocaleString('en-US'); },
    pct: function (v) { return (Math.round(v * 10) / 10) + '%'; },
    kg: function (v) { return (Math.round(v * 10) / 10) + ' kg'; },
    cm: function (v) { return (Math.round(v * 10) / 10) + ' cm'; },
    g: function (v) { return Math.round(v) + ' g'; }
  };
  function compact(kind) {
    return function (v) {
      var a = Math.abs(v), s = v < 0 ? '-' : '';
      var n = a >= 1e6 ? (a / 1e6).toFixed(a % 1e6 ? 1 : 0) + 'M' : a >= 1e3 ? (a / 1e3).toFixed(a % 1e3 ? 1 : 0).replace('.0', '') + 'k' : a;
      return kind === 'rs' ? s + n : s + n + (kind === 'pct' ? '%' : kind === 'kg' ? ' kg' : '');
    };
  }
  function hex(h, a) {
    var n = parseInt(h.slice(1), 16);
    return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
  }

  // Doughnut: show the total (or a label) in the middle.
  var centre = {
    id: 'centre',
    afterDraw: function (chart, args, opts) {
      if (!opts || !opts.text) return;
      var a = chart.chartArea, ctx = chart.ctx, x = (a.left + a.right) / 2, y = (a.top + a.bottom) / 2;
      ctx.save(); ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
      ctx.fillStyle = C.ink; ctx.font = '800 20px Archivo, "Figtree", sans-serif';
      ctx.fillText(opts.text, x, y - (opts.sub ? 8 : 0));
      if (opts.sub) { ctx.fillStyle = C.mut; ctx.font = '500 12px Figtree, sans-serif'; ctx.fillText(opts.sub, x, y + 14); }
      ctx.restore();
    }
  };

  function build(el) {
    var s = JSON.parse(el.getAttribute('data-chart'));
    var f = fmt[s.format || 'num'] || fmt.num, ctx2 = el.getContext('2d');
    var type = s.type === 'hbar' ? 'bar' : s.type;
    var horizontal = s.type === 'hbar';
    var isDonut = type === 'doughnut';
    var datasets = (s.datasets || []).map(function (d, i) {
      var col = d.color || '#5fa31f', t = d.type || type;
      if (isDonut) return { data: d.data, backgroundColor: d.colors || s.colors, borderColor: '#fff', borderWidth: 3, hoverOffset: 6, label: d.label };
      var o = { label: d.label, data: d.data, type: t, order: d.order == null ? (t === 'line' ? 0 : 1) : d.order, yAxisID: d.axis || 'y' };
      if (t === 'line') {
        o.borderColor = col; o.backgroundColor = d.fill === false ? col : function (c) {
          var a = c.chart.chartArea; if (!a) return hex(col, .15);
          var g = ctx2.createLinearGradient(0, a.top, 0, a.bottom); g.addColorStop(0, hex(col, .28)); g.addColorStop(1, hex(col, 0)); return g;
        };
        o.fill = d.fill === false ? false : true; o.tension = .35; o.borderWidth = 2.5; o.pointRadius = d.points ? 3.5 : 0; o.pointHoverRadius = 6;
        o.pointBackgroundColor = '#fff'; o.pointBorderColor = col; o.pointBorderWidth = 2; if (d.dash) o.borderDash = [6, 5];
      } else {
        o.backgroundColor = d.colors || col; o.borderRadius = 6; o.borderSkipped = false; o.maxBarThickness = 36;
        if (s.stacked) o.borderRadius = 3;
      }
      return o;
    });
    var scales = {};
    if (!isDonut) {
      var allInt = (s.format || 'num') === 'num' && datasets.every(function (d) { return (d.data || []).every(function (v) { return v === null || Math.abs(v - Math.round(v)) < 1e-9; }); });
      var val = { beginAtZero: s.zero !== false, stacked: !!s.stacked, grid: { color: C.line, drawTicks: false }, border: { display: false }, ticks: { padding: 8, callback: compact(s.format), precision: allInt ? 0 : undefined } };
      var cat = { stacked: !!s.stacked, grid: { display: false }, border: { color: C.line }, ticks: { padding: 6, maxRotation: 0, autoSkipPadding: 12 } };
      if (s.min != null) val.min = s.min; if (s.max != null) val.max = s.max;
      if (s.suggestedMin != null) val.suggestedMin = s.suggestedMin;
      scales = horizontal ? { x: val, y: Object.assign(cat, { grid: { display: false } }) } : { x: cat, y: val };
      if (s.y2) scales.y2 = { position: 'right', grid: { display: false }, border: { display: false }, beginAtZero: true, ticks: { callback: compact(s.y2) } };
    }
    new Chart(el, {
      type: type,
      data: { labels: s.labels, datasets: datasets },
      plugins: isDonut ? [centre] : [],
      options: {
        indexAxis: horizontal ? 'y' : 'x', responsive: true, interaction: { mode: isDonut ? 'nearest' : 'index', intersect: false },
        cutout: isDonut ? '68%' : undefined, scales: scales,
        plugins: {
          legend: { display: s.legend !== false && (isDonut || datasets.length > 1), position: isDonut ? 'bottom' : 'top', align: isDonut ? 'center' : 'start' },
          centre: s.centre || null,
          tooltip: { callbacks: { label: function (c) {
            var v = isDonut ? c.parsed : (horizontal ? c.parsed.x : c.parsed.y);
            var fk = (c.dataset.yAxisID === 'y2' && s.y2) ? fmt[s.y2] : f;
            var extra = '';
            if (isDonut) { var tot = c.dataset.data.reduce(function (a, b) { return a + b; }, 0); extra = '  (' + Math.round(v / tot * 100) + '%)'; }
            return ' ' + (c.dataset.label ? c.dataset.label + ': ' : c.label + ': ') + fk(v) + extra;
          } } }
        }
      }
    });
  }
  // Wait for the web fonts so Chart.js measures legends and labels with the real letter widths.
  var go = function () { document.querySelectorAll('canvas[data-chart]').forEach(function (el) { try { build(el); } catch (e) { console.error(e); } }); };
  var done = false, once = function () { if (!done) { done = true; go(); } };
  if (document.fonts && document.fonts.ready) { document.fonts.ready.then(once); setTimeout(once, 1500); } else once();
})();
