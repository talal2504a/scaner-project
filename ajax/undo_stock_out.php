<?php
/* ============================================================
   ajax/undo_stock_out.php — Galti se hua Stock Out undo karo
   POST: barcode
   Reverses: product stock + barcode un-consumed + stock_out record delete
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$barcode = trim($_POST['barcode'] ?? '');
if ($barcode === '') jout(['success' => false, 'message' => 'Barcode is required.']);

$bc = findBarcode($barcode);
if (!$bc) jout(['success' => false, 'message' => 'Barcode not found.']);

// Latest stock_out record dhoondo
$st = $conn->prepare("SELECT * FROM stock_out WHERE barcode = ? ORDER BY entry_id DESC LIMIT 1");
$st->bind_param('s', $barcode);
$st->execute();
$r = $st->get_result();
$rec = $r ? $r->fetch_assoc() : null;
if (!$rec) jout(['success' => false, 'message' => 'Is barcode ka koi Stock Out record nahi mila.']);

$pcs = (int)$rec['pcs_qty'];
$item_code = $rec['item_code'];

// Stock wapas add karo
$upd = $conn->prepare("UPDATE products SET current_stock_pcs = current_stock_pcs + ? WHERE item_code = ?");
$upd->bind_param('is', $pcs, $item_code);
$upd->execute();

// Stock out record delete
$d1 = $conn->prepare("DELETE FROM stock_out WHERE entry_id = ?");
$d1->bind_param('i', $rec['entry_id']);
$d1->execute();

// Barcode + children un-consumed
$u = $conn->prepare("UPDATE barcodes SET is_consumed = 0 WHERE barcode = ?");
$u->bind_param('s', $barcode);
$u->execute();
consumeChildrenUnset($barcode);

jout(['success' => true, 'message' => 'Undo done: +' . $pcs . ' pcs wapas add. Stock Out record delete.']);