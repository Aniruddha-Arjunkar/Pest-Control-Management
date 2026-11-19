<?php

$servername = " localhost";
$username = "shulvent_student";
$password = "#U1;2]PcN2z2";
$dbname = "shulvent_student_data";

// Create connection
$conn = mysqli_connect("localhost", "shulvent_student", "#U1;2]PcN2z2", "shulvent_student_data");
// Check connection
if (!$conn) {
   die("Connection failed: " . mysqli_connect_error());
}else{
     //echo "Connected successfully";
}

// mysqli_close($conn);   
?>
