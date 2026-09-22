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
</head>
<body>
<div class="app">
  <?php include 'sidebar.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>Dashboard</h1>
        <p class="desc">Stock In / Out ka quick overview</p>
      </div>
      <div style="display:flex;gap:10px">
        <a class="btn green" href="stock-in.php">+ Stock In</a>
    <a class="btn red" href="stock-out.php">Scan / Stock Out</a>
      </div>
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

/* ---- Items / Cartons: collapsed = total CTN, click = expand barcodes ---- */
function toggleCtn(head){
  var body = head.nextElementSibling;
  if(!body) return;
  var open = body.style.display !== 'none';
  body.style.display = open ? 'none' : 'block';
  head.classList.toggle('open', !open);
}
function toggleCtnEvent(e, el){
  if(e.target.closest('.del-item')) return;
  toggleCtn(el);
}
function renderItems(list){
  var box = $('ctnList');
  if(!box) return;
  if(!list || !list.length){ box.innerHTML='<span class="dash">Abhi koi item register nahi hua.</span>'; return; }
  box.innerHTML = list.map(function(it){
    var bcs = String(it.barcodes||'').split('|').filter(function(x){ return x !== ''; });
    var lines = bcs.map(function(b,i){
      return '<div class="ctn-line" data-barcode="'+esc(b)+'"><span class="idx">'+(i+1)+'.</span><span class="mono">'+esc(b)+'</span> <span class="tag out del-bc" style="cursor:pointer" data-barcode="'+esc(b)+'">Del</span></div>';
    }).join('');
    return '<div class="ctn-item">'+
      '<div class="ctn-head" data-code="'+esc(it.item_code)+'" onclick="toggleCtnEvent(event,this)">'+
        '<div><div class="name">'+esc(it.item_name)+'</div>'+
        '<div class="meta">'+esc(it.item_code)+' · Stock: '+esc(it.current_stock_pcs)+' pcs</div></div>'+
        '<div class="right"><span class="tag in">'+it.ctn_count+' CTN</span> <button class="btn btn-danger btn-sm del-item" data-code="'+esc(it.item_code)+'">Del</button> <span class="caret">&#9656;</span></div>'+
      '</div>'+
      '<div class="ctn-body">'+(lines || '<span class="dash">No barcodes.</span>')+'</div>'+
    '</div>';
  }).join('');
}

function deleteItem(code){
  if(!confirm('Delete this product and ALL its stock?')) return;
  fetch('../ajax/delete_product.php?item_code='+encodeURIComponent(code))
    .then(function(r){ return r.json(); })
    .then(function(d){ if(d.success){ var el=document.querySelector('.ctn-head[data-code="'+code+'"]'); if(el) el.closest('.ctn-item').remove(); } });
}
function deleteBc(barcode){
  if(!confirm('Delete this barcode and reverse its stock?')) return;
  fetch('../ajax/delete_barcode.php?barcode='+encodeURIComponent(barcode))
    .then(function(r){ return r.json(); })
    .then(function(d){ if(d.success){ var line=document.querySelector('.ctn-line[data-barcode="'+barcode+'"]'); if(line){ var body=line.closest('.ctn-body'); line.remove(); if(body&&!body.querySelector('.ctn-line')) body.innerHTML='<span class="dash">No barcodes.</span>'; } } });
}
$('ctnList').addEventListener('click', function(e){
  var di = e.target.closest('.del-item');
  if(di){ deleteItem(di.dataset.code); return; }
  var db = e.target.closest('.del-bc');
  if(db){ deleteBc(db.dataset.barcode); }
});

ajax('../ajax/dashboard_stats.php', function(d){
  if(!d.success) return;
  var s = d.data;
  $('stats').innerHTML =
    '<div class="stat-card"><div class="label">Total Products</div><div class="value">'+s.total_products+'</div></div>'+
    '<div class="stat-card"><div class="label">Total Stock</div><div class="value">'+s.total_stock+'</div></div>'+
    '<div class="stat-card"><div class="label">Total Stock In</div><div class="value green">+'+s.total_in+'</div></div>'+
    '<div class="stat-card"><div class="label">Total Stock Out</div><div class="value rust">-'+s.total_out+'</div></div>'+
    '<div class="stat-card"><div class="label">Low Stock Items</div><div class="value amber">'+s.low_stock+'</div></div>';
  renderRecent(s.recent);
});

renderItems(DASH_ITEMS);
</script>
</body>
</html>