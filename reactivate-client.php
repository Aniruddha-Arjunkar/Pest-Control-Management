<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['email'])) {
    header('location:login.php');
    exit();
}

if (isset($_GET['Id'])) {
    $clientId = intval($_GET['Id']);

    $sql = "UPDATE tbl_payment SET status = 'active' WHERE client_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $clientId);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Client reactivated successfully.";
    } else {
        $_SESSION['msg'] = "Error: " . $conn->error;
    }

    $stmt->close();
} else {
    $_SESSION['msg'] = "Invalid client ID.";
}

$conn->close();
header('Location: view-defaulters.php');
exit();
?>
