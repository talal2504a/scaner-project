<?php
/* ============================================================
   ajax/history_list.php — Stock In/Out history
   GET query:
     - type = all | in | out
     - month = YYYY-MM (default: current month)
     - date  = YYYY-MM-DD (exact date filter)
     - all   = 1 (full history — slow, avoid)
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$type  = $_GET['type']  ?? 'all';
$date  = $_GET['date']  ?? '';
$month = $_GET['month'] ?? '';
$all   = isset($_GET['all']) && $_GET['all'] === '1';

/* Type ke hisaab se base query */
if ($type === 'in') {
    $sql = "SELECT 'in' AS rec_type, id, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_in";
} elseif ($type === 'out') {
    $sql = "SELECT 'out' AS rec_type, id, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_out";
} else {
    $sql = "(SELECT 'in' AS rec_type, id, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_in)
            UNION ALL
            (SELECT 'out' AS rec_type, id, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_out)";
}

/* Filter: exact date > month > default current month > all */
$esc = function ($v) use ($conn) { return $conn->real_escape_string($v); };

if ($date !== '') {
    $sql .= " WHERE DATE(entry_date) = '" . $esc($date) . "'";
} elseif ($month !== '') {
    $sql .= " WHERE entry_date LIKE '" . $esc($month) . "%'";
} elseif (!$all) {
    /* Default: current month — 10k rows dump nahi hoga */
    $sql .= " WHERE entry_date LIKE '" . $esc(date('Y-m')) . "%'";
}

$sql .= " ORDER BY entry_date DESC";

/* all=1 chhodo, warna max 5000 (display list hai, counters alag hain) */
if (!$all) $sql .= " LIMIT 5000";

$res  = $conn->query($sql);
$data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
jout(['success' => true, 'data' => $data]);