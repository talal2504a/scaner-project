<?php
// root/history.php — Stock In/Out records with filter
$page = 'history';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>History — Stock System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include '_layout.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>History</h1>
        <p class="desc">Stock In / Out ke records</p>
      </div>
      <div style="display:flex;gap:10px;align-items:center">
        <input type="date" id="fDate" style="padding:9px 12px;border:1px solid #DDE3EC;border-radius:8px;font-size:13px">
        <button class="btn ghost" id="btnReset">Reset</button>
      </div>
    </div>

    <div class="nav-tabs">
      <button id="t1" class="active">All</button>
      <button id="t2">Stock In</button>
      <button id="t3">Stock Out</button>
    </div>

    <div class="panel">
      <table>
        <thead>
          <tr><th>Type</th><th>Date</th><th>Serial</th><th>Item Name</th><th>Qty</th><th>Source</th><th>Remark</th></tr>
        </thead>
        <tbody id="hBody"></tbody>
      </table>
      <div id="hEmpty" class="dash" style="display:none;text-align:center;padding:30px 0">Koi records nahi mile.</div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
<script>
var fType = 'all';

function loadHistory(){
  var url = '../ajax/history_list.php?type='+fType;
  var date = $('fDate').value;
  if (date) url += '&date='+date;
  ajax(url, function(d){
    if (!d.success) return;
    var rows = (d.data||[]).map(function(r){
      var tag = r.rec_type==='in' ? 'tag in' : 'tag out';
      var sign = r.rec_type==='in' ? '+' : '-';
      return '<tr>'+
        '<td><span class="'+tag+'">'+(r.rec_type==='in'?'Stock In':'Stock Out')+'</span></td>'+
        '<td>'+esc(r.entry_date)+'</td>'+
        '<td class="mono">'+esc(r.serial_code)+'</td>'+
        '<td>'+esc(r.item_name)+'</td>'+
        '<td class="num-cell">'+sign+esc(r.quantity)+'</td>'+
        '<td>'+esc(r.source)+'</td>'+
        '<td>'+esc(r.remark)+'</td></tr>';
    }).join('');
    $('hBody').innerHTML = rows;
    $('hEmpty').style.display = (!d.data || !d.data.length) ? 'block' : 'none';
  });
}

function setType(t){
  fType = t;
  ['t1','t2','t3'].forEach(function(id){
    var b = $(id); if (b) b.classList.toggle('active', id === t);
  });
  loadHistory();
}
$('t1').addEventListener('click', function(){ setType('all'); });
$('t2').addEventListener('click', function(){ setType('in'); });
$('t3').addEventListener('click', function(){ setType('out'); });
$('fDate').addEventListener('change', loadHistory);
$('btnReset').addEventListener('click', function(){ $('fDate').value=''; setType('all'); });
setType('all');
</script>
</body>
</html>