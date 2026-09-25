<?php $page = 'history'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>History — Diwan International Pvt Ltd</title>
<link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
</head>
<body>
<div class="app">
  <?php include 'sidebar.php'; ?>
  <main>
    <div class="pagehead">
      <div>
        <h1>History</h1>
        <p class="desc">Stock In / Out ka pura record (barcode, level, qty, date)</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <select id="fType">
        <option value="all">All</option>
        <option value="in">Stock In Only</option>
        <option value="out">Stock Out Only</option>
      </select>
      <input type="date" id="fDate">
      <button type="button" class="btn blue" id="btnFilter">Filter</button>
      <button type="button" class="btn ghost" id="btnReset">Reset</button>
    </div>

    <!-- Summary chips -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px">
      <span class="tag in" id="sumIn">IN: 0</span>
      <span class="tag out" id="sumOut">OUT: 0</span>
    </div>

    <div class="panel">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Type</th>
              <th>Barcode</th>
              <th>Level</th>
              <th>Item Code</th>
              <th>Items Name</th>
              <th>Pcs Qty</th>
              <th>Source</th>
              <th>Remark</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody id="histBody"><tr><td colspan="9"><span class="dash">Loading...</span></td></tr></tbody>
        </table>
      </div>
      <div id="histEmpty" class="dash" style="display:none;padding:20px 0;text-align:center">Koi record nahi mila.</div>
    </div>
  </main>
</div>

<script src="assets/js/app.js?v=<?php echo filemtime('assets/js/app.js'); ?>"></script>
<script>
$('btnReset').addEventListener('click', function(){
  $('fType').value = 'all';
  $('fMonth').value = '';
  loadHistory();
});
    .then(function(r){ return r.json(); })
    .then(function(d){
      var list = d.data || [];
      var body = $('histBody');
      if (!list.length){
        body.innerHTML = '';
        $('histEmpty').style.display = 'block';
        $('sumIn').textContent = 'IN: 0';
        $('sumOut').textContent = 'OUT: 0';
        return;
      }
      var sumIn = 0, sumOut = 0;
      var rows = list.map(function(r){
        if (r.rec_type === 'in') sumIn += parseInt(r.pcs_qty||0);
        else sumOut += parseInt(r.pcs_qty||0);
        var k = r.rec_type === 'in' ? 'tag in' : 'tag out';
        var lab = r.rec_type === 'in' ? 'IN +' : 'OUT -';
        return '<tr>'+
          '<td><span class="'+k+'">'+lab+r.pcs_qty+'</span></td>'+
          '<td class="mono">'+esc(r.barcode)+'</td>'+
          '<td>'+esc(r.level)+'</td>'+
          '<td class="mono">'+esc(r.item_code)+'</td>'+
          '<td>'+esc(r.item_name)+'</td>'+
          '<td><b>'+esc(r.pcs_qty)+'</b></td>'+
          '<td>'+esc(r.source)+'</td>'+
          '<td>'+esc(r.remark)+'</td>'+
          '<td>'+esc(r.entry_date)+'</td>'+
        '</tr>';
      }).join('');
      body.innerHTML = rows;
      $('histEmpty').style.display = 'none';
      $('sumIn').textContent = 'IN: ' + sumIn;
      $('sumOut').textContent = 'OUT: ' + sumOut;
    })
    .catch(function(){ $('histBody').innerHTML = '<tr><td colspan="9"><span class="tag low">⚠️ Server error.</span></td></tr>'; });
}
$('btnFilter').addEventListener('click', loadHistory);
$('btnReset').addEventListener('click', function(){
  $('fType').value = 'all';
  $('fDate').value = '';
  loadHistory();
});
loadHistory();
</script>
</body>
</html>