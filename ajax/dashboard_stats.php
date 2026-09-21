<?php
/* ============================================================
   ajax/dashboard_stats.php — Dashboard ke saare counters + lists
   Sab kuch PIECES mein (current_stock_pcs / pcs_qty).
   Koi LIMIT nahi lagti — full data.
   ============================================================ */
require_once dirname(__DIR__) . '/config/db.php';

/* ---------- Counters ---------- */
$totalProducts = (int)$conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalStock    = (int)$conn->query("SELECT COALESCE(SUM(current_stock_pcs),0) FROM products")->fetch_row()[0];
$totalIn       = (int)$conn->query("SELECT COALESCE(SUM(pcs_qty),0) FROM stock_in")->fetch_row()[0];
$totalOut      = (int)$conn->query("SELECT COALESCE(SUM(pcs_qty),0) FROM stock_out")->fetch_row()[0];
$lowStock      = (int)$conn->query("SELECT COUNT(*) FROM products WHERE current_stock_pcs <= 10")->fetch_row()[0];

/* ---------- Aaj ke In/Out (pieces) ---------- */
$today = date('Y-m-d');
$todayIn  = (int)$conn->query("SELECT COALESCE(SUM(pcs_qty),0) FROM stock_in  WHERE DATE(entry_date) = '$today'")->fetch_row()[0];
$todayOut = (int)$conn->query("SELECT COALESCE(SUM(pcs_qty),0) FROM stock_out WHERE DATE(entry_date) = '$today'")->fetch_row()[0];

/* ---------- Recent (in+out mixed, full list) ---------- */
$r = $conn->query("(SELECT 'in' AS rec_type, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_in)
                   UNION ALL
                   (SELECT 'out' AS rec_type, barcode, level, item_code, item_name, pcs_qty, source, remark, entry_date FROM stock_out)
                   ORDER BY entry_date DESC");
$recent = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];

/* ---------- Low stock products (full list) ---------- */
$l = $conn->query("SELECT item_code, item_name, current_stock_pcs FROM products WHERE current_stock_pcs <= 10 ORDER BY current_stock_pcs ASC");
$lowList = $l ? $l->fetch_all(MYSQLI_ASSOC) : [];

jout([
    'success' => true,
    'data' => [
        'total_products' => $totalProducts,
        'total_stock'    => $totalStock,
        'total_in'       => $totalIn,
        'total_out'      => $totalOut,
        'low_stock'      => $lowStock,
        'today_in'       => $todayIn,
        'today_out'      => $todayOut,
        'recent'         => $recent,
        'low_list'       => $lowList,
    ]
]);