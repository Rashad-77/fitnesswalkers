/* Meal planner survey: one question to a screen, with the live numbers on the last screen. */
(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
  var token = function () { return (document.querySelector('meta[name=csrf-token]') || {}).content || ''; };
  var fmt = function (n) { return Math.round(n).toLocaleString('en-US'); };
  var sign = function (n) { return (n > 0 ? '+' : n < 0 ? '−' : '') + fmt(Math.abs(n)); };

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
    ];
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
