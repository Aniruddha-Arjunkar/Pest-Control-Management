<?php
session_start();
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
}
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location:login.php');
}

?>


<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'db_connect.php';
if (isset($_POST['submit'])) {
    $branch = $_POST['branch'];
    $expenses = $_POST['expenses'];
	$services = $_POST['services'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $payment_gateway = $_POST['payment_gateway'];
    
    $sql = "INSERT INTO tbl_expenses (branch,expenses ,services, amount, date, payment_gateway) VALUES ('$branch','$expenses','$services', '$amount', '$date', '$payment_gateway')";
    if (mysqli_query($conn, $sql)) {
        ?> <script>alert(" Expenses details inserted successfully ...");
         window.location.href= 'add expenses.php';
         </script>
        <?php 
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

