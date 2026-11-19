<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "test2";

// Create connection
$conn = mysqli_connect("localhost", "root", "", "test2");
// Check connection
if (!$conn) {
   die("Connection failed: " . mysqli_connect_error());
}else{
     //echo "Connected successfully";
}

// mysqli_close($conn);   
?>