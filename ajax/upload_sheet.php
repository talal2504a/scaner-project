<?php
/* ============================================================
   ajax/upload_sheet.php — Sheet preview (Stock In / Stock Out pages)
   require_registered=1 ho to products/barcodes match bhi check hota hai.
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

if (!$_FILES['file'] || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jout(['success' => false, 'message' => 'File upload failed.']);
}

$rows  = sheetFileToRows($_FILES['file']);
if (empty($rows)) {
    jout(['success' => false, 'message' => 'No data found in file. Expected headers: Item Code | Item Name | Barcode (at minimum).']);
}

$items = sheetRowsToItems($rows);
$items = aggregateSheetItems($items);

if (empty($items)) {
    jout(['success' => false, 'message' => 'No CARTON/BOX/PCS barcode rows found.']);
}

/* ---- Stock In: registered products/barcodes ka status ---- */
$requireReg = !empty($_POST['require_registered']);
if ($requireReg) {
    $items = markRegisteredItems($items);
}

$out = array_map(function ($it) {
    return [
        'barcode'        => $it['barcode'],
        'level'          => $it['level'],
        'item_code'      => $it['item_code'],
        'item_name'      => $it['item_name'],
        'pcs_qty'        => $it['pcs_qty'],
        'parent_barcode' => $it['parent_barcode'],
        'registered'     => array_key_exists('registered', $it) ? (bool)$it['registered'] : true,
    ];
}, $items);

$unreg = array_values(array_filter($out, function ($it) { return empty($it['registered']); }));

jout([
    'success'          => true,
    'message'          => count($items) . ' barcodes detected.',
    'total'            => count($items),
    'items'            => $out,
    'unregistered'     => $unreg,
    'has_unregistered' => count($unreg) > 0,
]);