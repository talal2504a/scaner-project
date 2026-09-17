<?php
// root/index.php — Dashboard
$page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Stock System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include '_layout.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>Dashboard</h1>
        <p class="desc">Stock In / Out ka quick overview</p>
      </div>
      <div style="display:flex;gap:10px">
        <a class="btn green" href="stock-in.php">+ Stock In</a>
        <a class="btn red" href="scan.php">Scan / Stock Out</a>
      </div>
    </div>

    <div id="msgBox" class="msg-box"></div>

    <!-- STAT CARDS -->
    <div class="stat-row" id="stats"></div>

    <div class="grid-2">
      <!-- Recent activity -->
      <div class="panel">
        <h3>Recent Activity <span>(last 8)</span></h3>
        <div id="recentList"><span class="dash">Loading...</span></div>
      </div>

      <!-- Low stock -->
      <div class="panel">
        <h3>Low Stock Alert <span>(<=5)</span></h3>
        <div id="lowList"><span class="dash">Loading...</span></div>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
<script>
function renderRecent(list){
  if(!list || !list.length){ $('recentList').innerHTML='<span class="dash">Koi activity nahi.</span>'; return; }
  $('recentList').innerHTML = list.map(function(r){
    var klass = r.rec_type==='in' ? 'in-txt' : 'out-txt';
    var lab = r.rec_type==='in' ? 'IN +' : 'OUT -';
    return '<div class="item-row">'+
      '<div><div class="name">'+esc(r.item_name)+'</div>'+
      '<div class="meta">'+esc(r.serial_code)+' · '+esc(r.entry_date)+'</div></div>'+
      '<div class="right"><div class="qty '+klass+'">'+lab+r.quantity+'</div></div></div>';
  }).join('');
}
function renderLow(list){
  if(!list || !list.length){ $('lowList').innerHTML='<span class="dash">Sab stock theek hai.</span>'; return; }
  $('lowList').innerHTML = list.map(function(p){
    return '<div class="item-row">'+
      '<div><div class="name">'+esc(p.item_name)+'</div>'+
      '<div class="meta">'+esc(p.serial_code)+'</div></div>'+
      '<div class="right"><span class="tag low">'+p.current_stock+' left</span></div></div>';
  }).join('');
}
ajax('../ajax/dashboard_stats.php', function(d){
  if(!d.success) return;
  var s = d.data;
  $('stats').innerHTML =
    '<div class="stat-card"><div class="label">Total Products</div><div class="value">'+s.total_products+'</div></div>'+
    '<div class="stat-card"><div class="label">Total Stock</div><div class="value">'+s.total_stock+'</div></div>'+
    '<div class="stat-card"><div class="label">Total Stock In</div><div class="value green">+'+s.total_in+'</div></div>'+
    '<div class="stat-card"><div class="label">Today Stock Out</div><div class="value rust">-'+s.today_out+'</div></div>'+
    '<div class="stat-card"><div class="label">Low Stock Items</div><div class="value amber">'+s.low_stock+'</div></div>';
  renderRecent(s.recent);
  renderLow(s.low_list);
});
</script>
</body>
</html>