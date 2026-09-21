<?php
/* ============================================================
   ajax/search_product.php — Product / barcode search
   GET params:
     - serial → single lookup: exact barcode ya item_code
     - q      → list: empty = all products, warna name/code LIKE + barcode LIKE
   Koi LIMIT nahi.
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

// GROUP_CONCAT max length badhao (default 1024 bytes hota hai)
$conn->query("SET SESSION group_concat_max_len = 100000");

$q = isset($_GET['serial']) ? trim($_GET['serial']) : (isset($_GET['q']) ? trim($_GET['q']) : '');

/* ================= SINGLE LOOKUP (?serial=) ================= */
if (isset($_GET['serial'])) {
    if ($q === '') jout(['success' => true, 'found' => false, 'data' => null]);

    $bc = findBarcode($q);
    if ($bc) {
        jout([
            'success' => true,
            'found'   => true,
            'mode'    => 'barcode',
            'data'    => $bc,
        ]);
    }

    $p = findProduct($q);
    if ($p) {
        jout(['success' => true, 'found' => true, 'mode' => 'product', 'data' => $p]);
    }

    jout(['success' => true, 'found' => false, 'data' => null]);
}

/* ================= LIST (?q=) ================= */
if ($q === '') {
    $st = $conn->query("
        SELECT p.item_code, p.item_name, p.current_stock_pcs,
               GROUP_CONCAT(b.barcode SEPARATOR ', ') AS barcodes
        FROM products p
        LEFT JOIN barcodes b ON b.item_code = p.item_code
        GROUP BY p.item_code, p.item_name, p.current_stock_pcs
        ORDER BY p.item_name ASC
    ");
    $list = $st ? $st->fetch_all(MYSQLI_ASSOC) : [];
    jout(['success' => true, 'found' => !empty($list), 'mode' => 'list', 'data' => $list]);
}

// LIKE search
$like = '%' . $q . '%';
$st = $conn->prepare("
    SELECT p.item_code, p.item_name, p.current_stock_pcs,
           GROUP_CONCAT(b.barcode SEPARATOR ', ') AS barcodes
    FROM products p
    LEFT JOIN barcodes b ON b.item_code = p.item_code
    WHERE p.item_code LIKE ? OR p.item_name LIKE ?
    GROUP BY p.item_code, p.item_name, p.current_stock_pcs
    ORDER BY p.item_name ASC
");
$st->bind_param('ss', $like, $like);
$st->execute();
$list = $st->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($list)) {
    $b = $conn->prepare("
        SELECT DISTINCT p.item_code, p.item_name, p.current_stock_pcs,
               (SELECT GROUP_CONCAT(b2.barcode SEPARATOR ', ')
                FROM barcodes b2 WHERE b2.item_code = p.item_code) AS barcodes
        FROM barcodes b
        JOIN products p ON b.item_code = p.item_code
        WHERE b.barcode LIKE ?
        ORDER BY p.item_name ASC
    ");
    $b->bind_param('s', $like);
    $b->execute();
    $list = $b->get_result()->fetch_all(MYSQLI_ASSOC);
}

jout(['success' => true, 'found' => !empty($list), 'mode' => 'list', 'data' => $list]);