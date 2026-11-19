<!DOCTYPE html>
<html>
	<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		
		<title>Registration Form</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<!-- MATERIAL DESIGN ICONIC FONT -->
		<!--<link rel="stylesheet" href="fonts/material-design-iconic-font/css/material-design-iconic-font.min.css">-->
		
		<!-- STYLE CSS -->
		<link rel="stylesheet" href="css/style1.css">
	</head>

	<body>
<!-- Display submission status -->
     <?php if(!empty($statusMsg)){ ?>
    <p class="statusMsg <?php echo !empty($msgClass)?$msgClass:''; ?>"><?php echo $statusMsg; ?></p>
        <?php } ?>
		<div class="wrapper">
			<div class="inner">
				<form action="submit.php" method="POST" enctype="multipart/form-data">
					<center><h1>Job Application Form With Resume Upload</h1></center>
					<br>
					<br>
					<label class="form-group">
						<input type="text" name="name" value="<?php echo !empty($postData['name'])?$postData['name']:''; ?>" class="form"  required>
						<span>Name</span>
						<span class="border"></span>
					</label>
					
					<label class="form-group">
						<input type="text" name="email" value="<?php echo !empty($postData['email'])?$postData['email']:''; ?>" class="form"  required>
						<span for=""> E-Mail</span>
						<span class="border"></span>
					</label>
					<label class="form-group">
						<input type="text" name="phone" value="<?php echo !empty($postData['phone'])?$postData['phone']:''; ?>"class="form"  required>
						<span for="">Phone</span>
						<span class="border"></span>
					</label>
					<label class="form-group">
						<input type="text" name="position" class="form" value="<?php echo !empty($postData['position'])?$postData['position']:''; ?>" required>
						<span for="">Position you are applying for</span>
						<span class="border"></span>
					</label>
					<label class="form-group">
						<span>Resume upload</span><br><br>
						<input type="file" name="attachment" class="form" required>
						<span class="border"></span>
					</label>
					<label class="form-group">
						<input type="text" name="website" value="<?php echo !empty($postData['website'])?$postData['website']:''; ?>" class="form"  required>
						<span for="">Portfolio website</span>
						<span class="border"></span>
					</label>
					<label class="form-group">
						<input type="text" name="salary"  class="form" value="<?php echo !empty($postData['salary'])?$postData['salary']:''; ?>" required>
						<span for="">Salary Requirement</span>
						<span class="border"></span>
					</label>
					<label class="form-group">
						<span>When can you start?</span><br><br>
						<input type="date" name="date" value="<?php echo !empty($postData['date'])?$postData['date']:''; ?>" class="form"  required>
						<span class="border"></span>
					</label>
					<label class="form-group" >
						<textarea name="comment" id="" value="<?php echo !empty($postData['comment'])?$postData['comment']:''; ?>" class="form" required></textarea>
						<span for="">Comment</span>
						<span class="border"></span>
					</label>
					<button type="submit"  name="submit">Submit</button>
						<i class="zmdi zmdi-arrow-right"></i>
					</button>
				</form>
			</div>
		</div>
		
	</body>
</html>