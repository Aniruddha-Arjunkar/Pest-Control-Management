
<?php
session_start();

require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

if (isset($_POST['update_pending_work'])) {

    $client_id = (int)($_POST['client_id'] ?? 0);
    $work_description = $_POST['work_description'] ?? '';
    $assigned_to = trim($_POST['assigned_to'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? '';

    $fixed_works = [
        "Structure Installation",
        "Panel Fitting",
        "Wiring & Inverter Installation",
        "Released Order",
        "Generation Meter Fitting and Net Metering"
    ];

    if (
        $client_id <= 0 ||
        !in_array($work_description, $fixed_works, true) ||
        !in_array($status, ['Pending', 'Completed'], true)
    ) {
        die("Invalid request.");
    }

    // Check existing Solar work
    $stmt = mysqli_prepare($conn, "
        SELECT id
        FROM tbl_solar_pending_work
        WHERE client_id = ? AND work_description = ?
        LIMIT 1
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $client_id,
        $work_description
    );

    mysqli_stmt_execute($stmt);

    $existing = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if ($status === 'Completed') {

        // If already exists, UPDATE
        if ($existing) {

            $stmt = mysqli_prepare($conn, "
                UPDATE tbl_solar_pending_work SET
                    assigned_to = ?,
                    due_date = ?,
                    status = 'Completed'
                WHERE id = ?
            ");

            $work_id = (int)$existing['id'];
            $date_value = $due_date !== '' ? $due_date : null;

            mysqli_stmt_bind_param(
                $stmt,
                "ssi",
                $assigned_to,
                $date_value,
                $work_id
            );

            $success = mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);

        } else {

            // If not exists, INSERT
            $stmt = mysqli_prepare($conn, "
                INSERT INTO tbl_solar_pending_work
                (client_id, work_description, assigned_to, due_date, status)
                VALUES (?, ?, ?, ?, 'Completed')
            ");

            $date_value = $due_date !== '' ? $due_date : null;

            mysqli_stmt_bind_param(
                $stmt,
                "isss",
                $client_id,
                $work_description,
                $assigned_to,
                $date_value
            );

            $success = mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);
        }

    } else {

        // Pending means no database record should remain
        if ($existing) {

            $work_id = (int)$existing['id'];

            $stmt = mysqli_prepare($conn, "
                DELETE FROM tbl_solar_pending_work
                WHERE id = ?
            ");

            mysqli_stmt_bind_param($stmt, "i", $work_id);

            $success = mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);

        } else {
            // Nothing to insert for Pending work
            $success = true;
        }
    }

    if ($success) {
        $_SESSION['status'] = "Solar Work saved successfully!";
    } else {
        $_SESSION['status'] = "Failed to save Solar Work!";
    }

    header("Location: solar-view-client-detail.php?client_id=" . $client_id);
    exit();
}

$conn->close();
?>
