// Tiny helpers. No framework needed.
document.addEventListener('click', function (e) {
  document.querySelectorAll('details.menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
  var t = e.target.closest('[data-toggle-menu]');
  if (t) { document.querySelector('.side').classList.toggle('open'); }
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
