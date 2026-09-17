<?php
// ajax/dashboard_stats.php — Dashboard counters
require_once dirname(__DIR__) . '/config/db.php';

$totalProducts = (int)$conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$totalStock    = (int)$conn->query("SELECT COALESCE(SUM(current_stock),0) FROM products")->fetch_row()[0];
$totalIn       = (int)$conn->query("SELECT COALESCE(SUM(quantity),0) FROM stock_in")->fetch_row()[0];
$totalOut      = (int)$conn->query("SELECT COALESCE(SUM(quantity),0) FROM stock_out")->fetch_row()[0];
$lowStock      = (int)$conn->query("SELECT COUNT(*) FROM products WHERE current_stock <= 5")->fetch_row()[0];

$today = date('Y-m-d');
$todayIn  = (int)$conn->query("SELECT COALESCE(SUM(quantity),0) FROM stock_in  WHERE DATE(entry_date) = '$today'")->fetch_row()[0];
$todayOut = (int)$conn->query("SELECT COALESCE(SUM(quantity),0) FROM stock_out WHERE DATE(entry_date) = '$today'")->fetch_row()[0];

// Recent 8 records
$recent = [];
$r = $conn->query("(SELECT 'in' AS rec_type, serial_code, item_name, quantity, entry_date FROM stock_in)
                   UNION ALL
                   (SELECT 'out' AS rec_type, serial_code, item_name, quantity, entry_date FROM stock_out)
                   ORDER BY entry_date DESC LIMIT 8");
if ($r) $recent = $r->fetch_all(MYSQLI_ASSOC);

// Low stock products
$lowList = [];
$l = $conn->query("SELECT serial_code, item_name, current_stock FROM products WHERE current_stock <= 5 ORDER BY current_stock ASC LIMIT 8");
if ($l) $lowList = $l->fetch_all(MYSQLI_ASSOC);

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