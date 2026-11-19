<?php
include 'db_connect.php';

header('Content-Type: application/json');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================
   SAFE VALUE FUNCTION
========================= */
function getValue($conn, $query) {
    try {
        $result = mysqli_query($conn, $query);

        if ($result) {
            $row = mysqli_fetch_assoc($result);

            foreach ($row as $v) {
                return (int)$v;
            }
        }

        return 0;

    } catch (Exception $e) {
        return 0;
    }
}

/* =========================
   TABLE CHECK
========================= */
function tableExists($conn, $table) {
    $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return ($check && mysqli_num_rows($check) > 0);
}

/* =========================
   RESPONSE
========================= */
$data = [
    "clients"  => 0,
    "payments" => 0,
    "due"      => 0,
    "expenses" => 0   // ✅ ADDED
];

/* =========================
   CLIENTS
========================= */
$data['clients'] = tableExists($conn, 'tbl_client')
    ? getValue($conn, "SELECT COUNT(*) FROM tbl_client")
    : 0;

/* =========================
   PAYMENTS
========================= */
$data['payments'] = tableExists($conn, 'tbl_payment')
    ? getValue($conn, "SELECT COUNT(*) FROM tbl_payment")
    : 0;

/* =========================
   DUE PAYMENTS
========================= */
$data['due'] = tableExists($conn, 'tbl_payment')
    ? getValue($conn, "
        SELECT COUNT(*) FROM (

            SELECT c.Id

            FROM tbl_client c

            LEFT JOIN tbl_payment p
            ON c.Id = p.client_Id

            WHERE (
                p.status IS NULL
                OR LOWER(TRIM(p.status)) != 'defaulted'
            )

            GROUP BY c.Id

            HAVING (
                COALESCE(SUM(p.total_amount),0)
                - COALESCE(SUM(p.paid_amount),0)
            ) > 0

        ) AS due_clients
    ")
    : 0;

/* =========================
   TOTAL EXPENSES (NEW)
========================= */
$data['expenses'] = tableExists($conn, 'tbl_expenses')
    ? getValue($conn, "
        SELECT SUM(amount) FROM tbl_expenses
    ")
    : 0;

/* =========================
   OUTPUT
========================= */
echo json_encode($data);
?>