<?php
/* ============================================================
   ajax/stock_out_scanner.php — USB scanner se Stock Out
   POST: serial (barcode), remark
   Level se auto qty (CARTON=-180, BOX=-30, PCS=-1).
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$barcode = trim($_POST['serial'] ?? '');
if ($barcode === '') jout(['success' => false, 'message' => 'Barcode is required.']);

$remark = trim($_POST['remark'] ?? 'Scanner');
if ($remark === '') $remark = 'Scanner';

$res = processStockOut($barcode, 'scanner', $remark);
$res['done'] = $res['success'] ? 1 : 0;
jout($res);