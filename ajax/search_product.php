<?php
// ajax/search_product.php — serial/nai se product search
require_once dirname(__DIR__) . '/config/db.php';

$q = isset($_GET['serial']) ? trim($_GET['serial']) : (isset($_GET['q']) ? trim($_GET['q']) : '');

if ($q === '') jout(['success' => false, 'message' => 'Search khali hai.']);

// Exact serial match pehle
$row = findProduct($q);
if ($row) {
    jout(['success' => true, 'found' => true, 'mode' => 'single', 'product' => $row, 'data' => $row]);
}

// Phir search
$like = '%' . $q . '%';
$st = $conn->prepare("SELECT * FROM products WHERE serial_code LIKE ? OR item_name LIKE ? OR model LIKE ? OR category LIKE ? ORDER BY current_stock DESC LIMIT 50");
$st->bind_param('ssss', $like, $like, $like, $like);
$st->execute();
$list = $st->get_result()->fetch_all(MYSQLI_ASSOC);

jout(['success' => true, 'found' => !empty($list), 'mode' => 'list', 'data' => $list]);