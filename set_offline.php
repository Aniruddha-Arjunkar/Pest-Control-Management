<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['email'])) {

    $email = $_SESSION['email'];

    $stmt = mysqli_prepare($conn, "
        UPDATE users
        SET status = 'offline'
        WHERE email = ?
    ");

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
}
?>