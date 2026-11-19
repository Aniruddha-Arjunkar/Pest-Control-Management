<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
?>
<?php
include 'header.php';
require_once 'db_connect.php';
?>


<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">


                <!-- =================================
                     HEADER
                ================================== -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">👤</span>

                        <div>

                            <h2>Add Employee</h2>

                            <p>
                                Add employee details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    action="add-employee.php"
                    method="post"
                    enctype="multipart/form-data"
                    class="refollowup-form"
                >


                    <!-- =================================
                         PERSONAL DETAILS
                    ================================= -->

                    <h3>Personal Details</h3>


                    <div class="form-row">

                        <!-- TITLE -->

                        <div class="form-group">

                            <label for="title">
                                Title
                            </label>

                            <select
                                id="title"
                                name="title"
                                required
                            >

                                <option value="Mr.">Mr.</option>
                                <option value="Ms.">Ms.</option>
                                <option value="Mrs.">Mrs.</option>

                            </select>

                        </div>


                        <!-- NAME -->

                        <div class="form-group">

                            <label for="cust_name">
                                Name <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="cust_name"
                                name="cust_name"
                                placeholder="Enter employee's name"
                                required
                            >

                        </div>


                        <!-- PHONE -->

                        <div class="form-group">

                            <label for="cust_phone">
                                Phone
                            </label>

                            <input
                                type="text"
                                id="cust_phone"
                                name="cust_phone"
                                placeholder="Enter phone number"
                                maxlength="10"
                            >

                        </div>


                        <!-- DOB -->

                        <div class="form-group">

                            <label for="dob">
                                Date of Birth
                            </label>

                            <input
                                type="date"
                                id="dob"
                                name="dob"
                            >

                        </div>


                        <!-- GENDER -->

                        <div class="form-group">

                            <label for="gender">
                                Gender
                            </label>

                            <select
                                id="gender"
                                name="gender"
                                required
                            >

                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>

                            </select>

                        </div>


                        <!-- EMAIL -->

                        <div class="form-group">

                            <label for="cust_email">
                                Email
                            </label>

                            <input
                                type="email"
                                id="cust_email"
                                name="cust_email"
                                placeholder="Enter email address"
                            >

                        </div>


                        <!-- RELATIONSHIP -->

                        <div class="form-group">

                            <label for="alternate_phone_relation">
                                Relationship
                            </label>

                            <select
                                id="alternate_phone_relation"
                                name="alternate_phone_relation"
                            >

                                <option value="">
                                    Select Relation
                                </option>

                                <option value="Father">
                                    Father
                                </option>

                                <option value="Mother">
                                    Mother
                                </option>

                                <option value="Spouse">
                                    Spouse
                                </option>

                                <option value="Brother">
                                    Brother
                                </option>

                                <option value="Sister">
                                    Sister
                                </option>

                                <option value="Friend">
                                    Friend
                                </option>

                                <option value="Other">
                                    Other
                                </option>

                            </select>

                        </div>


                        <!-- ALTERNATE PHONE -->

                        <div class="form-group">

                            <label for="cust_alternate">
                                Alternate Phone
                            </label>

                            <input
                                type="text"
                                id="cust_alternate"
                                name="cust_alternate"
                                placeholder="Enter alternate phone number"
                                maxlength="10"
                            >

                        </div>


                        <!-- MOTHER NAME -->

                        <div class="form-group">

                            <label for="mother_name">
                                Mother's Name
                            </label>

                            <input
                                type="text"
                                id="mother_name"
                                name="mother_name"
                                placeholder="Enter mother's name"
                            >

                        </div>

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="cust_address1">
                                Current Address
                            </label>

                            <input
                                type="text"
                                id="cust_address1"
                                name="cust_address1"
                                placeholder="Enter current address"
                            >

                        </div>


                        <div class="form-group">

                            <label for="cust_address2">
                                Permanent Address
                            </label>

                            <input
                                type="text"
                                id="cust_address2"
                                name="cust_address2"
                                placeholder="Enter permanent address"
                            >

                        </div>

                    </div>


                    <!-- MARITAL STATUS -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="marital_status">
                                Marital Status
                            </label>

                            <select
                                id="marital_status"
                                name="marital_status"
                            >

                                <option value="Single">
                                    Single
                                </option>

                                <option value="Married">
                                    Married
                                </option>

                                <option value="Unmarried">
                                    Unmarried
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- SPOUSE DETAILS -->

                    <div
                        class="form-row"
                        id="spouse_details"
                        style="display:none;"
                    >

                        <div class="form-group">

                            <label for="spouse_name">
                                Spouse Name
                            </label>

                            <input
                                type="text"
                                id="spouse_name"
                                name="spouse_name"
                                placeholder="Enter spouse's name"
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
                                placeholder="Enter spouse's occupation"
                            >

                        </div>

                    </div>


                    <!-- =================================
                         EDUCATION DETAILS
                    ================================== -->

                    <h3>Education Details</h3>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="education_10th">
                                10th
                            </label>

                            <input
                                type="text"
                                id="education_10th"
                                name="education_10th"
                                placeholder="Enter 10th qualification"
                            >

                        </div>


                        <div class="form-group">

                            <label for="education_12th">
                                12th
                            </label>

                            <input
                                type="text"
                                id="education_12th"
                                name="education_12th"
                                placeholder="Enter 12th qualification"
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
                                placeholder="Enter graduation qualification"
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
                                placeholder="Enter post graduation qualification"
                            >

                        </div>

                    </div>


                    <!-- =================================
                         EXPERIENCE DETAILS
                    ================================== -->

                    <h3>Experience / Fresher Details</h3>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="experience_fresher">
                                Experience / Fresher
                            </label>

                            <select
                                id="experience_fresher"
                                name="experience_fresher"
                            >

                                <option value="Experience">
                                    Experience
                                </option>

                                <option value="Fresher">
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
                                placeholder="Enter previous experience"
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
                                placeholder="Enter work experience years"
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
                                placeholder="Enter previous company name"
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
                                placeholder="Enter previous designation"
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
                                placeholder="Enter previous job salary"
                            >

                        </div>

                    </div>


                    <!-- =================================
                         EMPLOYEE DETAILS
                    ================================== -->

                    <h3>Employee Details</h3>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="employee_type">
                                Employee Type
                            </label>

                            <select
                                id="employee_type"
                                name="employee_type"
                            >

                                <option value="">
                                    Select Type
                                </option>

                                <option value="Full Time">
                                    Full Time
                                </option>

                                <option value="Part Time">
                                    Part Time
                                </option>

                                <option value="Intern">
                                    Intern
                                </option>

                                <option value="Daily Based">
                                    Daily Based
                                </option>

                                <option value="Per Day">
                                    Per Day
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="department">
                                Department
                            </label>

                            <select
                                id="department"
                                name="department"
                            >

                                <option value="">
                                    Select Department
                                </option>

                                <option value="IT">
                                    IT
                                </option>

                                <option value="HR">
                                    HR
                                </option>

                                <option value="Trainer">
                                    Trainer
                                </option>

                                <option value="Sales">
                                    Sales
                                </option>

                                <option value="Pest Control">
                                    Pest Control
                                </option>

                                <option value="Business Developer">
                                    Business Developer
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="package">
                                Package
                            </label>

                            <input
                                type="text"
                                id="package"
                                name="package"
                                placeholder="Enter package"
                            >

                        </div>


                        <div class="form-group">

                            <label for="date">
                                Joining Date
                            </label>

                            <input
                                type="date"
                                id="date"
                                name="date"
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
                                placeholder="Enter designation"
                            >

                        </div>


                        <div class="form-group">

                            <label for="branch">
                                Branch
                            </label>

                            <select
                                id="branch"
                                name="branch"
                            >

                                <option value="">
                                    Select Branch
                                </option>

                                <option value="Nagpur">
                                    Nagpur
                                </option>

                                <option value="Bramhapuri">
                                    Bramhapuri
                                </option>

                                <option value="Chandrapur">
                                    Chandrapur
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- =================================
                         BANK DETAILS
                    ================================== -->

                    <h3>Bank Details</h3>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="account_no">
                                Account No.
                            </label>

                            <input
                                type="text"
                                id="account_no"
                                name="account_no"
                                placeholder="Enter Account No."
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
                                placeholder="Enter bank name"
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
                                placeholder="Enter branch name"
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
                                placeholder="Enter IFSC code"
                            >

                        </div>

                    </div>


                    <!-- =================================
                         KYC DETAILS
                    ================================== -->

                    <h3>KYC Details</h3>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="adhar_no">
                                Aadhar No
                            </label>

                            <input
                                type="text"
                                id="adhar_no"
                                name="adhar_no"
                                placeholder="Enter Aadhar Number"
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
                                placeholder="Enter PAN Card Number"
                            >

                        </div>

                    </div>


                    <!-- =================================
                         PROFILE PHOTO
                    ================================== -->

                    <h3>Profile Photo</h3>


                    <div class="form-group">

                        <label for="photo">
                            Upload Photo
                        </label>

                        <input
                            type="file"
                            id="photo"
                            name="photo"
                        >

                    </div>


                    <!-- =================================
                         PF & ESIC
                    ================================== -->

                    <h3>Provident Fund and ESIC Details</h3>


                    <div class="form-row">

                        <div class="form-group">

                            <label for="uan_no">
                                UAN No
                            </label>

                            <input
                                type="text"
                                id="uan_no"
                                name="uan_no"
                                placeholder="Enter UAN Number"
                            >

                        </div>


                        <div class="form-group">

                            <label for="pf_no">
                                PF No
                            </label>

                            <input
                                type="text"
                                id="pf_no"
                                name="pf_no"
                                placeholder="Enter PF Number"
                            >

                        </div>


                        <div class="form-group">

                            <label for="esic_no">
                                ESIC No
                            </label>

                            <input
                                type="text"
                                id="esic_no"
                                name="esic_no"
                                placeholder="Enter ESIC Number"
                            >

                        </div>

                    </div>


                    <!-- =================================
                         SUBMIT
                    ================================== -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>👤</span>

                        Submit

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =================================
     MARITAL STATUS JAVASCRIPT
================================= -->

<script>

document
    .getElementById("marital_status")
    .addEventListener("change", function () {

        var spouseDetails =
            document.getElementById("spouse_details");

        if (this.value === "Married") {

            spouseDetails.style.display = "flex";

        } else {

            spouseDetails.style.display = "none";

        }

    });

</script>


<!-- =================================
     EXISTING JS
================================= -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>

<script src="js/search.js"></script>

</body>
</html>

