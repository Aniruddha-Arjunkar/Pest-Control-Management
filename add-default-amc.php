<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';

$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
if ($client_id <= 0) {
    die("Invalid Client ID");
}

/* ===============================
   1️⃣ Check if form is submitted
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ Custom date & reason
    $default_date   = isset($_POST['default_date']) ? $_POST['default_date'] : date('Y-m-d');
    $default_reason = isset($_POST['default_reason']) ? trim($_POST['default_reason']) : 'DEFAULT';
    $status         = 'DEFAULT';  // ✅ status set as DEFAULT
    $operator       = '-';
    $monitor        = '-';

    /* ===============================
       2️⃣ Check if DEFAULT already exists
    ================================= */
    $sql_check = "SELECT * FROM tbl_amc WHERE client_Id = ? AND status = 'DEFAULT' LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $client_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['msg'] = "DEFAULT AMC already exists for this client.";
        header("Location: view-service-dates.php?client_id=$client_id");
        exit();
    }

    /* ===============================
       3️⃣ Insert DEFAULT AMC
    ================================= */
    $sql_insert = "
        INSERT INTO tbl_amc
        (client_Id, service_date, status, reason, operator_name, monitored_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param(
        "isssss",
        $client_id,
        $default_date,
        $status,          // ✅ DEFAULT status
        $default_reason,
        $operator,
        $monitor
    );

    if ($stmt_insert->execute()) {
        $_SESSION['msg'] = "DEFAULT AMC added successfully.";
        header("Location: view-service-dates.php?client_id=$client_id");
        exit();
    } else {
        die("Error: " . $stmt_insert->error);
    }
}

/* ===============================
   4️⃣ Redirect if accessed directly
================================ */
header("Location: view-service-dates.php?client_id=$client_id");
exit();
?>
