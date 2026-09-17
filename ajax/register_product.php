<?php
// ajax/register_product.php — naya product register
require_once dirname(__DIR__) . '/config/db.php';

$serial = trim($_POST['serial_code'] ?? '');
if ($serial === '') jout(['success' => false, 'message' => 'Serial code chahiye.']);
$photo = uploadPhoto($_FILES['photo'] ?? null);

$fields = [
    'category'  => trim($_POST['category'] ?? ''),
    'item_name' => trim($_POST['item_name'] ?? '') ?: $serial,
    'model'     => trim($_POST['model'] ?? ''),
    'poles'     => trim($_POST['poles'] ?? ''),
    'rating'    => trim($_POST['rating'] ?? ''),
    'voltage'   => trim($_POST['voltage'] ?? ''),
    'ka'        => trim($_POST['ka'] ?? ''),
    'packaging' => trim($_POST['packaging'] ?? ''),
    'notes'     => trim($_POST['notes'] ?? ''),
];

if (findProduct($serial)) jout(['success' => false, 'message' => 'Serial pehle se registered hai.']);

$product = ensureProduct($serial, $fields, $photo);
if (!$product) jout(['success' => false, 'message' => 'Product register nahi ho paya.']);

jout(['success' => true, 'message' => 'Product registered.', 'product' => $product]);