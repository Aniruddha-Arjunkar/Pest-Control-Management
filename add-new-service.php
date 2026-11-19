<?php
session_start();
ob_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

$client_Id = isset($_GET['client_id']) ? (int) $_GET['client_id'] : 0;
$back = $_GET['back'] ?? '';

if ($client_Id <= 0) {
    $_SESSION['error'] = "Invalid client ID.";
    header('Location: error.php');
    exit();
}

/* =========================
   GET CLIENT DETAILS
========================= */

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

$client_name = $client['cust_name'] ?? $client['name'] ?? 'Client';


/* =========================
   HANDLE FORM SUBMISSION
========================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $service_date = trim($_POST['service_date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $status = 'Pending';


    /* =========================
       INSERT NEW PENDING SERVICE
    ========================= */

    $sql_insert = "
        INSERT INTO tbl_pending_services
        (client_Id, service_date, description, status)
        VALUES (?, ?, ?, ?)
    ";

    $stmt_insert = $conn->prepare($sql_insert);

    $stmt_insert->bind_param(
        "isss",
        $client_Id,
        $service_date,
        $description,
        $status
    );


    if ($stmt_insert->execute()) {


        /* =========================
           HANDLE COMPLETE LATER
        ========================= */

        if (isset($_SESSION['complete_later'])) {

            $prev = $_SESSION['complete_later'];

            $status_completed = 'Completed';

            $existing_id = $prev['id'] ?? 0;


            /* =========================
               EXISTING PENDING SERVICE
            ========================= */

            if ($existing_id > 0) {

                $sql_check = "
                    SELECT Id, status
                    FROM tbl_pending_services
                    WHERE Id = ?
                    AND client_Id = ?
                    LIMIT 1
                ";

                $stmt_check = $conn->prepare($sql_check);

                $stmt_check->bind_param(
                    "ii",
                    $existing_id,
                    $client_Id
                );

                $stmt_check->execute();

                $result_check = $stmt_check->get_result();


                if ($result_check->num_rows > 0) {

                    $existing = $result_check->fetch_assoc();

                    $existing_status = $existing['status'];


                    if ($existing_status === 'Pending') {


                        /* =========================
                           UPDATE PENDING → COMPLETED
                        ========================= */

                        $sql_update = "
                            UPDATE tbl_pending_services
                            SET
                                operator_name = ?,
                                monitored_by = ?,
                                service_date = ?,
                                description = ?,
                                status = 'Completed'
                            WHERE Id = ?
                        ";

                        $stmt_update = $conn->prepare($sql_update);

                        $stmt_update->bind_param(
                            "ssssi",
                            $prev['operator_name'],
                            $prev['monitored_by'],
                            $prev['service_date'],
                            $prev['description'],
                            $existing_id
                        );

                        $stmt_update->execute();


                        /* =========================
                           INSERT SERVICE MATERIAL
                        ========================= */

                        $sql_material = "
                            INSERT INTO service_materials
                            (
                                client_id,
                                used_date,
                                remarks,
                                quantity_unit,
                                branch_id
                            )
                            VALUES (?, ?, ?, ?, ?)
                        ";

                        $stmt_material = $conn->prepare($sql_material);

                        $used_date = $prev['service_date'];
                        $remarks = $prev['description'];
                        $quantity_unit = 'N/A';
                        $branch_id = 0;

                        $stmt_material->bind_param(
                            "isssi",
                            $client_Id,
                            $used_date,
                            $remarks,
                            $quantity_unit,
                            $branch_id
                        );

                        $stmt_material->execute();
                    }
                }


            } else {


                /* =========================
                   INSERT NEW COMPLETED SERVICE
                ========================= */

                $sql_insert_completed = "
                    INSERT INTO tbl_pending_services
                    (
                        client_Id,
                        service_date,
                        operator_name,
                        monitored_by,
                        description,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ";

                $stmt_insert_completed =
                    $conn->prepare($sql_insert_completed);

                $stmt_insert_completed->bind_param(
                    "isssss",
                    $client_Id,
                    $prev['service_date'],
                    $prev['operator_name'],
                    $prev['monitored_by'],
                    $prev['description'],
                    $status_completed
                );

                $stmt_insert_completed->execute();


                /* =========================
                   INSERT SERVICE MATERIAL
                ========================= */

                $sql_material = "
                    INSERT INTO service_materials
                    (
                        client_id,
                        used_date,
                        remarks,
                        quantity_unit,
                        branch_id
                    )
                    VALUES (?, ?, ?, ?, ?)
                ";

                $stmt_material = $conn->prepare($sql_material);

                $used_date = $prev['service_date'];
                $remarks = $prev['description'];
                $quantity_unit = 'N/A';
                $branch_id = 0;

                $stmt_material->bind_param(
                    "isssi",
                    $client_Id,
                    $used_date,
                    $remarks,
                    $quantity_unit,
                    $branch_id
                );

                $stmt_material->execute();
            }


            /* =========================
               CLEAR SESSION
            ========================= */

            unset($_SESSION['complete_later']);
        }


        unset($_SESSION['pending_form_data']);

        $_SESSION['msg'] =
            "New pending service added successfully.";

        header(
            "Location: view-pending-service.php?client_id=" . $client_Id
        );

        exit();


    } else {

        $_SESSION['error'] =
            "Error adding service: " . $stmt_insert->error;
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

                            <h2>Add New Service</h2>

                            <p>
                                Add pending service details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     CLIENT NAME
                ================================== -->

                <div class="form-group">

                    <label>
                        Client Name
                    </label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($client_name); ?>"
                        readonly
                    >

                </div>


                <!-- =================================
                     ERROR MESSAGE
                ================================== -->

                <?php if (isset($_SESSION['error'])): ?>

                    <div class="alert alert-danger">

                        <?= htmlspecialchars($_SESSION['error']); ?>

                    </div>

                    <?php unset($_SESSION['error']); ?>

                <?php endif; ?>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    method="POST"
                    action="add-new-service.php?client_id=<?= $client_Id ?>&back=<?= htmlspecialchars($back) ?>"
                    class="refollowup-form"
                >


                    <!-- SERVICE DATE -->

                    <div class="form-group">

                        <label>
                            Service Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            name="service_date"
                            value="<?= htmlspecialchars($_POST['service_date'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label>
                            Description <span>*</span>
                        </label>

                        <textarea
                            name="description"
                            placeholder="Description"
                            rows="4"
                            required
                        ><?= htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        <span>✓</span>

                        Add Service

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
ob_end_flush();
?>