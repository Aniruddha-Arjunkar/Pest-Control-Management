<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['email'])) {

    $email = $_SESSION['email'];

    mysqli_query($conn, "
        UPDATE users 
        SET last_active = NOW()
        WHERE email = '$email'
    ");
}
?>