<?php
/* ============================================================
   config/db.php — Database connection + shared helper functions
   Ye file har backend (ajax/*.php) mein require hoti hai.

   System: CARTON -> BOX -> PCS hierarchy
   Stock hamesha PIECES mein track hota hai.
   ============================================================ */

error_reporting(E_ALL);
ini_set('display_errors', '0');   // Errors user ko nahi dikhate (clean UI)

// Optional: 3rd-party libs (PhpSpreadsheet, PdfParser) agar installed hon
$__autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($__autoload)) require_once $__autoload;

/* ---------- DATABASE CONFIG ---------- */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'stock_system');

// Connection banate hain
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'DB connection failed: ' . $conn->connect_error]));
}
$conn->set_charset('utf8mb4');


/* ============================================================
   RESPONSE HELPER
   ============================================================ */

/**
 * JSON response bhejta hai aur script rokh deta hai.
 * Example: jout(['success' => true, 'message' => 'Done.']);
 */
function jout($arr) {
    header('Content-Type: application/json');
    echo json_encode($arr, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

/* ============================================================
   PRODUCT HELPERS (item_code se)
   ============================================================ */

/**
 * item_code (product master code) se product dhundta hai.
 * Example: item_code = "45125"
 */
function findProduct($item_code) {
    global $conn;
    $s = trim($item_code);
    if ($s === '') return null;

    $st = $conn->prepare("SELECT * FROM products WHERE item_code = ? LIMIT 1");
    $st->bind_param('s', $s);
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_assoc() : null;
}

/**
 * Product ensure karta hai — exist to return, warna INSERT.
 * @param string $item_code  product master code
 * @param array  $fields     item_name, pcs_per_box, boxes_per_ctn, pcs_per_ctn
 */
function ensureProduct($item_code, $fields) {
    global $conn;
    $item_code = trim($item_code);
    if ($item_code === '') return null;

    $row = findProduct($item_code);
    if ($row) return $row;

    $item_name     = trim($fields['item_name'] ?? '');
    if ($item_name === '') $item_name = $item_code;

    $pcs_per_box   = (int)($fields['pcs_per_box']   ?? 0);
    $boxes_per_ctn = (int)($fields['boxes_per_ctn'] ?? 0);
    $pcs_per_ctn   = (int)($fields['pcs_per_ctn']   ?? 0);

    $st = $conn->prepare("INSERT INTO products (item_code, item_name, pcs_per_box, boxes_per_ctn, pcs_per_ctn) VALUES (?,?,?,?,?)");
    $st->bind_param('ssiii', $item_code, $item_name, $pcs_per_box, $boxes_per_ctn, $pcs_per_ctn);
    if (!$st->execute()) return null;

    return findProduct($item_code);
}


/* ============================================================
   BARCODE HELPERS (CARTON / BOX / PCS)
   ============================================================ */

/**
 * Barcode se detail dhundta hai (barcodes + uski product info).
 * @return array|null  barcode row: level, pcs_qty, is_consumed, item_code, item_name, current_stock_pcs, ...
 */
function findBarcode($barcode) {
    global $conn;
    $b = trim($barcode);
    if ($b === '') return null;

    $st = $conn->prepare("
        SELECT b.*, p.item_name, p.current_stock_pcs, p.pcs_per_box, p.boxes_per_ctn, p.pcs_per_ctn
        FROM barcodes b
        JOIN products p ON b.item_code = p.item_code
        WHERE b.barcode = ? LIMIT 1
    ");
    $st->bind_param('s', $b);
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_assoc() : null;
}

/**
 * Barcode ke prefix se level detect karta hai:
 *   L... -> CARTON | B... -> BOX | P... -> PCS  (default PCS)
 */
function levelFromBarcode($barcode) {
    $b = strtoupper(trim($barcode));
    if ($b !== '' && $b[0] === 'L') return 'CARTON';
    if ($b !== '' && $b[0] === 'B') return 'BOX';
    if ($b !== '' && $b[0] === 'P') return 'PCS';
    return 'PCS';
}

/**
 * Barcode ensure karta hai — exist to same return, warna INSERT.
 * pcs_qty nahi diya to level ke hisaab se product se compute hota hai:
 *   CARTON = pcs_per_ctn | BOX = pcs_per_box | PCS = 1
 * @param array $fields  item_code, level, parent_barcode, pcs_qty
 */
function ensureBarcode($barcode, $fields) {
    global $conn;
    $barcode = trim($barcode);
    if ($barcode === '') return null;

    $row = findBarcode($barcode);
    if ($row) return $row;   // duplicate barcode → skip

    $item_code = trim($fields['item_code'] ?? '');
    if ($item_code === '') return null;

    $product = findProduct($item_code);
    if (!$product) return null;   // product pehle banna chahiye

    $level = strtoupper(trim($fields['level'] ?? 'PCS'));
    if (!in_array($level, ['CARTON', 'BOX', 'PCS'], true)) $level = 'PCS';

    // pcs_qty: explicit ya level se default
    $pcs = (int)($fields['pcs_qty'] ?? 0);
    if ($pcs < 1) {
        if ($level === 'CARTON') $pcs = (int)$product['pcs_per_ctn'];
        elseif ($level === 'BOX') $pcs = (int)$product['pcs_per_box'];
        else $pcs = 1;
        if ($pcs < 1) $pcs = 1;
    }

    $parent = trim($fields['parent_barcode'] ?? '');

    $st = $conn->prepare("INSERT INTO barcodes (barcode, level, item_code, parent_barcode, pcs_qty) VALUES (?,?,?,?,?)");
    $st->bind_param('ssssi', $barcode, $level, $item_code, $parent, $pcs);
    if (!$st->execute()) return null;

    return findBarcode($barcode);
}


/* ============================================================
   CONSUMED / HIERARCHY HELPER
   ============================================================ */

/**
 * Ek barcode + uske saare children ko is_consumed = 1 karta hai.
 * Recursive: CARTON -> BOX -> PCS tak neeche chalta hai.
 */
function consumeChildren($barcode) {
    global $conn;
    $barcode = trim($barcode);
    if ($barcode === '') return;

    // Khud ko consume karo
    $s = $conn->prepare("UPDATE barcodes SET is_consumed = 1 WHERE barcode = ?");
    $s->bind_param('s', $barcode);
    $s->execute();

    // Direct children dhoondo aur unhe bhi consume karo
    $c = $conn->prepare("SELECT barcode FROM barcodes WHERE parent_barcode = ?");
    $c->bind_param('s', $barcode);
    $c->execute();
    $r = $c->get_result();
    while ($row = $r->fetch_assoc()) {
        consumeChildren($row['barcode']);
    }
}

/**
 * Stock IN process karta hai:
 *   barcode lookup → level se pcs_qty → products += qty → stock_in record → is_consumed = 0
 * @return array ['success'=>bool, 'message'=>string, ...]
 */
function processStockIn($barcode, $source, $remark, $qty = 0) {
    global $conn;
    $barcode = trim($barcode);
    if ($barcode === '') return ['success' => false, 'message' => 'Barcode is required.'];

    $bc = findBarcode($barcode);
    if (!$bc) return ['success' => false, 'message' => 'Barcode not registered. Please register the sheet first.'];

     $pcs = (int)$qty > 0 ? (int)$qty : (int)$bc['pcs_qty'];
    if ($pcs < 1) return ['success' => false, 'message' => 'Barcode has invalid pcs qty.'];

    // Product ka stock badhao
    $upd = $conn->prepare("UPDATE products SET current_stock_pcs = current_stock_pcs + ? WHERE item_code = ?");
    $upd->bind_param('is', $pcs, $bc['item_code']);
    $upd->execute();

    // Transaction record
    $st = $conn->prepare("INSERT INTO stock_in (barcode, level, item_code, item_name, pcs_qty, source, remark) VALUES (?,?,?,?,?,?,?)");
    $remark = trim($remark) !== '' ? $remark : 'Stock In';
    $st->bind_param('ssssiss', $barcode, $bc['level'], $bc['item_code'], $bc['item_name'], $pcs, $source, $remark);
    $st->execute();

    // Stock in ke baad sab available — is barcode + uske children un-consume karo
    // (children bhi 0 taake box/pcs phir se bech sakein)
    $r = $conn->prepare("UPDATE barcodes SET is_consumed = 0 WHERE barcode = ?");
    $r->bind_param('s', $barcode);
    $r->execute();
    consumeChildrenUnset($barcode);

    return [
        'success' => true,
        'message' => 'Stock In: +' . $pcs . ' pcs (' . $bc['level'] . ')',
        'barcode' => $barcode,
        'level'   => $bc['level'],
        'pcs'     => $pcs,
        'item_code' => $bc['item_code'],
    ];
}

/**
 * processStockIn ke liye — barcode + children ka is_consumed = 0
 */
function consumeChildrenUnset($barcode) {
    global $conn;
    $barcode = trim($barcode);
    if ($barcode === '') return;

    $s = $conn->prepare("UPDATE barcodes SET is_consumed = 0 WHERE barcode = ?");
    $s->bind_param('s', $barcode);
    $s->execute();

    $c = $conn->prepare("SELECT barcode FROM barcodes WHERE parent_barcode = ?");
    $c->bind_param('s', $barcode);
    $c->execute();
    $r = $c->get_result();
    while ($row = $r->fetch_assoc()) {
        consumeChildrenUnset($row['barcode']);
    }
}

/**
 * Stock OUT process karta hai:
 *   barcode lookup → not registered/throttle checks → products -= qty
 *   → stock_out record → self + children is_consumed = 1
 * @return array ['success'=>bool, 'message'=>string, ...]
 */
function processStockOut($barcode, $source, $remark) {
    global $conn;
    $barcode = trim($barcode);
    if ($barcode === '') return ['success' => false, 'message' => 'Barcode is required.'];

    $bc = findBarcode($barcode);
    if (!$bc) return ['success' => false, 'message' => 'Barcode not registered.'];

    if ((int)$bc['is_consumed'] === 1) {
        return ['success' => false, 'message' => 'Barcode already consumed.', 'consumed' => true];
    }

    $pcs = (int)$bc['pcs_qty'];
    if ($pcs < 1) return ['success' => false, 'message' => 'Barcode has invalid pcs qty.'];

    $stock = (int)$bc['current_stock_pcs'];
    if ($stock < $pcs) {
        return ['success' => false, 'message' => 'Insufficient stock. Available: ' . $stock . ' pcs'];
    }

    // Product ka stock ghatao
    $upd = $conn->prepare("UPDATE products SET current_stock_pcs = current_stock_pcs - ? WHERE item_code = ?");
    $upd->bind_param('is', $pcs, $bc['item_code']);
    $upd->execute();

    // Transaction record
    $st = $conn->prepare("INSERT INTO stock_out (barcode, level, item_code, item_name, pcs_qty, source, remark) VALUES (?,?,?,?,?,?,?)");
    $remark = trim($remark) !== '' ? $remark : 'Stock Out';
    $st->bind_param('ssssiss', $barcode, $bc['level'], $bc['item_code'], $bc['item_name'], $pcs, $source, $remark);
    $st->execute();

    // Barcode aur uske saare children consumed mark karo
    consumeChildren($barcode);

    return [
        'success'   => true,
        'message'   => 'Stock Out: -' . $pcs . ' pcs (' . $bc['level'] . ')',
        'barcode'   => $barcode,
        'level'     => $bc['level'],
        'pcs'       => $pcs,
        'item_code' => $bc['item_code'],
        'item_name' => $bc['item_name'],
    ];
}


/* ============================================================
   SHEET PARSING — raw cells (rows = array of cell strings)
   Nayi sheet ka expected column order:
   Level | Parent Barcode | Item code | Item name | Barcode
   | Qty Carton | Total Pcs | Boxes pr Ctn | Pcs pr Box | Status
   ============================================================ */

/**
 * Plain text ko rows (raw cells) mein todta hai.
 * Tab, pipe (|) ya multiple-spaces se columns alag hote hain.
 * NOTE: empty cells preserve hote hain (hierarchy sheet mein CARTON ka
 * parent blank hota hai — column order wahi rehna chahiye).
 */
function sheetRowsFromText($text) {
    $rows = [];
    $text = str_replace(["\r\n", "\r"], "\n", $text);   // sab newlines ko \n banao

    foreach (explode("\n", $text) as $line) {
        $line = trim($line);
        if ($line === '') continue;

        // Unified split: SINGLE tab | pipe (surrounding spaces sahit) | 2+ spaces
        // NOTE: tab ko "\t" (single) rakho — "\t+" ho to consecutive tabs ke
        // beech ka EMPTY cell (CARTON ka blank parent) collapse ho jata hai,
        // jisse saare columns shift ho jate hain.
        $parts = preg_split('/\t|(?:\s*\|\s*)|\s{2,}/', $line);
        $parts = array_map('trim', $parts);

        // Consecutive delimiters ke beech empty cells hatate nahi.
        // Sirf aakhri khaaliyaan (trailing) hatao jo split se bani hon.
        while (count($parts) && end($parts) === '') array_pop($parts);

        if (count($parts) < 2) continue;
        $rows[] = $parts;
    }
    return $rows;
}

/** .docx file ka text nikaalta hai (ZipArchive se) */
function docxToText($path) {
    $text = '';
    $zip = new ZipArchive();
    if ($zip->open($path) === true) {
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) return '';
        $xml = str_replace(['<w:p>', '</w:p>', '<w:tr>', '</w:tr>'], ["\n", "\n", "\n", "\n"], $xml);
        if (preg_match_all('/<w:t[^>]*>([^<]*)<\/w:t>/', $xml, $m)) $text = implode('', $m[1]);
    }
    return $text;
}

/** .xlsx file (bina lib) raw cells mein todta hai — XMLReader streaming, handles shared + inline strings */
function xlsxToRows($path) {
    if (!class_exists('XMLReader') || !class_exists('ZipArchive')) return [];
    $rows = [];
    $zip = new ZipArchive();
    $abs = realpath($path);
    if ($zip->open($path) !== true) return [];

    // Shared strings (xlsx mein text wahan store hota hai)
    $shared = [];
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss !== false && preg_match_all('/<si[^>]*>(.*?)<\/si>/s', $ss, $m)) {
        foreach ($m[1] as $si) {
            $val = '';
            if (preg_match_all('/<t[^>]*>([^<]*)<\/t>/', $si, $tm)) $val = implode('', $tm[1]);
            $shared[] = $val;
        }
    }
    unset($ss);

    // Pehli 10 sheets tak scan — XMLReader over zip:// stream (true streaming, fast, low memory)
    for ($i = 1; $i <= 10; $i++) {
        $reader = new XMLReader();
        $ok = @$reader->open('zip://' . str_replace('\\', '/', $abs) . '#xl/worksheets/sheet' . $i . '.xml');
        if (!$ok) break;

        $curRow = [];
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'row') {
                $curRow = [];
            } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'c') {
                $type  = $reader->getAttribute('t') ?? '';
                $v     = '';
                while ($reader->read()) {
                    if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 't') {
                        $v .= $reader->readString();
                    } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'v') {
                        $v .= $reader->readString();
                    } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'c') {
                        break;
                    }
                }
                if ($type === 's' && $v !== '' && ctype_digit($v)) {
                    $v = $shared[(int)$v] ?? '';
                }
                $curRow[] = trim($v);
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'row') {
                if (count($curRow)) {
                    $nonEmpty = count(array_filter($curRow, function ($x) { return $x !== ''; }));
                    if ($nonEmpty >= 2) $rows[] = $curRow;
                }
                $curRow = [];
            }
        }
        $reader->close();

        // hierarchy sheet pehle sheet mein hoti hai — data mil gaya to bas
        if (!empty($rows)) break;
    }
    $zip->close();
    return $rows;
}

/**
 * Uploaded file (csv/txt/xlsx/docx/pdf) ko RAW cell rows mein convert karta hai.
 * @return array  array of arrays (every row = cells list)
 */
function sheetFileToRows($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return [];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $tmp = $file['tmp_name'];

    // PDF — agar Smalot\PdfParser installed hai
    if ($ext === 'pdf' && class_exists('Smalot\PdfParser\Parser')) {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($tmp);
        return sheetRowsFromText($pdf->getText());
    }

    // DOCX
    if ($ext === 'docx') {
        return sheetRowsFromText(docxToText($tmp));
    }

    // XLSX — FAST XMLReader parser (hamesha, PhpSpreadsheet slow hai)
    if ($ext === 'xlsx') {
        return xlsxToRows($tmp);
    }

    // XLS (legacy binary) — sirf iske liye PhpSpreadsheet
    if ($ext === 'xls' && class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $readerType = $ext === 'xlsx' ? 'Xlsx' : 'Xls';
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($readerType);
        $reader->setReadDataOnly(true);
        $wb = $reader->load($tmp);
        $rows = [];
        foreach ($wb->getActiveSheet()->toArray(null, true, true, true) as $r) {
            $cells = array_map('trim', array_values($r));
            while (count($cells) && end($cells) === '') array_pop($cells);
            $nonEmpty = count(array_filter($cells, function ($v) { return $v !== ''; }));
            if ($nonEmpty < 2) continue;
            $rows[] = $cells;
        }
        $wb->disconnectWorksheets();
        return $rows;
    }

    // CSV
    if ($ext === 'csv') {
        $rows = [];
        $handle = fopen($tmp, 'r');
        if ($handle) {
            while (($line = fgetcsv($handle)) !== false) {
                $cells = array_map('trim', array_values($line));
                while (count($cells) && end($cells) === '') array_pop($cells);
                $nonEmpty = count(array_filter($cells, function ($v) { return $v !== ''; }));
                if ($nonEmpty < 2) continue;
                $rows[] = $cells;
            }
            fclose($handle);
        }
        return $rows;
    }

    // Plain text
    if ($ext === 'txt') {
        return sheetRowsFromText(file_get_contents($tmp));
    }

    return [];   // Unknown format
}

/* ============================================================
   AUTO COLUMN DETECTION — headers se columns identify
   "Item ID" | "Item Code" | "Product Code" | "Code" ...
   "Items Name" | "Product Name" | "Description" | "Name" ...
   "Barcode" | "Bar-Code" | "Serial No." ...
   Sab detect ho jata hai. Header na mile to purana positional
   fallback (exact column order) use hota hai.
   ============================================================ */

/**
 * Header text ko canonical banata hai: lowercase + sirf alphanumeric.
 * "Item ID" -> itemid | "Pcs per Box" -> pcsperbox | "Bar-Code" -> barcode
 */
function normalizeSheetHeader($h) {
    return strtolower(preg_replace('/[^a-z0-9]/i', '', trim($h)));
}

/**
 * Normalized header ko ek role assign karta hai.
 * ORDER IMPORTANT: pehle specific (parent/barcode), phir generic (code/id/name).
 */
function scoreSheetHeader($norm) {
    if ($norm === '') return null;

    // PARENT — sabse pehle taaki "Parent Barcode" barcode na bane
    if (strpos($norm, 'parent') !== false) return 'parent';

    // BARCODE — specific
    if (strpos($norm, 'barcode') !== false) return 'barcode';   // barcode, bar-code, barcode no
    if (strpos($norm, 'serial') !== false) return 'barcode';    // serial no, sr-no, serialnumber

    // ITEM CODE / ITEM ID (specific pehle, 'code'/'id' generic last)
    foreach (['itemcode', 'itemid', 'productcode', 'productid', 'partno', 'materialcode', 'sku', 'code', 'id'] as $k) {
        if (strpos($norm, $k) !== false) return 'item_code';
    }

    // ITEM NAME
    foreach (['itemname', 'itemsname', 'productname', 'description', 'name', 'item', 'product'] as $k) {
        if (strpos($norm, $k) !== false) return 'item_name';
    }

    // LEVEL
    if (strpos($norm, 'level') !== false) return 'level';

    // BOXES PR CTN | QTY CARTON (dono ek hi value hoti hai)
    foreach (['boxesprctn', 'boxprctn', 'boxesctn', 'boxctn', 'boxes', 'qtycarton', 'qtyctn', 'cartonqty'] as $k) {
        if (strpos($norm, $k) !== false) return 'boxes_per_ctn';
    }

    // PCS PR BOX
    foreach (['pcsperbox', 'pcsprbox', 'pcsbox', 'perbox', 'pcsox'] as $k) {
        if (strpos($norm, $k) !== false) return 'pcs_per_box';
    }

    // TOTAL PCS
    foreach (['totalpcs', 'pcsqty', 'totalkgs', 'ctnqty', 'qty'] as $k) {
        if (strpos($norm, $k) !== false) return 'total_pcs';
    }

    // STATUS
    if (strpos($norm, 'status') !== false) return 'status';

    return null;
}

/**
 * Rows ke pehle rows mein se header row dhundta hai.
 * Header tabhi maana jata hai jab ek hi row mein Barcode + Item Code + Item Name
 * wale columns milein (strong signal — data row aisa nahi hota).
 *
 * @return array|null  role => column index, + headerRow, rowLength (ya null)
 */
function detectSheetColumns($rows) {
    $limit = min(count($rows), 10);
    for ($ri = 0; $ri < $limit; $ri++) {
        $rowRoles = [];        // role => col index (is sirf rah row mein)
        $seenHere = [];        // is row ke already-consumed normalized headers
        $core     = ['barcode' => null, 'item_code' => null, 'item_name' => null];

        foreach ($rows[$ri] as $ci => $cell) {
            if ($ci > 15) continue;   // max 16 columns scan
            $norm = normalizeSheetHeader($cell);
            if ($norm === '' || strlen($norm) < 2) continue;
            if (isset($seenHere[$norm])) continue;   // duplicate normalized header
            $seenHere[$norm] = true;

            $role = scoreSheetHeader($norm);
            if (!$role || isset($rowRoles[$role])) continue;
            $rowRoles[$role] = $ci;
            if (in_array($role, ['barcode', 'item_code', 'item_name'], true)) {
                $core[$role] = $ci;
            }
        }

        if ($core['barcode'] !== null && $core['item_code'] !== null && $core['item_name'] !== null) {
            $rowRoles['headerRow'] = $ri;
            $rowRoles['rowLength'] = count($rows[$ri]);
            return $rowRoles;
        }
    }
    return null;
}

/**
 * Detect hue column map se rows ko items mein convert karta hai.
 * Title rows + header row skip hote hain. Level barcode prefix se auto-detect.
 * @return array  same shape as sheetRowsToHierarchyItems()
 */
function mapRowsToItems($rows, $map) {
    $items  = [];
    $levels = ['CARTON', 'BOX', 'PCS'];

    $gl = function ($cells, $role) use ($map) {
        if (!isset($map[$role]) || !isset($cells[$map[$role]])) return '';
        return trim($cells[$map[$role]]);
    };

    foreach ($rows as $ri => $cells) {
        if ($ri <= $map['headerRow']) continue;   // title rows + header row skip

        $barcode   = $gl($cells, 'barcode');
        $item_code = $gl($cells, 'item_code');
        if ($barcode === '' || $item_code === '') continue;

        // Data cell agar header jaisa ho (typo/extra header line) → skip
        $bn = normalizeSheetHeader($barcode);
        if ($bn !== '' && in_array($bn, ['barcode', 'itemcode', 'itemid', 'itemname', 'itemsname', 'name', 'serialno', 'serial'], true)) {
            continue;
        }

        $level = strtoupper($gl($cells, 'level'));
        if (!in_array($level, $levels, true)) $level = levelFromBarcode($barcode);

        $boxes_per_ctn = (int)$gl($cells, 'boxes_per_ctn');
        $pcs_per_box   = (int)$gl($cells, 'pcs_per_box');
        $pcs_qty       = (int)$gl($cells, 'total_pcs');

        if ($pcs_qty < 1 && $level === 'PCS') $pcs_qty = 1;
        if ($pcs_qty < 1 && $level === 'CARTON' && $boxes_per_ctn > 0 && $pcs_per_box > 0) {
            $pcs_qty = $boxes_per_ctn * $pcs_per_box;
        }

        $item_name = $gl($cells, 'item_name');
        if ($item_name === '') $item_name = $item_code;

        $items[] = [
            'level'          => $level,
            'parent_barcode' => $gl($cells, 'parent'),
            'item_code'      => $item_code,
            'item_name'      => $item_name,
            'barcode'        => $barcode,
            'pcs_qty'        => $pcs_qty,
            'boxes_per_ctn'  => $boxes_per_ctn,
            'pcs_per_box'    => $pcs_per_box,
        ];
    }
    return $items;
}

/**
 * Sheet rows → items: pehle header auto-detection, warna positional fallback.
 */
function sheetRowsToItems($rows) {
    $map = detectSheetColumns($rows);
    if ($map) {
        $items = mapRowsToItems($rows, $map);
        if (!empty($items)) return $items;
    }
    $items = sheetRowsToHierarchyItems($rows);
    if (!empty($items)) return $items;
    return sheetRowsToSimpleItems($rows);
}

/**
 * Raw sheet rows ko hierarchy items mein convert karta hai.
 * Sirf CARTON / BOX / PCS rows liye jate hain (headers/junk skip).
 *
 * @return array  [[ level, parent_barcode, item_code, item_name, barcode,
 *                   pcs_qty, boxes_per_ctn, pcs_per_box ]]
 */
function sheetRowsToHierarchyItems($rows) {
    $items  = [];
    $levels = ['CARTON', 'BOX', 'PCS'];

    foreach ($rows as $cells) {
        $cells = array_map('trim', array_values($cells));

        $level = strtoupper($cells[0] ?? '');
        if (!in_array($level, $levels, true)) continue;   // header/junk skip

        $parent    = $cells[1] ?? '';
        $item_code = $cells[2] ?? '';
        $item_name = $cells[3] ?? '';
        $barcode   = $cells[4] ?? '';

        if ($barcode === '' || $item_code === '') continue;
        if (preg_match('/^(barcode|serial|level|item.*code)$/i', $barcode)) continue;
        // GUARD: columns shifted hon to garbage product mut banao
        if (preg_match('/\s/', $item_code)) continue;
        if (levelFromBarcode($item_code) !== 'PCS' && preg_match('/^[LBP]/i', $item_code)) continue;

        $pcs_qty     = (int)($cells[6] ?? 0);   // Total Pcs column
        $boxes_per_ctn = (int)($cells[7] ?? 0); // Boxes pr Ctn
        $pcs_per_box   = (int)($cells[8] ?? 0); // Pcs pr Box

        if ($boxes_per_ctn < 1) $boxes_per_ctn = (int)($cells[5] ?? 0);

        if ($pcs_qty < 1) {
            $pcs_qty = $level === 'PCS' ? 1 : 0;
        }

        $items[] = [
            'level'          => $level,
            'parent_barcode' => $parent,
            'item_code'      => $item_code,
            'item_name'      => $item_name !== '' ? $item_name : $item_code,
            'barcode'        => $barcode,
            'pcs_qty'        => $pcs_qty,
            'boxes_per_ctn'  => $boxes_per_ctn,
            'pcs_per_box'    => $pcs_per_box,
        ];
    }
    return $items;
}

/**
 * LAST-RESORT fallback: simple 3-column sheet
 *   Row = [Item Code, Items Name, Barcode]
 *   Har row -> ek product + ek barcode (PCS).
 */
function sheetRowsToSimpleItems($rows) {
    $items = [];
    foreach ($rows as $cells) {
        $cells = array_map('trim', array_values($cells));
        if (count($cells) < 3) continue;

        $code    = $cells[0];
        $name    = $cells[1];
        $barcode = $cells[2];

        if (in_array(normalizeSheetHeader($code),    ['itemcode','itemid','code','srno','sr','serialno'], true)) continue;
        if (in_array(normalizeSheetHeader($name),    ['itemsname','itemname','productname','name'], true))   continue;
        if (in_array(normalizeSheetHeader($barcode), ['barcode','serialno','serial'], true))                 continue;

        if ($code === '' || $barcode === '') continue;
        if (preg_match('/\s/', $code)) continue;

        $level = levelFromBarcode($barcode);
        $items[] = [
            'level'          => $level,
            'parent_barcode' => '',
            'item_code'      => $code,
            'item_name'      => $name !== '' ? $name : $code,
            'barcode'        => $barcode,
            'pcs_qty'        => $level === 'PCS' ? 1 : 0,
            'boxes_per_ctn'  => 0,
            'pcs_per_box'    => 0,
        ];
    }
    return $items;
}

/**
 * Same item_code + same barcode wali rows ko 1 row mein merge karta hai.
 * qty (CTN QTY) sum hoti hai. Different barcode = naya row.
 */
function aggregateSheetItems($items) {
    $out = [];
    foreach ($items as $it) {
        $key = $it['item_code'] . "\0" . $it['barcode'];
        if (!isset($out[$key])) {
            $out[$key] = $it;
            $out[$key]['count'] = 1;
        } else {
            $out[$key]['pcs_qty'] += (int)$it['pcs_qty'];
            $out[$key]['count']++;
        }
    }
    return array_values($out);
}

/**
 * Har item ka check: item_code products mein aur barcode barcodes mein registered hai?
 * Har item mein 'registered' => true/false add kar deta hai.
 */
function markRegisteredItems($items) {
    global $conn;
    $codes = [];
    $bcs   = [];
    foreach ($items as $it) {
        if (($it['item_code'] ?? '') !== '') $codes[] = $it['item_code'];
        if (($it['barcode']   ?? '') !== '') $bcs[]   = $it['barcode'];
    }
    $codes = array_values(array_unique($codes));
    $bcs   = array_values(array_unique($bcs));

    $prodSet = [];
    if ($codes) {
        $esc = array_map(function ($c) use ($conn) { return "'" . $conn->real_escape_string($c) . "'"; }, $codes);
        if ($res = $conn->query("SELECT item_code FROM products WHERE item_code IN (" . implode(',', $esc) . ")")) {
            while ($row = $res->fetch_assoc()) $prodSet[$row['item_code']] = true;
        }
    }
    $bcSet = [];
    if ($bcs) {
        $esc = array_map(function ($b) use ($conn) { return "'" . $conn->real_escape_string($b) . "'"; }, $bcs);
        if ($res = $conn->query("SELECT barcode FROM barcodes WHERE barcode IN (" . implode(',', $esc) . ")")) {
            while ($row = $res->fetch_assoc()) $bcSet[$row['barcode']] = true;
        }
    }

    $out = [];
    foreach ($items as $it) {
        $it['registered'] = isset($prodSet[$it['item_code'] ?? '']) && isset($bcSet[$it['barcode'] ?? '']);
        $out[] = $it;
    }
    return $out;
}