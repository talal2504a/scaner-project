// ============================================
// assets/js/app.js — shared helpers
// ============================================

function $(id) {
  return document.getElementById(id);
}

function esc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
  });
}

function fmtDs(v) {
  if (!v) return '—';
  return v.charAt(0) === '0' ? v : v; // keep as-is; numbers bhi string rakhne
}

function showMsg(msg, type) {
  type = type || 'success';
  let box = $('msgBox');
  if (!box) {
    box = document.createElement('div');
    box.id = 'msgBox';
    box.className = 'alert';
    document.querySelector('.card') || document.body.insertBefore(box, document.body.firstChild);
    if (!document.querySelector('#msgBox')) document.body.insertBefore(box, document.body.firstChild);
  }
  box.style.display = 'block';
  box.className = 'alert alert-' + type;
  box.textContent = msg;
  window.scrollTo({ top: 0, behavior: 'smooth' });
  clearTimeout(box._t);
  box._t = setTimeout(function () { box.style.display = 'none'; }, 4000);
}

function postForm(url, formData, done) {
  fetch(url, { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (d.success) showMsg(d.message, 'success');
      else showMsg(d.message || 'Error', 'danger');
      if (done) done(d);
    })
    .catch(function (e) {
      showMsg('Network/Server error: ' + e, 'danger');
    });
}

function ajax(url, done) {
  fetch(url)
    .then(function (r) { return r.json(); })
    .then(function (d) { if (done) done(d); })
    .catch(function (e) { showMsg('Error: ' + e, 'danger'); });
}

// ---- Mode/tab helpers ----
function showTab(id) {
  document.querySelectorAll('.tab-pane').forEach(function (t) { t.style.display = 'none'; });
  document.querySelectorAll('.nav-tabs button').forEach(function (b) { b.classList.remove('active'); });
  var pane = $(id);
  if (pane) pane.style.display = 'block';
}

function bindTabs(btnIds, paneId) {
  btnIds.forEach(function (bid) {
    var b = $(bid);
    if (!b) return;
    b.addEventListener('click', function () {
      document.querySelectorAll('.nav-tabs button').forEach(function (x) { x.classList.remove('active'); });
      b.classList.add('active');
      showTab(paneId);
    });
  });
}