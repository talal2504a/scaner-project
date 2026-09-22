<?php
/* ============================================================
   ajax/undo_stock_out.php — Galti se hua Stock Out UNDO
   POST: barcode (ya stock_out id)
   Reverse: stock wapas add + consumed hatana + record delete
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$barcode = trim($_POST['barcode'] ?? '');
$outId   = (int)($_POST['out_id'] ?? 0);

if ($barcode === '' && $outId < 1) jout(['success' => false, 'message' => 'Barcode required.']);

/* Undo ka target record dhoondo */
if ($outId > 0) {
    $st = $conn->prepare("SELECT * FROM stock_out WHERE id = ? LIMIT 1");
    $st->bind_param('i', $outId);
} else {
    $st = $conn->prepare("SELECT * FROM stock_out WHERE barcode = ? ORDER BY id DESC LIMIT 1");
    $st->bind_param('s', $barcode);
}
$st->execute();
$rec = $st->get_result()->fetch_assoc();
if (!$rec) jout(['success' => false, 'message' => 'Stock out record not found.']);

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

/* 3) Barcode + children wapas available (is_consumed = 0) */
consumeChildrenUnset($outBarcode);

jout([
    'success' => true,
    'message' => 'Undo done: +' . $pcs . ' pcs wapas add ('. $rec['level'] .').',
    'barcode' => $outBarcode,
    'pcs'     => $pcs,
]);