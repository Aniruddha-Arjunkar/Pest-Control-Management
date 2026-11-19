
<?php
session_start();
ob_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

/* =========================
   LOGOUT
========================= */
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location: login.php');
    exit();
}

/* =========================
   DATABASE
========================= */
require_once 'db_connect.php';
include 'header.php';

$client_Id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;


/* =========================
   CLEAR OLD FORM DATA
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['pending_form_data']);
}


/* =========================
   FETCH CLIENT
========================= */
$client = [];
$due_date = '';

if ($client_Id > 0) {

    $sql = "SELECT * FROM tbl_client WHERE Id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_Id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {

        $_SESSION['error'] = "Client not found.";

        header('Location: error.php');
        exit();
    }

    $client = $result->fetch_assoc();

    $due_date = $client['due_date'];
}


/* =========================
   GET LAST SERVICE DATE
========================= */

$sql_last_service = "
    SELECT service_date
    FROM tbl_pending_services
    WHERE client_Id = ?
    ORDER BY service_date DESC
    LIMIT 1
";

$stmt_last_service = $conn->prepare($sql_last_service);
$stmt_last_service->bind_param("i", $client_Id);
$stmt_last_service->execute();

$result_last_service = $stmt_last_service->get_result();

$last_service_date = ($result_last_service->num_rows > 0)
    ? $result_last_service->fetch_assoc()['service_date']
    : $due_date;


/* =========================
   PRELOAD FORM DATA
========================= */

$form_data = $_SESSION['pending_form_data'] ?? [

    'service_date' => $last_service_date,
    'operator_name' => '',
    'monitored_by' => '',
    'description' => ''

];


/* =========================
   FORM SUBMISSION
========================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $client_id = intval($_POST['client_Id']);

    $service_date = trim($_POST['service_date'] ?? '');
    $operator_name = trim($_POST['operator_name'] ?? '');
    $monitored_by = trim($_POST['monitored_by'] ?? '');
    $description = trim($_POST['description'] ?? '');


    /* Store form data */

    $_SESSION['pending_form_data'] = [

        'service_date' => $service_date,
        'operator_name' => $operator_name,
        'monitored_by' => $monitored_by,
        'description' => $description

    ];


    /* =========================
       MARK PENDING
    ========================= */

    if (isset($_POST['mark_pending'])) {


        /* =========================
           CHECK EXISTING PENDING
        ========================= */

        $sql_check_pending = "
            SELECT Id, service_date
            FROM tbl_pending_services
            WHERE client_Id = ?
            AND status = 'Pending'
            LIMIT 1
        ";

        $stmt_check_pending = $conn->prepare($sql_check_pending);

        $stmt_check_pending->bind_param(
            "i",
            $client_id
        );

        $stmt_check_pending->execute();

        $result_pending = $stmt_check_pending->get_result();


        if ($result_pending->num_rows > 0) {

            $existing = $result_pending->fetch_assoc();

            $existing_id = $existing['Id'];


            /* Store data to complete later */

            $_SESSION['complete_later'] = [

                'id' => $existing_id,
                'client_id' => $client_id,
                'service_date' => $service_date,
                'operator_name' => $operator_name,
                'monitored_by' => $monitored_by,
                'description' => $description

            ];


            header(
                "Location: add-new-service.php?client_id=" .
                $client_id
            );

            exit();


        } else {


            /* =========================
               CHECK COMPLETED SERVICE
            ========================= */

            $sql_check_completed = "
                SELECT 1
                FROM tbl_pending_services
                WHERE client_Id = ?
                AND service_date = ?
                AND status = 'Completed'
            ";

            $stmt_check_completed =
                $conn->prepare($sql_check_completed);

            $stmt_check_completed->bind_param(
                "is",
                $client_id,
                $service_date
            );

            $stmt_check_completed->execute();

            $result_completed =
                $stmt_check_completed->get_result();


            if ($result_completed->num_rows > 0) {

                $error_message =
                    "Service already exists on this date and is marked as completed.";

            } else {


                /* =========================
                   STORE COMPLETE LATER
                ========================= */

                $_SESSION['complete_later'] = [

                    'client_id' => $client_id,
                    'service_date' => $service_date,
                    'operator_name' => $operator_name,
                    'monitored_by' => $monitored_by,
                    'description' => $description

                ];


                header(
                    "Location: add-new-service.php?client_id=" .
                    $client_id
                );

                exit();
            }
        }
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

                    <span class="phone-icon">📋</span>

                    <div>

                        <h2>Add Today's Work</h2>

                        <p>
                            Add customer service work details
                        </p>

                    </div>

                </div>

            </div>


            <!-- =================================
                 FORM
            ================================== -->

            <form
                action=""
                method="POST"
                class="refollowup-form"
            >


                <!-- CLIENT -->

                <?php if (!empty($client['cust_name'])): ?>

                    <div class="client-info">

                        Client Name :

                        <strong>
                            <?= htmlspecialchars(
                                $client['cust_name']
                            ); ?>
                        </strong>

                    </div>

                <?php endif; ?>


                <!-- ERROR -->

                <?php if (isset($error_message)): ?>

                    <div class="error-message">

                        <?= htmlspecialchars(
                            $error_message
                        ); ?>

                    </div>

                <?php endif; ?>


                <!-- CLIENT ID -->

                <input
                    type="hidden"
                    name="client_Id"
                    value="<?= htmlspecialchars($client_Id); ?>"
                >


                <!-- SERVICE DATE -->

                <div class="form-group">

                    <label for="service_date">

                        Service Date
                        <span>*</span>

                    </label>

                    <input
                        type="date"
                        name="service_date"
                        id="service_date"
                        value="<?= htmlspecialchars(
                            $form_data['service_date'] ?? ''
                        ); ?>"
                        required
                    >

                </div>


                <!-- OPERATOR -->

                <div class="form-group">

                    <label for="operator_name">

                        Operator Name
                        <span>*</span>

                    </label>

                    <input
                        type="text"
                        name="operator_name"
                        id="operator_name"
                        placeholder="Enter operator name"
                        value="<?= htmlspecialchars(
                            $form_data['operator_name'] ?? ''
                        ); ?>"
                        required
                    >

                </div>


                <!-- MONITORED BY -->

                <div class="form-group">

                    <label for="monitored_by">

                        Monitored By
                        <span>*</span>

                    </label>

                    <input
                        type="text"
                        name="monitored_by"
                        id="monitored_by"
                        placeholder="Enter monitored by"
                        value="<?= htmlspecialchars(
                            $form_data['monitored_by'] ?? ''
                        ); ?>"
                        required
                    >

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="description">

                        Description
                        <span>*</span>

                    </label>

                    <textarea
                        name="description"
                        id="description"
                        placeholder="Enter service description"
                        rows="4"
                        required
                    ><?= htmlspecialchars(
                        $form_data['description'] ?? ''
                    ); ?></textarea>

                </div>


                <!-- SUBMIT -->

                <div class="form-group">

                    <button
                        type="submit"
                        name="mark_pending"
                        class="submit-btn"
                    >

                        📋 Mark as Pending / Complete

                    </button>

                </div>

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

ob_end_flush();

?>

