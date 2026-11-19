<?php
session_start();
ob_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: login.php');
    exit();
}

/* =========================
   LOGOUT
========================= */
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('Location: login.php');
    exit();
}

/* =========================
   DATABASE
========================= */
require_once 'db_connect.php';
include 'header.php';

/* =========================
   GET SERVICE ID
========================= */
if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    $service_id = (int) $_GET['id'];

} else {

    $_SESSION['error'] = "Invalid service ID.";
    header('Location: view-pending-service.php');
    exit();
}

/* =========================
   FETCH SERVICE DETAILS
========================= */

$sql_service = "
    SELECT *
    FROM tbl_pending_services
    WHERE Id = ?
";

$stmt_service = $conn->prepare($sql_service);
$stmt_service->bind_param('i', $service_id);
$stmt_service->execute();

$result_service = $stmt_service->get_result();
$service = $result_service->fetch_assoc();

/* =========================
   SERVICE NOT FOUND
========================= */

if (!$service) {

    $_SESSION['error'] = "Service not found.";
    header('Location: view-pending-service.php');
    exit();
}

/* =========================
   UPDATE SERVICE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $service_date = $_POST['service_date'] ?? '';
    $description  = trim($_POST['description'] ?? '');

    /* =========================
       VALIDATION
    ========================= */

    if (!$service_date || !$description) {

        $_SESSION['error'] = "All fields are required.";

        header(
            'Location: update-service.php?id=' . $service_id
        );

        exit();
    }

    /* =========================
       UPDATE DATABASE
    ========================= */

    $sql_update = "
        UPDATE tbl_pending_services
        SET
            service_date = ?,
            description = ?
        WHERE Id = ?
    ";

    $stmt_update = $conn->prepare($sql_update);

    $stmt_update->bind_param(
        'ssi',
        $service_date,
        $description,
        $service_id
    );

    if ($stmt_update->execute()) {

        $_SESSION['success'] = "Service updated successfully.";

        header('Location: view-pending-service.php');
        exit();

    } else {

        $_SESSION['error'] = "Failed to update the service.";

        header(
            'Location: update-service.php?id=' . $service_id
        );

        exit();
    }
}
?>


<!-- =========================================
     MAIN CONTENT
========================================= -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">


                <!-- =================================
                     HEADER
                ================================== -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">🛠️</span>

                        <div>

                            <h2>Update Service</h2>

                            <p>
                                Update service date and description
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     ERROR MESSAGE
                ================================== -->

                <?php if (isset($_SESSION['error'])): ?>

                    <div class="alert alert-danger custom-alert">

                        <?= htmlspecialchars($_SESSION['error']); ?>

                    </div>

                    <?php unset($_SESSION['error']); ?>

                <?php endif; ?>


                <!-- =================================
                     SUCCESS MESSAGE
                ================================== -->

                <?php if (isset($_SESSION['success'])): ?>

                    <div class="alert alert-success custom-alert">

                        <?= htmlspecialchars($_SESSION['success']); ?>

                    </div>

                    <?php unset($_SESSION['success']); ?>

                <?php endif; ?>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    action="update-service.php?id=<?= $service_id; ?>"
                    method="post"
                    class="refollowup-form"
                >


                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label>
                            Description <span>*</span>
                        </label>

                        <textarea
                            name="description"
                            id="description"
                            placeholder="Enter service description"
                            rows="5"
                            required
                        ><?= htmlspecialchars($service['description'] ?? ''); ?></textarea>

                    </div>


                    <!-- SERVICE DATE -->

                    <div class="form-group">

                        <label>
                            Service Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            name="service_date"
                            id="service_date"
                            value="<?= htmlspecialchars($service['service_date'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        <span>✓</span>

                        Update Service

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>



<!-- =========================================
     JAVASCRIPT
========================================= -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>

</body>
</html>

<?php
mysqli_close($conn);
ob_end_flush();
?>