<?php
require_once 'db_connect.php';

$employee_id = $_GET['employee_id'] ?? 0;

$q = mysqli_query($conn, "
    SELECT
        SUM(CASE WHEN payment_type='Advance' THEN COALESCE(advance,0) ELSE 0 END) AS total_advance,
        SUM(CASE WHEN payment_type IN ('Salary','PerDay') THEN COALESCE(advance,0) ELSE 0 END) AS total_deducted
    FROM tbl_salary
    WHERE employee_id='$employee_id'
");

$data = mysqli_fetch_assoc($q);

$pending = ($data['total_advance'] ?? 0) - ($data['total_deducted'] ?? 0);

echo json_encode([
    'pending_advance' => max($pending,0)
]);
