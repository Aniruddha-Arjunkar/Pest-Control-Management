<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

/* =========================================================
   LOGOUT
========================================================= */

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location:login.php');
    exit();
}

/* =========================================================
   DATABASE
========================================================= */

require_once 'db_connect.php';
include 'header.php';

/* =========================================================
   CANDIDATE ID
========================================================= */

$Id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($Id <= 0) {
    echo '<div class="alert alert-danger">Invalid Candidate ID.</div>';
    exit();
}

/* =========================================================
   FETCH REGISTRATION
========================================================= */

$sql = "SELECT * FROM tbl_registration WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database prepare error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $Id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    echo '<div class="alert alert-danger">Candidate not found.</div>';
    exit();
}

$row = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

?>

<section id="main-content">

    <section class="wrapper">

        <div class="view-clients-page">

            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="view-clients-header">

                <div class="view-clients-title-wrap">

                    <h1 class="view-clients-title">

                        <span class="view-clients-title-icon">
                            👤
                        </span>

                        Candidate Details

                    </h1>

                    <p class="view-clients-subtitle">
                        View candidate personal, education and experience details
                    </p>

                </div>

                <span class="view-clients-count">

                    Candidate ID:
                    <span>
                        <?= htmlspecialchars($Id, ENT_QUOTES, 'UTF-8'); ?>
                    </span>

                </span>

            </div>


            <!-- =================================================
                 MAIN CARD
            ================================================== -->

            <div class="view-clients-card">


                <!-- =================================================
                     PERSONAL DETAILS
                ================================================== -->

                <div class="view-client-section">

                    <div class="view-client-section-header">

                        <h3>
                            👤 Personal Details
                        </h3>

                    </div>


                    <div class="view-clients-table-wrapper">

                        <table class="view-clients-table">

                            <thead>

                                <tr>

                                    <th>Name</th>
                                    <th>Mobile No.</th>
                                    <th>Email</th>
                                    <th>Date of Birth</th>
                                    <th>Address</th>
                                    <th>Gender</th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td class="view-client-name">

                                        <?= htmlspecialchars(
                                            $row['name'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td class="view-client-phone">

                                        <?= htmlspecialchars(
                                            $row['phone'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['email'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td class="view-client-date-cell">

                                        <?= htmlspecialchars(
                                            $row['dob'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['address'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['gender'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- =================================================
                     EDUCATION DETAILS
                ================================================== -->

                <div class="view-client-section">

                    <div class="view-client-section-header">

                        <h3>
                            🎓 Education Details
                        </h3>

                    </div>


                    <div class="view-clients-table-wrapper">

                        <table class="view-clients-table">

                            <thead>

                                <tr>

                                    <th>Qualification</th>
                                    <th>Year Of Passing</th>
                                    <th>Percentage</th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td class="view-client-service">
                                        10th
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['ten_th_year_of_passing'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['ten_th_percentage'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </td>

                                </tr>


                                <tr>

                                    <td class="view-client-service">
                                        12th
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['twelve_th_year_of_passing'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $row['twelve_th_percentage'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- =================================================
                     GRADUATION DETAILS
                ================================================== -->

                <div class="view-client-section">

                    <div class="view-client-section-header">

                        <h3>
                            🎓 Graduation Details
                        </h3>

                    </div>


                    <div class="view-clients-table-wrapper">

                        <table class="view-clients-table">

                            <thead>

                                <tr>

                                    <th>Qualification</th>
                                    <th>Year Of Passing</th>
                                    <th>Percentage</th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td class="view-client-service">

                                        <?= htmlspecialchars(
                                            $row['graduation'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['graduation_year_of_passing'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['graduation_percentage'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- =================================================
                     POST GRADUATION DETAILS
                ================================================== -->

                <div class="view-client-section">

                    <div class="view-client-section-header">

                        <h3>
                            🎓 Post Graduation Details
                        </h3>

                    </div>


                    <div class="view-clients-table-wrapper">

                        <table class="view-clients-table">

                            <thead>

                                <tr>

                                    <th>Qualification</th>
                                    <th>Year Of Passing</th>
                                    <th>Percentage</th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td class="view-client-service">

                                        <?= htmlspecialchars(
                                            $row['post_graduation'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['post_graduation_year_of_passing'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['post_graduation_percentage'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- =================================================
                     EXPERIENCE AND SKILLS
                ================================================== -->

                <div class="view-client-section">

                    <div class="view-client-section-header">

                        <h3>
                            💼 Experience / Skills
                        </h3>

                    </div>


                    <div class="view-clients-table-wrapper">

                        <table class="view-clients-table">

                            <thead>

                                <tr>

                                    <th>Experience</th>
                                    <th>Experience Description</th>
                                    <th>Skills</th>
                                    <th>Resume</th>

                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['experience'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['experience_description'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['skills'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <td>

                                        <?php if (!empty($row['resume'])) { ?>

                                            <a
                                                href="uploads/resumes/<?= urlencode($row['resume']); ?>"
                                                target="_blank"
                                                class="view-client-update-btn"
                                            >
                                                View Resume
                                            </a>

                                        <?php } else { ?>

                                            <span class="view-client-no-data">
                                                No Resume
                                            </span>

                                        <?php } ?>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- =================================================
                     ACTION BUTTONS
                ================================================== -->

                <div class="view-client-details-actions">

                    <a
                        href="agreement-sent.php?id=<?= urlencode($Id); ?>"
                        class="view-client-action-btn view-client-success-btn"
                    >
                        PCA Agreed
                    </a>


                    <a
                        href="not-interested-candidate.php?id=<?= urlencode($Id); ?>"
                        class="view-client-action-btn view-client-danger-btn"
                        onclick="return confirm('Are you sure you want to mark this student as not interested?');"
                    >
                        PCA Not Agreed
                    </a>


                    <a
                        href="update-registration.php?id=<?= urlencode($Id); ?>"
                        class="view-client-action-btn view-client-warning-btn"
                    >
                        Update
                    </a>

                </div>


            </div>

        </div>

    </section>

</section>


<!-- =========================================================
     EXISTING JS
========================================================= -->

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>


</body>
</html>