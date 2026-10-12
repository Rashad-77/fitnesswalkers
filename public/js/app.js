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
