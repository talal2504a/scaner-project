<?php
// ajax/stock_out_scanner.php — USB Scanner se Stock Out
require_once dirname(__DIR__) . '/config/db.php';

$serial = trim($_POST['serial'] ?? '');
$qty    = (int)($_POST['qty'] ?? 1);
$remark = trim($_POST['remark'] ?? '');
$photo  = uploadPhoto($_FILES['photo'] ?? null);

if ($serial === '') jout(['success' => false, 'message' => 'Scan karo (serial empty).']);
if ($qty <= 0)        jout(['success' => false, 'message' => 'Qty invalid.']);

$product = findProduct($serial);
if (!$product) jout(['success' => false, 'message' => 'Product nahi mila: ' . $serial, 'serial' => $serial]);

$stock = (int)$product['current_stock'];
if ($stock < $qty) jout(['success' => false, 'message' => 'Stock kam hai (Available: ' . $stock . ')', 'product' => $product]);

$conn->execute_query("UPDATE products SET current_stock = current_stock - ? WHERE serial_code = ?", [$qty, $serial]);

$st = $conn->prepare("INSERT INTO stock_out (serial_code, item_name, quantity, photo, remark, source) VALUES (?,?,?,?,?,?)");
$st->bind_param('ssisss', $serial, $product['item_name'], $qty, $photo, $remark, 'scanner');
$st->execute();

$updated = findProduct($serial);
jout(['success' => true, 'message' => 'Stock Out (scanner): -' . $qty, 'product' => $updated]);