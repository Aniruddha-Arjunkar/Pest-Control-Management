<?php
session_start();
require_once 'db_connect.php';

/* =========================
   CHECK POST DATA
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: solar-view-inquiry.php");
    exit;
}

$id      = (int)($_POST['id'] ?? 0);
$type    = $_POST['type'] ?? '';

$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$email   = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$msg     = trim($_POST['message'] ?? '');
$date    = trim($_POST['date'] ?? '');

/* =========================
   VALIDATION
========================= */

$allowed_types = [
    'refollowup',
    'visit',
    'service',
    'schedule'
];

if (
    $id <= 0 ||
    !in_array($type, $allowed_types, true) ||
    $name === '' ||
    $phone === '' ||
    $date === '' ||
    $msg === ''
) {
    $_SESSION['status'] = "Please fill all required fields!";
    header("Location: solar-view-inquiry.php");
    exit;
}

/* =========================
   DATE VALIDATION
========================= */

if (
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
    !checkdate(
        (int)substr($date, 5, 2),
        (int)substr($date, 8, 2),
        (int)substr($date, 0, 4)
    )
) {
    $_SESSION['status'] = "Please enter a valid date!";
    header("Location: solar-update-inquiry.php?id=$id&type=" . urlencode($type));
    exit;
}

/* =========================
   CHECK INQUIRY
========================= */

$check = $conn->prepare("
    SELECT id FROM tbl_solar_inquiry WHERE id = ?
");

$check->bind_param("i", $id);
$check->execute();

$exists = $check->get_result()->num_rows > 0;
$check->close();

if (!$exists) {
    $_SESSION['status'] = "Solar inquiry not found!";
    header("Location: solar-view-inquiry.php");
    exit;
}

/* =========================
   START TRANSACTION
========================= */

$conn->begin_transaction();

try {

    /* =========================
       UPDATE CUSTOMER DETAILS
    ========================= */

    $basicUpdate = $conn->prepare("
        UPDATE tbl_solar_inquiry
        SET
            name = ?,
            phone = ?,
            email = ?,
            address = ?
        WHERE id = ?
    ");

    $basicUpdate->bind_param(
        "ssssi",
        $name,
        $phone,
        $email,
        $address,
        $id
    );

    if (!$basicUpdate->execute()) {
        throw new Exception($basicUpdate->error);
    }

    $basicUpdate->close();

    /* =========================
       TYPE BASED UPDATE
    ========================= */

    $column = '';
    $clearColumn = '';

    if ($type == "refollowup") {
        $column = "re_followup_date";
        $clearColumn = "inq_date";
    }

    elseif ($type == "visit") {
        $column = "visit_date";
        $clearColumn = "re_followup_date";
    }

    elseif ($type == "service") {
        $column = "service_date";
        $clearColumn = "visit_date";
    }

    elseif ($type == "schedule") {
        $column = "schedule_date";
        $clearColumn = "service_date";
    }

    /* Column names are selected only from the allowed list above */

    $update = $conn->prepare("
        UPDATE tbl_solar_inquiry
        SET
            message = ?,
            `$column` = ?,
            `$clearColumn` = NULL
        WHERE id = ?
    ");

    $update->bind_param("ssi", $msg, $date, $id);

    if (!$update->execute()) {
        throw new Exception($update->error);
    }

    $update->close();

    /* =========================
       COMMIT
    ========================= */

    $conn->commit();

    $_SESSION['status'] = ucfirst($type) . " Updated Successfully!";

} catch (Exception $e) {

    $conn->rollback();

    $_SESSION['status'] = "Update Failed: " . $e->getMessage();
}

/* =========================
   REDIRECT
========================= */

$redirectPage = "solar-view-inquiry.php";

if ($type == "refollowup") {
    $redirectPage = "solar-view-refollowup.php";
}
elseif ($type == "visit") {
    $redirectPage = "solar-view-visit.php";
}
elseif ($type == "service") {
    $redirectPage = "solar-view-future.php";
}
elseif ($type == "schedule") {
    $redirectPage = "solar-view-schedule.php";
}

header("Location: " . $redirectPage);
exit;
?>
