<?php
// root/sidebar.php — shared bottom dock (macOS-style, vanilla JS — no React)
$nav = [
    'dashboard' => ['index.php',    'Dashboard'],
    'products'  => ['products.php', 'Products'],
    'stock-in'  => ['stock-in.php', 'Stock In'],
    'stock-out' => ['stock-out.php','Stock Out'],
    'history'   => ['history.php',  'History'],
];
$icons = [
    'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'products'  => '<path d="M21 8.5v7l-9 5-9-5v-7l9-5 9 5Z"/><path d="M3.3 7.5 12 12.5l8.7-5"/><path d="M12 12.5V20.5"/>',
    'stock-in'  => '<path d="M12 3v10"/><path d="m8 9 4 4 4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
    'stock-out' => '<path d="M12 13V3"/><path d="m8 7 4-4 4 4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
    'history'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
];
?>
<nav class="dock" id="dock">
  <div class="dock-inner" id="dockInner">
    <?php foreach ($nav as $key => $item): ?>
      <a class="dock-item <?php echo $page === $key ? 'active' : ''; ?>"
         href="<?php echo $item[0]; ?>" aria-label="<?php echo $item[1]; ?>">
        <span class="dock-tip"><?php echo $item[1]; ?></span>
        <span class="dock-ico"><svg viewBox="0 0 24 24"><?php echo $icons[$key]; ?></svg></span>
      </a>
    <?php endforeach; ?>
  </div>
</nav>

<script>
/* macOS-style magnify — cursor ke paas wale icon bade hote hain */
(function () {
  var inner = document.getElementById('dockInner');
  if (!inner) return;
  var items = inner.querySelectorAll('.dock-item');
  var RANGE = 110, MAX = 1.55;
  inner.addEventListener('mousemove', function (e) {
    items.forEach(function (it) {
      var r = it.getBoundingClientRect();
      var d = Math.abs(e.clientX - (r.left + r.width / 2));
      var s = d < RANGE ? 1 + (MAX - 1) * (1 - d / RANGE) : 1;
      it.style.transform = 'scale(' + s.toFixed(3) + ')';
    });
  });
  inner.addEventListener('mouseleave', function () {
    items.forEach(function (it) { it.style.transform = ''; });
  });
})();
</script>