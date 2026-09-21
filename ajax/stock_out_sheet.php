<?php
/* ============================================================
   ajax/stock_out_sheet.php — Sheet upload se Stock Out (barcodes)
   POST: file, selected (JSON of barcodes)
   - Not registered barcode = fail
   - Already consumed (e.g. uske parent carton isi batch mein out hua) = skip
   - Baaki processStockOut normal checks (stock to restriction).
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) jout(['success' => false, 'message' => 'File upload failed.']);

$rows  = sheetFileToRows($file);
if (empty($rows)) jout(['success' => false, 'message' => 'No data found in file.']);
$items = sheetRowsToItems($rows);
if (empty($items)) jout(['success' => false, 'message' => 'No CARTON/BOX/PCS rows found.']);

// Preview mein user ne jo select ki — sirf unhi process karo
$selected = json_decode($_POST['selected'] ?? 'null', true);
if (is_array($selected) && count($selected)) {
    $allowed = array_flip($selected);
    $items = array_values(array_filter($items, function ($it) use ($allowed) {
        return isset($allowed[$it['barcode']]);
    }));
}
if (empty($items)) jout(['success' => false, 'message' => 'No rows selected.']);

$done   = 0;
$fail   = 0;
$skip   = 0;
$totalPcs = 0;

foreach ($items as $it) {
    $barcode = $it['barcode'];

    $bc = findBarcode($barcode);
    if (!$bc) { $fail++; continue; }            // not registered
    if ((int)$bc['is_consumed'] === 1) { $skip++; continue; }  // already consumed

    $res = processStockOut($barcode, 'sheet', 'sheet');
    if ($res['success']) {
        $done++;
        $totalPcs += $res['pcs'];
    } else {
        $fail++;
    }
}

jout([
    'success' => true,
    'message' => "Stock Out done: {$done} barcodes, -{$totalPcs} pcs total" .
                 ($skip ? " ({$skip} already consumed, skipped)" : '') .
                 ($fail ? " ({$fail} failed)" : ''),
    'done'    => $done,
    'skipped' => $skip,
    'failed'  => $fail,
    'total_pcs' => $totalPcs,
]);