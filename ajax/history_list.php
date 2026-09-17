<?php
// ajax/history_list.php — Stock In/Out records
require_once dirname(__DIR__) . '/config/db.php';

$type = $_GET['type'] ?? 'all';   // all | in | out
$date = $_GET['date'] ?? '';      // optional YYYY-MM-DD

if ($type === 'in') {
    $sql = "SELECT 'in' AS rec_type, id, serial_code, item_name, quantity, remark, source, entry_date FROM stock_in";
} elseif ($type === 'out') {
    $sql = "SELECT 'out' AS rec_type, id, serial_code, item_name, quantity, remark, source, entry_date FROM stock_out";
} else {
    $sql = "(SELECT 'in' AS rec_type, id, serial_code, item_name, quantity, remark, source, entry_date FROM stock_in)
            UNION ALL
            (SELECT 'out' AS rec_type, id, serial_code, item_name, quantity, remark, source, entry_date FROM stock_out)";
}

if ($date !== '') {
    $sql .= " WHERE DATE(entry_date) = '" . $conn->real_escape_string($date) . "'";
}
$sql .= " ORDER BY entry_date DESC LIMIT 200";

$res = $conn->query($sql);
$data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
jout(['success' => true, 'data' => $data]);