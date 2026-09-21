<?php
/* ============================================================
   ajax/stock_in_form.php — Manual Stock In (barcode se auto qty)
   POST: barcode, remark
   Barcode ka level (CARTON=180 / BOX=30 / PCS=1) auto lagta hai.
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$barcode = trim($_POST['barcode'] ?? '');
$remark  = trim($_POST['remark'] ?? '');

if ($barcode === '') jout(['success' => false, 'message' => 'Barcode is required.']);

$res = processStockIn($barcode, 'manual', $remark);
jout($res);