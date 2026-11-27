<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// =========================================================
// LOGIN CHECK
// =========================================================

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {

    $_SESSION['msg'] = "You must log in first";

    header("Location: login.php");
    exit();
}

// =========================================================
// DATABASE
// =========================================================

require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

// =========================================================
// DELETE INQUIRY
// =========================================================

if (isset($_GET['id'])) {

    $id = (int) $_GET['id'];

    if ($id <= 0) {
        $_SESSION['message'] = "Invalid Inquiry ID";
        header("Location: solar-view-inquiry.php");
        exit();
    }

    try {

        mysqli_begin_transaction($conn);

        // =============================================
        // FETCH SOLAR INQUIRY
        // =============================================

        $select_query = "
            SELECT *
            FROM tbl_solar_inquiry
            WHERE id = ?
            LIMIT 1
        ";

        $select_stmt = mysqli_prepare($conn, $select_query);

        mysqli_stmt_bind_param($select_stmt, "i", $id);

        mysqli_stmt_execute($select_stmt);

        $select_result = mysqli_stmt_get_result($select_stmt);

        if (!$select_result || mysqli_num_rows($select_result) === 0) {

            throw new Exception("Inquiry Not Found");

        }

        $row = mysqli_fetch_assoc($select_result);

        // =============================================
        // NULL SAFE DATES
        // =============================================

        $follow_up_date = !empty($row['re_followup_date'])
            ? $row['re_followup_date']
            : null;

        $visit_date = !empty($row['visit_date'])
            ? $row['visit_date']
            : null;

        $service_date = !empty($row['service_date'])
            ? $row['service_date']
            : null;

        $schedule_date = !empty($row['schedule_date'])
            ? $row['schedule_date']
            : null;

        // =============================================
        // INSERT INTO SOLAR DELETE TABLE
        // =============================================

        $insert_query = "
            INSERT INTO tbl_solar_delete
            (
                cust_name,
                cust_phone,
                cust_email,
                cust_address,
                cust_message,
                inq_date,
                follow_up_date,
                visit_date,
                service_date,
                schedule_date
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $insert_stmt = mysqli_prepare($conn, $insert_query);

        mysqli_stmt_bind_param(
            $insert_stmt,
            "ssssssssss",
            $row['name'],
            $row['phone'],
            $row['email'],
            $row['address'],
            $row['message'],
            $row['inq_date'],
            $follow_up_date,
            $visit_date,
            $service_date,
            $schedule_date
        );

        if (!mysqli_stmt_execute($insert_stmt)) {

            throw new Exception("Failed To Store Deleted Data");

        }

        // =============================================
        // DELETE FROM SOLAR INQUIRY
        // =============================================

        $delete_query = "
            DELETE FROM tbl_solar_inquiry
            WHERE id = ?
        ";

        $delete_stmt = mysqli_prepare($conn, $delete_query);

        mysqli_stmt_bind_param($delete_stmt, "i", $id);

        if (!mysqli_stmt_execute($delete_stmt)) {

            throw new Exception("Delete Failed");

        }

        // =============================================
        // COMMIT
        // =============================================

        mysqli_commit($conn);

        $_SESSION['message'] = "Solar Inquiry Deleted & Stored Successfully";

    } catch (Exception $e) {

        mysqli_rollback($conn);

        error_log("Solar Inquiry Delete Error: " . $e->getMessage());

        $_SESSION['message'] = $e->getMessage();

    }

    header("Location: solar-view-inquiry.php");
    exit();

}

// =========================================================
// INVALID REQUEST
// =========================================================

$_SESSION['message'] = "Invalid Request";

header("Location: solar-view-inquiry.php");
exit();

?>
