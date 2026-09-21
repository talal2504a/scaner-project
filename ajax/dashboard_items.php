<?php
/* ============================================================
   ajax/dashboard_items.php — Fresh items + barcodes (AJAX)
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';
$conn->query("SET SESSION group_concat_max_len = 100000");
$res = $conn->query(
    "SELECT p.item_code, p.item_name, p.current_stock_pcs,
            COUNT(b.barcode) AS ctn_count,
            GROUP_CONCAT(CONCAT(b.level, ':', b.barcode) ORDER BY b.barcode SEPARATOR '|') AS barcodes
     FROM products p
     LEFT JOIN barcodes b ON b.item_code = p.item_code AND b.is_consumed = 0
     GROUP BY p.item_code, p.item_name, p.current_stock_pcs
     ORDER BY p.item_name ASC"
);
$items = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
jout(['success' => true, 'data' => $items]);
