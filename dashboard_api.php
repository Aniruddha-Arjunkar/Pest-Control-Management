<?php
session_start();

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');
include 'db_connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================
   READ MONTH FROM URL
   Calendar sets ?month=YYYY-MM in the URL directly
========================= */
$selected_month = $_GET['month'] ?? '';
$year  = null;
$month = null;

if (!empty($selected_month)) {
    $year  = (int)substr($selected_month, 0, 4);
    $month = (int)substr($selected_month, 5, 2);
}

/* =========================
   HELPERS
========================= */
function tableExists($conn, $table) {
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return ($res && mysqli_num_rows($res) > 0);
}

/* =========================
   CLIENTS
   tbl_client has no created_at — use due_date
========================= */
$clients = 0;
if (tableExists($conn, 'tbl_client')) {
    if ($year && $month) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tbl_client WHERE YEAR(due_date)=? AND MONTH(due_date)=?");
        $stmt->bind_param("ii", $year, $month);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tbl_client");
    }
    $stmt->execute();
    $clients = (int)$stmt->get_result()->fetch_assoc()['total'];
}

/* =========================
   PAYMENTS
   status is ENUM('active','defaulted') — no 'completed'
   A received payment = paid_amount > 0
   Date filter uses payment_date
========================= */
$payments = 0;
if (tableExists($conn, 'tbl_payment')) {
    if ($year && $month) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tbl_payment WHERE paid_amount>0 AND YEAR(payment_date)=? AND MONTH(payment_date)=?");
        $stmt->bind_param("ii", $year, $month);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tbl_payment WHERE paid_amount>0");
    }
    $stmt->execute();
    $payments = (int)$stmt->get_result()->fetch_assoc()['total'];
}

/* =========================
   DUE PAYMENTS
   due_amount column stores outstanding balance directly
   Filter by payment_date OR due_date
========================= */
$due = 0;
if (tableExists($conn, 'tbl_payment')) {
    if ($year && $month) {
        $due = (int)$conn->query("
            SELECT COUNT(*) AS total
            FROM tbl_payment
            WHERE due_amount > 0
              AND (
                  (payment_date IS NOT NULL AND YEAR(payment_date) = $year AND MONTH(payment_date) = $month)
               OR (due_date     IS NOT NULL AND YEAR(due_date)     = $year AND MONTH(due_date)     = $month)
              )
        ")->fetch_assoc()['total'];
    } else {
        $due = (int)$conn->query("SELECT COUNT(*) AS total FROM tbl_payment WHERE due_amount>0")->fetch_assoc()['total'];
    }
}

/* =========================
   EXPENSES
   Date column is `date` (not created_at)
   amount is TEXT — must CAST before SUM
========================= */
$expenses = 0;
if (tableExists($conn, 'tbl_expenses')) {
    if ($year && $month) {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(CAST(amount AS DECIMAL(12,2))),0) AS total FROM tbl_expenses WHERE YEAR(`date`)=? AND MONTH(`date`)=?");
        $stmt->bind_param("ii", $year, $month);
    } else {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(CAST(amount AS DECIMAL(12,2))),0) AS total FROM tbl_expenses");
    }
    $stmt->execute();
    $expenses = (float)$stmt->get_result()->fetch_assoc()['total'];
}

/* =========================
   CHART
   tbl_pending_services, column service_date
   Group by month for selected year (or current year)
   OLD bug: MONTH() alone merged all years together
========================= */
$chartYear = $year ?? (int)date('Y');
$chartData = array_fill(0, 12, 0);

if (tableExists($conn, 'tbl_pending_services')) {
    $stmt = $conn->prepare("
        SELECT MONTH(service_date) AS m, COUNT(*) AS total
        FROM tbl_pending_services
        WHERE YEAR(service_date) = ?
        GROUP BY MONTH(service_date)
    ");
    $stmt->bind_param("i", $chartYear);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $m = (int)$row['m'];
        if ($m >= 1 && $m <= 12) {
            $chartData[$m - 1] = (int)$row['total'];
        }
    }
}

/* =========================
   OUTPUT
========================= */
echo json_encode([
    'clients'  => $clients,
    'payments' => $payments,
    'due'      => $due,
    'expenses' => $expenses,
    'chart'    => $chartData,
]);