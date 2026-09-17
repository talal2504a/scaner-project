<?php
// root/scan.php — USB Scanner (Stock Out ONLY)
$page = 'scan';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scan — Stock Out — Stock System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include '_layout.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>USB Scanner — Stock Out</h1>
        <p class="desc">Serial scan karo aur stock out karo</p>
      </div>
    </div>

    <div id="msgBox" class="msg-box"></div>

    <!-- SCANNER ZONE -->
    <div class="scan-box">
      <div class="scan-title">📷 Scan Barcode (Enter daba kar)</div>
      <input type="text" id="scanInput" autocomplete="off" placeholder="Scan barcode / type serial then ENTER" autofocus>
      <div class="scan-hint">USB scanner automatic enter send karta hai. Product milte hi qty box focus hoga.</div>
    </div>

    <!-- Match / Not found -->
    <div class="prod-card" id="prodCard">
      <div class="p-name" id="pc_name"></div>
      <div class="p-chips" id="pc_chips"></div>
      <div style="display:flex;align-items:center;gap:18px;margin-top:14px;flex-wrap:wrap">
        <div>
          <div class="meta" id="pc_stock"></div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="number" id="scQty" value="1" min="1" style="width:80px;padding:9px;border-radius:8px;border:1px solid #DDE3EC;font-size:15px">
          <input type="text" id="scRemark" placeholder="Remark (optional)" style="width:220px;padding:9px;border-radius:8px;border:1px solid #DDE3EC;font-size:13px">
          <button class="btn red" id="btnConfirm">- Stock Out</button>
        </div>
      </div>
    </div>

    <div class="panel" style="margin-top:22px">
      <h3>Last 10 Scans <span>(stock out)</span></h3>
      <div id="scanHistory"><span class="dash">Loading...</span></div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
<script>
var currentProduct = null;
var historyCount = 0;

// --- Scanner main flow ---
$('scanInput').addEventListener('keydown', function(e){
  if (e.key === 'Enter'){
    e.preventDefault();
    var s = this.value.trim();
    if (!s) return;
    lookupScan(s);
  }
});

function lookupScan(s){
  fetch('../ajax/search_product.php?serial='+encodeURIComponent(s))
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.success && d.data){
        var p = d.data,
            c = [], fields = ['model','poles','rating','voltage','ka','packaging','category','notes'];
        fields.forEach(function(f){
          if (p[f]) c.push('<span class="prod-chip"><b>'+f.toUpperCase()+'</b> '+esc(p[f])+'</span>');
        });
        $('pc_name').innerHTML = esc(p.item_name) + ' &nbsp;<span class="tag">'+esc(p.serial_code)+'</span>';
        $('pc_chips').innerHTML = c.join('') + '<span class="prod-chip"><b>STOCK</b> '+p.current_stock+'</span>';
        $('pc_stock').innerHTML = 'Available: <span class="prod-stock">'+p.current_stock+'</span>';
        $('prodCard').classList.add('visible');
        currentProduct = p;
        $('scQty').value = 1; $('scRemark').value = '';
        $('scanInput').value = '';
        $('scQty').focus(); $('scQty').select();
      } else {
        showMsg('Product register nahi hai: ' + s, 'danger');
        $('prodCard').classList.remove('visible');
        currentProduct = null;
        $('scanInput').value = '';
        $('scanInput').focus();
      }
    });
}

// --- Confirm stock out ---
function confirmOut(){
  if (!currentProduct) return;
  var fd = new FormData();
  fd.append('serial', currentProduct.serial_code);
  fd.append('qty', $('scQty').value);
  fd.append('remark', $('scRemark').value.trim());
  postForm('../ajax/stock_out_scanner.php', fd, function(d){
    if (d.success){
      currentProduct = null;
      $('prodCard').classList.remove('visible');
      loadScanHistory(true);
      $('scanInput').focus();
    } else {
      if (d.product) { currentProduct = d.product; } // stock shortage pe refresh
      if (d.serial) { $('scanInput').value = d.serial; }
    }
  });
}
$('btnConfirm').addEventListener('click', confirmOut);
$('scQty').addEventListener('keydown', function(e){ if (e.key === 'Enter') confirmOut(); });

// --- History ---
function loadScanHistory(forceCount){
  ajax('../ajax/dashboard_stats.php', function(d){
    if (!d.success) return;
    var list = d.data.recent || [];
    if (!forceCount && historyCount >= list.length) return;
    historyCount = list.length;
    if (!list.length){ $('scanHistory').innerHTML = '<span class="dash">Abhi koi scan nahi hua.</span>'; return; }
    $('scanHistory').innerHTML = list.map(function(r){
      var lab = r.rec_type==='in' ? 'IN' : 'OUT';
      var k = r.rec_type==='in' ? 'tag in' : 'tag out';
      return '<div class="item-row"><div><div class="name">'+esc(r.item_name)+'</div>'+
        '<div class="meta">'+esc(r.serial_code)+' · '+esc(r.entry_date)+'</div></div>'+
        '<span class="'+k+'">'+lab+' '+r.quantity+'</span></div>';
    }).join('');
  });
}
loadScanHistory();
</script>
</body>
</html>