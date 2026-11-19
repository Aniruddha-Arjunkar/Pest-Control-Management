<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['email'])) {
    header("location: login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    $id = (int)$_GET['id'];

    // Optional: Related records delete first (important if foreign keys exist)
    $conn->query("DELETE FROM tbl_payment WHERE client_Id=$id");
    $conn->query("DELETE FROM tbl_placement WHERE candidate_id=$id");

    // Main candidate delete (prepared statement)
    $stmt = $conn->prepare("DELETE FROM tbl_agreement WHERE Id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: view-all-candidates.php?msg=deleted");
        exit();
    } else {
        echo "Error deleting record.";
    }

    $stmt->close();
} else {
    echo "Invalid ID.";
}

$conn->close();
?>
