<?php
session_start();
ob_start(); // Start output buffering

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php'; // Database connection
include 'header.php'; // Include header


// Get the employee ID from the URL
if (!isset($_GET['employee_id'])) {
    echo "Employee ID is missing!";
    exit();
}

$employee_id = $_GET['employee_id'];

// Fetch employee data from the database
$sql = "SELECT * FROM tbl_employee WHERE Id = '$employee_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    echo "Employee not found.";
    exit();
}

$employee = mysqli_fetch_assoc($result);

// If the form is submitted, update the employee details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $cust_name = mysqli_real_escape_string($conn, $_POST['cust_name']);
    $cust_email = mysqli_real_escape_string($conn, $_POST['cust_email']);
    $cust_phone = mysqli_real_escape_string($conn, $_POST['cust_phone']);
    $cust_alternate = mysqli_real_escape_string($conn, $_POST['cust_alternate']);
    $alternate_phone_relation = mysqli_real_escape_string($conn, $_POST['alternate_phone_relation']);
    $mother_name = mysqli_real_escape_string($conn, $_POST['mother_name']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $marital_status = mysqli_real_escape_string($conn, $_POST['marital_status']);
    $spouse_name = mysqli_real_escape_string($conn, $_POST['spouse_name']);
    $spouse_occupation = mysqli_real_escape_string($conn, $_POST['spouse_occupation']);
    $education_10th = mysqli_real_escape_string($conn, $_POST['education_10th']);
    $education_12th = mysqli_real_escape_string($conn, $_POST['education_12th']);
    $education_graduation = mysqli_real_escape_string($conn, $_POST['education_graduation']);
    $education_postgrad = mysqli_real_escape_string($conn, $_POST['education_postgrad']);
    $experience_fresher = mysqli_real_escape_string($conn, $_POST['experience_fresher']);
    $previous_experience = mysqli_real_escape_string($conn, $_POST['previous_experience']);
    $work_experience_years = mysqli_real_escape_string($conn, $_POST['work_experience_years']);
    $previous_company_name = mysqli_real_escape_string($conn, $_POST['previous_company_name']);
    $previous_designation = mysqli_real_escape_string($conn, $_POST['previous_designation']);
    $previous_job_salary = mysqli_real_escape_string($conn, $_POST['previous_job_salary']);
    $adhar_no = mysqli_real_escape_string($conn, $_POST['adhar_no']);
    $pan_card_no = mysqli_real_escape_string($conn, $_POST['pan_card_no']);
    $account_no = mysqli_real_escape_string($conn, $_POST['account_no']);
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $branch_name = mysqli_real_escape_string($conn, $_POST['branch_name']);
    $ifsc_code = mysqli_real_escape_string($conn, $_POST['ifsc_code']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $employee_type = mysqli_real_escape_string($conn, $_POST['employee_type']);
    $package = mysqli_real_escape_string($conn, $_POST['package']);
    $cust_address1 = mysqli_real_escape_string($conn, $_POST['cust_address1']);
    $cust_address2 = mysqli_real_escape_string($conn, $_POST['cust_address2']);
    $branch = mysqli_real_escape_string($conn, $_POST['branch']);

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {

        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["photo"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if the file is an image
        $check = getimagesize($_FILES["photo"]["tmp_name"]);

        if ($check !== false) {

            // File size limit 500KB
            if ($_FILES["photo"]["size"] > 500000) {
                echo "Sorry, your file is too large.";
                exit();
            }

            // Allow certain file formats
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                echo "Sorry, only JPG, JPEG, PNG, & GIF files are allowed.";
                exit();
            }

            // Try to upload the file
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                $photo = basename($_FILES["photo"]["name"]);
            } else {
                echo "Sorry, there was an error uploading your file.";
                exit();
            }

        } else {
            echo "File is not an image.";
            exit();
        }

    } else {
        // If no photo is uploaded, retain old photo
        $photo = $employee['photo'];
    }


    $update_sql = "UPDATE tbl_employee SET
        cust_name = '$cust_name',
        cust_email = '$cust_email',
        cust_phone = '$cust_phone',
        cust_alternate = '$cust_alternate',
        alternate_phone_relation = '$alternate_phone_relation',
        mother_name = '$mother_name',
        dob = '$dob',
        gender = '$gender',
        designation = '$designation',
        marital_status = '$marital_status',
        spouse_name = '$spouse_name',
        spouse_occupation = '$spouse_occupation',
        education_10th = '$education_10th',
        education_12th = '$education_12th',
        education_graduation = '$education_graduation',
        education_postgrad = '$education_postgrad',
        experience_fresher = '$experience_fresher',
        previous_experience = '$previous_experience',
        work_experience_years = '$work_experience_years',
        previous_company_name = '$previous_company_name',
        previous_designation = '$previous_designation',
        previous_job_salary = '$previous_job_salary',
        adhar_no = '$adhar_no',
        pan_card_no = '$pan_card_no',
        account_no = '$account_no',
        bank_name = '$bank_name',
        branch_name = '$branch_name',
        ifsc_code = '$ifsc_code',
        date = '$date',
        department = '$department',
        employee_type = '$employee_type',
        package = '$package',
        cust_address1 = '$cust_address1',
        cust_address2 = '$cust_address2',
        photo = '$photo',
        branch = '$branch'
        WHERE Id = '$employee_id'";

    if (mysqli_query($conn, $update_sql)) {

        $_SESSION['msg'] = "Employee details updated successfully!";

        header("Location: view-employee.php?employee_id=$employee_id");
        exit();

    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee Profile</title>
</head>

<body>

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">

                <!-- Header -->
                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">👤</span>

                        <div>
                            <h2>Update Employee Details</h2>
                            <p>
                                Update profile details for Employee ID:
                                <?= htmlspecialchars($employee['Id']); ?>
                            </p>
                        </div>

                    </div>

                </div>


                <!-- Form -->
                <form
                    action="edit-employee.php?employee_id=<?= urlencode($employee['Id']); ?>"
                    method="POST"
                    enctype="multipart/form-data"
                    class="refollowup-form"
                >

                    <!-- ================= PERSONAL DETAILS ================= -->

                    <h3>Personal Details</h3>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="title">Title</label>

                            <select id="title" name="title" required>

                                <option value="Mr."
                                    <?php echo ($employee['title'] == 'Mr.') ? 'selected' : ''; ?>>
                                    Mr.
                                </option>

                                <option value="Ms."
                                    <?php echo ($employee['title'] == 'Ms.') ? 'selected' : ''; ?>>
                                    Ms.
                                </option>

                                <option value="Mrs."
                                    <?php echo ($employee['title'] == 'Mrs.') ? 'selected' : ''; ?>>
                                    Mrs.
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="cust_name">Name</label>

                            <input
                                type="text"
                                id="cust_name"
                                name="cust_name"
                                value="<?php echo htmlspecialchars($employee['cust_name']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="cust_phone">Phone</label>

                            <input
                                type="text"
                                id="cust_phone"
                                name="cust_phone"
                                value="<?php echo htmlspecialchars($employee['cust_phone']); ?>"
                                maxlength="10"
                            >

                        </div>

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="alternate_phone_relation">
                                Alternate Phone Relation
                            </label>

                            <input
                                type="text"
                                id="alternate_phone_relation"
                                name="alternate_phone_relation"
                                value="<?php echo htmlspecialchars($employee['alternate_phone_relation']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="cust_alternate">
                                Alternate Phone
                            </label>

                            <input
                                type="text"
                                id="cust_alternate"
                                name="cust_alternate"
                                value="<?php echo htmlspecialchars($employee['cust_alternate']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="mother_name">
                                Mother Name
                            </label>

                            <input
                                type="text"
                                id="mother_name"
                                name="mother_name"
                                value="<?php echo htmlspecialchars($employee['mother_name']); ?>"
                            >

                        </div>

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="dob">Date of Birth</label>

                            <input
                                type="date"
                                id="dob"
                                name="dob"
                                value="<?php echo htmlspecialchars($employee['dob']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="gender">Gender</label>

                            <select id="gender" name="gender" required>

                                <option value="Male"
                                    <?php echo ($employee['gender'] == 'Male') ? 'selected' : ''; ?>>
                                    Male
                                </option>

                                <option value="Female"
                                    <?php echo ($employee['gender'] == 'Female') ? 'selected' : ''; ?>>
                                    Female
                                </option>

                                <option value="Other"
                                    <?php echo ($employee['gender'] == 'Other') ? 'selected' : ''; ?>>
                                    Other
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="cust_email">Email</label>

                            <input
                                type="email"
                                id="cust_email"
                                name="cust_email"
                                value="<?php echo htmlspecialchars($employee['cust_email']); ?>"
                            >

                        </div>

                    </div>


                    <!-- Address -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="cust_address1">
                                Current Address
                            </label>

                            <input
                                type="text"
                                name="cust_address1"
                                id="cust_address1"
                                value="<?php echo htmlspecialchars($employee['cust_address1']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="cust_address2">
                                Permanent Address
                            </label>

                            <input
                                type="text"
                                name="cust_address2"
                                id="cust_address2"
                                value="<?php echo htmlspecialchars($employee['cust_address2']); ?>"
                            >

                        </div>

                    </div>


                    <!-- Marital Status -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="marital_status">
                                Marital Status
                            </label>

                            <select
                                id="marital_status"
                                name="marital_status"
                                required
                            >

                                <option value="Single"
                                    <?php echo ($employee['marital_status'] == 'Single') ? 'selected' : ''; ?>>
                                    Single
                                </option>

                                <option value="Married"
                                    <?php echo ($employee['marital_status'] == 'Married') ? 'selected' : ''; ?>>
                                    Married
                                </option>

                                <option value="Unmarried"
                                    <?php echo ($employee['marital_status'] == 'Unmarried') ? 'selected' : ''; ?>>
                                    Unmarried
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- Spouse Details -->

                    <div
                        class="form-row"
                        id="spouse_details"
                        <?php if ($employee['marital_status'] != 'Married') echo 'hidden'; ?>
                    >

                        <div class="form-group">

                            <label for="spouse_name">
                                Spouse Name
                            </label>

                            <input
                                type="text"
                                id="spouse_name"
                                name="spouse_name"
                                value="<?php echo htmlspecialchars($employee['spouse_name']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="spouse_occupation">
                                Spouse Occupation
                            </label>

                            <input
                                type="text"
                                id="spouse_occupation"
                                name="spouse_occupation"
                                value="<?php echo htmlspecialchars($employee['spouse_occupation']); ?>"
                            >

                        </div>

                    </div>


                    <!-- ================= EDUCATION ================= -->

                    <h3>Education Details</h3>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="education_10th">10th</label>

                            <input
                                type="text"
                                id="education_10th"
                                name="education_10th"
                                value="<?php echo htmlspecialchars($employee['education_10th']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="education_12th">12th</label>

                            <input
                                type="text"
                                id="education_12th"
                                name="education_12th"
                                value="<?php echo htmlspecialchars($employee['education_12th']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="education_graduation">
                                Graduation
                            </label>

                            <input
                                type="text"
                                id="education_graduation"
                                name="education_graduation"
                                value="<?php echo htmlspecialchars($employee['education_graduation']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="education_postgrad">
                                Post Graduation
                            </label>

                            <input
                                type="text"
                                id="education_postgrad"
                                name="education_postgrad"
                                value="<?php echo htmlspecialchars($employee['education_postgrad']); ?>"
                            >

                        </div>

                    </div>


                    <!-- ================= EXPERIENCE ================= -->

                    <h3>Experience / Fresher Details</h3>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="experience_fresher">
                                Experience / Fresher
                            </label>

                            <select
                                id="experience_fresher"
                                name="experience_fresher"
                                required
                            >

                                <option value="Experience"
                                    <?php echo ($employee['experience_fresher'] == 'Experience') ? 'selected' : ''; ?>>
                                    Experience
                                </option>

                                <option value="Fresher"
                                    <?php echo ($employee['experience_fresher'] == 'Fresher') ? 'selected' : ''; ?>>
                                    Fresher
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="previous_experience">
                                Previous Experience
                            </label>

                            <input
                                type="text"
                                id="previous_experience"
                                name="previous_experience"
                                value="<?php echo htmlspecialchars($employee['previous_experience']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="work_experience_years">
                                Work Experience (Years)
                            </label>

                            <input
                                type="text"
                                id="work_experience_years"
                                name="work_experience_years"
                                value="<?php echo htmlspecialchars($employee['work_experience_years']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="previous_company_name">
                                Previous Company Name
                            </label>

                            <input
                                type="text"
                                id="previous_company_name"
                                name="previous_company_name"
                                value="<?php echo htmlspecialchars($employee['previous_company_name']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="previous_designation">
                                Previous Designation
                            </label>

                            <input
                                type="text"
                                id="previous_designation"
                                name="previous_designation"
                                value="<?php echo htmlspecialchars($employee['previous_designation']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="previous_job_salary">
                                Previous Job Salary (Annual)
                            </label>

                            <input
                                type="text"
                                id="previous_job_salary"
                                name="previous_job_salary"
                                value="<?php echo htmlspecialchars($employee['previous_job_salary']); ?>"
                            >

                        </div>

                    </div>


                    <!-- ================= ADDITIONAL DETAILS ================= -->

                    <h3>Additional Employee Details</h3>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="date">Joining Date</label>

                            <input
                                type="date"
                                id="date"
                                name="date"
                                value="<?php echo htmlspecialchars($employee['date']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="employee_type">
                                Employee Type
                            </label>

                            <select id="employee_type" name="employee_type">

                                <option value="">
                                    Select Type
                                </option>

                                <option value="Full Time"
                                    <?php echo ($employee['employee_type'] == 'Full Time') ? 'selected' : ''; ?>>
                                    Full Time
                                </option>

                                <option value="Part Time"
                                    <?php echo ($employee['employee_type'] == 'Part Time') ? 'selected' : ''; ?>>
                                    Part Time
                                </option>

                                <option value="Intern"
                                    <?php echo ($employee['employee_type'] == 'Intern') ? 'selected' : ''; ?>>
                                    Intern
                                </option>

                                <option value="Daily Based"
                                    <?php echo ($employee['employee_type'] == 'Daily Based') ? 'selected' : ''; ?>>
                                    Daily Based
                                </option>

                                <option value="Per Day"
                                    <?php echo ($employee['employee_type'] == 'Per Day') ? 'selected' : ''; ?>>
                                    Per Day
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="department">
                                Department
                            </label>

                            <select id="department" name="department">

                                <option value="">
                                    Select Department
                                </option>

                                <option value="IT"
                                    <?php echo ($employee['department'] == 'IT') ? 'selected' : ''; ?>>
                                    IT
                                </option>

                                <option value="HR"
                                    <?php echo ($employee['department'] == 'HR') ? 'selected' : ''; ?>>
                                    HR
                                </option>

                                <option value="Trainer"
                                    <?php echo ($employee['department'] == 'Trainer') ? 'selected' : ''; ?>>
                                    Trainer
                                </option>

                                <option value="Sales"
                                    <?php echo ($employee['department'] == 'Sales') ? 'selected' : ''; ?>>
                                    Sales
                                </option>

                                <option value="Pest Control"
                                    <?php echo ($employee['department'] == 'Pest Control') ? 'selected' : ''; ?>>
                                    Pest Control
                                </option>

                                <option value="Business Developer"
                                    <?php echo ($employee['department'] == 'Business Developer') ? 'selected' : ''; ?>>
                                    Business Developer
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="package">
                                Salary Package
                            </label>

                            <input
                                type="number"
                                id="package"
                                name="package"
                                value="<?php echo htmlspecialchars($employee['package']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="designation">
                                Designation
                            </label>

                            <input
                                type="text"
                                id="designation"
                                name="designation"
                                value="<?php echo htmlspecialchars($employee['designation']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="branch">
                                Branch
                            </label>

                            <select
                                id="branch"
                                name="branch"
                                required
                            >

                                <option value="Nagpur"
                                    <?php echo ($employee['branch'] == 'Nagpur') ? 'selected' : ''; ?>>
                                    Nagpur
                                </option>

                                <option value="Bramhapuri"
                                    <?php echo ($employee['branch'] == 'Bramhapuri') ? 'selected' : ''; ?>>
                                    Bramhapuri
                                </option>

                                <option value="Chandrapur"
                                    <?php echo ($employee['branch'] == 'Chandrapur') ? 'selected' : ''; ?>>
                                    Chandrapur
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- ================= KYC ================= -->

                    <h3>KYC Details</h3>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="adhar_no">
                                Aadhaar No
                            </label>

                            <input
                                type="text"
                                id="adhar_no"
                                name="adhar_no"
                                value="<?php echo htmlspecialchars($employee['adhar_no']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="pan_card_no">
                                PAN Card No
                            </label>

                            <input
                                type="text"
                                id="pan_card_no"
                                name="pan_card_no"
                                value="<?php echo htmlspecialchars($employee['pan_card_no']); ?>"
                            >

                        </div>

                    </div>


                    <!-- ================= BANK DETAILS ================= -->

                    <h3>Bank Account Details</h3>

                    <div class="form-row">

                        <div class="form-group">

                            <label for="account_no">
                                Account Number
                            </label>

                            <input
                                type="text"
                                id="account_no"
                                name="account_no"
                                value="<?php echo htmlspecialchars($employee['account_no']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="bank_name">
                                Bank Name
                            </label>

                            <input
                                type="text"
                                id="bank_name"
                                name="bank_name"
                                value="<?php echo htmlspecialchars($employee['bank_name']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="branch_name">
                                Branch Name
                            </label>

                            <input
                                type="text"
                                id="branch_name"
                                name="branch_name"
                                value="<?php echo htmlspecialchars($employee['branch_name']); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label for="ifsc_code">
                                IFSC Code
                            </label>

                            <input
                                type="text"
                                id="ifsc_code"
                                name="ifsc_code"
                                value="<?php echo htmlspecialchars($employee['ifsc_code']); ?>"
                            >

                        </div>

                    </div>


                    <!-- ================= PHOTO ================= -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="photo">
                                Upload Photo
                            </label>

                            <input
                                type="file"
                                id="photo"
                                name="photo"
                                accept=".jpg,.jpeg,.png,.gif"
                            >

                        </div>


                        <?php if (!empty($employee['photo'])): ?>

                            <div class="form-group">

                                <label>
                                    Current Photo
                                </label>

                                <img
                                    src="uploads/<?= htmlspecialchars($employee['photo']); ?>"
                                    alt="Current Photo"
                                    width="150"
                                    height="150"
                                >

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- ================= SUBMIT ================= -->

                    <div class="form-row">

                        <div class="form-group full-width">

                            <button
                                type="submit"
                                class="submit-btn"
                            >
                                <span>💾</span>
                                Update Profile
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </section>

</section>


<script>
document.getElementById("marital_status").addEventListener("change", function () {

    const spouseDetails = document.getElementById("spouse_details");

    if (this.value === "Married") {
        spouseDetails.hidden = false;
    } else {
        spouseDetails.hidden = true;
    }

});
</script>


<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>

</body>
</html>

<?php
ob_end_flush(); // End output buffering and send output to browser
?>