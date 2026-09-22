<?php
/* ============================================================
   ajax/undo_stock_out.php — Stock Out UNDO
   POST: barcode (optional) | out_id (optional)
   Barcode na do → LAST stock out record undo hota hai.
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$barcode = trim($_POST['barcode'] ?? '');
$outId   = (int)($_POST['out_id'] ?? 0);

/* Target record dhoondo */
if ($barcode !== '') {
    $st = $conn->prepare("SELECT * FROM stock_out WHERE barcode = ? ORDER BY id DESC LIMIT 1");
    $st->bind_param('s', $barcode);
} elseif ($outId > 0) {
    $st = $conn->prepare("SELECT * FROM stock_out WHERE id = ? LIMIT 1");
    $st->bind_param('i', $outId);
} else {
    /* LAST STOCK OUT — bina barcode */
    $st = $conn->prepare("SELECT * FROM stock_out ORDER BY id DESC LIMIT 1");
}
$st->execute();
$rec = $st->get_result()->fetch_assoc();
if (!$rec) jout(['success' => false, 'message' => 'Koi stock out record nahi mila.']);

$pcs = (int)$rec['pcs_qty'];
$item_code = $rec['item_code'];
$outBarcode = $rec['barcode'];

/* 1) Stock wapas add */
$upd = $conn->prepare("UPDATE products SET current_stock_pcs = current_stock_pcs + ? WHERE item_code = ?");
$upd->bind_param('is', $pcs, $item_code);
$upd->execute();

/* 2) Stock out record delete */
$del = $conn->prepare("DELETE FROM stock_out WHERE id = ?");
$del->bind_param('i', $rec['id']);
$del->execute();

/* 3) Barcode + children wapas available */
consumeChildrenUnset($outBarcode);

jout([
    'success' => true,
    'message' => 'Undo done: +' . $pcs . ' pcs wapas add (' . $rec['level'] . ') — ' . $outBarcode,
    'barcode' => $outBarcode,
    'pcs'     => $pcs,
]);