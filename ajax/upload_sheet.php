<?php
// ajax/upload_sheet.php — File upload karke parse + preview deta hai
require_once dirname(__DIR__) . '/config/db.php';

if (!$_FILES['file'] || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jout(['success' => false, 'message' => 'File upload nahi hui.']);
}

$rows = sheetFileToRows($_FILES['file']);
if (empty($rows)) {
    jout(['success' => false, 'message' => 'File se koi data nahi mila. Format: Serial | Description']);
}

$items = sheetRowsToItems($rows);
if (empty($items)) {
    jout(['success' => false, 'message' => 'Koi serial wali row nahi mili (serial column khali tha).']);
}

jout([
    'success'  => true,
    'message'  => count($items) . ' items detect huye.',
    'total'    => count($items),
    'items'    => $items,
]);