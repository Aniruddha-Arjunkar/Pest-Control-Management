<?php
session_start();
  if (!isset($_SESSION['email'])  || !isset($_SESSION['role'])) {
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
require_once 'db_connect.php';
	
include'header.php';

?>

<!--main content start-->
<div class="reg-w3">
<div class="w3layouts-main" style="
    margin-bottom: 25px;
    margin-top: 50px;
    padding-bottom: 30px;">
    
	<h2>Follow Up</h2>
			
	<form action="" method="post">
		                                       <?php
										$Id=$_GET["id"];
								      $sql = "select * from tbl_student_inquiries where id=$Id";
                                   $result = mysqli_query($conn, $sql);
                                 if (mysqli_num_rows($result) > 0) {
                                    // output data of each row
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        ?>
										<tr>
										
              
			 
      	    <input type="text" class="ggg" name="name" placeholder="NAME" required value="<?= $row["name"]; ?>"> 
      	    
			<input type="text" class="ggg" name="phone" placeholder="Phone" required value="<?= $row["phone"]; ?> ">
			
			<input type="text" class="ggg" name="email" placeholder="E-MAIL" required value="<?= $row["email"]; ?> ">
			
			<input type="text" class="ggg" name="address" placeholder="Address" value="<?= $row["address"]; ?>" >
			<input type="text" class="ggg" name="education" placeholder="Education" value="<?= $row["education"]; ?>" >
			
			
			 <input textarea name="message" class="ggg" cols="30" rows="1" placeholder="Message"  value="<?= $row["message"]; ?>"required></textarea>
			 
			<input type="date" class="ggg" name="follow_up"  placeholder="Date" required >
			
				<div class="clearfix"></div>
			 <?php
                                    }
                                } else {
                                    echo "0 results";
                                }
										 
                                ?>
				</tr>
				
                                        
			<input name="submit" type="submit"  class="primary-btn" value="Submit">
			<?php
			if (isset($_POST['submit'])) {
	    	
			$follow_up = $_POST ['follow_up'];
			$name = $_POST['name'];
			$phone = $_POST['phone'];
			$email = $_POST['email'];
			$address = $_POST['address'];
			$message = $_POST['message'];
		
			
     $sql= "UPDATE `tbl_student_inquiries` SET 
       `name` = '$name', 
       `phone` = '$phone', 
       `email` = '$email', 
       `address` = '$address', 
       `message` = '$message' ,
       `follow_up` ='$follow_up'
       WHERE follow_up IS NULL AND `tbl_student_inquiries`.`id` = $Id";
       
      if (mysqli_query($conn, $sql)) {
         ?> <script>alert("Updated successfully...");
         window.location.href='view-followup.php';
         </script>
        <?php 
          
        //header('Refresh: 0; url=shulventures.com/index.html');
    } else {
       echo "Error: " . mysqli_connect_error($conn);
    }
    
     $sql1= "UPDATE `tbl_student_inquiries` SET 
      `name` = '$name', 
       `phone` = '$phone', 
       
       `address` = '$address', 
       `message` = '$message' ,
       `follow_up` ='$follow_up'
       WHERE follow_up IS NOT NULL AND `tbl_student_inquiries`.`id` = $Id";
     
    if (mysqli_query($conn, $sql1)) {
         ?> <script>alert("Updated successfully...");
         window.location.href='view-followup.php';
         </script>
        <?php 
          
        //header('Refresh: 0; url=shulventures.com/index.html');
    } else {
       echo "Error: " . mysqli_connect_error($conn);
    }
}
mysqli_close($conn);
?>

			
		</form>
		
		<!--<p>Already Registered.<a href="login.html">Login</a></p>-->
</div>
</div>

 <!-- footer  
		  <div class="footer">
			<div class="wthree-copyright">
			  <p>© 2017 Visitors. All rights reserved | Design by <a href="http://w3layouts.com">W3layouts</a></p>
			</div>
		  </div>
  <!-- / footer -->


<!--main content end-->

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot-chart/excanvas.min.js"></script><![endif]-->
<script src="js/jquery.scrollTo.js"></script>
</body>
</html>
 
		