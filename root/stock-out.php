<?php
/* ============================================================
   root/stock-out.php — Stock Out page (Manual + Sheet + Scanner)
   Barcode se auto qty: CARTON=-180, BOX=-30, PCS=-1
   ============================================================ */
$page = 'stock-out';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock Out — Diwan International Pvt Ltd</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
</head>
<body>
<!-- Undo Confirm Modal -->
<div class="del-modal-overlay" id="undoModal">
  <div class="del-modal-box">
    <button type="button" class="del-modal-close" onclick="closeUndoModal()">&times;</button>
    <div class="del-icon">↩️</div>
    <h3 id="undoModalTitle">Undo Stock Out</h3>
    <span class="del-code" id="undoModalCode" style="display:none"></span>
    <p id="undoModalMsg">Last stock out wapas aayega?</p>
    <div class="del-modal-actions">
      <button type="button" class="btn ghost" onclick="closeUndoModal()">Cancel</button>
      <button type="button" class="btn amber" id="undoModalConfirm">Undo</button>
    </div>
  </div>
</div>

<div class="app">
  <?php include 'sidebar.php'; ?>

  <main>
    <!-- Page header -->
    <div class="pagehead">
      <div>
        <h1>Stock Out</h1>
        <p class="desc">Remove stock — barcode ke level ke hisaab se auto pieces (CARTON=180, BOX=30, PCS=1)</p>
      </div>
    </div>

    <!-- Messages yahan aati hain (app.js se) -->
    <div id="msgBox" class="msg-box"></div>

    <!-- Tabs: Manual | Sheet | Scanner -->
    <div class="nav-tabs">
      <button id="t1" class="active">Manual Form</button>
      <button id="t2">Sheet Upload</button>
      <button type="button" id="t3" style="margin-left:auto;background:var(--blue);border-color:var(--blue);color:#fff" onclick="openScanModal()">📷 USB Scanner</button>
    </div>

    <!-- ============ TAB 1: Manual Form ============ -->
    <div class="tab-pane active" id="pane1">
      <div class="panel" style="max-width:760px">
        <div class="form-wrap">
          <!-- Barcode lookup -->
          <div class="field">
            <label>Barcode *</label>
            <input type="text" id="m_serial" placeholder="Scan or type">
            <div class="helper-note" id="m_lookup">Type barcode to auto-fill item info.</div>
          </div>

          <!-- Level (auto, readonly) -->
          <div class="field">
            <label>Level (auto)</label>
            <input type="text" id="m_level" readonly placeholder="CARTON / BOX / PCS">
          </div>

          <!-- Pcs Qty (auto) -->
          <div class="field">
            <label>Pcs Qty (auto)</label>
            <input type="text" id="m_pcsqty" readonly placeholder="180 / 30 / 1">
          </div>

          <!-- Item name (auto-fill, readonly) -->
          <div class="field">
            <label>Item Name (auto-fill)</label>
            <input type="text" id="m_name" readonly>
          </div>

          <!-- Available stock (readonly) -->
          <div class="field">
            <label>Available Stock (Pcs)</label>
            <input type="text" id="m_stock" readonly>
          </div>

          <!-- Remark -->
          <div class="field" style="grid-column:1/-1">
            <label>Remark</label>
            <input type="text" id="m_remark" placeholder="e.g. Dispatch to Karachi branch">
          </div>
        </div>

        <button type="button" class="btn red" id="btnSubmitOut">- Stock Out</button>

        <!-- ===== UNDO BOX (sirf Undo Last) ===== -->
        <div class="panel" style="margin-top:18px;padding:16px 18px;border-color:#5A2026;background:var(--panel)">
          <h3 style="margin:0 0 8px 0;font-size:14.5px">↩️ Undo Last Stock Out <span style="font-weight:400;color:var(--muted);font-size:12.5px">(galti se out hua stock wapas lo)</span></h3>
          <p class="desc" style="margin:0 0 12px 0">Aakhri stock out wapas add ho jayega aur record delete.</p>
          <button type="button" class="btn amber" id="btnUndoLast">↩️ Undo Last</button>
          <div id="undoResult" style="margin-top:10px"></div>
        </div>
        <!-- ===== / UNDO BOX ===== -->

      </div>
    </div>

    <!-- ============ TAB 2: Sheet Upload ============ -->
    <div class="tab-pane" id="pane2">
      <div class="panel" style="max-width:820px">
        <!-- Upload zone: click = file choose -->
        <div class="upload-zone" id="upZone">
          <div class="up-icon">📄</div>
          <div><b>Click</b> or drag a file — <b>PDF / Excel / Word / CSV</b></div>
          <div class="meta">Format: Level | Parent | Item Code | Item Name | Barcode | Total Pcs | ...</div>
        </div>
        <input type="file" id="upFile" accept=".pdf,.xlsx,.xls,.csv,.docx,.txt" style="display:none">

        <div class="upload-line">
          <button class="btn blue" id="btnPreview">Preview</button>
          <button class="btn red" id="btnUploadOut" disabled>Upload &amp; Stock Out</button>
        </div>

        <!-- Preview table yahan render hoti hai -->
        <div id="previewArea" style="margin-top:18px"></div>
      </div>
    </div>
  </main>
</div>

<!-- ============ SCANNER MODAL ============ -->
<div class="modal-overlay" id="scanModal" style="display:none">
  <div class="modal-box" style="max-width:540px">
    <div class="modal-header">
      <h2>📷 USB Scanner — Auto Stock Out</h2>
      <button type="button" class="modal-close" onclick="closeScanModal()">&times;</button>
    </div>
    <p class="desc" style="margin-bottom:12px">Scan → barcode aate hi level ke hisaab se auto stock out (CARTON -180 / BOX -30 / PCS -1). Manual: barcode type karke ENTER.</p>

    <!-- Scanner messages -->
    <div id="scanMsg" class="msg-box"></div>

    <!-- Lock/Unlock safety button (accidental scans rokne ke liye) -->
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:14px;">
      <button id="scanLockBtn" class="btn red" style="font-size:14px;padding:12px 22px">🔒 LOCKED</button>
      <span id="scanLockMsg" class="dash" style="font-size:13px">Lock is off — scans will be ignored.</span>
    </div>

    <!-- Scanner input box -->
    <div class="scan-box" id="scanBox" onclick="document.getElementById('scanInput').focus()" style="padding:14px">
      <input type="text" id="scanInput" autocomplete="off"
             placeholder="USB scan or type barcode + ENTER" autofocus style="caret-color:transparent;cursor:default">
      <div class="scan-hint" id="scanHint" style="font-size:12px">Unlock first, then scan.</div>
    </div>

    <!-- Last few scans -->
    <div class="panel" style="margin-top:16px;padding:12px">
      <h3 style="font-size:14px;margin:0 0 8px 0">Last 5 Scans</h3>
      <div id="scanHistory"><span class="dash">Loading...</span></div>
    </div>
  </div>
</div>

<script src="assets/js/app.js?v=<?php echo filemtime('assets/js/app.js'); ?>"></script>
<script>
/* ---------- TABS ---------- */
function switchPane(id){
  document.querySelectorAll('.tab-pane').forEach(function(p){ p.classList.remove('active'); });
  document.querySelectorAll('.nav-tabs button').forEach(function(b){ b.classList.remove('active'); });
  var pane = $(id);
  if (pane) pane.classList.add('active');
  var btn = id==='pane1' ? $('t1') : $('t2');
  if (btn) btn.classList.add('active');
}
$('t1').addEventListener('click', function(){ switchPane('pane1'); });
$('t2').addEventListener('click', function(){ switchPane('pane2'); });

/* ---------- AUTO-LOOKUP: barcode type karte hi product info ---------- */
$('m_serial').addEventListener('input', function(){
  var s = this.value.trim();
  if (s.length < 3) {
    $('m_lookup').textContent = 'Type barcode to auto-fill item info.';
    $('m_name').value=''; $('m_level').value=''; $('m_pcsqty').value=''; $('m_stock').value='';
    return;
  }
  fetch('../ajax/search_product.php?serial='+encodeURIComponent(s))
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.success && d.found && d.data && d.data.level){
        $('m_name').value   = d.data.item_name || '';
        $('m_level').value  = d.data.level || '';
        $('m_pcsqty').value = d.data.pcs_qty || '';
        $('m_stock').value  = d.data.current_stock_pcs || 0;
        $('m_lookup').textContent = 'Barcode found — ' + d.data.level + ' (' + d.data.pcs_qty + ' pcs). Stock: ' + d.data.current_stock_pcs;
      } else if (d.success && d.found && d.data){
        $('m_name').value   = d.data.item_name || '';
        $('m_level').value  = '';
        $('m_pcsqty').value = '';
        $('m_stock').value  = d.data.current_stock_pcs || 0;
        $('m_lookup').textContent = 'Item code found, but no barcode registered — register barcode first.';
      } else {
        $('m_name').value=''; $('m_level').value=''; $('m_pcsqty').value=''; $('m_stock').value='';
        $('m_lookup').textContent = 'Barcode not registered — please register the sheet first.';
      }
    });
});

/* ---------- MANUAL STOCK OUT SUBMIT ---------- */
$('btnSubmitOut').addEventListener('click', function(){
  var fd = new FormData();
  fd.append('barcode', $('m_serial').value.trim());
  fd.append('remark',  $('m_remark').value.trim());
  postForm('../ajax/stock_out_form.php', fd, function(d){
    if (d.success){
      $('m_remark').value = '';
      $('m_serial').value = '';
      $('m_name').value=''; $('m_level').value=''; $('m_pcsqty').value=''; $('m_stock').value='';
      $('m_lookup').textContent = 'Type barcode to auto-fill item info.';
      $('m_serial').focus();
    }
  });
});

/* ================= UNDO LAST (MODAL) ================= */
var undoPending = false;

function openUndoModal(){
  undoPending = true;
  $('undoModalTitle').textContent = 'Undo Last Stock Out';
  $('undoModalCode').style.display = 'none';
  $('undoModalMsg').textContent = 'Aakhri stock out wapas add ho jayega aur record delete. Sure?';
  var m = $('undoModal');
  var box = m.querySelector('.del-modal-box');
  m.classList.remove('anim-out');
  m.classList.add('anim-in');
  if (box){ box.classList.remove('anim-out'); box.classList.add('anim-in'); }
  document.body.classList.add('modal-docking');
  m.classList.add('open');
}
function closeUndoModal(){
  var m = $('undoModal');
  var box = m.querySelector('.del-modal-box');
  m.classList.add('anim-out');
  m.classList.remove('anim-in');
  if (box){ box.classList.add('anim-out'); box.classList.remove('anim-in'); }
  setTimeout(function(){
    m.classList.remove('open');
    document.body.classList.remove('modal-docking');
  }, 300);
  undoPending = false;
}
function doUndoLast(){
  var fd = new FormData();   /* koi barcode nahi — server last uthayega */
  fetch('../ajax/undo_stock_out.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
      $('undoResult').innerHTML = d.success
        ? '<span class="tag ok">✅ ' + d.message + '</span>'
        : '<span class="tag low">⚠️ ' + (d.message || 'Error') + '</span>';
    })
    .catch(function(){ $('undoResult').innerHTML = '<span class="tag low">⚠️ Server error.</span>'; });
}

/* ================= SCANNER MODAL ================= */
var scanUnlocked   = false;   // safety lock
var scanBusy       = false;
var scanModalOpen  = false;   // modal khula hai?
var scanBuffer     = '';      // scanner ka char buffer
var scanPauseTimer = null;    // bina-Enter scanner ke pause timer

/* Modal open/close — spring dialog animation (dock se) */
function openScanModal(){
  scanModalOpen = true;
  scanBuffer = '';
  var m = $('scanModal');
  var box = m.querySelector('.modal-box');
  m.classList.remove('anim-out');
  m.classList.add('anim-in');
  if (box){ box.classList.remove('anim-out'); box.classList.add('anim-in'); }
  document.body.classList.add('modal-docking');
  m.style.display = 'flex';
  $('scanInput').value = '';
  loadScanHistory();
  $('scanInput').focus();
}
function closeScanModal(){
  scanModalOpen = false;
  scanPauseStop();
  var m = $('scanModal');
  var box = m.querySelector('.modal-box');
  m.classList.add('anim-out');
  m.classList.remove('anim-in');
  if (box){ box.classList.add('anim-out'); box.classList.remove('anim-in'); }
  setTimeout(function(){
    m.style.display = 'none';
    document.body.classList.remove('modal-docking');
  }, 360);
  scanUnlocked = false;
  scanBuffer = '';
  setScanLockUI();
  $('scanInput').value = '';
}

$('scanModal').addEventListener('click', function(e){
  if (e.target === this) closeScanModal();
});

/* Lock button UI update */
function setScanLockUI(){
  var b = $('scanLockBtn');
  if (scanUnlocked){
    b.className = 'btn green'; b.innerHTML = '🔓 UNLOCKED';
    $('scanLockMsg').textContent = 'Lock open — scan will auto stock out.';
    $('scanBox').style.borderColor = 'var(--blue)';
    $('scanHint').textContent = 'SCAN NOW — barcode triggers auto stock out.';
  } else {
    b.className = 'btn red'; b.innerHTML = '🔒 LOCKED';
    $('scanLockMsg').textContent = 'Lock is off — scans will be ignored.';
    $('scanBox').style.borderColor = '';
    $('scanHint').textContent = 'Unlock first, then scan or type barcode + ENTER.';
  }
  $('scanInput').focus();
}
$('scanLockBtn').addEventListener('click', function(){ scanUnlocked = !scanUnlocked; setScanLockUI(); });

function scanMsg(html, type){
  var el = $('scanMsg');
  el.className = 'msg-box ' + (type || 'info');
  el.innerHTML = html;
}

function scanResetBuffer(){ scanBuffer = ''; $('scanInput').value = ''; }
function scanPauseStop(){ if (scanPauseTimer){ clearTimeout(scanPauseTimer); scanPauseTimer = null; } }
function scanArmPause(){
  scanPauseStop();
  scanPauseTimer = setTimeout(function(){
    var v = scanBuffer.trim();
    scanBuffer = ''; $('scanInput').value = '';
    if (v) scanProcess(v);
  }, 180);
}

/* GLOBAL KEYBOARD CAPTURE — modal khula ho to scanner kaam kare */
document.addEventListener('keydown', function(e){
  if (!scanModalOpen) return;
  if (e.key === 'Escape'){ e.preventDefault(); closeScanModal(); return; }
  e.preventDefault();
  if (e.key === 'Enter'){
    scanPauseStop();
    var s = scanBuffer.trim();
    scanBuffer = ''; $('scanInput').value = '';
    if (s) scanProcess(s);
    $('scanInput').focus();
    return;
  }
  if (e.key === 'Backspace'){
    scanBuffer = scanBuffer.slice(0, -1);
    $('scanInput').value = scanBuffer;
    return;
  }
  if (e.key.length === 1){
    scanBuffer += e.key;
    $('scanInput').value = scanBuffer;
    scanArmPause();
  }
});

/* Ek barcode process karo → server par Stock Out */
function scanProcess(s){
  if (scanBusy) return;
  s = s.trim();
  if (!s) return;
  if (!scanUnlocked){
    scanMsg('🔒 LOCKED — unlock first', 'danger');
    scanResetBuffer();
    $('scanInput').focus();
    return;
  }
  scanBusy = true;
  var fd = new FormData();
  fd.append('serial', s);
  fd.append('remark', 'Scanner');
  fetch('../ajax/stock_out_scanner.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.success){
        scanMsg('✅ <b>' + s + '</b> — ' + esc(d.message), 'success');
        showMsg('✅ ' + s + ' — ' + d.message, 'success');
      } else {
        scanMsg('⚠️ ' + s + ' — ' + (d.message || 'Error'), 'danger');
      }
      loadScanHistory();
      scanResetBuffer();
      scanBusy = false;
      $('scanInput').focus();
    })
    .catch(function(e){
      scanMsg('Server error: ' + e, 'danger');
      scanResetBuffer();
      scanBusy = false;
      $('scanInput').focus();
    });
}

/* Last scans ki list */
function loadScanHistory(){
  ajax('../ajax/dashboard_stats.php', function(d){
    if (!d.success) return;
    var list = (d.data.recent || []).slice(0, 5);
    if (!list.length){ $('scanHistory').innerHTML = '<span class="dash">No scans yet.</span>'; return; }
    $('scanHistory').innerHTML = list.map(function(r){
      var k = r.rec_type==='in' ? 'tag in' : 'tag out';
      return '<div class="item-row"><div><div class="name">'+esc(r.item_name)+'</div>'+
        '<div class="meta">'+esc(r.barcode)+' · '+esc(r.level)+' · '+esc(r.entry_date)+'</div></div>'+
        '<span class="'+k+'">'+ (r.rec_type==='in' ? '+' : '-') + r.pcs_qty +'</span></div>';
    }).join('');
  });
}

/* ---------- SHEET UPLOAD (file select hote hi auto-preview) ---------- */
var selFile = null;
$('upZone').addEventListener('click', function(){ $('upFile').click(); });
$('upFile').addEventListener('change', function(){
  selFile = this.files[0];
  if (selFile) doPreview();
});

function doPreview(){
  if (!selFile) { showMsg('Please select a file first.', 'danger'); return; }
  var fd = new FormData();
  fd.append('file', selFile);
  postForm('../ajax/upload_sheet.php', fd, function(d){
    if (!d.success) return;
    $('btnUploadOut').disabled = false;
    var rows = d.items.map(function(it){
      var lvl = '<span class="tag ' + (it.level === 'CARTON' ? 'low' : (it.level === 'BOX' ? 'out' : 'in')) + '">' + esc(it.level) + '</span>';
      return '<tr><td><input type="checkbox" class="row-chk" data-serial="'+esc(it.barcode)+'" checked></td>'+
             '<td class="mono">'+esc(it.barcode)+'</td>'+'<td>'+lvl+'</td>'+
             '<td>'+esc(it.item_name)+'</td>'+
             '<td><span class="tag in">'+esc(it.pcs_qty)+'</span></td></tr>';
    }).join('');
    $('previewArea').innerHTML =
      '<h3 style="margin:0 0 10px 0;font-size:14px">Preview - '+d.total+' barcodes '+
      '<label style="font-weight:400;font-size:12px;margin-left:10px"><input type="checkbox" id="chkAll" checked> Select all</label></h3>' +
      '<table><thead><tr><th>Sel</th><th>Barcode</th><th>Level</th><th>Items Name</th><th><span class="tag in">Pcs Qty</span></th></tr></thead>'+
      '<tbody>'+rows+'</tbody></table>';
    $('chkAll').addEventListener('change', function(){
      var on = this.checked;
      document.querySelectorAll('.row-chk').forEach(function(c){ c.checked = on; });
    });
  });
}

function getSelectedSerials(){
  var list = [];
  document.querySelectorAll('.row-chk:checked').forEach(function(c){ list.push(c.getAttribute('data-serial')); });
  return list;
}

$('btnPreview').addEventListener('click', doPreview);

$('btnUploadOut').addEventListener('click', function(){
  if (!selFile) return;
  var sel = getSelectedSerials();
  if (!sel.length) { showMsg('Select at least one row.', 'danger'); return; }
  var fd = new FormData();
  fd.append('file', selFile);
  fd.append('selected', JSON.stringify(sel));
  postForm('../ajax/stock_out_sheet.php', fd, function(d){
    if (d.success){
      $('btnUploadOut').disabled = true;
      $('previewArea').innerHTML = '';
      $('upFile').value = ''; selFile = null;
    }
  });
});
</script>
</body>
</html>