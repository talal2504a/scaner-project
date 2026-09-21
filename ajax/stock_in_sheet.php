<?php
/* ============================================================
   ajax/stock_in_sheet.php — Sheet upload se Stock In (barcodes)
   Sirf wahi barcodes process hote hain jo products/barcodes mein registered hain.
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) jout(['success' => false, 'message' => 'File upload failed.']);

$rows  = sheetFileToRows($file);
if (empty($rows)) jout(['success' => false, 'message' => 'No data found in file.']);
$items = sheetRowsToItems($rows);
$items = aggregateSheetItems($items);
if (empty($items)) jout(['success' => false, 'message' => 'No CARTON/BOX/PCS rows found.']);

$selected = json_decode($_POST['selected'] ?? 'null', true);
if (is_array($selected) && count($selected)) {
    $allowed = array_flip($selected);
    $items = array_values(array_filter($items, function ($it) use ($allowed) {
        return isset($allowed[$it['barcode']]);
    }));
}
if (empty($items)) jout(['success' => false, 'message' => 'No rows selected.']);

/* ---- Sirf registered products/barcodes ---- */
$items   = markRegisteredItems($items);
$skipped = 0;
$items   = array_values(array_filter($items, function ($it) use (&$skipped) {
    if (empty($it['registered'])) { $skipped++; return false; }
    return true;
}));

if (empty($items)) {
    jout(['success' => false, 'message' => 'Koi bhi barcode registered product se match nahi karta. Pehle Products page se product add karo, phir Stock In sheet dalo.']);
}

$done = 0; $fail = 0; $totalPcs = 0;
foreach ($items as $it) {
    $res = processStockIn($it['barcode'], 'sheet', 'sheet', (int)$it['pcs_qty']);
    if ($res['success']) { $done++; $totalPcs += $res['pcs']; } else { $fail++; }
}

jout([
    'success'   => true,
    'message'   => "Stock In done: {$done} barcodes, +{$totalPcs} pcs total" .
                   ($skipped ? ", {$skipped} skipped (product add nahi hai)" : '') .
                   ($fail ? " ({$fail} failed)" : ''),
    'done'      => $done,
    'failed'    => $fail,
    'skipped'   => $skipped,
    'total_pcs' => $totalPcs,
]);