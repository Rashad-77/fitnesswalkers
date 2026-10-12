/* Meal plan: "Change dish", "Change dessert" and "Add dessert". Every option is checked against the day's targets
   (calories, protein, carbs, fat) and marked good, close or off, and the best fit is starred. */
(function () {
  var data; try { data = JSON.parse(document.getElementById('dietui').textContent); } catch (e) { return; }
  var T = data.t, NAMES = { kcal: 'Calories', protein: 'Protein', carbs: 'Carbs', fat: 'Fat' }, UNIT = { kcal: 'kcal', protein: 'g', carbs: 'g', fat: 'g' };
  var RULE = { kcal: [0.93, 1.07], protein: [0.9, 1.45], carbs: [0.85, 1.15], fat: [0.8, 1.2] };
  var token = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
  var fmt = function (n) { return Math.round(n).toLocaleString('en-US'); };
  var K = ['kcal', 'protein', 'carbs', 'fat'];

  var sum = function (a, b) { var o = {}; K.forEach(function (k) { o[k] = (a[k] || 0) + (b[k] || 0); }); return o; };
  var sub = function (a, b) { var o = {}; K.forEach(function (k) { o[k] = (a[k] || 0) - (b[k] || 0); }); return o; };
  var day = data.meals.reduce(function (t, m) { return sum(t, sum(m.km, m.kd)); }, { kcal: 0, protein: 0, carbs: 0, fat: 0 });

  /* how far a day is from the targets: the same formula the server uses */
  function error(g) {
    return 2 * Math.abs(g.kcal - T.kcal) / Math.max(T.kcal, 60) + 1.5 * Math.max(0, T.protein - g.protein) / Math.max(T.protein, 8) +
      0.4 * Math.max(0, g.protein - T.protein * 1.4) / Math.max(T.protein, 8) + Math.abs(g.carbs - T.carbs) / Math.max(T.carbs, 15) + Math.abs(g.fat - T.fat) / Math.max(T.fat, 6);
  }
  function fit(g) {
    var out = {}, all = 'ok';
    K.forEach(function (k) {
      var r = g[k] / Math.max(1, T[k]), s = r < RULE[k][0] ? 'low' : (r > RULE[k][1] ? 'high' : 'ok');
      out[k] = { s: s, diff: Math.round(g[k] - T[k]), got: g[k], pct: r * 100 };
      if (s !== 'ok') all = (Math.abs(r * 100 - 100) > 25 || all === 'off') ? 'off' : 'near';
    });
    out.all = all; return out;
  }
  var asTotals = function (o) { return { kcal: o[1], protein: o[2], carbs: o[3], fat: o[4] }; };

  var sheet = document.getElementById('sheet'), slot = document.getElementById('sheet-slot'), title = document.getElementById('sheet-title'), opener = null;
  function closeSheet() { if (!sheet) return; sheet.hidden = true; slot.innerHTML = ''; document.body.classList.remove('sheet-open'); if (opener) try { opener.focus(); } catch (e) {} }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && sheet && !sheet.hidden) closeSheet(); });
  if (sheet) sheet.addEventListener('click', function (e) { if (e.target.closest('[data-cancel]')) closeSheet(); });

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-open]'); if (!b || !sheet) return;
    var more = b.closest('details'); if (more) more.open = false;
    opener = b;
    var mi = +b.getAttribute('data-mi'), part = b.getAttribute('data-open'), meal = data.meals[mi];
    title.textContent = (part === 'dessert' ? 'Dessert for ' : 'Change dish for ') + (meal.slot.indexOf('snack') === 0 ? 'snack' : meal.slot);
    slot.innerHTML = '';
    var key = meal.slot.indexOf('snack') === 0 ? 'snack' : meal.slot;
    var list = part === 'dessert' ? data.dess : (data.opts[key] || []);
    var cur = part === 'dessert' ? meal.kd : meal.km, curId = part === 'dessert' ? meal.des : meal.dish, curScale = part === 'dessert' ? meal.dscale : meal.scale;
    var others = sub(day, cur);

    /* every dish at its best portion for this day */
    var rows = list.map(function (d) {
      var best = null;
      d.o.forEach(function (o) { var e2 = error(sum(others, asTotals(o))) + 0.02 * Math.abs(o[0] - 1); if (!best || e2 < best.e) best = { e: e2, o: o }; });
      return { d: d, best: best };
    }).sort(function (a, c) { return a.best.e - c.best.e || a.d.n.localeCompare(c.d.n); });
    if (!rows.length) { slot.innerHTML = '<p class="small muted">There is nothing else to choose from for this meal.</p>'; sheet.hidden = false; return; }
    var starId = rows[0].d.id;

    var f = document.createElement('form');
    f.method = 'post'; f.action = data.url; f.className = 'dpanel';
    f.innerHTML = '<input type="hidden" name="_token" value="' + esc(token) + '"><input type="hidden" name="day" value="' + data.day + '"><input type="hidden" name="meal" value="' + mi + '"><input type="hidden" name="part" value="' + part + '">' +
      '<label class="chglab">' + (part === 'dessert' ? 'Dessert' : 'Dish') + '</label><select name="dish_id"></select>' +
      '<label class="chglab">Portion</label><select name="scale"></select>' +
      '<div class="dfit" aria-live="polite"></div>' +
      '<div class="row" style="gap:.5rem"><button class="btn sm">Use this</button><button type="button" class="btn ghost sm" data-cancel>Cancel</button></div>';
    slot.appendChild(f); sheet.hidden = false; document.body.classList.add('sheet-open');
    var sd = f.querySelector('select[name=dish_id]'), sp = f.querySelector('select[name=scale]'), box = f.querySelector('.dfit');

    sd.innerHTML = rows.map(function (r) {
      var isCur = r.d.id === curId, label = (isCur ? 'Now: ' : '') + r.d.n + (r.d.id === starId && !isCur ? '  ★ best fit' : '');
      return '<option value="' + r.d.id + '"' + (isCur ? ' selected' : '') + '>' + esc(label) + '</option>';
    }).join('');
    if (!curId) sd.selectedIndex = 0;

    function portions(preferred) {
      var r = rows.filter(function (x) { return String(x.d.id) === sd.value; })[0];
      sp.innerHTML = r.d.o.map(function (o) {
        var t = asTotals(o), d = t.kcal - (String(r.d.id) === String(curId) ? cur.kcal : 0);
        return '<option value="' + o[0] + '"' + (o[0] === preferred ? ' selected' : '') + '>' + (o[0] === 1 ? '1' : String(o[0])) + '× · ' + esc(o[5]) + ' · ' + fmt(t.kcal) + ' kcal</option>';
      }).join('');
    }
    function show() {
      var r = rows.filter(function (x) { return String(x.d.id) === sd.value; })[0];
      var o = r.d.o.filter(function (x) { return String(x[0]) === sp.value; })[0] || r.d.o[0];
      var g = sum(others, asTotals(o)), v = fit(g);
      var bad = K.filter(function (k) { return v[k].s !== 'ok'; }).map(function (k) { return NAMES[k] + ' ' + fmt(Math.abs(v[k].diff)) + ' ' + UNIT[k] + (v[k].diff > 0 ? ' over' : ' under'); });
      var head = v.all === 'ok' ? '<b class="verdict ok">✓ Good choice</b> Your day stays on target.' : (v.all === 'near' ? '<b class="verdict near">⚠ Close</b> ' + esc(bad.join(', ')) + '.' : '<b class="verdict off">✗ Not a good fit</b> ' + esc(bad.join(', ')) + '.');
      var better = '';
      if (v.all !== 'ok' && String(r.d.id) !== String(starId)) better = '<div class="small" style="margin-top:.3rem">Better: <a href="#" data-pick="' + rows[0].d.id + '">' + esc(rows[0].d.n) + '</a> at ' + rows[0].best.o[0] + '×.</div>';
      else if (v.all !== 'ok') { var alt = null; r.d.o.forEach(function (x) { var e2 = error(sum(others, asTotals(x))); if (!alt || e2 < alt.e) alt = { e: e2, o: x }; }); if (alt && String(alt.o[0]) !== String(o[0])) better = '<div class="small" style="margin-top:.3rem">Try a <a href="#" data-scale="' + alt.o[0] + '">' + alt.o[0] + '× portion</a> to fit better.</div>'; }
      box.innerHTML = head + '<div class="dchips">' + K.map(function (k) { return '<span class="dc ' + v[k].s + '">' + NAMES[k] + ' <b>' + fmt(v[k].got) + '</b>/' + fmt(T[k]) + '</span>'; }).join('') + '</div>' + better;
    }
    sd.addEventListener('change', function () { var r = rows.filter(function (x) { return String(x.d.id) === sd.value; })[0]; portions(r.best.o[0]); show(); });
    sp.addEventListener('change', show);
    f.addEventListener('click', function (ev) {
      var a = ev.target.closest('[data-pick]'), s = ev.target.closest('[data-scale]');
      if (a) { ev.preventDefault(); sd.value = a.getAttribute('data-pick'); var r = rows.filter(function (x) { return String(x.d.id) === sd.value; })[0]; portions(r.best.o[0]); show(); }
      if (s) { ev.preventDefault(); sp.value = s.getAttribute('data-scale'); show(); }
    });
    var r0 = rows.filter(function (x) { return String(x.d.id) === sd.value; })[0];
    portions(curId ? curScale : r0.best.o[0]); show();
    var first = f.querySelector('select'); if (first) first.focus();
  });
})();
