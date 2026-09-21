<?php
// ajax/register_sheet.php — Preview token se barcodes register (barcodes v2)
require_once dirname(__DIR__) . '/config/db.php';

$token = trim($_POST['token'] ?? '');
if ($token === '') jout(['success' => false, 'message' => 'Token missing — pehle sheet preview karo.']);

$cacheDir = sys_get_temp_dir() . '/suno_sheet_cache';
$file = $cacheDir . '/' . basename($token) . '.json';
if (!is_file($file)) jout(['success' => false, 'message' => 'Sheet cache expire ho gaya. Dobara preview karo.']);

$items = json_decode(@file_get_contents($file), true);
if (!is_array($items) || empty($items)) jout(['success' => false, 'message' => 'Sheet cache khali hai. Dobara preview karo.']);

$done = 0;
$skipped = [];
$regProduct = 0;
$regBarcode = 0;

foreach ($items as $it) {
    $barcode = trim($it['barcode'] ?? '');
    $item_code = trim($it['item_code'] ?? '');
    if ($barcode === '') continue;

    if ($item_code === '' && !$it['registered']) {
        $skipped[] = $barcode . ' (no item_code)';
        continue;
    }
    if (findBarcode($barcode)) { $skipped[] = $barcode . ' (exists)'; continue; }

    // Product ensure
    $level = in_array($it['level'], ['CARTON', 'BOX', 'PCS'], true) ? $it['level'] : 'PCS';
    $pcs_qty = max(1, (int)($it['pcs_qty'] ?? 1));
    $item_name = trim($it['item_name'] ?? $item_code);
    $item_code = trim($item_code);
    if ($item_code === '') { $skipped[] = $barcode . ' (no item_code)'; continue; }

    $prod = ensureProduct($item_code, ['item_name' => $item_name]);
    if (!$prod) { $skipped[] = $barcode . ' (product create fail)'; continue; }
    $regProduct++;

    if (!ensureBarcode($barcode, $item_code, $level, $pcs_qty)) { $skipped[] = $barcode . ' (insert fail)'; continue; }
    $regBarcode++;
    $done++;
}

// Cache delete karo
@unlink($file);

$msg = "Register done: {$done} barcodes";
if ($regProduct) $msg .= " ({$regProduct} product rows)";
if (!empty($skipped)) $msg .= ' | Skipped: ' . implode(', ', array_slice($skipped, 0, 12));
jout(['success' => true, 'message' => $msg, 'done' => $done, 'products' => count($items), 'skipped' => $skipped]);