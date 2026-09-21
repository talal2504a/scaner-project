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

// Agar barcode available hai (stock-out nahi hua), toh stock-in reverse karo
if ($isConsumed === 0 && $pcs > 0) {
    $upd = $conn->prepare("UPDATE products SET current_stock_pcs = current_stock_pcs - ? WHERE item_code = ?");
    $upd->bind_param('is', $pcs, $item_code);
    $upd->execute();
}

// Stock_in cleanup + barcode delete
$conn->prepare("DELETE FROM stock_in WHERE barcode = ?")->bind_param('s', $barcode)->execute();
$conn->prepare("DELETE FROM barcodes WHERE barcode = ?")->bind_param('s', $barcode)->execute();

jout(['success' => true, 'message' => 'Barcode deleted. Stock updated.']);