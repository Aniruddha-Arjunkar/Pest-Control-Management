<?php
include 'header.php';

?>
<head>
<link href="css/form.css" rel="stylesheet">
</head>
 <body style="overflow:scroll">

<section class="container">

<form action="regg.php" method="POST">

<table align="center"  id="table" cellpadding = "10">
 <center><h2>STUDENT REGISTRATION FORM</h2></center>
<!----- Name ---->
<tr>
<td>NAME :</td>
<td><input class="input" type="text" name="name" required="" placeholder= "Enter Your Name... "/ >

</td>
</tr>

<!----- Mobile Number --->
<tr>
<td>MOBILE NUMBER :</td>
<td>
<input type="text" class=" input" name="phone" maxlength="10" required="" />

</td>
</tr>

<!----- Email Id --->
<tr>
<td>EMAIL ID :</td>
<td><input type="email" class=" input" name="email" maxlength="100" required=""/></td>
</tr>



<!----- Date Of Birth --->
<tr>
<td>DATE OF BIRTH :</td>
<td><input type="date" class=" input" name="dob" maxlength="100" required=""/></td>


</tr>
 <br><br>
<!----- Qualification-->
<tr>
<td>QUALIFICATION : <br /></td>
 

<table id="table1" cellpadding = "5" style="margin-left:250px">
 
<tr>
<td align="center" style="padding-right:10px"><b>Sr.No.</b></td>
<td align="center"style="padding-right:15px"><b>Examination</b></td>
<td align="center"><b>Course</b></td>
<td align="center"><b>Percentage</b></td>
<td align="center"><b>Year of Passing</b></td>
</tr>
 
<tr>
<td>1</td>
<td>Higher Education</td>
<td><input type="text" class=" input" name="PostGraduation_Course" maxlength="30" /></td>
<td><input type="text" class=" input" name="PostGraduation_Percentage" maxlength="30" /></td>
<td><input type="text"  class=" input" name="PostGraduation_YrOfPassing" maxlength="30" /></td>
</tr>
 
<!--<tr>
<td>2</td>
<td>Graduation</td>
<td><input type="text" class=" input"  name="Graduation_Course" maxlength="30" /></td>
<td><input type="text" class=" input"  name="Graduation_Percentage" maxlength="30" /></td>
<td><input type="text" class=" input"  name="Graduation_YrOfPassing" maxlength="30" /></td>
</tr>
 
<tr>
<td>3</td>
<td>12th</td>
<td><input type="text" class=" input"  name="XIIth_Course" maxlength="30" /></td>
<td><input type="text" class=" input"  name="XIIth_Percentage" maxlength="30" /></td>
<td><input type="text" class=" input"  name="XIIth_YrOfPassing" maxlength="30" /></td>
</tr>
 
<tr>
<td>4</td>
<td>10th</td>
<td><input type="text" class=" input" name="Xth_Course" maxlength="30" /></td>
<td><input type="text" class=" input" name="Xth_Percentage" maxlength="30" /></td>
<td><input type="text" class=" input" name="Xth_YrOfPassing" maxlength="30" /></td>
</tr>-->
 

</table>
 

</tr>
<br />
 <table  id="table">
<!-- Experience -->
<tr>
<td>EXPERIENCE :<br /></td>
<td>

<input type="text" class=" input" name="experience" value="">

</td>
</tr>

 <!----- Address -->
<tr>
<td>ADDRESS :</td>
<td><input name="address" class=" input" rows="4" cols="30" required=""></td>
</tr>

<tr>
<td>SKILLS :<br /></td>
<td>

<input type="text" class=" input" name="skills" value="">

</td>
</tr>
<!-- Submit and Reset ---->
<tr>
<td colspan="2" align="center">
<div class="button">
<input class="genric-btn primary circle mt-30" type="submit" name="submit" value="Submit" title="Send">
<input type="reset"  class="genric-btn primary circle mt-30" onclick="this.form.reset();" value="Reset">
</div>
</td>
</tr>
</table>
</table> 
</form>
</section>
 <script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot-chart/excanvas.min.js"></script><![endif]-->
<script src="js/jquery.scrollTo.js"></script>
</body>
</html>
<style>
html,body{
background-image: url("images/background_contact_form.jpg");

}

</style>



	