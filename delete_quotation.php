<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Fetch file path to delete PDF from folder
    $result = $conn->query("SELECT file_path FROM tbl_generated_quotations WHERE id = $id");
    $row = $result->fetch_assoc();

    if ($row) {
        // Delete PDF file
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }

        // Delete database record
        $conn->query("DELETE FROM tbl_generated_quotations WHERE id = $id");
    }
}

header("Location: view_quotations.php");
exit();
?>
