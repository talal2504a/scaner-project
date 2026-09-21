<?php
/* ============================================================
   ajax/history_list.php — Stock In/Out history
   GET query:
     - type = all | in | out
     - date = YYYY-MM-DD (optional filter)
   Koi LIMIT nahi.
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

$type = $_GET['type'] ?? 'all';
$date = $_GET['date'] ?? '';

/* Type ke hisaab se base query (naye columns: barcode, level, pcs_qty) */
if ($type === 'in') {
    $sql = "SELECT 'in' AS rec_type, id, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_in";
} elseif ($type === 'out') {
    $sql = "SELECT 'out' AS rec_type, id, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_out";
} else {
    $sql = "(SELECT 'in' AS rec_type, id, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_in)
            UNION ALL
            (SELECT 'out' AS rec_type, id, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_out)";
}

/* Date filter (safe: real_escape_string) */
if ($date !== '') {
    $sql .= " WHERE DATE(entry_date) = '" . $conn->real_escape_string($date) . "'";
}
$sql .= " ORDER BY entry_date DESC";

$res  = $conn->query($sql);
$data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
jout(['success' => true, 'data' => $data]);