<?php
// ajax/stock_out_sheet.php — Sheet upload se Stock Out
require_once dirname(__DIR__) . '/config/db.php';

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) jout(['success' => false, 'message' => 'File upload nahi hui.']);

$rows = sheetFileToRows($file);
if (empty($rows)) jout(['success' => false, 'message' => 'File se koi data nahi mila.']);

$items = sheetRowsToItems($rows);
if (empty($items)) jout(['success' => false, 'message' => 'Koi serial row nahi mili.']);

$done = 0;
$skipped = [];
$totalQty = 0;
foreach ($items as $it) {
    $serial = $it['serial'];
    $qty = $it['qty'];
    if ($serial === '' || $qty <= 0) continue;

    $product = findProduct($serial);
    if (!$product) { $skipped[] = $serial . ' (not found)'; continue; }

    $stock = (int)$product['current_stock'];
    if ($stock < $qty) { $skipped[] = $serial . ' (stock ' . $stock . ')'; continue; }

    $conn->execute_query("UPDATE products SET current_stock = current_stock - ? WHERE serial_code = ?", [$qty, $serial]);

    $st = $conn->prepare("INSERT INTO stock_out (serial_code, item_name, quantity, remark, source) VALUES (?,?,?,?,?)");
    $remark = 'sheet';
    $st->bind_param('ssiss', $serial, $product['item_name'], $qty, $remark, 'sheet');
    $st->execute();

    $done++;
    $totalQty += $qty;
}

$msg = "Stock Out done: {$done} items, total -{$totalQty} qty";
if (!empty($skipped)) $msg .= ' | Skipped: ' . implode(', ', array_slice($skipped, 0, 15));
jout(['success' => true, 'message' => $msg, 'done' => $done, 'skipped' => $skipped]);