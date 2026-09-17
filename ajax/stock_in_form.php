<?php
// ajax/stock_in_form.php — Manual Stock In
require_once dirname(__DIR__) . '/config/db.php';

$serial = trim($_POST['serial_code'] ?? '');
$qty    = (int)($_POST['quantity'] ?? 0);
$remark = trim($_POST['remark'] ?? '');
$photo  = uploadPhoto($_FILES['photo'] ?? null);

if ($serial === '') jout(['success' => false, 'message' => 'Serial code chahiye.']);
if ($qty <= 0)        jout(['success' => false, 'message' => 'Quantity 0 se badi honi chahiye.']);

$name = trim($_POST['item_name'] ?? '');
$fields = parseDescription($name !== '' ? $name : $serial);

// Product pehle se nahi hai to register karo
$product = ensureProduct($serial, $fields, $photo);
if (!$product) jout(['success' => false, 'message' => 'Product create nahi ho paya.']);

// Stock update + record
$conn->execute_query("UPDATE products SET current_stock = current_stock + ? WHERE serial_code = ?", [$qty, $serial]);

$st = $conn->prepare("INSERT INTO stock_in (serial_code, item_name, quantity, photo, remark, source) VALUES (?,?,?,?,?,?)");
$st->bind_param('ssisss', $serial, $fields['item_name'], $qty, $photo, $remark, 'manual');
$st->execute();

jout(['success' => true, 'message' => 'Stock In ho gaya: +' . $qty, 'product' => findProduct($serial)]);