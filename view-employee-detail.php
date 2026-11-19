<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

$employee_id = $_GET['employee_id'] ?? '';

if (empty($employee_id)) {
    echo "Invalid employee ID.";
    exit();
}

/* =========================
   EMPLOYEE DETAILS
========================= */

$sql = "SELECT * FROM tbl_employee WHERE Id = '$employee_id'";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$employee = mysqli_fetch_assoc($result);

if (!$employee) {
    echo "Employee not found.";
    exit();
}


/* =========================
   EMPLOYEE DOCUMENTS
========================= */

$doc_sql = "SELECT * FROM employee_documents WHERE employee_id = '$employee_id'";
$doc_result = mysqli_query($conn, $doc_sql);

$documents = [];

if ($doc_result) {
    while ($row = mysqli_fetch_assoc($doc_result)) {
        $documents[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Employee Profile</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

    <style>

      
        #main-content {
            width: 100%;
            min-height: 100vh;
        }

        .wrapper {
            width: 100%;
            padding: 12px;
        }

        .employee-container {
            width: 100%;
            max-width: 1050px;
            margin: 0 auto;
        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-header {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .page-title {
            font-size: 16px;
            font-weight: 600;
            color: #27364b;
        }

        .page-title i {
            color: #4b82e8;
            margin-right: 5px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            height: 26px;
            padding: 0 12px;

            text-decoration: none !important;

            background: #5269f3;
            color: #fff !important;

            border-radius: 4px;

            font-size: 10px;
            font-weight: 500;

            transition: all 0.2s ease;
        }

        .back-btn:hover {
            background: #3d55dc;
            color: #fff !important;
            transform: translateY(-1px);
        }


        /* =====================================================
           PROFILE CARD
        ===================================================== */

        .profile-card {
            width: 100%;

            background: #fff;

            border: 1px solid #dfe4ec;
            border-radius: 5px;

            padding: 12px;

            display: flex;
            align-items: center;

            gap: 15px;

            box-shadow: 0 2px 6px rgba(0, 0, 0, .04);

            margin-bottom: 10px;
        }

        .profile-photo {
            width: 60px;
            height: 60px;

            border-radius: 50%;

            object-fit: cover;

            border: 2px solid #4b82e8;

            padding: 2px;

            flex-shrink: 0;
        }

        .profile-info {
            min-width: 0;
        }

        .profile-info h2 {
            margin: 0 0 3px;

            font-size: 16px;
            font-weight: 600;

            color: #27364b;
        }

        .profile-info p {
            margin: 2px 0;

            color: #697586;

            font-size: 12px;
        }

        .profile-info .designation {
            color: #555;
        }


        /* =====================================================
           TWO COLUMN LAYOUT
        ===================================================== */

        .columns {
            width: 100%;

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 10px;

            align-items: start;
        }

        .column {
            min-width: 0;

            display: flex;
            flex-direction: column;

            gap: 10px;
        }


        /* =====================================================
           CARD
        ===================================================== */

        .card {
            width: 100%;

            background: #fff;

            border: 1px solid #dfe4ec;

            border-radius: 5px;

            padding: 8px;

            box-shadow: 0 2px 6px rgba(0, 0, 0, .04);
        }


        /* =====================================================
           SECTION TITLE
        ===================================================== */

        .section-title {
            width: 100%;

            background: #4b82e8;

            color: #fff;

            font-size: 15px;
            font-weight: 600;

            padding: 6px 9px;

            border-radius: 3px;

            margin-bottom: 8px;
        }


        /* =====================================================
           FIELD GRID
        ===================================================== */

        .field-grid {
            width: 100%;

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 7px 8px;
        }

        .field {
            min-width: 0;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            display: block;


            font-size: 14px;

            margin-bottom: 3px;

            font-weight: 500;
        }

        .field-value {
            width: 100%;

            min-height: 25px;

            border: 1px solid #d3dce9;

            border-radius: 3px;

            background: #fff;

            padding: 5px 7px;


            font-size: 12px;

            display: flex;
            align-items: center;

            overflow-wrap: anywhere;
            word-break: break-word;
        }


        /* =====================================================
           EMPLOYEE ACTIONS
        ===================================================== */

        .actions-card {
            position: static !important;

            width: 100% !important;
            max-width: none !important;

            margin: 10px 0 0 0 !important;

            padding: 8px !important;

            background: #fff !important;

            border: 1px solid #dfe4ec !important;

            border-radius: 5px !important;

            box-shadow: 0 2px 6px rgba(0, 0, 0, .04);

            clear: both;
        }

        .actions-title {
            position: static !important;

            width: 100% !important;

            background: #4b82e8;

            color: #fff;

            font-size: 12px;

            font-weight: 600;

            padding: 6px 9px;

            border-radius: 3px;

            margin-bottom: 8px;
        }


        /* =====================================================
           ACTION BUTTON CONTAINER
        ===================================================== */

        .action-buttons {
            position: static !important;

            width: 100% !important;

            height: auto !important;

            margin: 0 !important;
            padding: 0 !important;

            display: flex !important;

            flex-direction: row !important;

            align-items: center !important;

            justify-content: flex-start !important;

            flex-wrap: wrap !important;

            gap: 6px !important;

            float: none !important;

            clear: both !important;
        }


        /* =====================================================
           ALL ACTION BUTTONS
        ===================================================== */

        .action-buttons a,
        .action-buttons button {
            position: static !important;

            display: inline-flex !important;

            align-items: center !important;

            justify-content: center !important;

            width: auto !important;

            min-width: 0 !important;

            height: 27px !important;

            margin: 0 !important;

            padding: 0 11px !important;

            border: none !important;

            border-radius: 4px !important;

            text-decoration: none !important;

            color: #fff !important;

            font-family: 'Poppins', sans-serif;

            font-size: 12px !important;

            font-weight: 500 !important;

            line-height: 1 !important;

            white-space: nowrap !important;

            float: none !important;

            cursor: pointer;

            transition:
                background-color 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .action-buttons a:hover,
        .action-buttons button:hover {
            color: #fff !important;

            transform: translateY(-1px);

            box-shadow: 0 3px 7px rgba(0, 0, 0, .12);
        }


        /* =====================================================
           ATTENDANCE
        ===================================================== */

        .btn-attendance {
            background: #12a66a !important;
        }

        .btn-attendance:hover {
            background: #078c57 !important;
        }


        /* =====================================================
           SALARY
        ===================================================== */

        .btn-salary {
            background: #754bc3 !important;
        }

        .btn-salary:hover {
            background: #6039a5 !important;
        }


        /* =====================================================
           UPLOAD
        ===================================================== */

        .btn-upload {
            background: #1689d7 !important;
        }

        .btn-upload:hover {
            background: #086fb6 !important;
        }


        /* =====================================================
           UPDATE
        ===================================================== */

        .btn-update {
            background: #f0a900 !important;
        }

        .btn-update:hover {
            background: #d28e00 !important;
        }


        /* =====================================================
           RESIGNATION
        ===================================================== */

        .btn-resignation {
            background: #e53935 !important;
        }

        .btn-resignation:hover {
            background: #c62828 !important;
        }


        /* =====================================================
           DOCUMENTS
        ===================================================== */

        .documents-card {
            position: static !important;

            width: 100%;

            background: #fff;

            border: 1px solid #dfe4ec;

            border-radius: 5px;

            padding: 8px;

            margin-top: 10px;

            margin-bottom: 20px;

            box-shadow: 0 2px 6px rgba(0, 0, 0, .04);

            clear: both;
        }

        .document-row {
            width: 100%;

            padding: 7px 5px;

            border-bottom: 1px solid #edf0f5;

            font-size: 10px;
        }

        .document-row:last-child {
            border-bottom: none;
        }

        .document-type {
            color: #555;

            font-weight: 500;
        }

        .document-link {
            color: #2878d4;

            text-decoration: none;

            margin-left: 5px;
        }

        .document-link:hover {
            text-decoration: underline;
        }

        .no-document {
            color: #777;

            font-size: 10px;

            padding: 5px;
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 768px) {

            .wrapper {
                padding: 10px;
            }

            .columns {
                grid-template-columns: 1fr;
            }

            .profile-card {
                padding: 10px;
            }

            .profile-photo {
                width: 55px;
                height: 55px;
            }

            .action-buttons {
                gap: 6px !important;
            }

            .action-buttons a,
            .action-buttons button {
                height: 29px !important;
            }
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 500px) {

            .wrapper {
                padding: 7px;
            }

            .page-header {
                margin-bottom: 7px;
            }

            .page-title {
                font-size: 11px;
            }

            .back-btn {
                height: 24px;

                font-size: 8px;

                padding: 0 9px;
            }

            .profile-card {
                padding: 9px;

                gap: 10px;
            }

            .profile-photo {
                width: 50px;
                height: 50px;
            }

            .profile-info h2 {
                font-size: 12px;
            }

            .profile-info p {
                font-size: 12px;
            }

            .field-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }


            /* Mobile Buttons */

            .action-buttons {
                position: static !important;

                display: grid !important;

                grid-template-columns: 1fr 1fr !important;

                gap: 6px !important;

                width: 100% !important;
            }

            .action-buttons a,
            .action-buttons button {
                width: 100% !important;

                height: 30px !important;

                padding: 0 5px !important;

                font-size: 8px !important;

                text-align: center;
            }
        }


        /* =====================================================
           VERY SMALL MOBILE
        ===================================================== */

        @media (max-width: 350px) {

            .action-buttons {
                grid-template-columns: 1fr !important;
            }

            .action-buttons a,
            .action-buttons button {
                width: 100% !important;

                height: 30px !important;
            }
        }


        /* =====================================================
           IMPORTANT OVERRIDE
           Stops external/header CSS from making buttons
           floating on right side.
        ===================================================== */

        #main-content .employee-container .actions-card {
            position: static !important;
            right: auto !important;
            left: auto !important;
            top: auto !important;
            bottom: auto !important;

            transform: none !important;

            width: 100% !important;
            max-width: none !important;
        }

        #main-content .employee-container .actions-card .action-buttons {
            position: static !important;
            right: auto !important;
            left: auto !important;
            top: auto !important;
            bottom: auto !important;

            transform: none !important;

            width: 100% !important;
            height: auto !important;

            display: flex !important;
            flex-direction: row !important;

            justify-content: flex-start !important;
            align-items: center !important;

            flex-wrap: wrap !important;

            margin: 0 !important;
            padding: 0 !important;
        }

        #main-content .employee-container .actions-card .action-buttons a {
            position: static !important;

            right: auto !important;
            left: auto !important;
            top: auto !important;
            bottom: auto !important;

            transform: none;

            float: none !important;

            margin: 0 !important;
        }

    </style>

</head>


<body>

<section id="main-content">

    <div class="wrapper">

        <div class="employee-container">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-header">

                <div class="page-title">
                    👤 Employee Details
                </div>

                <a
                    href="view-employee.php"
                    class="back-btn"
                >
                    ← Back
                </a>

            </div>


            <!-- =================================================
                 EMPLOYEE PROFILE
            ================================================== -->

            <div class="profile-card">

                <img
                    class="profile-photo"
                    src="uploads/<?= !empty($employee['photo'])
                        ? htmlspecialchars($employee['photo'])
                        : 'default.png'; ?>"
                    alt="Employee Photo"
                >

                <div class="profile-info">

                    <h2>

                        <?php
                        if (isset($employee['title']) && !empty($employee['title'])) {
                            echo htmlspecialchars($employee['title']) . ' ';
                        }
                        ?>

                        <?= htmlspecialchars($employee['cust_name']); ?>

                    </h2>

                    <p class="designation">
                        <?= htmlspecialchars($employee['designation']); ?>
                    </p>

                    <p>
                        Employee ID :
                        <?= htmlspecialchars($employee['emp_id']); ?>
                    </p>

                </div>

            </div>


            <!-- =================================================
                 TWO COLUMN CONTENT
            ================================================== -->

            <div class="columns">


                <!-- =================================================
                     LEFT COLUMN
                ================================================== -->

                <div class="column">


                    <!-- PERSONAL INFORMATION -->

                    <div class="card">

                        <div class="section-title">
                            Personal Information
                        </div>

                        <div class="field-grid">


                            <div class="field">

                                <label>Phone</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['cust_phone']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Email</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['cust_email']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Mother Name</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['mother_name']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>DOB</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['dob']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Gender</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['gender']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Marital Status</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['marital_status']); ?>
                                </div>

                            </div>


                            <?php if ($employee['marital_status'] == "Married") { ?>

                                <div class="field">

                                    <label>Spouse Name</label>

                                    <div class="field-value">
                                        <?= htmlspecialchars($employee['spouse_name']); ?>
                                    </div>

                                </div>


                                <div class="field">

                                    <label>Spouse Occupation</label>

                                    <div class="field-value">
                                        <?= htmlspecialchars($employee['spouse_occupation']); ?>
                                    </div>

                                </div>

                            <?php } ?>


                            <div class="field full">

                                <label>Current Address</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['cust_address1']); ?>
                                </div>

                            </div>


                            <div class="field full">

                                <label>Permanent Address</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['cust_address2']); ?>
                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- EXPERIENCE -->

                    <div class="card">

                        <div class="section-title">
                            Experience
                        </div>

                        <div class="field-grid">


                            <div class="field">

                                <label>Experience / Fresher</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['experience_fresher']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Work Experience</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['work_experience_years']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Previous Company</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['previous_company_name']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Previous Designation</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['previous_designation']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Previous Job Salary</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['previous_job_salary']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Previous Experience</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['previous_experience']); ?>
                                </div>

                            </div>


                        </div>

                    </div>


                </div>


                <!-- =================================================
                     RIGHT COLUMN
                ================================================== -->

                <div class="column">


                    <!-- EDUCATIONAL DETAILS -->

                    <div class="card">

                        <div class="section-title">
                            Educational Details
                        </div>

                        <div class="field-grid">


                            <div class="field">

                                <label>10th</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['education_10th']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>12th</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['education_12th']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Graduation</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['education_graduation']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Post Graduation</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['education_postgrad']); ?>
                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- EMPLOYMENT DETAILS -->

                    <div class="card">

                        <div class="section-title">
                            Employment Details
                        </div>

                        <div class="field-grid">


                            <div class="field">

                                <label>Department</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['department']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Employee Type</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['employee_type']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Joining Date</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['date']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Salary</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['package']); ?>
                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- KYC DETAILS -->

                    <div class="card">

                        <div class="section-title">
                            KYC Details
                        </div>

                        <div class="field-grid">


                            <div class="field">

                                <label>Aadhaar No</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['adhar_no']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>PAN Card</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['pan_card_no']); ?>
                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- BANK DETAILS -->

                    <div class="card">

                        <div class="section-title">
                            Bank Details
                        </div>

                        <div class="field-grid">


                            <div class="field">

                                <label>Account No</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['account_no']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Bank Name</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['bank_name']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>Branch</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['branch_name']); ?>
                                </div>

                            </div>


                            <div class="field">

                                <label>IFSC</label>

                                <div class="field-value">
                                    <?= htmlspecialchars($employee['ifsc_code']); ?>
                                </div>

                            </div>


                        </div>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 EMPLOYEE ACTIONS
            ================================================== -->

            <div class="actions-card">

                <div class="actions-title">
                    Employee Actions
                </div>


                <div class="action-buttons">


                    <!-- VIEW ATTENDANCE -->

                    <a
                        href="view-emp-attendance.php?employee_id=<?= urlencode($employee['Id']); ?>"
                        class="btn-attendance"
                    >
                        ✓&nbsp; View Attendance
                    </a>


                    <!-- VIEW SALARY -->

                    <a
                        href="view-salary.php?employee_id=<?= urlencode($employee['Id']); ?>"
                        class="btn-salary"
                    >
                        ₹&nbsp; View Salary
                    </a>


                    <!-- UPLOAD DOCUMENT -->

                    <a
                        href="upload_document.php?employee_id=<?= urlencode($employee['Id']); ?>"
                        class="btn-upload"
                    >
                        ↑&nbsp; Upload Document
                    </a>


                    <!-- UPDATE PROFILE -->

                    <a
                        href="edit-employee.php?employee_id=<?= urlencode($employee['Id']); ?>"
                        class="btn-update"
                    >
                        ✎&nbsp; Update Profile
                    </a>


                    <!-- RESIGNATION -->

                    <a
                        href="process_resignation.php?employee_id=<?= urlencode($employee['Id']); ?>"
                        class="btn-resignation"
                        onclick="return confirm('Are you sure you want to process resignation?');"
                    >
                        ⚠&nbsp; Resignation
                    </a>


                </div>

            </div>


            <!-- =================================================
                 VIEW DOCUMENTS
            ================================================== -->

            <div class="documents-card">

                <div class="section-title">
                    View Documents
                </div>


                <?php if (count($documents) > 0) { ?>


                    <?php foreach ($documents as $doc) { ?>

                        <div class="document-row">

                            <span class="document-type">

                                <?= htmlspecialchars($doc['document_type']); ?> :

                            </span>


                            <a
                                href="<?= htmlspecialchars($doc['file_path']); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="document-link"
                            >

                                <?= htmlspecialchars($doc['document_name']); ?>

                            </a>

                        </div>

                    <?php } ?>


                <?php } else { ?>

                    <div class="no-document">
                        No documents uploaded.
                    </div>

                <?php } ?>


            </div>


        </div>

    </div>

</section>


<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>

<script src="js/search.js"></script>


</body>

</html>