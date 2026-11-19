<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'connect.php';
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $mobile = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
	$addressp = $_POST['addressp'];
	$package = $_POST['package'];
	$qualification = $_POST['qualification'];
	$date = $_POST['date'];
	$designation = $_POST['designation'];
    $sql = "INSERT INTO add_emp (name, mobile, email, address, addressp, package, qualification, date, designation) VALUES ('$name', '$mobile', '$email', '$address', '$addressp', '$package', '$qualification', '$date', '$designation')";
    if (mysqli_query($conn, $sql)) {
        ?> <script>alert("New record created successfully");</script>
        <?php

        header('Refresh: 0; url=index.html');
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

