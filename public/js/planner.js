/* Meal planner: the survey (one question to a screen) and "Change" on a planned food, with the calorie effect of each choice. */
(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
  var token = function () { return (document.querySelector('meta[name=csrf-token]') || {}).content || ''; };
  var fmt = function (n) { return Math.round(n).toLocaleString('en-US'); };
  var sign = function (n) { return (n > 0 ? '+' : n < 0 ? '−' : '') + fmt(Math.abs(n)); };

  /* ---------------- changing one planned food, to fit the calorie target ---------------- */
  var meals = $('#meals[data-item-url]');
  if (meals) {
    var alts = {};
    try { alts = JSON.parse($('#alts').textContent); } catch (e) {}
    var dayKcal = +meals.getAttribute('data-daykcal') || 0, target = +meals.getAttribute('data-target') || 0;
    meals.addEventListener('click', function (e) {
      var b = e.target.closest('.chg'); if (!b) return;
      var li = b.closest('li'), open = $('.chgform', li);
      if (open) { open.remove(); return; }
      $$('.chgform', meals).forEach(function (x) { x.remove(); });
      var grp = b.getAttribute('data-grp'), sub = b.getAttribute('data-sub'), cur = b.getAttribute('data-food');
      var curK = +b.getAttribute('data-kcal') || 0, n = +b.getAttribute('data-servings') || 1;
      var list = (alts[grp] || []).filter(function (f) {
        return (!sub || f.s === sub) && (String(f.id) === cur || f.m.indexOf(b.getAttribute('data-type')) > -1);
      }).map(function (f) { return { f: f, gap: target ? Math.abs(dayKcal - curK + f.k * n - target) : 0 }; })
        .sort(function (a, c) { return a.gap - c.gap || a.f.n.localeCompare(c.f.n); });
      var best = list.length ? list[0].f.id : null, byId = {};
      list.forEach(function (o) { byId[o.f.id] = o.f; });
      var form = document.createElement('form');
      form.method = 'post'; form.action = meals.getAttribute('data-item-url'); form.className = 'chgform';
      form.innerHTML = '<input type="hidden" name="_token" value="' + esc(token()) + '">' +
        '<input type="hidden" name="day" value="' + esc(meals.getAttribute('data-day')) + '"><input type="hidden" name="meal" value="' + esc(b.getAttribute('data-meal')) + '"><input type="hidden" name="item" value="' + esc(b.getAttribute('data-item')) + '">' +
        '<label class="chglab">Food</label><select name="food_id" aria-label="Replace ' + esc(b.getAttribute('data-name')) + ' with"></select>' +
        '<label class="chglab">Portion</label><select name="servings" aria-label="How much"></select>' +
        '<button class="btn sm">Use this</button><p class="chghint small muted"></p>';
      li.appendChild(form);
      var sel = $('select[name=food_id]', form), por = $('select[name=servings]', form), hint = $('.chghint', form);
      sel.innerHTML = list.map(function (o) {
        var d = o.f.k * n - curK;
        var label = String(o.f.id) === cur ? 'Keep: ' + o.f.n : o.f.n + ' (' + (d === 0 ? 'same calories' : sign(d) + ' kcal') + ')' + (o.f.id === best && String(o.f.id) !== cur ? ' \u2605 best fit' : '');
        return '<option value="' + o.f.id + '"' + (String(o.f.id) === cur ? ' selected' : '') + '>' + esc(label) + '</option>';
      }).join('');
      var chosen = n;
      var fillPortions = function () {
        var f = byId[sel.value], top = Math.max(6, n + 3), html = '';
        for (var k = 1; k <= Math.min(8, top); k++) {
          var d = f.k * k - curK;
          html += '<option value="' + k + '"' + (k === chosen ? ' selected' : '') + '>' + k + ' \u00d7 ' + esc(f.q) + ' (' + fmt(f.k * k) + ' kcal' + (k === n && String(f.id) === cur ? ', now' : (d === 0 ? '' : ', ' + sign(d))) + ')</option>';
        }
        por.innerHTML = html;
      };
      var say = function () {
        var f = byId[sel.value], k = +por.value || chosen, after = dayKcal - curK + f.k * k;
        hint.textContent = target ? 'Day total after this change: about ' + fmt(after) + ' kcal (target ' + fmt(target) + (after > target * 1.05 ? ', a little over' : after < target * 0.93 ? ', a little under' : ', on target') + ').' : '';
      };
      sel.addEventListener('change', function () { chosen = +por.value || chosen; fillPortions(); say(); });
      por.addEventListener('change', function () { chosen = +por.value; say(); });
      fillPortions();
      say();
      sel.focus();
    });
  }

  /* ---------------- the survey ---------------- */
  var form = $('#wiz');
  if (!form) return;

  var steps = $$('.wstep', form), idx = 0;
  var val = function (name) { var e = form.querySelector('[name="' + name + '"]:checked'); return e ? e.value : ''; };
  var checked = function (n) { return $$('[name="' + n + '"]:checked', form).map(function (i) { return i.value; }); };
  var NAMES = { cereal: 'Rice and cereals', veg: 'Vegetables and leaves', fruit: 'Fruit', pulse: 'Pulses', protein: 'Fish or lean meat', egg: 'Egg', dairy: 'Milk or curd', nuts: 'Nuts and seeds', coconut: 'Coconut', oil: 'Oil' };
  var ORDER = ['cereal', 'veg', 'fruit', 'pulse', 'protein', 'egg', 'dairy', 'nuts', 'coconut', 'oil'];
  var PACE = { lose: ['about 0.25 kg a week (about 300 calories a day less)', 'about 0.5 kg a week (about 500 calories a day less)', 'lose weight'], gain: ['about 0.25 kg a week (about 250 calories a day more)', 'about 0.4 kg a week (about 400 calories a day more)', 'gain weight'] };
  var AUTO = ['meals'];

  form.classList.add('stepped');

  /* show only the questions that matter */
  function adapt() {
    var p = PACE[val('achievement')];
    $('#pacebox').hidden = !p;
    if (p) { $('#pace-g').textContent = p[0]; $('#pace-s').textContent = p[1]; $('#pacegoal').textContent = p[2]; }
    $('#meatbox').hidden = ['vegan', 'lacto', 'ovo', 'ovolacto'].indexOf(val('pattern')) > -1;
    $('#causes-note').hidden = checked('causes[]').indexOf('medication') < 0;
  }

  /* one screen at a time */
  function show(i) {
    idx = Math.max(0, Math.min(steps.length - 1, i));
    steps.forEach(function (s, n) { s.classList.toggle('on', n === idx); });
    var last = idx === steps.length - 1;
    $('#wbar').style.width = ((idx + 1) / steps.length * 100) + '%';
    $('#wcount').textContent = last ? 'Last step' : 'Question ' + (idx + 1) + ' of ' + (steps.length - 1);
    $('#wback').hidden = idx === 0; $('#wnext').hidden = last; $('#wsubmit').hidden = !last; $('#wdisc').hidden = !last;
    if (last) { review(); preview(); }
    window.scrollTo({ top: 0 });
    var first = $('input:not([type=hidden]):not([type=radio]):not([type=checkbox])', steps[idx]); if (first && !first.closest('[hidden]')) try { first.focus({ preventScroll: true }); } catch (e) {}
  }
  function valid() {
    var bad = $$('input', steps[idx]).filter(function (i) { return !i.closest('[hidden]') && i.willValidate && !i.checkValidity(); })[0];
    if (bad) { bad.reportValidity(); return false; }
    if (steps[idx].getAttribute('data-step') === 'you' && +$('#w-age').value < 10) { $('#w-age').reportValidity(); return false; }
    return true;
  }
  $('#wnext').addEventListener('click', function () { if (valid()) show(idx + 1); });
  $('#wback').addEventListener('click', function () { show(idx - 1); });
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') { e.preventDefault(); if (idx < steps.length - 1 && valid()) show(idx + 1); }
  });
  form.addEventListener('change', adapt);
  /* tapping the answer on a one-question screen moves on, like a quiz */
  form.addEventListener('change', function (e) {
    var t = e.target, step = t.closest('.wstep');
    if (t.type === 'radio' && step && steps[idx] === step && AUTO.indexOf(step.getAttribute('data-step')) > -1) setTimeout(function () { if (valid()) show(idx + 1); }, 180);
  });

  /* the answers, written out for checking */
  function label(name) { var e = form.querySelector('[name="' + name + '"]:checked'); var b = e && e.closest('label').querySelector('b'); return b ? b.textContent : (e ? e.closest('label').textContent.trim() : ''); }
  function texts(name) { return checked(name).map(function (v) { var i = $('[name="' + name + '"][value="' + v + '"]', form); return i.nextElementSibling.textContent; }); }
  function review() {
    var avoid = ['no_beef', 'no_pork'].filter(function (n) { return form.querySelector('[name=' + n + ']').checked && !$('#meatbox').hidden; }).map(function (n) { return n === 'no_beef' ? 'no beef' : 'no pork'; });
    var usual = ['breakfast', 'lunch', 'dinner'].map(function (s) { var t = texts('usual_' + s + '[]'); return t.length ? s + ': ' + t.slice(0, 3).join(', ') + (t.length > 3 ? ' and ' + (t.length - 3) + ' more' : '') : ''; }).filter(Boolean);
    var tw = form.target_weight.value ? ', target ' + form.target_weight.value + ' kg' : '';
    var all = texts('allergies[]'), causes = texts('causes[]');
    var wn = $('.wnote span', form);
    var rows = [
      ['Goal', label('achievement') + (PACE[val('achievement')] ? ', ' + label('pace').toLowerCase() : '')],
      ['You', val('sex') + ', ' + form.age.value + ' years, ' + form.height.value + ' cm, ' + form.weight.value + ' kg' + tw],
      ['Body', label('current_body') + ' now, aiming for ' + label('body_goal').toLowerCase()],
      ['Metabolism', label('metabolism') + '. Best shape: ' + label('best_shape').toLowerCase()],
      ['Affecting weight', causes.length ? causes.join(', ') : 'Nothing in particular'],
      ['Activity', label('job') + '. ' + (wn ? wn.textContent.replace(/ Your meals.*$/, '') : '')],
      ['Meals', label('meals')],
      ['Eating', label('pattern') + (avoid.length ? ' (' + avoid.join(', ') + ')' : '')],
      ['Allergies', all.length ? all.join(', ') : 'None']
    ].concat(usual.length ? [['Usually eat', usual.join('. ')]] : []);
    $('#rv-list').innerHTML = rows.map(function (r) { return '<dt>' + r[0] + '</dt><dd>' + esc(r[1]) + '</dd>'; }).join('');
  }

  /* live numbers on the last screen */
  var seq = 0;
  function preview() {
    var my = ++seq;
    fetch(form.getAttribute('data-preview'), { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token() }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).then(function (d) { if (my === seq) paint(d); }).catch(function () {});
  }
  function paint(d) {
    var warn = $('#s-warn'), sv = $('#s-sv'), go = $('#wsubmit');
    warn.innerHTML = ''; sv.innerHTML = ''; go.disabled = false;
    if (d.incomplete) { ['#s-kcal', '#s-p', '#s-tdee', '#s-water'].forEach(function (i) { $(i).textContent = '-'; }); return; }
    var t = d.targets;
    $('#s-kcal').textContent = t.kcal.toLocaleString('en-US'); $('#s-p').textContent = t.protein + ' g'; $('#s-tdee').textContent = t.tdee.toLocaleString('en-US');
    $('#s-water').textContent = d.water ? d.water.litres + ' L' : '-';
    if (d.blocked) { warn.innerHTML = d.issues.map(function (i) { return '<li class="bad">' + esc(i) + '</li>'; }).join(''); go.disabled = true; return; }
    sv.innerHTML = ORDER.filter(function (g) { return d.servings[g] > 0; }).map(function (g) { return '<li><span>' + NAMES[g] + '</span><b>' + d.servings[g] + '</b></li>'; }).join('');
    var w = (d.notes || []).concat(d.warnings || []);
    if (t.floor_applied) w.push('Your target was raised to the lowest amount we ever plan for (' + t.kcal + ' calories).');
    warn.innerHTML = w.map(function (i) { return '<li>' + esc(i) + '</li>'; }).join('');
  }

  /* a failed submit (for example the tick box) lands on the last screen */
  adapt();
  show(form.querySelector('.err') ? steps.length - 1 : 0);
})();
