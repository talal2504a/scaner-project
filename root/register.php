<?php
// root/register.php — naya product register
$page = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Product — Stock System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include '_layout.php'; ?>

  <main>
    <div class="pagehead">
      <div>
        <h1>Register Product</h1>
        <p class="desc">Naya serial/product system mein add karo</p>
      </div>
    </div>

    <div id="msgBox" class="msg-box"></div>

    <div class="panel" style="max-width:760px">
      <div class="split-row">
        <div class="field">
          <label>Serial Code / Barcode *</label>
          <input type="text" id="r_serial" placeholder="e.g. 814009">
        </div>
        <div class="field">
          <label>Item Name *</label>
          <input type="text" id="r_name" placeholder="e.g. NXB-63 1P C2A 6KA (180Pcs Ctn)">
        </div>
      </div>

      <div class="split-row">
        <div class="field"><label>Category</label><input type="text" id="r_category" placeholder="e.g. AC MCB"></div>
        <div class="field"><label>Model</label><input type="text" id="r_model" placeholder="e.g. NXB-63"></div>
      </div>

      <div class="split-row">
        <div class="field"><label>Poles</label><input type="text" id="r_poles" placeholder="1P / 2P / 3P / 4P"></div>
        <div class="field"><label>Rating</label><input type="text" id="r_rating" placeholder="C2A / 100A"></div>
      </div>

      <div class="split-row">
        <div class="field"><label>Voltage</label><input type="text" id="r_voltage" placeholder="220V / 500V"></div>
        <div class="field"><label>KA</label><input type="text" id="r_ka" placeholder="6KA / 10KA"></div>
      </div>

      <div class="split-row">
        <div class="field"><label>Packaging</label><input type="text" id="r_packaging" placeholder="180Pcs Ctn"></div>
        <div class="field"><label>Notes</label><input type="text" id="r_notes" placeholder="30mA / D/Outlet ..."></div>
      </div>

      <div class="field">
        <label>Photo (optional)</label>
        <input type="file" id="r_photo" accept="image/*">
      </div>

      <button class="btn" id="btnReg">Register Product</button>
    </div>
  </main>
</div>

<script src="assets/js/app.js"></script>
<script>
$('btnReg').addEventListener('click', function(){
  var fd = new FormData();
  fd.append('serial_code', $('r_serial').value.trim());
  fd.append('item_name',   $('r_name').value.trim());
  fd.append('category',    $('r_category').value.trim());
  fd.append('model',       $('r_model').value.trim());
  fd.append('poles',       $('r_poles').value.trim());
  fd.append('rating',      $('r_rating').value.trim());
  fd.append('voltage',     $('r_voltage').value.trim());
  fd.append('ka',          $('r_ka').value.trim());
  fd.append('packaging',   $('r_packaging').value.trim());
  fd.append('notes',       $('r_notes').value.trim());
  var p = $('r_photo').files[0]; if (p) fd.append('photo', p);

  postForm('../ajax/register_product.php', fd, function(d){
    if (d.success){
      ['r_serial','r_name','r_category','r_model','r_poles','r_rating',
       'r_voltage','r_ka','r_packaging','r_notes'].forEach(function(id){ var el=$(id); if(el) el.value=''; });
      $('r_photo').value='';
      $('r_serial').focus();
    }
  });
});
</script>
</body>
</html>