<?php
session_start();
require_once 'db_connect.php';

// Get logged-in user email
$email = $_SESSION['email'] ?? '';

// ✅ Set user OFFLINE before logout
if($email){
    mysqli_query($conn, "UPDATE users SET status='offline' WHERE email='$email'");
}

unset($_SESSION['email']);
session_destroy();

header("Location: login.php");
exit;
?>

