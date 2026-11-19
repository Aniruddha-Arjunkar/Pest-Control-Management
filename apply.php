<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Helper function to safely escape strings
    function escape_post($conn, $key) {
        return isset($_POST[$key]) ? mysqli_real_escape_string($conn, $_POST[$key]) : '';
    }

    // Helper function to safely convert numeric fields
    function numeric_post($key) {
        return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : NULL;
    }

    // Collect and sanitize form data
    $job_profile = escape_post($conn, 'job_profile');
    $name = escape_post($conn, 'name');
    $email = escape_post($conn, 'email');
    $phone = escape_post($conn, 'phone');
    $address = escape_post($conn, 'address');
    $experience = escape_post($conn, 'experience');
    $experience_description = escape_post($conn, 'experience_description');
    $dob = escape_post($conn, 'dob');
    $gender = escape_post($conn, 'gender');
    $skills = escape_post($conn, 'skills');

    // Educational qualifications
    $ten_th = escape_post($conn, 'ten_th');
    $ten_th_percentage = numeric_post('ten_th_percentage');
    $ten_th_year_of_passing = numeric_post('ten_th_year_of_passing');

    $twelve_th = escape_post($conn, 'twelve_th');
    $twelve_th_percentage = numeric_post('twelve_th_percentage');
    $twelve_th_year_of_passing = numeric_post('twelve_th_year_of_passing');

    $graduation = escape_post($conn, 'graduation');
    $graduation_percentage = numeric_post('graduation_percentage');
    $graduation_year_of_passing = numeric_post('graduation_year_of_passing');

    $post_graduation = escape_post($conn, 'post_graduation');
    $post_graduation_percentage = numeric_post('post_graduation_percentage');
    $post_graduation_year_of_passing = numeric_post('post_graduation_year_of_passing');
    
    // =========================
    // 📁 FILE UPLOAD (RESUME)
    // =========================
    $resume_name = '';

    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {

        $upload_dir = "uploads/resumes/";

        // Create folder if not exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp = $_FILES['resume']['tmp_name'];
        $file_name = time() . "_" . basename($_FILES['resume']['name']);
        $target_file = $upload_dir . $file_name;

        // Allowed file types
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if ($file_type == "pdf" || $file_type == "doc" || $file_type == "docx") {

            if (move_uploaded_file($file_tmp, $target_file)) {
                $resume_name = $file_name;
            } else {
                die("Error uploading file.");
            }

        } else {
            die("Only PDF, DOC, DOCX files allowed.");
        }
    }

    
    
    

    // Use NULL for empty numeric fields
    $sql = "INSERT INTO tbl_registration 
            (job_profile, name, email, phone, address, experience, experience_description, dob, gender, skills, 
            ten_th, ten_th_percentage, ten_th_year_of_passing, twelve_th, twelve_th_percentage, twelve_th_year_of_passing, 
            graduation, graduation_percentage, graduation_year_of_passing, post_graduation, post_graduation_percentage, post_graduation_year_of_passing, resume)
            VALUES (
                '$job_profile', '$name', '$email', '$phone', '$address', '$experience', '$experience_description', '$dob', '$gender', '$skills',
                '$ten_th', " . ($ten_th_percentage !== NULL ? "'$ten_th_percentage'" : "NULL") . ", " . ($ten_th_year_of_passing !== NULL ? "'$ten_th_year_of_passing'" : "NULL") . ",
                '$twelve_th', " . ($twelve_th_percentage !== NULL ? "'$twelve_th_percentage'" : "NULL") . ", " . ($twelve_th_year_of_passing !== NULL ? "'$twelve_th_year_of_passing'" : "NULL") . ",
                '$graduation', " . ($graduation_percentage !== NULL ? "'$graduation_percentage'" : "NULL") . ", " . ($graduation_year_of_passing !== NULL ? "'$graduation_year_of_passing'" : "NULL") . ",
                '$post_graduation', " . ($post_graduation_percentage !== NULL ? "'$post_graduation_percentage'" : "NULL") . ", " . ($post_graduation_year_of_passing !== NULL ? "'$post_graduation_year_of_passing'" : "NULL") . ", '$resume_name'
            )";

    if (mysqli_query($conn, $sql)) {

    echo json_encode([
        "status" => "success",
        "message" => "Your application submitted successfully. Our HR team will get back to you soon."
    ]);

} else {

    echo json_encode([
        "status" => "error",
        "message" => mysqli_error($conn)
    ]);
}
}
?>
