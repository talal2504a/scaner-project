<?php
require_once dirname(__DIR__) . '/config/db.php';
if (!empty($_FILES['sheet']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['sheet']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'])) jout(['success' => false, 'message' => 'xlsx/xls/csv/txt file do.']);
    $rows  = sheetFileToRows($_FILES['sheet']);
    $items = sheetRowsToItems($rows);
    $table = []; $newCount = 0; $dupCount = 0;
    foreach ($items as $it) {
        $s = $it['serial'];
        if ($s === '') continue;
        $exists = findProduct($s) ? true : false;
        if ($exists) $dupCount++; else $newCount++;
        if (count($table) >= 60) continue;
        $table[] = [
            'serial'    => $s,
            'category'  => $it['category'] ?? '',
            'name'      => $it['item_name'] ?? '',
            'model'     => $it['model'] ?? '',
            'poles'     => $it['poles'] ?? '',
            'rating'    => $it['rating'] ?? '',
            'voltage'   => $it['voltage'] ?? '',
            'ka'        => $it['ka'] ?? '',
            'packaging' => $it['packaging'] ?? '',
            'exists'    => $exists,
        ];
    }
    jout(['success' => true, 'total' => count($items), 'rows' => $table, 'newCount' => $newCount, 'dupCount' => $dupCount]);
}
jout(['success' => false, 'message' => 'Sheet chahiye.']);