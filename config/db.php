<?php
/**
 * config/db.php — DB connection + shared helpers
 * Har ajax file isko include karegi.
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'stock_system');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'DB connection failed: ' . $conn->connect_error]));
}
$conn->set_charset('utf8mb4');

/** JSON response bhejo aur exit */
function jout($arr) {
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
}

/** Photo upload -> returns path (uploads/photos/xxx) ya '' */
function uploadPhoto($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return '';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return '';
    $name = date('Ymd_His') . '_' . rand(1000, 9999) . '.' . $ext;
    $dir = dirname(__DIR__) . '/uploads/photos/';
    if (move_uploaded_file($file['tmp_name'], $dir . $name)) {
        return 'uploads/photos/' . $name;
    }
    return '';
}

/** Product serial se dhoondho -> row array ya null */
function findProduct($serial) {
    global $conn;
    $s = trim($serial);
    if ($s === '') return null;
    $st = $conn->prepare("SELECT * FROM products WHERE serial_code = ? LIMIT 1");
    $st->bind_param('s', $s);
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_assoc() : null;
}

/** Product register karo agar missing ho. Khali serial reject. */
function ensureProduct($serial, $fields, $photo = '') {
    global $conn;
    $serial = trim($serial);
    if ($serial === '') return null;
    $row = findProduct($serial);
    if ($row) return $row;

    $st = $conn->prepare("INSERT INTO products
        (serial_code, item_name, category, model, poles, rating, voltage, ka, packaging, notes, photo)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $st->bind_param('sssssssssss',
        $serial,
        $fields['item_name'],
        $fields['category'],
        $fields['model'],
        $fields['poles'],
        $fields['rating'],
        $fields['voltage'],
        $fields['ka'],
        $fields['packaging'],
        $fields['notes'],
        $photo
    );
    if (!$st->execute()) return null;
    return findProduct($serial);
}

/**
 * Description parse -> structured columns
 * e.g. "NXB-63 1P C2A 6KA (180Pcs Ctn)"
 *      "NOARK EX9UEP 20 1P 750 EU"
 *      "NXBLE-63 2P C16A 30mA 6KA"
 */
function parseDescription($desc, $category = '') {
    $d = [
        'category'  => $category,
        'item_name' => trim($desc),
        'model'     => '',
        'poles'     => '',
        'rating'    => '',
        'voltage'   => '',
        'ka'        => '',
        'packaging' => '',
        'notes'     => '',
    ];
    $rest = trim($desc);
    if ($rest === '') return $d;

    // Packaging: "(180Pcs Ctn)"
    if (preg_match('/\(([^()]{1,60})\)/', $rest, $m)) {
        $d['packaging'] = $m[1];
        $rest = trim(str_replace($m[0], '', $rest));
    }

    // KA: 6KA / 10KA / 25KA
    if (preg_match('/\b(\d+(?:\.\d+)?)\s*KA\b/i', $rest, $m)) {
        $d['ka'] = strtoupper($m[1]) . 'KA';
        $rest = trim(preg_replace('/\b(\d+(?:\.\d+)?)\s*KA\b/i', '', $rest));
    }

    // Voltage: 220V / 500V / 1000V / 380V
    if (preg_match('/\b(\d+(?:\.\d+)?)\s*V\b/i', $rest, $m)) {
        $d['voltage'] = strtoupper($m[1]) . 'V';
        $rest = trim(preg_replace('/\b(\d+(?:\.\d+)?)\s*V\b/i', '', $rest));
    }

    // Poles: 1P/2P/3P/4P
    if (preg_match('/\b([1-4])\s*P\b/', $rest, $m)) {
        $d['poles'] = $m[1] . 'P';
    }

    // Rating: C2A / C32A / 100A / 40KA-385V wala (short circuit)
    if (preg_match('/\bC\d+(?:\.\d+)?A\b/i', $rest, $m)) {
        $d['rating'] = strtoupper($m[0]);
        $rest = trim(preg_replace('/\bC\d+(?:\.\d+)?A\b/i', '', $rest));
    } elseif (preg_match('/\b(\d+(?:\.\d+)?)\s*A\b(?!\w)/', $rest, $m)) {
        $d['rating'] = strtoupper($m[1]) . 'A';
        $rest = trim(preg_replace('/\b(\d+(?:\.\d+)?)\s*A\b(?!\w)/', '', $rest));
    } elseif (preg_match('/\b\d+(?:\.\d+)?\s*KA\/\d+V\b/i', $rest, $m)) {
        $d['rating'] = strtoupper($m[0]);
        $rest = trim(str_replace($m[0], '', $rest));
    }

    // Model: aage ke 1-3 tokens jo feature token na ho
    $tokens = preg_split('/\s+/', $rest);
    $modelParts = [];
    foreach ($tokens as $t) {
        if (count($modelParts) >= 3) break;
        if (preg_match('/^([1-4])P$/', $t)) break;
        if (preg_match('/^(C?\d+(?:\.\d+)?A|\d+(?:\.\d+)?KA|\d+(?:\.\d+)?V|\d+mA|\d+\/\d+A)$/i', $t)) break;
        if (preg_match('/^[\d\/]+Hz$/i', $t)) break;
        $modelParts[] = $t;
    }
    $d['model'] = implode(' ', $modelParts);
    $rest = trim(str_replace($d['model'], '', $rest));

    // Notes: bacha hua (30mA, 50/60Hz, D/Outlet, CCC, 50C, EU, PATI ...)
    $d['notes'] = trim($rest);
    $d['notes'] = preg_replace('/\s+/', ' ', $d['notes']);

    return $d;
}

/** Text/CSV lines -> rows [serial, description, qty] */
function sheetRowsFromText($text) {
    $rows = [];
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    foreach (explode("\n", $text) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = [];

        $tabs = preg_split('/\t+/', $line);
        $pipes = preg_split('/\s*\|\s*/', $line);
        $spaces = preg_split('/\s{2,}/', $line);

        foreach ([$tabs, $pipes, $spaces] as $set) {
            $set = array_values(array_filter(array_map('trim', $set), function ($p) { return $p !== ''; }));
            if (count($set) >= 2) { $parts = $set; break; }
        }
        if (count($parts) < 2) continue;

        $serial = $parts[0];
        $desc = '';
        for ($i = 1; $i < count($parts); $i++) $desc .= ($desc ? ' ' : '') . $parts[$i];
        $qty = 1;
        if (preg_match('/\b(\d{1,6})\b\s*$/', $desc, $qm)) {
            $qty = (int)$qm[1];
        }
        $rows[] = ['serial' => $serial, 'description' => $desc, 'qty' => $qty];
    }
    return $rows;
}

/** DOCX -> text */
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
    $text = preg_replace('/[^\x20-\x7E\n\t]/', '', $text);
    return $text;
}

/** XLSX -> rows */
function xlsxToRows($path) {
    if (!class_exists('ZipArchive')) return [];
    $rows = [];
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return $rows;

    $shared = [];
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss !== false && preg_match_all('/<si[^>]*>(.*?)<\/si>/s', $ss, $m)) {
        foreach ($m[1] as $si) {
            $val = '';
            if (preg_match_all('/<t[^>]*>([^<]*)<\/t>/', $si, $tm)) $val = implode('', $tm[1]);
            $shared[] = $val;
        }
    }

    for ($i = 1; $i <= 10; $i++) {
        $sheetXml = $zip->getFromName('xl/worksheets/sheet' . $i . '.xml');
        if ($sheetXml === false) break;
        if (preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $sheetXml, $rm)) {
            foreach ($rm[1] as $rxml) {
                $vals = [];
                if (preg_match_all('/<c[^>]*?(?:t="(\w+)")?[^>]*>(?:<v>([^<]*)<\/v>)?/', $rxml, $cm, PREG_SET_ORDER)) {
                    foreach ($cm as $c) {
                        $v = isset($c[2]) ? $c[2] : '';
                        if (isset($c[1]) && $c[1] === 's' && isset($shared[(int)$v])) $v = $shared[(int)$v];
                        $vals[] = trim($v);
                    }
                }
                $vals = array_values(array_filter($vals, function ($v) { return $v !== ''; }));
                if (count($vals) < 2) continue;
                $rows[] = ['serial' => $vals[0], 'description' => $vals[1], 'qty' => (isset($vals[2]) && is_numeric($vals[2])) ? (int)$vals[2] : 1];
            }
        }
    }
    $zip->close();
    return $rows;
}

/**
 * Uploaded file ko rows mein badlo (pdf/docx/xls/xlsx/csv/txt)
 * Returns [serial, description, qty]
 */
function sheetFileToRows($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return [];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $tmp = $file['tmp_name'];

    // Vendor libraries (agar installed hain)
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    $vendorLoaded = file_exists($autoload);

    if ($ext === 'pdf' && $vendorLoaded && class_exists('Smalot\PdfParser\Parser')) {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($tmp);
        return sheetRowsFromText($pdf->getText());
    } elseif ($ext === 'docx') {
        return sheetRowsFromText(docxToText($tmp));
    } elseif (in_array($ext, ['xls', 'xlsx']) && $vendorLoaded && class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(strtoupper($ext));
        $wb = $reader->load($tmp);
        $rows = [];
        foreach ($wb->getActiveSheet()->toArray() as $r) {
            $cells = array_values(array_filter(array_map('trim', $r), function ($v) { return $v !== ''; }));
            if (count($cells) < 2) continue;
            $rows[] = ['serial' => $cells[0], 'description' => $cells[1], 'qty' => (isset($cells[2]) && is_numeric($cells[2])) ? (int)$cells[2] : 1];
        }
        return $rows;
    } elseif ($ext === 'xlsx') {
        return xlsxToRows($tmp);
    } elseif ($ext === 'csv') {
        $rows = [];
        $handle = fopen($tmp, 'r');
        if ($handle) {
            while (($line = fgetcsv($handle)) !== false) {
                $cells = array_values(array_filter(array_map('trim', $line), function ($v) { return $v !== ''; }));
                if (count($cells) < 2) continue;
                $rows[] = ['serial' => $cells[0], 'description' => $cells[1], 'qty' => (isset($cells[2]) && is_numeric($cells[2])) ? (int)$cells[2] : 1];
            }
            fclose($handle);
        }
        return $rows;
    } elseif ($ext === 'txt') {
        return sheetRowsFromText(file_get_contents($tmp));
    }
    return [];
}

/**
 * Rows -> items (category headers handle karta hai + multi-barcode split)
 * Return items with: serial, item_name, category, model, poles, rating,
 *                    voltage, ka, packaging, notes, qty
 */
function sheetRowsToItems($rows) {
    $items = [];
    $cat = '';
    foreach ($rows as $r) {
        $serial = trim($r['serial']);
        $desc = trim($r['description']);
        if ($serial === '') {
            if ($desc !== '') $cat = trim($desc, ' .');
            continue;
        }
        $item = parseDescription($desc, $cat);
        $qty = (isset($r['qty']) && $r['qty'] > 0) ? (int)$r['qty'] : 1;

        $serials = [trim($serial)];
        if (strpos($serial, '/') !== false) {
            $serials = [];
            foreach (explode('/', $serial) as $s) {
                $s = trim($s);
                if ($s !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $s)) $serials[] = $s;
            }
            if (!$serials) $serials[] = trim($serial);
        }

        foreach ($serials as $s) {
            $cp = $item;
            $cp['serial'] = $s;
            $cp['qty'] = $qty;
            $items[] = $cp;
        }
    }
    return $items;
}