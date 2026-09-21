<?php
/* ============================================================
   ajax/register_product.php — Product + Barcode register (manual + sheet)
   POST:
     - Bulk: sheet (file) + selected (JSON of barcodes)
     - Manual: item_code, item_name + optional barcode
   OPTIMIZED: batch lookups + transaction for bulk inserts
   ============================================================ */
set_time_limit(180);
require_once dirname(__DIR__) . '/config/db.php';

/* ---------- BULK: hierarchy sheet upload ---------- */
if (!empty($_FILES['sheet']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['sheet']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'])) {
        jout(['success' => false, 'message' => 'Please upload xlsx/xls/csv/txt file.']);
    }

    /* ---------- ITEMS: preview cache se (token) warna file parse ---------- */
    $items = [];
    $token = trim($_POST['token'] ?? '');
    if ($token !== '' && preg_match('/^[0-9a-f]{16}$/', $token)) {
        $cacheFile = sys_get_temp_dir() . '/suno_sheet_cache/' . $token . '.json';
        if (file_exists($cacheFile)) {
            $cached = json_decode(@file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                $items = $cached;
                @unlink($cacheFile);   // ek baar ka use — consume
            }
        }
    }

    if (empty($items)) {
        $rows  = sheetFileToRows($_FILES['sheet']);
        $items = sheetRowsToItems($rows);
    }

    if (empty($items)) {
        jout(['success' => false, 'message' => 'No CARTON/BOX/PCS rows found in file.']);
    }

    $selected = json_decode($_POST['selected'] ?? 'null', true);
    if (is_array($selected) && count($selected)) {
        $allowed = array_flip($selected);
        $items = array_values(array_filter($items, function ($it) use ($allowed) {
            return isset($allowed[$it['barcode']]);
        }));
    }
    if (empty($items)) jout(['success' => false, 'message' => 'No rows selected.']);

    // Product config aggregate per item_code
    $agg = [];
    foreach ($items as $it) {
        $code = $it['item_code'];
        if (!isset($agg[$code])) {
            $agg[$code] = [
                'item_name'     => $it['item_name'],
                'pcs_per_box'   => $it['pcs_per_box'],
                'boxes_per_ctn' => $it['boxes_per_ctn'],
                'pcs_per_ctn'   => ($it['level'] === 'CARTON') ? $it['pcs_qty'] : 0,
            ];
        } else {
            $agg[$code]['pcs_per_box']   = max($agg[$code]['pcs_per_box'], $it['pcs_per_box']);
            $agg[$code]['boxes_per_ctn'] = max($agg[$code]['boxes_per_ctn'], $it['boxes_per_ctn']);
            if ($it['level'] === 'CARTON') $agg[$code]['pcs_per_ctn'] = $it['pcs_qty'];
        }
        if ($agg[$code]['item_name'] === $code) $agg[$code]['item_name'] = $it['item_name'];
    }

    foreach ($agg as $code => &$cfg) {
        if ((int)$cfg['pcs_per_ctn'] < 1 && (int)$cfg['pcs_per_box'] > 0 && (int)$cfg['boxes_per_ctn'] > 0) {
            $cfg['pcs_per_ctn'] = (int)$cfg['pcs_per_box'] * (int)$cfg['boxes_per_ctn'];
        }
    }
    unset($cfg);

    /* ---- BATCH existence check: 2 queries, no bind_param ---- */
    $allItemCodes = array_keys($agg);
    $existingProducts = [];
    if (!empty($allItemCodes)) {
        $esc = array_map(function ($c) use ($conn) { return "'" . $conn->real_escape_string($c) . "'"; }, $allItemCodes);
        $sql = "SELECT item_code FROM products WHERE item_code IN (" . implode(',', $esc) . ")";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) $existingProducts[$row['item_code']] = true;
        }
    }

    $allBarcodes = array_values(array_unique(array_map(function($it) { return $it['barcode']; }, $items)));
    $existingBarcodes = [];
    if (!empty($allBarcodes)) {
        $esc = array_map(function ($b) use ($conn) { return "'" . $conn->real_escape_string($b) . "'"; }, $allBarcodes);
        $sql = "SELECT barcode FROM barcodes WHERE barcode IN (" . implode(',', $esc) . ")";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) $existingBarcodes[$row['barcode']] = true;
        }
    }

    /* ---- BATCH INSERT with transaction ---- */
    $es = function ($v) use ($conn) { return "'" . $conn->real_escape_string((string)$v) . "'"; };

    $conn->begin_transaction();

    try {
        // Products — sirf naye insert karo (chunked multi-row INSERT)
        $productsNew = 0;
        $prodChunks  = [];
        foreach ($agg as $code => $cfg) {
            if (isset($existingProducts[$code])) continue;
            $name  = $cfg['item_name'] ?: $code;
            $ppb   = (int)$cfg['pcs_per_box'];
            $bpctc = (int)$cfg['boxes_per_ctn'];
            $ppctc = (int)$cfg['pcs_per_ctn'];
            $prodChunks[] = '(' . $es($code) . ',' . $es($name) . ',' . (int)$ppb . ',' . (int)$bpctc . ',' . (int)$ppctc . ')';
        }
        foreach (array_chunk($prodChunks, 200) as $chunk) {
            $sql = "INSERT INTO products (item_code, item_name, pcs_per_box, boxes_per_ctn, pcs_per_ctn) VALUES " . implode(',', $chunk);
            if ($conn->query($sql)) $productsNew += count($chunk);
        }

        // Barcodes — sirf naye, chunked multi-row INSERT
        $regBarcodes  = 0;
        $skipBarcodes = 0;
        $bcChunks     = [];
        foreach ($items as $it) {
            $barcode = $it['barcode'];
            if (isset($existingBarcodes[$barcode])) {
                $skipBarcodes++;
                continue;
            }
            $existingBarcodes[$barcode] = true;   // same batch mein duplicate roko
            $level  = in_array($it['level'], ['CARTON', 'BOX', 'PCS'], true) ? $it['level'] : 'PCS';
            $icode  = $it['item_code'];
            $parent = $it['parent_barcode'] ?? '';
            $pcs    = (int)$it['pcs_qty'];
            if ($pcs < 1) $pcs = 1;
            $bcChunks[] = '(' . $es($barcode) . ',' . $es($level) . ',' . $es($icode) . ',' . $es($parent) . ',' . (int)$pcs . ')';
        }
        foreach (array_chunk($bcChunks, 500) as $chunk) {
            $sql = "INSERT INTO barcodes (barcode, level, item_code, parent_barcode, pcs_qty) VALUES " . implode(',', $chunk);
            if ($conn->query($sql)) $regBarcodes += count($chunk);
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        jout(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }

    jout([
        'success'      => true,
        'message'      => 'Sheet processed: ' . $regBarcodes . ' barcodes registered, ' .
                          $productsNew . ' products created, ' . $skipBarcodes . ' skipped.',
        'registered'   => $regBarcodes,
        'products_new' => $productsNew,
        'skipped'      => $skipBarcodes,
    ]);
}

/* ---------- MANUAL: product + barcode (3 fields) ---------- */
$item_code = trim($_POST['item_code'] ?? '');
if ($item_code === '') jout(['success' => false, 'message' => 'Item ID is required.']);

$fields = [
    'item_name'     => trim($_POST['item_name'] ?? ''),
    'pcs_per_box'   => (int)($_POST['pcs_per_box']   ?? 0),
    'boxes_per_ctn' => (int)($_POST['boxes_per_ctn'] ?? 0),
    'pcs_per_ctn'   => (int)($_POST['pcs_per_ctn']   ?? 0),
];

$product = ensureProduct($item_code, $fields);
if (!$product) jout(['success' => false, 'message' => 'Could not register product.']);

if ($product['item_name'] === '') {
    jout(['success' => false, 'message' => 'Item Name is required.']);
}

$msgs = ['Product registered.'];

$barcode = trim($_POST['barcode'] ?? '');
if ($barcode !== '') {
    $level = strtoupper(trim($_POST['level'] ?? ''));
    if (!in_array($level, ['CARTON', 'BOX', 'PCS'], true)) $level = levelFromBarcode($barcode);

    $explicitQty = (int)($_POST['pcs_qty'] ?? 0);

    if ($level === 'CARTON' && (int)$product['pcs_per_ctn'] < 1 && $explicitQty < 1) {
        $msgs[] = 'Barcode not registered — CARTON pack size unknown. Use Upload Sheet to register boxes/cartons.';
    } elseif ($level === 'BOX' && (int)$product['pcs_per_box'] < 1 && $explicitQty < 1) {
        $msgs[] = 'Barcode not registered — BOX pack size unknown. Use Upload Sheet to register boxes/cartons.';
    } else {
        $bc = ensureBarcode($barcode, [
            'item_code'      => $item_code,
            'level'          => $level,
            'parent_barcode' => trim($_POST['parent_barcode'] ?? ''),
            'pcs_qty'        => $explicitQty,
        ]);
        if ($bc) {
            $msgs[] = 'Barcode ' . $barcode . ' registered (' . $bc['level'] . ', ' . $bc['pcs_qty'] . ' pcs).';
        } else {
            $msgs[] = 'Barcode could not be registered (duplicate or missing product).';
        }
    }
}

jout(['success' => true, 'message' => implode(' ', $msgs), 'product' => $product]);
