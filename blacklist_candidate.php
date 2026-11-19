<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['email'])) {
    header("location: login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    $id = (int)$_GET['id'];

    $sql = "UPDATE tbl_agreement 
            SET status='Blacklisted' 
            WHERE Id=$id";

    if (mysqli_query($conn, $sql)) {
        header("Location: view-agreement-details.php?id=$id");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }

} else {
    echo "Invalid ID";
}

mysqli_close($conn);
?>
