<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['email'])) {
    header('location:login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = intval($_POST['client_Id']);
    $note = trim($_POST['defaulter_note']);

    $sql = "UPDATE tbl_payment 
            SET status = 'defaulted', defaulter_note = ?
            WHERE client_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $note, $clientId);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "Client marked as defaulter with reason.";
    } else {
        $_SESSION['msg'] = "Error: " . $conn->error;
    }

    header('Location: due-payments.php');
    exit();
}
?>
