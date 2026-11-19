<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'db_connect.php';
if (isset($_POST['submit'])) {
    $services = $_POST['services'];
    $cust_name = $_POST['cust_name'];
    $cust_phone = $_POST['cust_phone'];
	$cust_address = $_POST['cust_address'];
    $cust_email = $_POST['cust_email'];
    $cust_message = $_POST['cust_message'];
    $inq_date = $_POST ['inq_date'];
    $follow_up_date=$_POST['follow_up_date'];
    $visit_date=$_POST['visit_date'];
     $service_date=$_POST['service_date'];
     
     
     $sql = "INSERT INTO tbl_inquiry(services,cust_name, cust_phone,cust_address,cust_email,cust_message,inq_date) VALUES ('".$services."','".$cust_name."', '".$cust_phone."','".$cust_address."','".$cust_email."','".$cust_message."',NOW())";
     
     if (mysqli_query($conn, $sql)) {
         ?> <script>alert("Thank You For Contacting us...");
         window.location.href= 'index.html';
         </script>
        <?php 
          
        //header('Refresh: 0; url=shulventures.com/index.html');
    } else {
       echo "Error: " . mysqli_connect_error($conn);
    }
    
$to = "pestcontrol@shulventures.com, atulurkude765@gmail.com, prash.chunarkar@gmail.com"; 
$from = $_POST['cust_email']; 
$subject = "Shul - Online Inquiry";   
$cust_message = $_POST['cust_message'] ; 
$headers = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers = 'Name: '. $cust_name .   "\r\nServices: ". $services .  "\r\nPhone no.: " . $cust_phone.  "\r\nFrom: " . $cust_email . "\r\n";
$sent = mail($to,$subject,$headers,$cust_message) ; 
if($sent) 
{
    print "Your mail was sent successfully";
    }
else 
{
    print "We encountered an error sending your mail";
    }
}
 mysqli_close($conn);
?>


        