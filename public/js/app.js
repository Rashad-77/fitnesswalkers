// Tiny helpers. No framework needed.
document.addEventListener('click', function (e) {
  document.querySelectorAll('details.menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
  var t = e.target.closest('[data-toggle-menu]');
  if (t) { var sd = document.querySelector('.side'); if (sd) { sd.classList.toggle('open'); document.body.classList.toggle('menu-open', sd.classList.contains('open')); } }
  var c = e.target.closest('[data-confirm]');
  if (c && !confirm(c.getAttribute('data-confirm'))) { e.preventDefault(); }
});
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
}

/* Public website: header that turns solid on scroll, mobile menu, and gentle reveal-on-scroll. */
(function () {
  var head = document.getElementById('phead');
  if (head) {
    var onScroll = function () { head.classList.toggle('scrolled', window.scrollY > 24); };
    onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
    var btn = document.getElementById('pnav-toggle');
    if (btn) {
      btn.addEventListener('click', function () {
        var open = head.classList.toggle('open'); btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      document.addEventListener('click', function (e) { if (head.classList.contains('open') && !head.contains(e.target)) { head.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); } });
    }
  }
  var items = document.querySelectorAll('[data-reveal]');
  if (!items.length) return;
  if (!('IntersectionObserver' in window)) { items.forEach(function (el) { el.classList.add('in'); }); return; }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
  }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
  items.forEach(function (el) { io.observe(el); });
})();

/* Owner top bar: find a member as you type. */
(function () {
  var form = document.querySelector('.topsearch[data-search]');
  if (!form) return;
  var input = form.querySelector('input'), box = document.createElement('div'), timer, seq = 0;
  box.className = 'livebox'; box.hidden = true; form.appendChild(box);
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };
  input.setAttribute('autocomplete', 'off');
  input.addEventListener('input', function () {
    clearTimeout(timer);
    var q = input.value.trim();
    if (q.length < 2) { box.hidden = true; return; }
    timer = setTimeout(function () {
      var my = ++seq;
      fetch(form.getAttribute('data-search') + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); }).then(function (rows) {
          if (my !== seq) return;
          box.innerHTML = rows.length ? rows.map(function (m) {
            return '<a href="' + esc(m.url) + '"><i>' + esc(m.initials) + '</i><span><b>' + esc(m.name) + '</b><small>' + esc(m.no) + (m.phone ? ' · ' + esc(m.phone) : '') + '</small></span><em class="b ' + esc(m.status) + '">' + esc(m.status === 'none' ? 'no package' : m.status) + '</em></a>';
          }).join('') : '<div class="none">No member found. Press Enter to search the full list.</div>';
          box.hidden = false;
        }).catch(function () {});
    }, 180);
  });
  document.addEventListener('click', function (e) { if (!form.contains(e.target)) box.hidden = true; });
  input.addEventListener('keydown', function (e) { if (e.key === 'Escape') box.hidden = true; });
})();
