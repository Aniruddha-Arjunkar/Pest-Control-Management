<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

// Helper to convert quantity to rate unit
function convert_to_rate_unit($quantity, $from_unit, $to_unit) {
    $conversions = [
        'ml'  => ['ml' => 1, 'ltr' => 0.001],
        'ltr' => ['ml' => 1000, 'ltr' => 1],
        'gm'  => ['gm' => 1, 'kg' => 0.001],
        'kg'  => ['gm' => 1000, 'kg' => 1],
        'pc'  => ['pc' => 1],
        'box' => ['box' => 1],
    ];
    $from = strtolower($from_unit);
    $to   = strtolower($to_unit);
    return isset($conversions[$from][$to]) ? $quantity * $conversions[$from][$to] : $quantity;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = intval($_POST['client_id']);
    $material_ids = $_POST['material_id'];
    $quantities = $_POST['quantity_used'];
    $units = $_POST['unit'];
    $remarks = $_POST['remarks'];
    $branch_id = intval($_POST['branch_id']);

    $used_date = $_POST['used_date'] ?? date('Y-m-d'); // use posted date or fallback
    $success = 0;
    $errors = 0;

    for ($i = 0; $i < count($material_ids); $i++) {
        $material_id = intval($material_ids[$i]);
        $quantity_used = floatval($quantities[$i]);
        $input_unit = $units[$i];
        $remark = mysqli_real_escape_string($conn, $remarks[$i]);
        
        

        // Fetch material details
        $mat_query = mysqli_query($conn, "SELECT rate_per_unit, rate_unit FROM materials WHERE id = $material_id");
        if (!$mat_query || mysqli_num_rows($mat_query) === 0) {
            $errors++;
            continue;
        }

        $material = mysqli_fetch_assoc($mat_query);
        $base_rate = floatval($material['rate_per_unit']);
        $rate_unit = $material['rate_unit'];
        
        
        // ================= STOCK CHECK =================

// View-stock style available calculation
$stock_q = mysqli_query($conn, "
SELECT 
  COALESCE(p.total_purchased,0)
+ COALESCE(ti.total_in,0)
- COALESCE(u.total_used,0)
- COALESCE(tof.total_out,0) AS available_stock

FROM materials m

LEFT JOIN (
    SELECT material_id, branch_id, SUM(quantity) AS total_purchased
    FROM stock_purchases
    GROUP BY material_id, branch_id
) p ON p.material_id = m.id AND p.branch_id = $branch_id

LEFT JOIN (
    SELECT material_id, to_branch AS branch_id, SUM(quantity) AS total_in
    FROM stock_transfers
    GROUP BY material_id, to_branch
) ti ON ti.material_id = m.id AND ti.branch_id = $branch_id

LEFT JOIN (
    SELECT 
        sm.material_id, 
        sm.branch_id, 
        SUM(
            CASE 
                WHEN m.rate_unit = 'Ltr' AND sm.quantity_unit = 'ml' THEN sm.quantity_used / 1000
                WHEN m.rate_unit = 'Ltr' AND sm.quantity_unit = 'Ltr' THEN sm.quantity_used
                WHEN m.rate_unit = 'Kg'  AND sm.quantity_unit = 'gm' THEN sm.quantity_used / 1000
                WHEN m.rate_unit = 'Kg'  AND sm.quantity_unit = 'Kg' THEN sm.quantity_used
                WHEN m.rate_unit = 'Pc'  AND sm.quantity_unit = 'Pc' THEN sm.quantity_used
                WHEN m.rate_unit = 'Box' AND sm.quantity_unit = 'Box' THEN sm.quantity_used
                ELSE 0
            END
        ) AS total_used
    FROM service_materials sm
    INNER JOIN materials m ON m.id = sm.material_id
    GROUP BY sm.material_id, sm.branch_id
) u ON u.material_id = m.id AND u.branch_id = $branch_id

LEFT JOIN (
    SELECT material_id, from_branch AS branch_id, SUM(quantity) AS total_out
    FROM stock_transfers
    GROUP BY material_id, from_branch
) tof ON tof.material_id = m.id AND tof.branch_id = $branch_id

WHERE m.id = $material_id
");

$stock = mysqli_fetch_assoc($stock_q);
$available_stock = floatval($stock['available_stock']);



        // Convert quantity to match rate unit
        $qty_in_rate_unit = convert_to_rate_unit($quantity_used, $input_unit, $rate_unit);
        
        
        if ($available_stock <= 0) {
    echo "<script>
        alert('❌ Stock not available for selected material');
        window.history.back();
    </script>";
    exit;
}

if ($qty_in_rate_unit > $available_stock) {
    echo "<script>
        alert('❌ Insufficient stock. Available: {$available_stock} {$rate_unit}');
        window.history.back();
    </script>";
    exit;
}


        // Insert into service_materials
        $query = "INSERT INTO service_materials 
            (client_id, branch_id, material_id, quantity_used, quantity_unit, rate_at_time, used_date, remarks)
            VALUES 
            ('$client_id', '$branch_id', '$material_id', '$quantity_used', '$input_unit', '$base_rate', '$used_date', '$remark')";

        if (mysqli_query($conn, $query)) {
            $success++;
        } else {
            $errors++;
        }
    }

    // Redirect with message
    if ($success > 0 && $errors === 0) {
        echo "<script>alert('✅ Materials saved successfully.'); window.location.href='stock-history-table.php';</script>";
    } elseif ($success > 0) {
        echo "<script>alert('⚠️ Some materials saved, but some failed.'); window.location.href='stock-history.php';</script>";
    } else {
        echo "<script>alert('❌ Failed to save any materials.'); window.history.back();</script>";
    }
} else {
    echo "Invalid request.";
}
