<?php $page = 'products'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — Diwan International Pvt Ltd</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
</head>
<body>
<div class="app">
  <?php include 'sidebar.php'; ?>
  <main>
    <div class="pagehead">
      <div>
        <h1>Products</h1>
        <p class="desc">All items appear here. Item code, current stock.</p>
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
            <th>Item Code</th><th>Items Name</th>
            <th>Barcodes</th><th>Stock (Pcs)</th>
          </tr>
        </thead>
        <tbody id="pBody"></tbody>
      </table>
      <div id="pEmpty" class="dash" style="display:none;padding:20px 0;text-align:center">No products found.</div>
    </div>
  </main>
</div>

<!-- REGISTER MODAL -->
<div class="modal-overlay" id="regModal" style="display:none">
  <div class="modal-box" style="max-width:860px">
    <div class="modal-header">
      <h2>Register Product</h2>
      <button type="button" class="modal-close" onclick="closeReg()">&times;</button>
    </div>
    <div id="regMsgBox" class="msg-box"></div>
    <div class="field" style="margin-bottom:12px">
      <button type="button" class="btn tab-btn active" id="tabManual" onclick="showPan('manual')">Manual Register</button>
      <button type="button" class="btn tab-btn" id="tabSheet" onclick="showPan('sheet')">Sheet Upload (Bulk)</button>
    </div>

    <!-- Manual -->
    <div id="panelManual">
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
      <div class="field">
        <label>Barcode <span style="color:var(--muted)">(optional)</span></label>
        <input type="text" id="r_barcode" placeholder="e.g. P001001000001">
      </div>
      <button type="button" class="btn" onclick="submitReg()">Register Product</button>
    </div>

    <!-- Sheet -->
    <div id="panelSheet" style="display:none">
      <p class="desc" style="margin-bottom:12px">
        Hierarchy sheet — columns auto-detected from headers (Item ID | Items Name | Barcode minimum).
      </p>
      <div class="field" style="margin-bottom:12px">
        <label>Sheet File (xlsx, xls, csv, txt) *</label>
        <input type="file" id="r_sheet" accept=".xlsx,.xls,.csv,.txt">
      </div>
      <div class="field" style="margin-bottom:12px">
        <button type="button" class="btn btn-ok" onclick="previewSheet()">Preview</button>
        <button type="button" class="btn" id="btnSheetUpload" onclick="submitSheet()" disabled>Upload &amp; Register Sheet</button>
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
  </div>
</div>

<script src="assets/js/app.js?v=<?php echo filemtime('assets/js/app.js'); ?>"></script>
<script>
function $(el){ return document.getElementById(el); }
function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
function openReg(){ document.getElementById('regModal').style.display='flex'; }
function closeReg(){ document.getElementById('regModal').style.display='none'; document.getElementById('regMsgBox').className='msg-box'; }
function showPan(t){
  if(t==='manual'){ $('panelManual').style.display=''; $('panelSheet').style.display='none'; $('tabManual').classList.add('active'); $('tabSheet').classList.remove('active'); }
  else{ $('panelManual').style.display='none'; $('panelSheet').style.display=''; $('tabSheet').classList.add('active'); $('tabManual').classList.remove('active'); }
}

// Progress helper
function showProgress(label, pct){
  $('progressWrap').classList.add('show');
  $('progressLabel').textContent = label;
  if (pct !== null){ $('progressPct').textContent = pct + '%'; $('progressBar').style.width = pct + '%'; }
  else { $('progressPct').textContent = ''; $('progressBar').style.width = '100%'; $('progressBar').style.opacity = '.4'; }
}
function hideProgress(){ $('progressWrap').classList.remove('show'); $('progressBar').style.width = '0%'; $('progressBar').style.opacity = '1'; }
function uploadWithProgress(url, fd, onDone){
  var xhr = new XMLHttpRequest(); xhr.open('POST', url, true);
  xhr.upload.onprogress = function(e){ if(e.lengthComputable){ showProgress('Uploading file...', Math.round((e.loaded/e.total)*100)); } };
  xhr.onload = function(){
    if (xhr.status===200){ showProgress('Processing...', null); try{var d=JSON.parse(xhr.responseText);hideProgress();onDone(d);}catch(ex){hideProgress();onDone({success:false,message:'Invalid response'});} }
    else { hideProgress(); onDone({success:false,message:'Upload failed'}); }
  };
  xhr.onerror = function(){ hideProgress(); onDone({success:false,message:'Network error'}); };
  xhr.send(fd);
}

// Manual register
function submitReg(){
  var fd = new FormData();
  fd.append('item_code', $('r_itemid').value.trim());
  fd.append('item_name', $('r_itemname').value.trim());
  fd.append('barcode', $('r_barcode').value.trim());
  postForm('../ajax/register_product.php', fd, function(d){
    $('regMsgBox').className='msg-box '+(d.success?'success':'error');
    $('regMsgBox').innerText=d.message;
    if(d.success){ ['r_itemid','r_itemname','r_barcode'].forEach(function(id){var el=$(id);if(el)el.value='';}); $('r_itemid').focus(); }
  });
}

// Sheet preview — XHR with REAL upload progress
function previewSheet(){
  var file=$('r_sheet').files[0];
  if(!file){$('sheetStatus').innerHTML='<span class="tag low">Please select a file.</span>';return;}
  var fd=new FormData(); fd.append('sheet',file);
  $('btnSheetUpload').disabled=true;
  uploadWithProgress('../ajax/sheet_preview.php',fd,function(d){
    var el=$('sheetStatus');
    if(!d.success){el.innerHTML='<span class="tag low">'+(d.message||'Error')+'</span>';return;}
    var rows=(d.rows||[]).map(function(p){
      var st=p.exists?'<span class="tag low">exists</span>':'<span class="tag ok">new</span>';
      var lvl='<span class="tag '+(p.level==='CARTON'?'low':(p.level==='BOX'?'out':'in'))+'">'+esc(p.level)+'</span>';
      return '<tr><td><input type="checkbox" class="row-chk" data-serial="'+esc(p.barcode)+'" checked></td><td>'+lvl+'</td><td class="mono">'+esc(p.barcode)+'</td><td>'+esc(p.item_name)+'</td><td><span class="tag in">'+esc(p.pcs_qty)+'</span></td><td>'+st+'</td></tr>';
    }).join('');
    $('sheetPreview').innerHTML=rows;
    $('sheetPreviewWrap').style.display=(rows?'block':'none');
    $('btnSheetUpload').disabled=false;
    var bl=d.byLevel||{};
    el.innerHTML='<label style="margin-right:10px"><input type="checkbox" id="chkAll" checked> Select all</label><span class="tag ok">Total: '+d.total+' | CARTON: '+(bl.CARTON||0)+' | BOX: '+(bl.BOX||0)+' | PCS: '+(bl.PCS||0)+' | New: '+d.newCount+' | Already exist: '+d.dupCount+'</span>';
    $('chkAll').addEventListener('change',function(){var on=this.checked;document.querySelectorAll('.row-chk').forEach(function(c){c.checked=on;});});
  });
}
function getSelSerial(){var list=[];document.querySelectorAll('.row-chk:checked').forEach(function(c){list.push(c.getAttribute('data-serial'));});return list;}

// Sheet upload — XHR with REAL upload progress
function submitSheet(){
  var file=$('r_sheet').files[0];
  if(!file){$('sheetStatus').innerHTML='<span class="tag low">Please select a file.</span>';return;}
  var sel=getSelSerial();
  if(!sel.length){$('sheetStatus').innerHTML='<span class="tag low">Select at least one row.</span>';return;}
  var fd=new FormData(); fd.append('sheet',file); fd.append('selected',JSON.stringify(sel));
  $('btnSheetUpload').disabled=true;
  uploadWithProgress('../ajax/register_product.php',fd,function(d){
    var el=$('sheetStatus');
    if(d.success){el.innerHTML='<span class="tag ok">Done! '+d.message+'</span>';$('r_sheet').value='';$('btnSheetUpload').disabled=true;$('sheetPreviewWrap').style.display='none';}
    else{el.innerHTML='<span class="tag low">'+(d.message||'Error')+'</span>';$('btnSheetUpload').disabled=false;}
  });
}

function loadProducts(q){
  q=q||'';
  fetch('../ajax/search_product.php?q='+encodeURIComponent(q))
    .then(function(r){return r.json();})
    .then(function(d){
      var rows=(d.data||[]).map(function(p){
        var bc=parseInt(p.barcode_count||0);
        var bcTag=bc>0?'<span class="tag in">'+bc+'</span>':'<span class="tag low">0</span>';
        return '<tr><td class="mono">'+esc(p.item_code)+'</td><td>'+esc(p.item_name)+'</td><td>'+bcTag+'</td><td><b>'+esc(p.current_stock_pcs)+'</b></td></tr>';
      }).join('');
      $('pBody').innerHTML=rows;
      $('pEmpty').style.display=(!d.data||!d.data.length)?'block':'none';
    });
}
$('searchBox').addEventListener('input',function(e){loadProducts(e.target.value);});
loadProducts();
</script>
</body>
</html>