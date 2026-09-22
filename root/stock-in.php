<?php
// root/stock-in.php — Manual + Sheet tab (barcode → auto level/qty)
$page = 'stock-in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock In — Diwan International Pvt Ltd</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
</head>
<body>
<div class="app">
  <?php include 'sidebar.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>Stock In</h1>
        <p class="desc">Scan / type barcode — level ke hisaab se pieces auto add hote hain (CARTON=180, BOX=30, PCS=1)</p>
      </div>
    </div>

    <div id="msgBox" class="msg-box"></div>

    <div class="nav-tabs">
      <button type="button" id="t1" class="active">Manual / Scan</button>
      <button type="button" id="t2">Sheet Upload</button>
    </div>

    <!-- ============ TAB 1: Manual ============ -->
    <div class="tab-pane active" id="pane1">
      <div class="panel" style="max-width:760px">
        <div class="form-wrap">

          <div class="field">
            <label>Barcode *</label>
            <input type="text" id="m_serial" placeholder="Scan or type barcode">
            <div class="helper-note" id="m_lookup">Type barcode to auto-fill item info.</div>
          </div>

          <div class="field">
            <label>Level (auto)</label>
            <input type="text" id="m_level" readonly placeholder="CARTON / BOX / PCS">
          </div>

          <div class="field">
            <label>Item Name (auto-fill)</label>
            <input type="text" id="m_name" readonly>
          </div>

          <div class="field">
            <label>Pcs Qty (auto)</label>
            <input type="text" id="m_pcsqty" readonly placeholder="180 / 30 / 1">
          </div>

          <div class="field">
            <label>Available Stock (Pcs)</label>
            <input type="text" id="m_stock" readonly>
          </div>

          <div class="field" style="grid-column:1/-1">
            <label>Remark</label>
            <input type="text" id="m_remark" placeholder="e.g. Vendor delivery #123">
          </div>
        </div>

        <button type="button" class="btn green" id="btnSubmitIn">+ Stock In</button>
      </div>
    </div>

    <!-- ============ TAB 2: Sheet Upload ============ -->
    <div class="tab-pane" id="pane2">
      <div class="panel" style="max-width:820px">
        <div class="upload-zone" id="upZone">
          <div class="up-icon">📄</div>
          <div><b>Click</b> or drag a file — <b>PDF / Excel / Word / CSV</b></div>
          <div class="meta">Format: Item Code | Item Name | CTN Qty | Barcode</div>
        </div>
        <input type="file" id="upFile" accept=".pdf,.xlsx,.xls,.csv,.docx,.txt" style="display:none">
        <div class="upload-line">
          <button type="button" class="btn blue" id="btnPreview">Preview</button>
          <button type="button" class="btn red" id="btnUploadIn" disabled>Upload &amp; Stock In</button>
        </div>

        <div id="previewArea" style="margin-top:18px"></div>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js?v=<?php echo filemtime('assets/js/app.js'); ?>"></script>
<script>
// Tabs
function switchPane(id){
  document.querySelectorAll('.tab-pane').forEach(function(p){ p.classList.remove('active'); });
  document.querySelectorAll('.nav-tabs button').forEach(function(b){ b.classList.remove('active'); });
  var pane = $(id);
  if (pane) pane.classList.add('active');
  var btnId = $('t1') && id==='pane1' ? 't1' : 't2';
  var btn = $(btnId); if (btn) btn.classList.add('active');
}
$('t1').addEventListener('click', function(){ switchPane('pane1'); });
$('t2').addEventListener('click', function(){ switchPane('pane2'); });

// ---- Auto-lookup barcode -> item info ----
$('m_serial').addEventListener('input', function(){
  var s = this.value.trim();
  if (s.length < 3) {
    $('m_lookup').textContent = 'Type barcode to auto-fill item info.';
    $('m_name').value = ''; $('m_level').value = ''; $('m_pcsqty').value = ''; $('m_stock').value = '';
    return;
  }
  fetch('../ajax/search_product.php?serial='+encodeURIComponent(s))
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.success && d.found && d.data && d.data.level){
        // Registered barcode mila → level + qty + stock bharo
        $('m_name').value   = d.data.item_name || '';
        $('m_level').value  = d.data.level || '';
        $('m_pcsqty').value = d.data.pcs_qty || '';
        $('m_stock').value  = d.data.current_stock_pcs || 0;
        $('m_lookup').textContent = 'Barcode found — ' + d.data.level +
          ' (+' + d.data.pcs_qty + ' pcs). Stock: ' + d.data.current_stock_pcs;
      } else if (d.success && d.found && d.data){
        // Item code exact — product hai par barcode nahi
        $('m_name').value   = d.data.item_name || '';
        $('m_level').value  = '';
        $('m_pcsqty').value = '';
        $('m_stock').value  = d.data.current_stock_pcs || 0;
        $('m_lookup').textContent = 'Item code found, but no barcode registered — register barcode first.';
      } else {
        $('m_name').value = ''; $('m_level').value = ''; $('m_pcsqty').value = ''; $('m_stock').value = '';
        $('m_lookup').textContent = 'Barcode not registered — please register the sheet first.';
      }
    });
});

$('btnSubmitIn').addEventListener('click', function(){
  var fd = new FormData();
  fd.append('barcode', $('m_serial').value.trim());
  fd.append('remark',  $('m_remark').value.trim());
  postForm('../ajax/stock_in_form.php', fd, function(d){
    if (d.success){
      $('m_remark').value = '';
      $('m_serial').value = '';
      $('m_name').value = ''; $('m_level').value = ''; $('m_pcsqty').value = ''; $('m_stock').value = '';
      $('m_lookup').textContent = 'Type barcode to auto-fill item info.';
      $('m_serial').focus();
    }
  });
});

// ---- Sheet upload ----
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
  fd.append('require_registered', '1');
  postForm('../ajax/upload_sheet.php', fd, function(d){
    if (!d.success) return;
    $('btnUploadIn').disabled = false;
    if (d.has_unregistered) {
      showMsg((d.unregistered||[]).length + ' rows products mein add nahi hain — pehle Products page se add karo.', 'danger');
    }
    var rows = d.items.map(function(it){
      var lvl = '<span class="tag ' + (it.level === 'CARTON' ? 'low' : (it.level === 'BOX' ? 'out' : 'in')) + '">' + esc(it.level) + '</span>';
      var bad = it.registered === false;
      return '<tr' + (bad ? ' style="opacity:.55"' : '') + '>'+
             '<td><input type="checkbox" class="row-chk" data-serial="'+esc(it.barcode)+'" '+(bad?'disabled':'checked')+'></td>'+
             '<td class="mono">'+esc(it.barcode)+'</td>'+
             '<td>'+lvl+'</td>'+
             '<td>'+esc(it.item_name)+'</td>'+
             '<td>'+(bad ? '<span class="tag out">Not registered</span>' : '<span class="tag in">'+esc(it.pcs_qty)+'</span>')+'</td></tr>';
    }).join('');
    $('previewArea').innerHTML =
      '<div class="panel" style="padding:14px 16px">'+
      '<div class="ledger-head"><h3 style="margin:0">Preview — '+d.total+' barcodes</h3>'+
      '<label style="font-size:12px;font-weight:500;color:var(--muted)"><input type="checkbox" id="chkAll" checked> Select all</label></div>' +
      '<div class="table-wrap"><table><thead><tr><th>Sel</th><th>Barcode</th><th>Level</th><th>Items Name</th><th><span class="tag in">Pcs Qty</span></th></tr></thead>'+
      '<tbody>'+rows+'</tbody></table></div></div>';
    $('chkAll').addEventListener('change', function(){
      var on = this.checked;
      document.querySelectorAll('.row-chk').forEach(function(c){ if (!c.disabled) c.checked = on; });
    });
  });
}
// collect selected barcodes
function getSelectedSerials(){
  var list = [];
  document.querySelectorAll('.row-chk:checked').forEach(function(c){ list.push(c.getAttribute('data-serial')); });
  return list;
}

$('btnPreview').addEventListener('click', doPreview);

$('btnUploadIn').addEventListener('click', function(){
  if (!selFile) return;
  var sel = getSelectedSerials();
  if (!sel.length) { showMsg('Select at least one row.', 'danger'); return; }
  var fd = new FormData();
  fd.append('file', selFile);
  fd.append('selected', JSON.stringify(sel));
  postForm('../ajax/stock_in_sheet.php', fd, function(d){
    if (d.success){
      $('btnUploadIn').disabled = true;
      $('previewArea').innerHTML = '';
      $('upFile').value = ''; selFile = null;
    }
  });
});
</script>
</body>
</html>