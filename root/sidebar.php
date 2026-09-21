<?php
// root/sidebar.php — shared sidebar
// Expects: $page (string) = current page key
$nav = [
    'dashboard' => ['index.php',   'Dashboard'],
    'products'  => ['products.php', 'Products'],
    'stock-in'  => ['stock-in.php', 'Stock In'],
    'stock-out' => ['stock-out.php','Stock Out'],
   
    'history'   => ['history.php',  'History'],
];
?>
<aside class="sidebar">
  <div class="brand">
    <div class="mark">S</div>
    <div class="name">Stock System</div>
    <div class="sub">In / Out Manager</div>
  </div>
  <nav>
    <?php foreach ($nav as $key => $item): ?>
      <a class="nav-item <?php echo $page === $key ? 'active' : ''; ?>" href="<?php echo $item[0]; ?>">
        <span class="dot"></span><span><?php echo $item[1]; ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">Stock System v1.0<br>Blue/Red Theme</div>
</aside>