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
/* macOS-style dock magnify — spring smooth (rAF + cached rects) */
(function () {
  var inner = document.getElementById('dockInner');
  if (!inner) return;
  var items = Array.prototype.slice.call(inner.querySelectorAll('.dock-item'));
  var RANGE = 130;     // cursor ka effect range
  var MAX   = 1.5;     // max zoom
  var SPEED = 0.22;    // smoothness (0.1 = slow, 0.4 = fast)

  var rects   = items.map(function () { return 0; });
  var current = items.map(function () { return 1; });
  var mouseX = null, hovering = false, raf = null;

  /* Rect sirf ek baar / hover start pe measure — har frame pe nahi */
  function measure() {
    rects = items.map(function (it) {
      var r = it.getBoundingClientRect();
      return r.left + r.width / 2;
    });
  }

  function tick() {
    var active = false;
    for (var i = 0; i < items.length; i++) {
      var t = 1;
      if (hovering && mouseX !== null) {
        var d = Math.abs(mouseX - rects[i]);
        if (d < RANGE) t = 1 + (MAX - 1) * (1 - d / RANGE);
      }
      /* smooth lerp — spring jaisa feel */
      current[i] += (t - current[i]) * SPEED;
      if (Math.abs(t - current[i]) > 0.002) active = true;
      items[i].style.transform = 'scale(' + current[i].toFixed(4) + ')';
    }
    raf = (hovering || active) ? requestAnimationFrame(tick) : null;
  }
/* Prefetch — hover pe page pehle se load, click pe instant */
items.forEach(function (it) {
  it.addEventListener('mouseenter', function () {
    var href = it.getAttribute('href');
    if (!href) return;
    var l = document.createElement('link');
    l.rel = 'prefetch'; l.href = href;
    if (!document.querySelector('link[href="' + href + '"]')) document.head.appendChild(l);
  });
});
  inner.addEventListener('mouseenter', function () {
    hovering = true; measure();
    if (!raf) raf = requestAnimationFrame(tick);
  });
  inner.addEventListener('mousemove', function (e) { mouseX = e.clientX; });
  inner.addEventListener('mouseleave', function () {
    hovering = false;
    if (!raf) raf = requestAnimationFrame(tick);
  });
  window.addEventListener('resize', function () { if (hovering) measure(); });
})();
</script>