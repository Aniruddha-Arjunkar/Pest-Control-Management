<?php
require_once 'db_connect.php';

$Id = $_GET['id']; 
$sql=  "DELETE FROM `tbl_delete` WHERE `id` = '$Id'";

	if (mysqli_query($conn, $sql)) {
        ?> <script>alert("Deleted successfully");
     window.location.href= 'homepage.php';
        </script>
        <?php
    } else {
       echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
	mysqli_close($conn);
?>                  
        