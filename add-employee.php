<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Database connection
require_once 'db_connect.php';

// Check if form is submitted
if (isset($_POST['submit'])) {
    // Get form data and sanitize input
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $cust_name = mysqli_real_escape_string($conn, $_POST['cust_name']);
    $mother_name = mysqli_real_escape_string($conn, $_POST['mother_name']);
    $cust_phone = mysqli_real_escape_string($conn, $_POST['cust_phone']);
    $cust_alternate = mysqli_real_escape_string($conn, $_POST['cust_alternate']);
    $alternate_phone_relation = mysqli_real_escape_string($conn, $_POST['alternate_phone_relation']);
    $cust_email = mysqli_real_escape_string($conn, $_POST['cust_email']);
    $cust_address1 = mysqli_real_escape_string($conn, $_POST['cust_address1']);
    $cust_address2 = mysqli_real_escape_string($conn, $_POST['cust_address2']);
    $package = mysqli_real_escape_string($conn, $_POST['package']);
    $qualification = mysqli_real_escape_string($conn, $_POST['qualification'] ?? '');
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation'] ?? '');
    $education_10th = mysqli_real_escape_string($conn, $_POST['education_10th']);
    $education_12th = mysqli_real_escape_string($conn, $_POST['education_12th']);
    $education_graduation = mysqli_real_escape_string($conn, $_POST['education_graduation']);
    $education_postgrad = mysqli_real_escape_string($conn, $_POST['education_postgrad']);
    $experience_fresher = mysqli_real_escape_string($conn, $_POST['experience_fresher']);
    $previous_experience = mysqli_real_escape_string($conn, $_POST['previous_experience']);
    $work_experience_years = mysqli_real_escape_string($conn, $_POST['work_experience_years']);
    $adhar_no = mysqli_real_escape_string($conn, $_POST['adhar_no']);
    $pan_card_no = mysqli_real_escape_string($conn, $_POST['pan_card_no']);
    $account_no = mysqli_real_escape_string($conn, $_POST['account_no']);
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $branch_name = mysqli_real_escape_string($conn, $_POST['branch_name']);
    $ifsc_code = mysqli_real_escape_string($conn, $_POST['ifsc_code']);
    
    // Marital Status and Spouse Details
    $marital_status = mysqli_real_escape_string($conn, $_POST['marital_status']);
    $spouse_name = ($marital_status == "Married") ? mysqli_real_escape_string($conn, $_POST['spouse_name']) : null;
    $spouse_occupation = ($marital_status == "Married") ? mysqli_real_escape_string($conn, $_POST['spouse_occupation']) : null;
    
    // Previous Company and Designation
    $previous_company_name = mysqli_real_escape_string($conn, $_POST['previous_company_name']);
    $previous_designation = mysqli_real_escape_string($conn, $_POST['previous_designation']);
    $previous_job_salary = mysqli_real_escape_string($conn, $_POST['previous_job_salary']);
    
    // Employee Type and Department
    $employee_type = mysqli_real_escape_string($conn, $_POST['employee_type']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    
    // PF and ESIC Details
    $pf_uan = mysqli_real_escape_string($conn, $_POST['pf_uan'] ?? '');
    $pf_number = mysqli_real_escape_string($conn, $_POST['pf_number'] ?? '');
    $esic_number = mysqli_real_escape_string($conn, $_POST['esic_number'] ?? '');
    $branch = mysqli_real_escape_string($conn, $_POST['branch']);
    
    // Handling file uploads for documents
    $upload_dir = 'uploads/';

    // Create the uploads directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Initialize photo variable with NULL (or empty string) in case no photo is uploaded
    $photo = null;

    // Check if file is uploaded
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $photo = $_FILES['photo']['name'];
        $photo_tmp = $_FILES['photo']['tmp_name'];
        $photo_path = $upload_dir . basename($photo);

        // Ensure file is an image (optional)
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($photo, PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions)) {
            // Move uploaded photo to the desired location
            if (move_uploaded_file($photo_tmp, $photo_path)) {
                // File successfully uploaded, no further action needed here
            } else {
                echo "Error: Unable to upload the photo.";
            }
        } else {
            echo "Error: Only image files (JPG, JPEG, PNG, GIF) are allowed.";
        }
    }

    // Insert data into the database, photo can be null if not uploaded
    $sql = "INSERT INTO tbl_employee (title, cust_name, mother_name, cust_phone, cust_alternate, alternate_phone_relation, cust_email, cust_address1, cust_address2, package, qualification, dob, gender, date, designation, education_10th, education_12th, education_graduation, education_postgrad, experience_fresher, previous_experience, work_experience_years, adhar_no, pan_card_no, account_no, bank_name, branch_name, ifsc_code, marital_status, spouse_name, spouse_occupation, previous_company_name, previous_designation, previous_job_salary, employee_type, department, pf_uan, pf_number, esic_number, photo, branch) 
            VALUES ('$title', '$cust_name', '$mother_name', '$cust_phone', '$cust_alternate', '$alternate_phone_relation', '$cust_email', '$cust_address1', '$cust_address2', '$package', '$qualification', '$dob', '$gender', '$date', '$designation', '$education_10th', '$education_12th', '$education_graduation', '$education_postgrad', '$experience_fresher', '$previous_experience', '$work_experience_years', '$adhar_no', '$pan_card_no', '$account_no', '$bank_name', '$branch_name', '$ifsc_code', '$marital_status', '$spouse_name', '$spouse_occupation', '$previous_company_name', '$previous_designation', '$previous_job_salary', '$employee_type', '$department', '$pf_uan', '$pf_number', '$esic_number', '$photo', '$branch')";

    if (mysqli_query($conn, $sql)) {
        // Get the last inserted ID (auto-incremented)
        $last_id = mysqli_insert_id($conn);
        
        // Generate the formatted emp_id with "SHUL-" prefix and two-digit ID
        $emp_id = 'SHUL-' . str_pad($last_id, 2, '0', STR_PAD_LEFT);

        // Update the employee's emp_id in the database
        $update_sql = "UPDATE tbl_employee SET emp_id = '$emp_id' WHERE id = '$last_id'";

        if (mysqli_query($conn, $update_sql)) {
            echo "<script>alert('New Employee added successfully with ID: $emp_id'); window.location.href='view-employee.php';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
} else {
    echo "Error: Invalid form submission.";
}

mysqli_close($conn); // Close database connection

?>
