<?php
// root/index.php — Dashboard
$page = 'dashboard';
require_once dirname(__DIR__) . '/config/db.php';

/* ---------- Items + unke saare barcodes (carton-wise) ---------- */
$conn->query("SET SESSION group_concat_max_len = 100000");
$itemRes = $conn->query(
    "SELECT p.item_code, p.item_name, p.current_stock_pcs,
            COUNT(b.barcode) AS ctn_count,
            GROUP_CONCAT(b.barcode ORDER BY b.barcode SEPARATOR '|') AS barcodes
     FROM products p
     LEFT JOIN barcodes b ON b.item_code = p.item_code AND b.is_consumed = 0
     GROUP BY p.item_code, p.item_name, p.current_stock_pcs
     ORDER BY p.item_name ASC"
);
$dashItems = $itemRes ? $itemRes->fetch_all(MYSQLI_ASSOC) : [];
$dashItemsJson = json_encode($dashItems, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Diwan International Pvt Ltd</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
<style>
  /* ---- Delete Confirmation Modal ---- */
.del-modal-overlay{
  display:none;position:fixed;top:0;left:0;right:0;bottom:0;
  background:rgba(0,0,0,.72);z-index:2000;
  align-items:center;justify-content:center;padding:20px;
}
.del-modal-overlay.open{display:flex}
.del-modal-box{
  background:var(--panel);border:1px solid var(--line);border-radius:14px;
  padding:26px;width:100%;max-width:370px;text-align:center;
  box-shadow:0 20px 60px rgba(0,0,0,.55);
  animation:slideDown .25s ease;
}
.del-icon{
  width:52px;height:52px;border-radius:50%;margin:0 auto 14px auto;
  background:var(--rust-bg);color:var(--rust);
  display:flex;align-items:center;justify-content:center;
  font-size:24px;border:1.5px solid #5A2026;
}
.del-modal-box h3{margin:0 0 8px 0;font-size:16px;color:var(--text)}
.del-modal-box p{font-size:13px;color:var(--muted);margin:0 0 20px 0;line-height:1.6}
.del-modal-box .del-code{
  display:inline-block;background:var(--panel-2);border:1px solid var(--line);
  border-radius:6px;padding:3px 10px;font-size:12px;color:var(--amber);
  font-family:'Sora',sans-serif;margin-bottom:12px;
}
.del-modal-actions{display:flex;gap:10px}
.del-modal-actions .btn{flex:1}
  .bc-code-chip{
  font-size:12px;color:var(--muted);background:var(--panel-2);
  border:1px solid var(--line);border-radius:7px;padding:5px 10px;
  margin:8px 0 8px 0;display:inline-block;
}
.bc-code-chip b{color:var(--amber)}
.bc-left{display:flex;flex-direction:column;gap:3px;align-items:flex-start}
.bc-svg{
  background:#fff;border:1px solid var(--line);border-radius:6px;
  padding:4px 6px;height:34px;
}
.bc-num{font-size:13px;letter-spacing:.06em;color:var(--text)}
.del-bc-btn{
  background:var(--rust-bg);color:var(--rust);border:1.5px solid #5A2026;
  border-radius:7px;padding:5px 12px;font-size:11.5px;font-weight:600;cursor:pointer;
  font-family:'Inter',sans-serif;transition:all .15s;margin-left:auto;
}
.del-bc-btn:hover{background:#42161A;color:#FF4D5E;border-color:#FF4D5E}
.del-item{
  background:var(--rust-bg);color:var(--rust);border:1.5px solid #5A2026;
  border-radius:7px;padding:3px 10px;font-size:11px;font-weight:600;cursor:pointer;
  font-family:'Inter',sans-serif;transition:all .15s;
}
.del-item:hover{background:#42161A;color:#FF4D5E;border-color:#FF4D5E}
/* ---- Items / Cartons panel (dashboard) ---- */
.ctn-head{
  display:flex;justify-content:space-between;align-items:center;padding:10px 0;
  border-bottom:1px solid var(--line);cursor:pointer;border-radius:6px;
  transition:background .2s;
}
.ctn-head:hover{background:rgba(255,255,255,.05)}
.ctn-head .name{font-size:13px;font-weight:500;color:var(--text)}
.ctn-head .meta{font-size:11.5px;color:var(--muted)}
.ctn-head .right{display:flex;align-items:center;gap:10px;white-space:nowrap}
.ctn-head .caret{
  color:var(--muted);font-size:11px;display:inline-block;
  transition:transform .35s cubic-bezier(.4,0,.2,1),color .2s;
}
.ctn-head:hover .caret{color:var(--amber)}
.ctn-head.open .caret{transform:rotate(90deg);color:var(--amber)}
.ctn-body{
  max-height:0;overflow:hidden;padding:0 0 0 14px;border-bottom:1px solid var(--line);
  transition:max-height .4s ease,padding .3s ease;
}
.ctn-body.open{max-height:800px;padding:6px 0 12px 14px}
.ctn-item:last-child .ctn-head{border-bottom:none}
.ctn-item:last-child .ctn-body{border-bottom:none}
.ctn-line{
  font-size:12.5px;padding:4px 8px;color:var(--text);display:flex;align-items:center;gap:8px;
  border-radius:6px;opacity:0;transform:translateX(-8px);
  transition:opacity .25s ease,transform .25s ease,background .15s;
}
.ctn-body.open .ctn-line{opacity:1;transform:translateX(0)}
.ctn-body.open .ctn-line:nth-child(1){transition-delay:.05s}
.ctn-body.open .ctn-line:nth-child(2){transition-delay:.10s}
.ctn-body.open .ctn-line:nth-child(3){transition-delay:.15s}
.ctn-body.open .ctn-line:nth-child(4){transition-delay:.20s}
.ctn-body.open .ctn-line:nth-child(5){transition-delay:.25s}
.ctn-line:hover{background:rgba(229,20,46,.08)}
.ctn-line .idx{color:var(--muted);display:inline-block;width:28px}
</style>
</head>
<body>
<div class="app">
  <?php include 'sidebar.php'; ?>
<!-- Delete Confirmation Modal -->
<div class="del-modal-overlay" id="delModal">
  <div class="del-modal-box">
    <div class="del-icon">🗑️</div>
    <h3 id="delModalTitle">Delete</h3>
    <span class="del-code" id="delModalCode" style="display:none"></span>
    <p id="delModalMsg">Are you sure?</p>
    <div class="del-modal-actions">
      <button type="button" class="btn ghost" onclick="closeDelModal()">Cancel</button>
      <button type="button" class="btn red" id="delModalConfirm">Delete</button>
    </div>
  </div>
</div>

  <main>
    <div class="pagehead">
      <div>
        <h1>Dashboard</h1>
        <p class="desc">Stock In / Out ka quick overview</p>
      </div>
         <div style="display:flex;gap:10px"></div>
    </div>

    <div id="msgBox" class="msg-box"></div>

    <!-- STAT CARDS -->
    <div class="stat-row" id="stats"></div>

    <div class="grid-2">
      <!-- Items / Cartons (LEFT) -->
      <div class="panel">
      <h3>Items — Cartons <span>(click kisi bhi row pe — har carton ka real barcode)</span></h3>    
      <div id="ctnList"><span class="dash">Loading...</span></div>
      </div>

      <!-- Recent Activity (RIGHT) -->
      <div class="panel">
        <h3>Recent Activity <span>(all)</span></h3>
        <div id="recentList"><span class="dash">Loading...</span></div>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js?v=<?php echo filemtime('assets/js/app.js'); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
var DASH_ITEMS = <?php echo $dashItemsJson; ?>;
function renderRecent(list){
  if(!list || !list.length){ $('recentList').innerHTML='<span class="dash">No activity yet.</span>'; return; }
  $('recentList').innerHTML = list.map(function(r){
    var klass = r.rec_type==='in' ? 'in-txt' : 'out-txt';
    var lab = r.rec_type==='in' ? 'IN +' : 'OUT -';
    return '<div class="item-row">'+
      '<div><div class="name">'+esc(r.item_name)+'</div>'+
      '<div class="meta">'+esc(r.barcode)+' · '+esc(r.level)+' ('+esc(r.pcs_qty)+' pcs) · '+esc(r.entry_date)+'</div></div>'+
      '<div class="right"><div class="qty '+klass+'">'+lab+r.pcs_qty+'</div></div></div>';
  }).join('');
}

/* ---- Items / Cartons: row click pe smooth toggle (koi button nahi) ---- */
function toggleCtn(head){
  var body = head.nextElementSibling;
  if(!body) return;
  var open = body.classList.contains('open');
  body.classList.toggle('open', !open);
  head.classList.toggle('open', !open);
}
function toggleCtnEvent(e, el){
  if(e.target.closest('.del-item') || e.target.closest('.del-bc-btn')) return;
  toggleCtn(el);
}
/* ---- Delete Confirmation Modal ---- */
var delPending = null;

function openDelModal(title, code, msg, type, value){
  $('delModalTitle').textContent = title;
  $('delModalMsg').textContent = msg;
  $('delModalCode').style.display = code ? 'inline-block' : 'none';
  $('delModalCode').textContent = code || '';
  delPending = { type: type, value: value };
  $('delModal').classList.add('open');
}
function closeDelModal(){
  $('delModal').classList.remove('open');
  delPending = null;
}
$('delModalConfirm').addEventListener('click', function(){
  if(!delPending) return;
  var p = delPending;
  closeDelModal();
  if(p.type === 'bc') doDeleteBc(p.value);
  else doDeleteItem(p.value);
});

function deleteItem(code){
  var name = '';
  var el = document.querySelector('.ctn-head[data-code="'+code+'"]');
  if(el && el.querySelector('.name')) name = el.querySelector('.name').textContent;
  openDelModal('Delete Product', code, 'Delete "'+name+'" and ALL its stock? Ye wapas nahi aayega.', 'item', code);
}
function deleteBc(barcode){
  openDelModal('Delete Barcode', barcode, 'Delete this barcode and reverse its stock? Ye wapas nahi aayega.', 'bc', barcode);
}

function doDeleteItem(code){
  fetch('../ajax/delete_product.php?item_code='+encodeURIComponent(code))
    .then(function(r){ return r.json(); })
    .then(function(d){
      if(!d.success){ alert(d.message || 'Delete failed.'); return; }
      refreshDashboard();
    })
    .catch(function(){ alert('Delete error.'); });
}
function doDeleteBc(barcode){
  fetch('../ajax/delete_barcode.php?barcode='+encodeURIComponent(barcode))
    .then(function(r){ return r.json(); })
    .then(function(d){
      if(!d.success){ alert(d.message || 'Delete failed.'); return; }
      refreshDashboard();
    })
    .catch(function(){ alert('Delete error.'); });
}
function renderItems(list){
  var box = $('ctnList');
  if(!box) return;
  if(!list || !list.length){ box.innerHTML='<span class="dash">Abhi koi item register nahi hua.</span>'; return; }
  box.innerHTML = list.map(function(it){
    var bcs = String(it.barcodes||'').split('|').filter(function(x){ return x !== ''; }).map(function(x){
      var i = x.indexOf(':');
      return i > 0 ? x.substring(i+1) : x;
    });
    var lines = bcs.map(function(b,i){
      return '<div class="ctn-line" data-barcode="'+esc(b)+'">'+
        '<div class="bc-left">'+
          '<svg class="bc-svg" data-bc="'+esc(b)+'"></svg>'+
          '<span class="bc-num mono">'+esc(b)+'</span>'+
        '</div>'+
        '<button type="button" class="del-bc-btn" data-barcode="'+esc(b)+'" title="Delete barcode">Del</button>'+
      '</div>';
    }).join('');
    return '<div class="ctn-item">'+
      '<div class="ctn-head" data-code="'+esc(it.item_code)+'" onclick="toggleCtnEvent(event,this)">'+
        '<div><div class="name">'+esc(it.item_name)+'</div>'+
        '<div class="meta">'+esc(it.item_code)+' · Stock: '+esc(it.current_stock_pcs)+' pcs</div></div>'+
        '<div class="right"><span class="tag in">'+it.ctn_count+' CTN</span> <button type="button" class="del-item" data-code="'+esc(it.item_code)+'">Del</button> <span class="caret">&#10095;</span></div>'+
      '</div>'+
      '<div class="ctn-body">'+
        '<div class="bc-code-chip">Item Code: <b>'+esc(it.item_code)+'</b></div>'+
        (lines || '<span class="dash">No barcodes.</span>')+
      '</div>'+
    '</div>';
  }).join('');
  var svgs = box.querySelectorAll('.bc-svg');
  for(var i=0;i<svgs.length;i++){
    (function(svg){
      try{
        JsBarcode(svg, svg.getAttribute('data-bc'), {
          format:'CODE128', height:28, width:1.4,
          displayValue:false, background:'#ffffff', lineColor:'#000000'
        });
      }catch(e){ svg.style.display='none'; }
    })(svgs[i]);
  }
}

function refreshDashboard(){
  /* Items + CTN counts */
  ajax('../ajax/dashboard_items.php', function(d){
    if(d && d.success) renderItems(d.data);
  });
  /* Stats cards + Recent Activity */
  ajax('../ajax/dashboard_stats.php', function(d){
    if(!d.success) return;
    var s = d.data;
    $('stats').innerHTML =
      '<div class="stat-card"><div class="label">Total Products</div><div class="value">'+s.total_products+'</div></div>'+
      '<div class="stat-card"><div class="label">Total Stock</div><div class="value">'+s.total_stock+'</div></div>'+
     '<div class="stat-card"><div class="label">Total Stock In</div><div class="value green">'+(s.total_in>0 ? '+'+s.total_in : '0')+'</div></div>'+
      '<div class="stat-card"><div class="label">Total Stock Out</div><div class="value rust">'+(s.total_out>0 ? '-'+s.total_out : '0')+'</div></div>'+
      '<div class="stat-card"><div class="label">Low Stock Items</div><div class="value amber">'+s.low_stock+'</div></div>';
    renderRecent(s.recent);
  });
}
$('ctnList').addEventListener('click', function(e){
  var di = e.target.closest('.del-item');
  if(di){ deleteItem(di.dataset.code); return; }
  var db = e.target.closest('.del-bc-btn');
  if(db){ deleteBc(db.dataset.barcode); }
});

ajax('../ajax/dashboard_stats.php', function(d){
  if(!d.success) return;
  var s = d.data;
  $('stats').innerHTML =
    '<div class="stat-card"><div class="label">Total Products</div><div class="value">'+s.total_products+'</div></div>'+
    '<div class="stat-card"><div class="label">Total Stock</div><div class="value">'+s.total_stock+'</div></div>'+
    '<div class="stat-card"><div class="label">Total Stock In</div><div class="value green">+'+s.total_in+'</div></div>'+
    '<div class="stat-card"><div class="label">Total Stock Out</div><div class="value rust">'+(s.total_out>0 ? '-'+s.total_out : '0')+'</div></div>'+
    '<div class="stat-card"><div class="label">Low Stock Items</div><div class="value amber">'+s.low_stock+'</div></div>';
  renderRecent(s.recent);
});

renderItems(DASH_ITEMS);
</script>
</body>
</html>