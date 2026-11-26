
<?php
ob_start();
session_start();

/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

/* =========================================================
   LOGOUT
========================================================= */

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

/* =========================================================
   DATABASE
========================================================= */

require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$message = "";

/* =========================================================
   GET VENDOR ID
========================================================= */

$vendor_id = (int)($_GET['vendor_id'] ?? $_POST['vendor_id'] ?? 0);

if ($vendor_id <= 0) {
    die("Invalid Vendor ID");
}

/* =========================================================
   FETCH VENDOR DATA
========================================================= */

$vendorStmt = $conn->prepare("
    SELECT id, vendor_name
    FROM tbl_solar_vendor
    WHERE id = ?
    LIMIT 1
");

$vendorStmt->bind_param("i", $vendor_id);
$vendorStmt->execute();

$vendorResult = $vendorStmt->get_result();
$vendor = $vendorResult->fetch_assoc();

$vendorStmt->close();

if (!$vendor) {
    die("Vendor Not Found");
}

/* =========================================================
   FORM SUBMIT HANDLE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_client'])) {

    $name         = trim($_POST['client_name'] ?? '');
    $mobile       = trim($_POST['mobile'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $work         = trim($_POST['work'] ?? '');
    $total_amount = (float)($_POST['total_amount'] ?? 0);
    $service_date = trim($_POST['service_date'] ?? '');

    $added_by = 'vendor';

    if (
        $name === '' ||
        $mobile === '' ||
        $service_date === '' ||
        $total_amount <= 0
    ) {

        $message = "Please fill all required fields correctly.";

    } else {

        /* =========================================
           START TRANSACTION
        ========================================= */

        $conn->begin_transaction();

        try {

            /* =====================================
               INSERT SOLAR CLIENT
            ===================================== */

            $insertClient = $conn->prepare("
                INSERT INTO tbl_solar_client
                (
                    cust_name,
                    cust_phone,
                    cust_email,
                    cust_address,
                    service,
                    total_amount,
                    service_date,
                    added_by,
                    vendor_id
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertClient->bind_param(
                "sssss dssi",
                $name,
                $mobile,
                $email,
                $address,
                $work,
                $total_amount,
                $service_date,
                $added_by,
                $vendor_id
            );

            $insertClient->execute();

            $client_id = $conn->insert_id;

            $insertClient->close();

            /* =====================================
               INSERT SOLAR DUE PAYMENT
            ===================================== */

            $paid_amount = 0;
            $due_amount  = $total_amount;

            $paymentDate = date('Y-m-d');

            $insertPayment = $conn->prepare("
                INSERT INTO tbl_solar_payment
                (
                    client_id,
                    vendor_id,
                    total_amount,
                    paid_amount,
                    due_amount,
                    payment_date,
                    due_date
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $insertPayment->bind_param(
                "iidddss",
                $client_id,
                $vendor_id,
                $total_amount,
                $paid_amount,
                $due_amount,
                $paymentDate,
                $service_date
            );

            $insertPayment->execute();
            $insertPayment->close();

            /* =====================================
               COMMIT
            ===================================== */

            $conn->commit();

            $_SESSION['status'] = "Vendor Client Added Successfully!";
            $_SESSION['status_code'] = "success";

            header("Location: view-vendor-client.php?id=" . $vendor_id);
            exit();

        } catch (Exception $e) {

            $conn->rollback();

            $message = "Error: " . $e->getMessage();
        }
    }
}

/* =========================================================
   HEADER
========================================================= */

include('header.php');
?>

<section id="main-content">
<section class="wrapper">

<div class="reg-w3">
    <div class="refollowup-card">

        <div class="refollowup-header">

            <h2 class="refollowup-title">
                Add Client for Vendor
            </h2>
            
            <p>
                Vendor Name:
                <strong><?= e($vendor['vendor_name']); ?></strong>
            </p>

        </div>

        <?php if ($message !== '') { ?>
            <div class="alert alert-danger">
                <?= e($message); ?>
            </div>
        <?php } ?>

        <form
            method="POST"
            action="add-vendor-client.php?vendor_id=<?= $vendor_id; ?>"
            class="refollowup-form"
        >

            <input
                type="hidden"
                name="vendor_id"
                value="<?= $vendor_id; ?>"
            >

            <h5 class="text-primary">Client Details</h5>
            <hr>

            <div class="form-row">

                <div class="form-group">
                    <label>Client Name <span style="color:red;">*</span></label>

                    <input
                        type="text"
                        name="client_name"
                        placeholder="Enter Client Name"
                        value="<?= e($_POST['client_name'] ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Mobile Number <span style="color:red;">*</span></label>

                    <input
                        type="text"
                        name="mobile"
                        placeholder="Enter Mobile Number"
                        value="<?= e($_POST['mobile'] ?? ''); ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        placeholder="Enter Email"
                        value="<?= e($_POST['email'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Total Amount <span style="color:red;">*</span></label>

                    <input
                        type="number"
                        name="total_amount"
                        placeholder="Enter Total Amount"
                        min="0.01"
                        step="0.01"
                        value="<?= e($_POST['total_amount'] ?? ''); ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Address</label>

                    <textarea
                        name="address"
                        placeholder="Enter Client Address"
                    ><?= e($_POST['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Work to Do</label>

                    <textarea
                        name="work"
                        placeholder="Enter Work Details"
                    ><?= e($_POST['work'] ?? ''); ?></textarea>
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Service Date <span style="color:red;">*</span></label>

                    <input
                        type="date"
                        name="service_date"
                        value="<?= e($_POST['service_date'] ?? date('Y-m-d')); ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-group">

                <button
                    type="submit"
                    name="save_client"
                    class="submit-btn"
                >
                    Submit Client
                </button>

            </div>

        </form>

    </div>
</div>

</section>
</section>

<!-- PAGE SCRIPTS -->

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
$conn->close();
ob_end_flush();
?>
