<?php
// root/stock-in.php — Manual + Sheet tab
$page = 'stock-in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stock In — Stock System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include '_layout.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>Stock In</h1>
        <p class="desc">Manual entry ya sheet upload se stock add karo</p>
      </div>
    </div>

    <div id="msgBox" class="msg-box"></div>

    <div class="nav-tabs">
      <button id="t1" class="active">Manual Form</button>
      <button id="t2">Sheet Upload</button>
    </div>

    <!-- ============ TAB 1: Manual ============ -->
    <div class="tab-pane active" id="pane1">
      <div class="panel" style="max-width:760px">
        <div class="form-wrap">

          <div class="field">
            <label>Serial Code / Barcode *</label>
            <input type="text" id="m_serial" placeholder="Scan ya type karo">
            <div class="helper-note" id="m_lookup">Serial daalo to product info auto-aa jayegi.</div>
          </div>

          <div class="field">
            <label>Quantity *</label>
            <input type="number" id="m_qty" min="1" value="1">
          </div>

          <div class="field">
            <label>Item Name (auto-fill / edit)</label>
            <input type="text" id="m_name">
          </div>

          <div class="field">
            <label>Category</label>
            <input type="text" id="m_category">
          </div>

          <div class="field" style="grid-column:1/-1">
            <label>Remark</label>
            <input type="text" id="m_remark" placeholder="e.g. Vendor delivery #123">
          </div>

          <div class="field" style="grid-column:1/-1">
            <label>Photo (optional)</label>
            <input type="file" id="m_photo" accept="image/*">
          </div>
        </div>

        <button class="btn green" id="btnSubmitIn">+ Stock In</button>
      </div>
    </div>

    <!-- ============ TAB 2: Sheet Upload ============ -->
    <div class="tab-pane" id="pane2">
      <div class="panel" style="max-width:820px">
        <div class="upload-zone" id="upZone">
          <div class="up-icon">📄</div>
          <div><b>Click</b> ya file drag karo — <b>PDF / Excel / Word / CSV</b></div>
          <div class="meta">Format: 1st column = Serial, 2nd column = Description.</div>
        </div>
        <input type="file" id="upFile" accept=".pdf,.xlsx,.xls,.csv,.docx,.txt" style="display:none">
        <div class="upload-line">
          <button class="btn" id="btnPreview">Preview</button>
          <button class="btn" id="btnUploadIn" disabled>Upload &amp; Stock In</button>
        </div>

        <div id="previewArea" style="margin-top:18px"></div>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
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

// ---- Auto-lookup serial -> product info ----
$('m_serial').addEventListener('input', function(){
  var s = this.value.trim();
  if (s.length < 3) { $('m_lookup').textContent = 'Serial daalo to product info auto-aa jayegi.'; return; }
  fetch('../ajax/search_product.php?serial='+encodeURIComponent(s))
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.success && d.data){
        $('m_name').value     = d.data.item_name || '';
        $('m_category').value = d.data.category  || '';
        $('m_lookup').textContent = 'Mila: stock = ' + d.data.current_stock;
      } else {
        $('m_lookup').textContent = 'Naya serial lagega — product auto-register hoga.';
      }
    });
});

$('btnSubmitIn').addEventListener('click', function(){
  var fd = new FormData();
  fd.append('serial_code', $('m_serial').value.trim());
  fd.append('quantity',    $('m_qty').value);
  fd.append('item_name',   $('m_name').value.trim());
  fd.append('remark',      $('m_remark').value.trim());
  var p = $('m_photo').files[0]; if (p) fd.append('photo', p);
  postForm('../ajax/stock_in_form.php', fd, function(d){
    if (d.success){
      $('m_qty').value = 1;
      $('m_remark').value = '';
      $('m_photo').value = '';
      $('m_serial').value = '';
      $('m_name').value = ''; $('m_category').value = '';
      $('m_serial').focus();
    }
  });
});

// ---- Sheet upload ----
var selFile = null;
$('upZone').addEventListener('click', function(){ $('upFile').click(); });
$('upFile').addEventListener('change', function(){
  selFile = this.files[0];
  if (selFile) $('btnPreview').disabled = false;
});

$('btnPreview').addEventListener('click', function(){
  if (!selFile) { showMsg('Pehle file select karo.', 'danger'); return; }
  var fd = new FormData();
  fd.append('file', selFile);
  postForm('../ajax/upload_sheet.php', fd, function(d){
    if (!d.success) return;
    $('btnUploadIn').disabled = false;
    var rows = d.items.map(function(it){
      return '<tr><td class="mono">'+esc(it.serial)+'</td><td>'+esc(it.item_name)+'</td>'+
             '<td>'+esc(it.category)+'</td><td>'+esc(it.model)+'</td><td class="num-cell">'+esc(it.qty)+'</td></tr>';
    }).join('');
    $('previewArea').innerHTML =
      '<h3 style="margin:0 0 10px 0;font-size:14px">Preview — '+d.total+' items</h3>' +
      '<table><thead><tr><th>Serial</th><th>Description</th><th>Category</th><th>Model</th><th>Qty</th></tr></thead>'+
      '<tbody>'+rows+'</tbody></table>';
  });
});

$('btnUploadIn').addEventListener('click', function(){
  if (!selFile) return;
  var fd = new FormData();
  fd.append('file', selFile);
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