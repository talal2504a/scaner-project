<?php
$page = 'scan';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Scan — Stock Out — Stock System</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
</head>
<body>
<div class="app">
  <?php include 'sidebar.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>USB Scanner — Stock Out</h1>
        <p class="desc">Lock open karo → USB scanner se scan karo → AUTO Stock Out. Manual ho to barcode type karke ENTER.</p>
      </div>
    </div>

    <div id="msgBox" class="msg-box"></div>

    <!-- LOCK TOGGLE -->
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:18px;">
      <button id="lockBtn" class="btn red" style="font-size:15px;padding:14px 26px">
        🔒 LOCKED — Unlock karo
      </button>
      <span id="lockMsg" class="dash" style="font-size:13px">Lock band hai — scan ignore hoga. Unlock karke scan karo.</span>
    </div>

    <!-- SCANNER ZONE -->
    <div class="scan-box" id="scanBox" onclick="$('scanInput').focus()">
      <div class="scan-title">📷 Scan Barcode <span id="scanState"></span></div>
      <input type="text" id="scanInput" autocomplete="off" placeholder="Scan barcode ya barcode type karke ENTER dabaao" autofocus>
      <div class="scan-hint" id="scanHint">Pehle lock unlock karo, phir scan — auto stock out hoga.</div>
    </div>

    <!-- HISTORY -->
    <div class="panel" style="margin-top:22px">
      <h3>Last 10 Scans <span>(stock out)</span></h3>
      <div id="scanHistory"><span class="dash">Loading...</span></div>
    </div>
  </main>
</div>

<script src="assets/js/app.js?v=<?php echo filemtime('assets/js/app.js'); ?>"></script>
<script>
var unlocked = false;   // Lock DEFAULT BAND
var scanning = false;
var historyCount = 0;
var barcodeTimer = null; // USB scanner bina-Enter wale ke liye

/* ===== LOCK / UNLOCK ===== */
function setLock(open){
  unlocked = open;
  var btn = $('lockBtn');
  if (open){
    btn.className = 'btn green';
    btn.innerHTML = '🔓 UNLOCKED — Lock karo';
    $('lockMsg').textContent = 'Lock khula hai — scan karo, auto stock out hoga.';
    $('scanBox').style.borderColor = 'var(--blue)';
    $('scanHint').textContent = 'AB SCAN KARO — barcode aate hi auto stock out ho jayega.';
  } else {
    btn.className = 'btn red';
    btn.innerHTML = '🔒 LOCKED — Unlock karo';
    $('lockMsg').textContent = 'Lock band hai — scan ignore hoga. Unlock karke scan karo.';
    $('scanBox').style.borderColor = '';
    $('scanHint').textContent = 'Pehle lock unlock karo, phir scan ya barcode type karke ENTER.';
    $('scanInput').value = '';
  }
  $('scanInput').focus();
}
$('lockBtn').addEventListener('click', function(){ setLock(!unlocked); });

/* ===== SCAN PROCESS ===== */
function processSerial(s){
  if (scanning) return;
  s = s.trim();
  if (!s) return;

  // Lock BAND hai => ignore
  if (!unlocked){
    showMsg('🔒 LOCKED hai — pehle Unlock karo, phir scan karo', 'danger');
    clearBarcodeTimer();
    $('scanInput').value = '';
    $('scanInput').focus();
    return;
  }

  scanning = true;
  var fd = new FormData();
  fd.append('serial', s);
  fd.append('qty', 1);
  fd.append('remark', '');

  fetch('../ajax/stock_out_scanner.php', { method: 'POST', body: fd })
    .then(function(r){ return r.json(); })
    .then(function(d){
      if (d.success){
        showMsg('✅ ' + s + ' — Stock Out ho gaya (-1)', 'success');
      } else {
        showMsg('⚠️ ' + s + ' — ' + (d.message || 'Error'), 'danger');
      }
      loadScanHistory(true);
      $('scanInput').value = '';
      scanning = false;
      $('scanInput').focus();
    })
    .catch(function(e){
      showMsg('Server error: ' + e, 'danger');
      $('scanInput').value = '';
      scanning = false;
    });
}

/* USB scanner bina-Enter hon: typing rukne ke 150ms baad auto process */
function clearBarcodeTimer(){ if (barcodeTimer){ clearTimeout(barcodeTimer); barcodeTimer = null; } }
function armBarcodeTimer(){
  clearBarcodeTimer();
  var el = $('scanInput');
  barcodeTimer = setTimeout(function(){
    var v = el.value.trim();
    if (v && !scanning){ el.blur(); processSerial(v); }
  }, 150);
}

/* ENTER (manual ya scanner-with-Enter) */
$('scanInput').addEventListener('keydown', function(e){
  if (e.key === 'Enter'){
    e.preventDefault();
    clearBarcodeTimer();
    var s = this.value.trim();
    this.blur();
    if (!s){ this.focus(); return; }
    processSerial(s);
  }
});
$('scanInput').addEventListener('input', function(){ armBarcodeTimer(); });

/* ===== HISTORY ===== */
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
setLock(false);  // Start: LOCKED
loadScanHistory();
</script>
</body>
</html>