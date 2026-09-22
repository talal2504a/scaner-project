<?php
// root/register.php — naya product + barcode register (Item ID | Item Name | Barcode)
$page = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Product — Diwan International Pvt Ltd</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
<style>
/* ---- Styled file upload ---- */
.file-upload{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.file-upload input[type=file]{display:none !important}
.file-upload .up-btn{
  background:var(--panel-2);color:var(--text);border:1.5px solid var(--line);
  border-radius:8px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;
  font-family:'Inter',sans-serif;transition:all .15s;
}
.file-upload .up-btn:hover{background:var(--amber);border-color:var(--amber);color:#fff}
.file-upload .fname{
  font-size:12.5px;color:var(--muted);
  background:var(--panel-2);border:1px dashed var(--line);
  border-radius:8px;padding:8px 12px;min-width:180px;flex:1;
}
.file-upload .fname b{color:var(--amber)}
/* ---- Progress wrap (sirf .show pe dikhe) ---- */
.progress-wrap{display:none !important;margin-top:12px;background:var(--panel);border:1px solid var(--line);border-radius:8px;padding:14px}
.progress-wrap.show{display:block !important}
.progress-bar{height:6px;background:var(--line);border-radius:4px;overflow:hidden;margin-bottom:6px}
.progress-text{display:flex;align-items:center;gap:10px;font-size:12px;color:var(--muted);font-family:'Inter',sans-serif}
.progress-spinner{width:14px;height:14px;border:2px solid var(--line);border-top-color:var(--amber);border-radius:50%;animation:spin .8s linear infinite}
.pct{color:var(--amber);font-weight:600;margin-left:auto}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<div class="app">
  <?php include 'sidebar.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>Register Product</h1>
        <p class="desc">Add a new item / barcode to the system</p>
      </div>
    </div>

    <div id="msgBox" class="msg-box"></div>

    <!-- Tabs -->
    <div class="field" style="max-width:760px; margin-bottom:12px">
      <button class="btn tab-btn active" id="tabManual">Manual Register</button>
      <button class="btn tab-btn" id="tabSheet">Sheet Upload (Bulk)</button>
    </div>

    <!-- Manual Form -->
    <div class="panel" style="max-width:760px" id="panelManual">
      <div class="split-row">
        <div class="field">
          <label>Item ID *</label>
          <input type="text" id="r_itemid" placeholder="e.g. 45125">
        </div>
        <div class="field">
          <label>Item Name *</label>
          <input type="text" id="r_itemname" placeholder="e.g. Chint breaker">
        </div>
      </div>

      <div class="split-row">
        <div class="field">
          <label>Barcode <span style="color:var(--muted)">(optional)</span></label>
          <input type="text" id="r_barcode" placeholder="e.g. P001001000001">
        </div>
      </div>

      <button class="btn amber" id="btnReg">Register Product</button>
    </div>

    <!-- Sheet Upload -->
    <div class="panel" style="max-width:760px; display:none" id="panelSheet">
      <p class="desc" style="margin-bottom:12px">
        Hierarchy sheet — columns auto-detected from headers (Item ID | Items Name | Barcode minimum).<br>
        Level (L/B/P), Parent, Boxes pr Ctn, Pcs pr Box, Total Pcs — headers se mil jaayenge.
      </p>
      <div class="field" style="margin-bottom:12px">
        <label>Sheet File (xlsx, xls, csv, txt) *</label>
        <div class="file-upload">
          <label class="up-btn" for="r_sheet">📂 &nbsp;Choose File</label>
          <input type="file" id="r_sheet" accept=".xlsx,.xls,.csv,.txt">
          <span class="fname" id="sheetFileName">No file selected</span>
        </div>
      </div>
      <div class="field" style="margin-bottom:12px">
        <button class="btn green" id="btnPreview">Preview</button>
        <button class="btn red" id="btnSheetUpload" disabled>Upload &amp; Register Sheet</button>
      </div>
      <!-- Progress bar -->
      <div class="progress-wrap" id="progressWrap">
        <div class="progress-bar" id="progressBar"></div>
        <div class="progress-text">
          <div class="progress-spinner"></div>
          <span id="progressLabel">Uploading...</span>
          <span class="pct" id="progressPct">0%</span>
        </div>
      </div>
      <div id="sheetStatus" style="margin-top:10px"></div>
      <div id="sheetPreviewWrap" class="panel" style="display:none;margin-top:12px;padding:0;overflow:auto;max-height:300px">
        <table>
          <thead>
            <tr>
              <th>Sel</th><th>Level</th><th>Barcode</th><th>Items Name</th>
              <th><span class="tag in">Pcs Qty</span></th><th>Status</th>
            </tr>
          </thead>
          <tbody id="sheetPreview"></tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js?v=<?php echo filemtime('assets/js/app.js'); ?>"></script>
<script>
// ---------- Progress helper ----------
function showProgress(label, pct){
  $('progressWrap').classList.add('show');
  $('progressLabel').textContent = label;
  if (pct !== null){
    $('progressPct').textContent = pct + '%';
    $('progressBar').style.width = pct + '%';
  } else {
    $('progressPct').textContent = '';
    $('progressBar').style.width = '100%';
    $('progressBar').style.opacity = '.4';
  }
}
function hideProgress(){
  $('progressWrap').classList.remove('show');
  $('progressBar').style.width = '0%';
  $('progressBar').style.opacity = '1';
}

// XHR upload — REAL upload progress (bytes), then honest spinner for processing
function uploadWithProgress(url, fd, onDone){
  var xhr = new XMLHttpRequest();
  xhr.open('POST', url, true);

  // REAL upload progress (file bytes tracking)
  xhr.upload.onprogress = function(e){
    if (e.lengthComputable){
      var pct = Math.round((e.loaded / e.total) * 100);
      showProgress('Uploading file...', pct);
    }
  };

  xhr.onload = function(){
    if (xhr.status === 200){
      // Upload done — ab server processing (honest spinner, no fake %)
      showProgress('Processing...', null);
      var d = null;
      try {
        d = JSON.parse(xhr.responseText);
      } catch(ex){
        hideProgress();
        onDone({success:false, message:'Invalid response'});
        return;
      }
      hideProgress();
      onDone(d);
    } else {
      hideProgress();
      onDone({success:false, message:'Upload failed ('+xhr.status+')'});
    }
  };

  xhr.onerror = function(){
    hideProgress();
    onDone({success:false, message:'Network error'});
  };

  xhr.send(fd);
}

// Tab switching
$('tabManual').addEventListener('click', function(){
  $('panelManual').style.display = '';
  $('panelSheet').style.display = 'none';
  $('tabManual').classList.add('active');
  $('tabSheet').classList.remove('active');
});
$('tabSheet').addEventListener('click', function(){
  $('panelManual').style.display = 'none';
  $('panelSheet').style.display = '';
  $('tabSheet').classList.add('active');
  $('tabManual').classList.remove('active');
});

// Manual register (Item ID + Item Name + optional Barcode)
function productFd(){
  var fd = new FormData();
  fd.append('item_code',  $('r_itemid').value.trim());
  fd.append('item_name',  $('r_itemname').value.trim());
  fd.append('barcode',    $('r_barcode').value.trim());
  return fd;
}
$('btnReg').addEventListener('click', function(){
  postForm('../ajax/register_product.php', productFd(), function(d){
    if (d.success){
      ['r_itemid','r_itemname','r_barcode'].forEach(function(id){ var el=$(id); if(el) el.value=''; });
      $('r_itemid').focus();
    }
  });
});
// File selected → name dikhao
$('r_sheet').addEventListener('change', function(){
  var f = this.files[0];
  $('sheetFileName').innerHTML = f
    ? '<b>'+f.name+'</b> · '+(f.size/1024).toFixed(0)+' KB'
    : 'No file selected';
});
// Sheet preview — XHR with REAL upload progress
$('btnPreview').addEventListener('click', function(){
  var file = $('r_sheet').files[0];
  if (!file) { $('sheetStatus').innerHTML = '<span class="tag low">Please select a file first.</span>'; return; }
  var fd = new FormData();
  fd.append('sheet', file);

  $('btnPreview').disabled = true;
  $('btnSheetUpload').disabled = true;

  uploadWithProgress('../ajax/sheet_preview.php', fd, function(d){
    $('btnPreview').disabled = false;
    var el = $('sheetStatus');
    if (!d.success){ el.innerHTML = '<span class="tag low">' + (d.message || 'Error') + '</span>'; return; }
    var rows = (d.rows || []).map(function(p){
      var st = p.exists ? '<span class="tag low">exists</span>' : '<span class="tag ok">new</span>';
      var lvl = '<span class="tag ' + (p.level === 'CARTON' ? 'low' : (p.level === 'BOX' ? 'out' : 'in')) + '">' + esc(p.level) + '</span>';
      return '<tr><td><input type="checkbox" class="row-chk" data-serial="'+esc(p.barcode)+'" checked></td>'+
             '<td>'+lvl+'</td><td class="mono">'+esc(p.barcode)+'</td><td>'+esc(p.item_name)+'</td>'+
             '<td><span class="tag in">'+esc(p.pcs_qty)+'</span></td><td>'+st+'</td></tr>';
    }).join('');
    $('sheetPreview').innerHTML = rows;
    $('sheetPreviewWrap').style.display = (rows ? 'block' : 'none');
    $('btnSheetUpload').disabled = false;
    var bl = d.byLevel || {};
    el.innerHTML = '<label style="margin-right:10px"><input type="checkbox" id="chkAll" checked> Select all</label> ' +
      '<span class="tag ok">Total: '+d.total+' | CARTON: '+(bl.CARTON||0)+' | BOX: '+(bl.BOX||0)+' | PCS: '+(bl.PCS||0)+
      ' | New: '+d.newCount+' | Already exist: '+d.dupCount+'</span>';
    $('chkAll').addEventListener('change', function(){
      var on = this.checked;
      document.querySelectorAll('.row-chk').forEach(function(c){ c.checked = on; });
    });
  });
});

// Checked rows ke barcodes collect karo
function getSelSerial(){
  var list = [];
  document.querySelectorAll('.row-chk:checked').forEach(function(c){ list.push(c.getAttribute('data-serial')); });
  return list;
}

// Sheet upload (sirf selected rows) — XHR with REAL upload progress
$('btnSheetUpload').addEventListener('click', function(){
  var file = $('r_sheet').files[0];
  if (!file) { $('sheetStatus').innerHTML = '<span class="tag low">Please select a file first.</span>'; return; }
  var sel = getSelSerial();
  if (!sel.length) { $('sheetStatus').innerHTML = '<span class="tag low">Select at least one row.</span>'; return; }

  var fd = new FormData();
  fd.append('sheet', file);
  fd.append('selected', JSON.stringify(sel));

  $('btnSheetUpload').disabled = true;

  uploadWithProgress('../ajax/register_product.php', fd, function(d){
    $('btnSheetUpload').disabled = false;
    var el = $('sheetStatus');
    if (d.success){
      el.innerHTML = '<span class="tag ok">Done! ' + d.message + '</span>';
      $('r_sheet').value='';
      $('sheetPreviewWrap').style.display = 'none';
    } else {
      el.innerHTML = '<span class="tag low">' + (d.message || 'Error') + '</span>';
    }
  });
});
</script>
</body>
</html>