<?php
session_start();
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

// Check if the ID is set in the query parameters
if (isset($_GET['id'])) {
    $Id = intval($_GET['id']); // Convert to integer for security
    $sql = "SELECT * FROM tbl_registration WHERE id = $Id";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    // Fetch the data
    $row = mysqli_fetch_assoc($result);
    if (!$row) {
        echo "No results found.";
        exit();
    }
} else {
    echo "ID not provided.";
    exit();
}

// Handle form submission (though we don’t need to handle inputs now)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Data fetched from tbl_registration to insert into tbl_agreement
    $name = mysqli_real_escape_string($conn, $row['name']);
    $phone = mysqli_real_escape_string($conn, $row['phone']);
    $email = mysqli_real_escape_string($conn, $row['email']);
    $address = mysqli_real_escape_string($conn, $row['address']);
    $experience = mysqli_real_escape_string($conn, $row['experience']);
    $skills = mysqli_real_escape_string($conn, $row['skills']);
    $agreement_date = mysqli_real_escape_string($conn, $_POST['agreement_date']);
    $experience_description = mysqli_real_escape_string($conn, $row['experience_description']);
    $dob = mysqli_real_escape_string($conn, $row['dob']);
    $gender = mysqli_real_escape_string($conn, $row['gender']);
    
    // Educational qualifications
    $ten_th = mysqli_real_escape_string($conn, $row['ten_th']);
      $ten_th_percentage = (float)($row['ten_th_percentage'] ?? 0);
    $ten_th_year_of_passing = (int)($row['ten_th_year_of_passing'] ?? 0);
    
    $twelve_th  = mysqli_real_escape_string($conn, $row['twelve_th'] ?? '');
   $twelve_th_percentage = (float)($row['twelve_th_percentage'] ?? 0);
    $twelve_th_year_of_passing = (int)($row['twelve_th_year_of_passing'] ?? 0);
  
    $graduation  = mysqli_real_escape_string($conn, $row['graduation']);
    $graduation_percentage = (float)($row['graduation_percentage'] ?? 0);
    $graduation_year_of_passing = (int)($row['graduation_year_of_passing'] ?? 0);
    
    $post_graduation  = mysqli_real_escape_string($conn, $row['post_graduation']);
     $post_graduation_percentage = (float)($row['post_graduation_percentage'] ?? 0);
    $post_graduation_year_of_passing = (int)($row['post_graduation_year_of_passing'] ?? 0);
    
     // Determine the status (agreed or non-agreed)
    $status = !empty($agreement_date) ? 'Agreed' : 'Not Agreed';
    
    
    // Check for duplicate entry in tbl_agreement
    $check_sql = "SELECT * FROM tbl_agreement WHERE name = '$name' AND phone = '$phone' AND email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        echo "<script>alert('This candidate is already added in the agreement.'); window.location.href='view.php?id=$Id';</script>";
        exit();
    }

    // Insert into tbl_agreement
    $insert_sql = "INSERT INTO tbl_agreement (name, phone, email,address, experience, skills, agreement_date, experience_description, dob, gender, 
                                                    ten_th, ten_th_percentage, ten_th_year_of_passing, twelve_th, twelve_th_percentage, twelve_th_year_of_passing, 
                                                    graduation, graduation_percentage, graduation_year_of_passing, post_graduation, post_graduation_percentage, post_graduation_year_of_passing, status) 
                   VALUES ('$name', '$phone', '$email' , '$address', '$experience', '$skills', '$agreement_date', '$experience_description', '$dob', '$gender', 
                           '$ten_th', '$ten_th_percentage', '$ten_th_year_of_passing', '$twelve_th', '$twelve_th_percentage', '$twelve_th_year_of_passing', 
                           '$graduation', '$graduation_percentage', '$graduation_year_of_passing', '$post_graduation', '$post_graduation_percentage', '$post_graduation_year_of_passing', '$status')";

    if (mysqli_query($conn, $insert_sql)) {
        // Remove from tbl_registration after insertion
        $delete_sql = "DELETE FROM tbl_registration WHERE id = $Id";
        if (mysqli_query($conn, $delete_sql)) {
            // Success message
            echo "<script>alert('Agreement submitted and student removed from registration.'); window.location.href='view-agreement-student.php';</script>";
        } else {
            echo "Error deleting record: " . mysqli_error($conn);
        }
    } else {
        echo "Error inserting record into tbl_agreement: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>
<section id="main-content">
<section class="wrapper">
<!-- Main content start -->
<div class="reg-w3">
    <div class="w3layouts-main" style="margin-bottom: 25px; margin-top: 50px; padding-bottom: 30px;">
        <h2>Agreement</h2>
        <form action="" method="post">
            <!-- Display the required data from tbl_registration -->
            <input type="text" class="ggg" name="name" placeholder="NAME" value="<?= htmlspecialchars($row["name"]); ?>" required readonly>
            <input type="text" class="ggg" name="phone" placeholder="Phone" value="<?= htmlspecialchars($row["phone"]); ?>" required readonly>
            <input type="email" class="ggg" name="email" placeholder="E-MAIL" value="<?= htmlspecialchars($row["email"]); ?>" readonly>
            <input type="text" class="ggg" name="experience" placeholder="Experience" value="<?= htmlspecialchars($row["experience"]); ?>" required readonly>
            <input type="text" class="ggg" name="skills" placeholder="Skills" value="<?= htmlspecialchars($row["skills"]); ?>" required readonly>
            <input type="date" class="ggg" name="agreement_date" id="date" placeholder="Date" required>
            <div class="clearfix"></div>
            <input name="submit" type="submit" class="primary-btn" value="Submit">
        </form>
    </div>
</div>
</section>
</section>

<!-- footer -->
<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
</body>
</html>
