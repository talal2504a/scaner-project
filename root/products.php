<?php
// root/products.php — product list + search
$page = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — Stock System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include '_layout.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>Products</h1>
        <p class="desc">Serial, model, ratings — master list</p>
      </div>
      <a class="btn" href="register.php">+ Register Product</a>
    </div>

    <div class="field" style="max-width:420px">
      <input type="text" id="searchBox" placeholder="Search serial / name / model / category...">
    </div>

    <div class="panel">
      <table>
        <thead>
          <tr><th>Serial</th><th>Item Name</th><th>Category</th><th>Model</th>
              <th>Poles</th><th>Rating</th><th>Voltage</th><th>KA</th>
              <th>Packaging</th><th>Stock</th><th>Photo</th></tr>
        </thead>
        <tbody id="pBody"></tbody>
      </table>
      <div id="pEmpty" class="dash" style="display:none;padding:20px 0;text-align:center">Koi product nahi mila.</div>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
<script>
function loadProducts(q){
  q = q || '';
  fetch('../ajax/search_product.php?q='+encodeURIComponent(q))
    .then(function(r){ return r.json(); })
    .then(function(d){
      var rows = (d.data || []).map(function(p){
        var photo = p.photo
          ? '<img src="../'+esc(p.photo)+'" style="width:42px;height:42px;object-fit:cover;border-radius:6px">'
          : '<span class="dash">—</span>';
        var stockCls = parseInt(p.current_stock) <= 5 ? 'class="tag low"' : '';
        return '<tr>'+
          '<td class="mono">'+esc(p.serial_code)+'</td>'+
          '<td>'+esc(p.item_name)+'</td>'+
          '<td>'+esc(p.category)+'</td>'+
          '<td>'+esc(p.model)+'</td>'+
          '<td>'+esc(p.poles)+'</td>'+
          '<td>'+esc(p.rating)+'</td>'+
          '<td>'+esc(p.voltage)+'</td>'+
          '<td>'+esc(p.ka)+'</td>'+
          '<td>'+esc(p.packaging)+'</td>'+
          '<td><span '+stockCls+'>'+esc(p.current_stock)+'</span></td>'+
          '<td>'+photo+'</td></tr>';
      }).join('');
      $('pBody').innerHTML = rows;
      $('pEmpty').style.display = (!d.data || !d.data.length) ? 'block' : 'none';
    });
}
$('searchBox').addEventListener('input', function(e){ loadProducts(e.target.value); });
loadProducts();
</script>
</body>
</html>