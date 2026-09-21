<?php $page = 'products'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products - Diwan International Pvt Ltd</title>
  <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
</head>
<body>
  <div class="app">
    <?php include 'sidebar.php'; ?>
    <main>
      <div class="pagehead">
        <div>
          <h1>Products</h1>
          <p class="desc">All items appear here. Item code, barcodes, current stock and delete.</p>
        </div>
        <button type="button" class="btn" onclick="openReg()">+ Register Product</button>
      </div>

      <div class="field" style="max-width:420px">
        <input type="text" id="searchBox" placeholder="Search item code / name / barcode...">
      </div>

      <div class="panel">
        <table>
          <thead>
            <tr>
              <th>Item Code</th>
              <th>Items Name</th>
            
              <th>Stock (Pcs)</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="pBody"></tbody>
        </table>
        <div id="pEmpty" class="dash" style="display:none;padding:20px 0;text-align:center">
          No products found.
        </div>
      </div>
    </main>
  </div>

  <!-- REGISTER MODAL -->
  <div class="modal-overlay" id="regModal" style="display:none">
    <div class="modal-box" style="max-width:900px">
      <div class="modal-header">
        <h2>Register Product</h2>
        <button type="button" class="modal-close" onclick="closeReg()">&times;</button>
      </div>
      <div id="regMsgBox" class="msg-box"></div>

      <div class="field" style="margin-bottom:12px">
        <button type="button" class="btn tab-btn active" id="tabManual" onclick="showPan('manual')">Manual Register</button>
        <button type="button" class="btn tab-btn" id="tabSheet" onclick="showPan('sheet')">Sheet Upload (Bulk)</button>
      </div>

      <!-- MANUAL -->
      <div id="panelManual">
        <div class="split-row">
          <div class="field">
          <label>Item Code *</label>
<input type="text" id="r_itemcode" placeholder="e.g. 45125">

          </div>
          <div class="field">
            <label>Item Name *</label>
            <input type="text" id="r_itemname" placeholder="e.g. Chint breaker">
          </div>
        </div>
        
        <button type="button" class="btn" onclick="submitReg()">Register Product</button>
      </div>

      <!-- SHEET -->
      <div id="panelSheet" style="display:none">
        <div class="sheet-info-box">
          <strong>Hierarchy sheet</strong> — columns auto-detected from headers.
          Required: <b>Item ID</b>, <b>Items Name</b>, <b>Barcode</b>.
          Optional: Level, Parent, Boxes pr Ctn, Pcs pr Box, Total Pcs — headers se mil jaayenge.
        </div>
        <div class="field" style="margin-bottom:12px">
          <label>Sheet File (xlsx, xls, csv, txt) *</label>
          <input type="file" id="r_sheet" accept=".xlsx,.xls,.csv,.txt">
        </div>
        <div class="field" style="margin-bottom:12px;display:flex;gap:10px;align-items:center">
          <button type="button" class="btn btn-ok" onclick="previewSheet()">Preview</button>
          <button type="button" class="btn" id="btnSheetUpload" onclick="submitSheet()" disabled>Upload &amp; Register Sheet</button>
          <span id="sheetFileHint" class="sheet-file-hint"></span>
        </div>
        <div class="progress-wrap" id="progressWrap">
          <div class="progress-bar" id="progressBar"></div>
          <div class="progress-text">
            <div class="progress-spinner"></div>
            <span id="progressLabel">Uploading...</span>
            <span class="pct" id="progressPct">0%</span>
          </div>
        </div>
        <div id="sheetStatus" style="margin-top:10px"></div>
        <div id="sheetPreviewWrap" style="display:none;margin-top:12px">
          <div class="preview-table-wrap">
            <table class="preview-table">
              <thead>
                <tr>
                  <th style="width:40px"><input type="checkbox" id="chkAllHead" checked></th>
                  <th>Level</th>
                  <th>Item Code</th>
                  <th>Items Name</th>
                  <th>Pcs Qty</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="sheetPreview"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js?v=<?php echo filemtime('assets/js/app.js'); ?>"></script>
  <script>
    function $(el) { return document.getElementById(el); }
    function esc(s) {
      return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }
    function openReg() { $('regModal').style.display = 'flex'; }
    function closeReg() { $('regModal').style.display = 'none'; $('regMsgBox').className = 'msg-box'; }
    function showPan(t) {
      if (t === 'manual') {
        $('panelManual').style.display = '';
        $('panelSheet').style.display = 'none';
        $('tabManual').classList.add('active');
        $('tabSheet').classList.remove('active');
      } else {
        $('panelManual').style.display = 'none';
        $('panelSheet').style.display = '';
        $('tabSheet').classList.add('active');
        $('tabManual').classList.remove('active');
      }
    }

    function submitReg() {
      var fd = new FormData();
      fd.append('item_code',  $('r_itemcode').value.trim());

      fd.append('item_name',  $('r_itemname').value.trim());
      
      postForm('../ajax/register_product.php', fd, function(d) {
        $('regMsgBox').className = 'msg-box ' + (d.success ? 'success' : 'error');
        $('regMsgBox').innerText = d.message;
        if (d.success) {
                  ['r_itemcode','r_itemname'].forEach(function(id) {

            var el = $(id); if (el) el.value = '';
          });
                    $('r_itemcode').focus();

          loadProducts($('searchBox').value);
        }
      });
    }

    /* ---- Progress bar ---- */
    function showProgress(label, pct){
      $('progressWrap').classList.add('show');
      $('progressLabel').textContent = label;
      if (pct !== null){
        $('progressPct').textContent = pct + '%';
        $('progressBar').style.width = pct + '%';
        $('progressBar').style.opacity = '1';
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
    function uploadWithProgress(url, fd, onDone){
      var xhr = new XMLHttpRequest();
      xhr.open('POST', url, true);
      xhr.upload.onprogress = function(e){
        if (e.lengthComputable){
          showProgress('Uploading file...', Math.round((e.loaded / e.total) * 100));
        }
      };
      xhr.upload.onload = function(){
        showProgress('Processing on server...', null);
      };
      xhr.onload = function(){
        if (xhr.status === 200){
          try { var d = JSON.parse(xhr.responseText); hideProgress(); onDone(d); }
          catch(ex){ hideProgress(); onDone({success:false, message:'Invalid response from server'}); }
        } else { hideProgress(); onDone({success:false, message:'Upload failed (HTTP ' + xhr.status + ')'}); }
      };
      xhr.onerror = function(){ hideProgress(); onDone({success:false, message:'Network error'}); };
      xhr.send(fd);
    }

    /* ---- Sheet preview ---- */
    function previewSheet() {
      var file = $('r_sheet').files[0];
      if (!file) { $('sheetStatus').innerHTML = '<span class="tag out">Please select a file first.</span>'; return; }
      var fd = new FormData();
      fd.append('sheet', file);
      $('btnSheetUpload').disabled = true;
      $('sheetFileHint').textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
      uploadWithProgress('../ajax/sheet_preview.php', fd, function(d) {
        var el = $('sheetStatus');
        if (!d.success) { el.innerHTML = '<span class="tag out">' + (d.message || 'Error') + '</span>'; return; }
        sheetTruncated = !!d.truncated;
        sheetToken = d.token || '';
        var rows = (d.rows || []).map(function(p) {
          var st = p.exists ? '<span class="tag out">exists</span>' : '<span class="tag ok">new</span>';
          var lvl = '<span class="tag tag-' + p.level.toLowerCase() + '">' + esc(p.level) + '</span>';
          return '<tr>' +
                   '<td><input type="checkbox" class="row-chk" data-serial="'+esc(p.barcode)+'" checked></td>' +
                   '<td>' + lvl + '</td>' +
                   '<td class="mono">' + esc(p.item_code) + '</td>' +
                   '<td>' + esc(p.item_name) + '</td>' +
                   '<td class="mono">' + esc(p.barcode) + '</td>' +
                   '<td>' + esc(p.pcs_qty) + '</td>' +
                   '<td>' + st + '</td>' +
                 '</tr>';
        }).join('');
        $('sheetPreview').innerHTML = rows;
        $('sheetPreviewWrap').style.display = (rows ? 'block' : 'none');
        $('btnSheetUpload').disabled = false;
        var bl = d.byLevel || {};
        el.innerHTML = '<span class="tag ok">Total: '+d.total+'</span> ' +
          '<span class="tag tag-carton">CARTON: '+(bl.CARTON||0)+'</span> ' +
          '<span class="tag tag-box">BOX: '+(bl.BOX||0)+'</span> ' +
          '<span class="tag tag-pcs">PCS: '+(bl.PCS||0)+'</span> ' +
          '<span class="tag ok">New: '+d.newCount+'</span> ' +
          '<span class="tag out">Exists: '+d.dupCount+'</span>' +
          (sheetTruncated ? ' <span class="sheet-file-hint">(Showing first 200 rows — upload will register all '+d.total+')</span>' : '');
      });
    }

    var sheetTruncated = false;
var sheetToken = '';

    function getSelSerial() {
      var list = [];
      document.querySelectorAll('.row-chk:checked').forEach(function(c) { list.push(c.getAttribute('data-serial')); });
      return list;
    }

    function submitSheet() {
      var file = $('r_sheet').files[0];
      if (!file) { $('sheetStatus').innerHTML = '<span class="tag out">Please select a file first.</span>'; return; }
      var fd = new FormData();
      fd.append('sheet', file);
      if (sheetToken) fd.append('token', sheetToken);
      if (!sheetTruncated) {
        var sel = getSelSerial();
        if (!sel.length) { $('sheetStatus').innerHTML = '<span class="tag out">Select at least one row.</span>'; return; }
        fd.append('selected', JSON.stringify(sel));
      }
      $('btnSheetUpload').disabled = true;
      uploadWithProgress('../ajax/register_product.php', fd, function(d) {
        var el = $('sheetStatus');
        if (d.success) {
          el.innerHTML = '<span class="tag ok">Done! ' + d.message + '</span>';
          $('r_sheet').value = '';
          $('sheetFileHint').textContent = '';
          $('btnSheetUpload').disabled = true;
          $('sheetPreviewWrap').style.display = 'none';
          loadProducts($('searchBox').value);
        } else {
          el.innerHTML = '<span class="tag out">' + (d.message || 'Error') + '</span>';
          $('btnSheetUpload').disabled = false;
        }
      });
    }

    /* ---- Products list ---- */
    function loadProducts(q) {
      q = q || '';
      fetch('../ajax/search_product.php?q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(d) {
          var rows = (d.data || []).map(function(p) {
            var bcs = p.barcodes || '';
            var bcTag = bcs
              ? '<span class="tag in" style="font-size:11px;max-width:300px;word-break:break-all">' + esc(bcs) + '</span>'
              : '<span class="tag out">none</span>';
                        return '<tr>' +
                     '<td class="mono">' + esc(p.item_code) + '</td>' +
                     '<td>' + esc(p.item_name) + '</td>' +
                     '<td><b>' + esc(p.current_stock_pcs) + '</b></td>' +
                     '<td><button type="button" class="btn btn-danger btn-sm" onclick="delProduct(\'' + esc(p.item_code) + '\')">Delete</button></td>' +
                   '</tr>';

          }).join('');
          $('pBody').innerHTML = rows;
          $('pEmpty').style.display = (!d.data || !d.data.length) ? 'block' : 'none';
        });
    }

    function delProduct(item_code) {
      if (!confirm('Delete this product (item code)?\n' + item_code)) return;
      fetch('../ajax/delete_product.php?item_code=' + encodeURIComponent(item_code))
        .then(function(r) { return r.json(); })
        .then(function(d) {
          if (d.success) { loadProducts($('searchBox').value); }
          else showMsg(d.message || 'Error', 'danger');
        });
    }

    /* ---- Select all toggle ---- */
    $('chkAllHead').addEventListener('change', function(){
      var on = this.checked;
      document.querySelectorAll('.row-chk').forEach(function(c){ c.checked = on; });
    });

    $('searchBox').addEventListener('input', function(e) { loadProducts(e.target.value); });
    loadProducts();
  </script>
</body>
</html>
