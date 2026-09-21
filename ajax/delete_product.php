<?php
/* ============================================================
   ajax/delete_product.php — Product + uske sab records delete
   GET param: item_code
   Order: stock_out → stock_in → barcodes → products
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$item_code = trim($_GET['item_code'] ?? '');
if ($item_code === '') jout(['success' => false, 'message' => 'Item code is required.']);

$prod = findProduct($item_code);
if (!$prod) jout(['success' => false, 'message' => 'Product not found.']);

/* Transaction-saaf delete (history + barcodes + product) */
$d1 = $conn->prepare("DELETE FROM stock_out WHERE item_code = ?");
$d1->bind_param('s', $item_code);
$d1->execute();

$d2 = $conn->prepare("DELETE FROM stock_in WHERE item_code = ?");
$d2->bind_param('s', $item_code);
$d2->execute();

$d3 = $conn->prepare("DELETE FROM barcodes WHERE item_code = ?");
$d3->bind_param('s', $item_code);
$d3->execute();

$d4 = $conn->prepare("DELETE FROM products WHERE item_code = ?");
$d4->bind_param('s', $item_code);
$d4->execute();
if ($d4->affected_rows <= 0) jout(['success' => false, 'message' => 'Delete failed.']);

jout(['success' => true, 'message' => 'Product deleted.']);