<?php
/* ============================================================
   ajax/delete_barcode.php — Single barcode delete + stock reverse
   GET param: barcode
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$barcode = trim($_GET['barcode'] ?? '');
if ($barcode === '') jout(['success' => false, 'message' => 'Barcode required.']);

$bc = findBarcode($barcode);
if (!$bc) jout(['success' => false, 'message' => 'Barcode not found.']);

$pcs = (int)$bc['pcs_qty'];
$item_code = $bc['item_code'];
$isConsumed = (int)($bc['is_consumed'] ?? 0);

// Stock reverse (agar available ho)
if ($isConsumed === 0 && $pcs > 0) {
    $upd = $conn->prepare("UPDATE products SET current_stock_pcs = current_stock_pcs - ? WHERE item_code = ?");
    $upd->bind_param('is', $pcs, $item_code);
    $upd->execute();
}

// DATABASE SE DELETE — teeno jagah se:
$d1 = $conn->prepare("DELETE FROM stock_in WHERE barcode = ?");
$d1->bind_param('s', $barcode);
$d1->execute();

$d2 = $conn->prepare("DELETE FROM stock_out WHERE barcode = ?");
$d2->bind_param('s', $barcode);
$d2->execute();

$d3 = $conn->prepare("DELETE FROM barcodes WHERE barcode = ?");
$d3->bind_param('s', $barcode);
$d3->execute();

jout(['success' => true, 'message' => 'Barcode deleted. Stock updated.']);