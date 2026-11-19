<?php
require_once ('connect.php');

if(isset($_POST['submit'])){
	
	//variable declaration
	
	$name=$_POST['name'];
	$phone=$_POST['phone'];
	$email=$_POST['email'];
	$address=$_POST['address'];
	$dob=$_POST['dob'];
	$PostGraduation_Percentage=$_POST['PostGraduation_Percentage'];
	$PostGraduation_YrOfPassing=$_POST['PostGraduation_YrOfPassing'];
	//$Graduation_Percentage=$_POST['Graduation_Percentage'];		
	//$Graduation_YrOfPassing=$_POST['Graduation_YrOfPassing'];
	//$XIIth_Percentage=$_POST['XIIth_Percentage'];
	//$XIIth_YrOfPassing=$_POST['XIIth_YrOfPassing'];
	//$Xth_Percentage=$_POST['Xth_Percentage'];
	//$Xth_YrOfPassing=$_POST['Xth_YrOfPassing'];
	$PostGraduation_Course=$_POST['PostGraduation_Course'];
	//$Graduation_Course=$_POST['Graduation_Course'];
	//$XIIth_Course=$_POST['XIIth_Course'];
	//$Xth_Course=$_POST['Xth_Course'];
	$experience=$_POST['experience'];
	$skills=$_POST['skills'];
	
	//Insert Query
	
	$sql=" INSERT INTO tbl_placement(name,phone,email,dob,PostGraduation_Percentage,PostGraduation_YrOfPassing,PostGraduation_Course,experience,address,skills)
	
	VALUES('$name','$phone','$email','$dob','$PostGraduation_Percentage','$PostGraduation_YrOfPassing', '$PostGraduation_Course', '$experience','$address','$skills')";
	
	//Condition
	
	if(mysqli_query( $conn, $sql)){
		?><script>alert("inserted successfully");
	   window.location.href='reg.php';
	 </script>
	 <?php
	} else
	 {
		 echo "error" . $sql . "<br>" . mysqli_error($conn);
	 }
}