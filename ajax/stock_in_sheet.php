<?php
// ajax/stock_in_sheet.php — Sheet upload se Stock In
require_once dirname(__DIR__) . '/config/db.php';

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) jout(['success' => false, 'message' => 'File upload nahi hui.']);

$rows = sheetFileToRows($file);
if (empty($rows)) jout(['success' => false, 'message' => 'File se koi data nahi mila.']);

$items = sheetRowsToItems($rows);
if (empty($items)) jout(['success' => false, 'message' => 'Koi serial row nahi mili.']);

$done = 0;
$totalQty = 0;
foreach ($items as $it) {
    $serial = $it['serial'];
    $qty = $it['qty'];
    if ($serial === '' || $qty <= 0) continue;

    $product = ensureProduct($serial, $it);
    if (!$product) continue;

    $conn->execute_query("UPDATE products SET current_stock = current_stock + ? WHERE serial_code = ?", [$qty, $serial]);

    $st = $conn->prepare("INSERT INTO stock_in (serial_code, item_name, quantity, remark, source) VALUES (?,?,?,?,?)");
    $remark = 'sheet';
    $st->bind_param('ssiss', $serial, $it['item_name'], $qty, $remark, 'sheet');
    $st->execute();

    $done++;
    $totalQty += $qty;
}

jout(['success' => true, 'message' => "Stock In done: {$done} items, total +{$totalQty} qty"]);