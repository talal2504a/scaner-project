<?php
// ajax/stock_out_form.php — Manual Stock Out
require_once dirname(__DIR__) . '/config/db.php';

$serial = trim($_POST['serial_code'] ?? '');
$qty    = (int)($_POST['quantity'] ?? 0);
$remark = trim($_POST['remark'] ?? '');
$photo  = uploadPhoto($_FILES['photo'] ?? null);

if ($serial === '') jout(['success' => false, 'message' => 'Serial code chahiye.']);
if ($qty <= 0)        jout(['success' => false, 'message' => 'Quantity 0 se badi honi chahiye.']);

$product = findProduct($serial);
if (!$product) jout(['success' => false, 'message' => 'Product register nahi hai. Pehle register karo.']);

$stock = (int)$product['current_stock'];
if ($stock < $qty) jout(['success' => false, 'message' => 'Stock kam hai. Available: ' . $stock]);

$conn->execute_query("UPDATE products SET current_stock = current_stock - ? WHERE serial_code = ?", [$qty, $serial]);

$st = $conn->prepare("INSERT INTO stock_out (serial_code, item_name, quantity, photo, remark, source) VALUES (?,?,?,?,?,?)");
$st->bind_param('ssisss', $serial, $product['item_name'], $qty, $photo, $remark, 'manual');
$st->execute();

jout(['success' => true, 'message' => 'Stock Out ho gaya: -' . $qty, 'product' => findProduct($serial)]);