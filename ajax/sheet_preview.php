<?php
/* ============================================================
   ajax/sheet_preview.php — Sheet preview (v2: CARTON -> BOX -> PCS)
   Ye 3 pages use karte hain (sab yahi shape maangte hain):
     root/products.php:216 | root/register.php:196 | root/history.php:158

   Response: success, message, total, rows[], newCount, dupCount,
             byLevel{CARTON,BOX,PCS}, truncated, token
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

if (empty($_FILES['sheet']['tmp_name']) || $_FILES['sheet']['error'] !== UPLOAD_ERR_OK) {
    jout(['success' => false, 'message' => 'Sheet chahiye (xlsx/xls/csv/txt).']);
}

$ext = strtolower(pathinfo($_FILES['sheet']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
    jout(['success' => false, 'message' => 'xlsx/xls/csv/txt file do.']);
}

$rows = sheetFileToRows($_FILES['sheet']);
if (empty($rows)) {
    jout(['success' => false, 'message' => 'No data found in file. Expected headers: Item ID | Items Name | Barcode (at minimum).']);
}

$items = aggregateSheetItems(sheetRowsToItems($rows));
if (empty($items)) {
    jout(['success' => false, 'message' => 'No CARTON/BOX/PCS barcode rows found. Check Level / Barcode columns.']);
}

$items = markRegisteredItems($items);   // 2 batch queries — har item me 'registered'

/* ---- Preview rows (pehli 200) + counts ---- */
$limit    = 200;
$rowsOut  = [];
$byLevel  = ['CARTON' => 0, 'BOX' => 0, 'PCS' => 0];
$newCount = 0;
$dupCount = 0;

foreach ($items as $i => $it) {
    $level  = $it['level'];
    $exists = !empty($it['registered']);

    if (isset($byLevel[$level])) $byLevel[$level]++;
    if ($exists) $dupCount++; else $newCount++;

    if ($i < $limit) {
        $rowsOut[] = [
            'barcode'   => $it['barcode'],
            'level'     => $level,
            'item_code' => $it['item_code'],
            'item_name' => $it['item_name'],
            'pcs_qty'   => (int)$it['pcs_qty'],
            'exists'    => $exists,
        ];
    }
}

/* ---- Token cache: register_product.php isi ko padhta hai (16 hex chars) ---- */
$token    = '';
$cacheDir = sys_get_temp_dir() . '/suno_sheet_cache';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0777, true);
if (is_dir($cacheDir) && is_writable($cacheDir)) {
    $token = bin2hex(random_bytes(8));
    $json  = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false || @file_put_contents($cacheDir . '/' . $token . '.json', $json) === false) {
        $token = '';
    }
}

jout([
    'success'   => true,
    'message'   => count($items) . ' barcodes detected.',
    'total'     => count($items),
    'rows'      => $rowsOut,
    'newCount'  => $newCount,
    'dupCount'  => $dupCount,
    'byLevel'   => $byLevel,
    'truncated' => count($items) > $limit,
    'token'     => $token,
]);
