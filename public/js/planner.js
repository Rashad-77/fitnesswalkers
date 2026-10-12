/* Meal planner: the questionnaire (one question to a screen) and "Change" on a planned food. */
(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
  var token = function () { return (document.querySelector('meta[name=csrf-token]') || {}).content || ''; };

  /* ---------------- changing one planned food ---------------- */
  var meals = $('#meals[data-item-url]');
  if (meals) {
    var alts = {};
    try { alts = JSON.parse($('#alts').textContent); } catch (e) {}
    meals.addEventListener('click', function (e) {
      var b = e.target.closest('.chg'); if (!b) return;
      var li = b.closest('li'), open = $('.chgform', li);
      if (open) { open.remove(); return; }
      $$('.chgform', meals).forEach(function (x) { x.remove(); });
      var list = (alts[b.getAttribute('data-grp')] || []).filter(function (f) { return (!b.getAttribute('data-sub') || f.s === b.getAttribute('data-sub')) && (String(f.id) === b.getAttribute('data-food') || f.m.indexOf(b.getAttribute('data-type')) > -1); });
      var f = document.createElement('form');
      f.method = 'post'; f.action = meals.getAttribute('data-item-url'); f.className = 'chgform';
      f.innerHTML = '<input type="hidden" name="_token" value="' + esc(token()) + '">' +
        '<input type="hidden" name="day" value="' + esc(meals.getAttribute('data-day')) + '"><input type="hidden" name="meal" value="' + esc(b.getAttribute('data-meal')) + '"><input type="hidden" name="item" value="' + esc(b.getAttribute('data-item')) + '">' +
        '<select name="food_id" aria-label="Replace ' + esc(b.getAttribute('data-name')) + ' with">' +
        list.map(function (a) { return '<option value="' + a.id + '"' + (String(a.id) === b.getAttribute('data-food') ? ' selected' : '') + '>' + esc(a.n) + '</option>'; }).join('') +
        '</select><button class="btn sm">Use this</button>';
      li.appendChild(f);
      $('select', f).focus();
    });
  }

  /* ---------------- the questionnaire ---------------- */
  var form = $('#wiz');
  if (!form) return;

  var steps = $$('.wstep', form), idx = 0;
  var val = function (name) { var e = form.querySelector('[name="' + name + '"]:checked'); return e ? e.value : ''; };
  var checked = function (n) { return $$('[name="' + n + '"]:checked', form).map(function (i) { return i.value; }); };
  var NAMES = { cereal: 'Rice and cereals', veg: 'Vegetables and leaves', fruit: 'Fruit', pulse: 'Pulses', protein: 'Fish or lean meat', egg: 'Egg', dairy: 'Milk or curd', nuts: 'Nuts and seeds', coconut: 'Coconut', oil: 'Oil' };
  var ORDER = ['cereal', 'veg', 'fruit', 'pulse', 'protein', 'egg', 'dairy', 'nuts', 'coconut', 'oil'];
  var PACE = { lose: ['about 0.25 kg a week (about 300 calories a day less)', 'about 0.5 kg a week (about 500 calories a day less)', 'lose weight'], gain: ['about 0.25 kg a week (about 250 calories a day more)', 'about 0.4 kg a week (about 400 calories a day more)', 'build muscle'] };

  form.classList.add('stepped');

  /* show only the questions that matter */
  function adapt() {
    var g = val('goal'), p = PACE[g];
    $('#pacebox').hidden = !p;
    if (p) { $('#pace-g').textContent = p[0]; $('#pace-s').textContent = p[1]; $('#pacegoal').textContent = p[2]; }
    $('#minbox').hidden = val('train_days') === '0';
    $('#meatbox').hidden = ['vegan', 'lacto', 'ovo', 'ovolacto'].indexOf(val('pattern')) > -1;
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
    if (steps[idx].getAttribute('data-step') === 'body' && +$('#w-age').value < 10) { $('#w-age').reportValidity(); return false; }
    return true;
  }
  $('#wnext').addEventListener('click', function () { if (valid()) show(idx + 1); });
  $('#wback').addEventListener('click', function () { show(idx - 1); });
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') { e.preventDefault(); if (idx < steps.length - 1 && valid()) show(idx + 1); }
  });
  form.addEventListener('change', adapt);
  /* tapping an answer on a choose-one screen moves on, like a quiz */
  form.addEventListener('change', function (e) {
    var t = e.target, step = t.closest('.wstep');
    if (t.type === 'radio' && step && step.querySelectorAll('input[type=radio]').length && ['goal', 'eating', 'meals'].indexOf(step.getAttribute('data-step')) > -1 && steps[idx] === step) {
      var needMore = (step.getAttribute('data-step') === 'goal' && val('goal') !== 'maintain') || (step.getAttribute('data-step') === 'eating' && !$('#meatbox').hidden);
      if (!needMore && t.name !== 'pace') setTimeout(function () { if (valid()) show(idx + 1); }, 180);
    }
  });

  /* the answers, written out for checking */
  function label(name) { var e = form.querySelector('[name="' + name + '"]:checked'); var b = e && e.closest('label').querySelector('b'); return b ? b.textContent : (e ? e.closest('label').textContent.trim() : ''); }
  function review() {
    var all = checked('allergies[]').map(function (a) { return $('[name="allergies[]"][value="' + a + '"]', form).nextElementSibling.textContent; });
    var avoid = ['no_beef', 'no_pork'].filter(function (n) { return form.querySelector('[name=' + n + ']').checked && !$('#meatbox').hidden; }).map(function (n) { return n === 'no_beef' ? 'no beef' : 'no pork'; });
    var rows = [
      ['Goal', label('goal') + (PACE[val('goal')] ? ', ' + label('pace').toLowerCase() : '')],
      ['You', form.age.value + ' years, ' + val('sex') + ', ' + form.height.value + ' cm, ' + form.weight.value + ' kg'],
      ['Activity', label('job') + '. ' + (val('train_days') === '0' ? 'No workouts.' : val('train_days') + ' workouts a week, ' + val('train_minutes') + ' minutes each.')],
      ['Eating', label('pattern') + (avoid.length ? ' (' + avoid.join(', ') + ')' : '')],
      ['Allergies', all.length ? all.join(', ') : 'None'],
      ['Meals', label('meals')]
    ];
    $('#rv-list').innerHTML = rows.map(function (r, i) { return '<dt>' + r[0] + '</dt><dd>' + esc(r[1]) + '</dd>'; }).join('');
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
    if (d.incomplete) { ['#s-kcal', '#s-p', '#s-tdee'].forEach(function (i) { $(i).textContent = '-'; }); return; }
    var t = d.targets;
    $('#s-kcal').textContent = t.kcal.toLocaleString('en-US'); $('#s-p').textContent = t.protein + ' g'; $('#s-tdee').textContent = t.tdee.toLocaleString('en-US');
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
