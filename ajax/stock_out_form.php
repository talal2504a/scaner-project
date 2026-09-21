<?php
/* ============================================================
   ajax/stock_out_form.php — Manual Stock Out (barcode se auto qty)
   POST: barcode, remark
   Checks: registered, not consumed, sufficient stock.
   Stock out ke baad children barcodes bhi consumed mark hote hain.
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$barcode = trim($_POST['barcode'] ?? '');
$remark  = trim($_POST['remark'] ?? '');

if ($barcode === '') jout(['success' => false, 'message' => 'Barcode is required.']);

$res = processStockOut($barcode, 'manual', $remark);
jout($res);