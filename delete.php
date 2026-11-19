<?php
require_once 'db_connect.php';

$Id = $_GET['id']; 

$sql =  "INSERT INTO tbl_delete SELECT * FROM `tbl_inquiry` WHERE `id` = '$Id'";

$sql1 =  "DELETE FROM `tbl_inquiry` WHERE `id` = '$Id'";

	if (mysqli_query($conn, $sql)) {
        ?> <script>alert("Deleted successfully");
        window.location.href='view-inquiry.php';
       </script>
       <?php
    } else {
       echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
    
	if (mysqli_query($conn, $sql1)) {
        ?> <script>alert("Deleted successfully");
     window.location.href= 'view-inquiry.php';
        </script>
        <?php
    } else {
       echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
	mysqli_close($conn);
?>                  
        