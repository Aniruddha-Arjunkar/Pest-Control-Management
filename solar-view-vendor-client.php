<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================================================
   LOGIN CHECK
========================================================= */
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   GET VENDOR ID
========================================================= */
$vendor_id = (int)($_GET['id'] ?? 0);

if ($vendor_id <= 0) {
    echo "<script>alert('Invalid Vendor ID'); window.location.href='view-vendor.php';</script>";
    exit();
}

/* =========================================================
   FETCH VENDOR DETAILS
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM tbl_solar_vendor WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $vendor_id);
mysqli_stmt_execute($stmt);

$vendor_result = mysqli_stmt_get_result($stmt);
$vendor = mysqli_fetch_assoc($vendor_result);

mysqli_stmt_close($stmt);

if (!$vendor) {
    echo "<script>alert('Vendor not found!'); window.location.href='view-vendor.php';</script>";
    exit();
}

/* =========================================================
   FETCH VENDOR CLIENTS
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM tbl_solar_client
     WHERE added_by = 'vendor' AND vendor_id = ?
     ORDER BY id DESC"
);

mysqli_stmt_bind_param($stmt, "i", $vendor_id);
mysqli_stmt_execute($stmt);

$clients_query = mysqli_stmt_get_result($stmt);

$total_clients = mysqli_num_rows($clients_query);

include('header.php');
?>

<section id="main-content">
<section class="wrapper">

<div class="inquiry-page">

    <!-- =================================================
         PAGE HEADER
    ================================================== -->
    <div class="inquiry-page-header">

        <div>
            <h2>Vendor Details</h2>
            <p>Vendor information and clients added by this vendor</p>
        </div>

        <a href="view-vendor.php" class="action-btn primary2">
            <i class="fa fa-arrow-left"></i> Back to Vendors
        </a>

    </div>

    <!-- =================================================
         VENDOR INFORMATION
    ================================================== -->
    <div class="inquiry-card mb-4">

        <div class="refollowup-header">
            <h3 class="refollowup-title">
                <i class="fa fa-user"></i> Vendor Information
            </h3>
        </div>

        <div class="p-3">

            <div class="row">

                <div class="col-md-3 mb-3">
                    <strong>Vendor Name</strong>
                    <p><?= e($vendor['vendor_name']); ?></p>
                </div>

                <div class="col-md-3 mb-3">
                    <strong>Phone</strong>
                    <p><?= e($vendor['phone']); ?></p>
                </div>

                <div class="col-md-3 mb-3">
                    <strong>Email</strong>
                    <p><?= e($vendor['email'] ?: '-'); ?></p>
                </div>

                <div class="col-md-3 mb-3">
                    <strong>Address</strong>
                    <p><?= nl2br(e($vendor['address'])); ?></p>
                </div>

            </div>

        </div>

    </div>

    <!-- =================================================
         CLIENTS HEADER
    ================================================== -->
    <div class="inquiry-page-header">

        <div>
            <h3>Clients Added by This Vendor</h3>
            <p>All clients registered under this vendor</p>
        </div>

        <div class="inquiry-count">
            Total Clients: <?= $total_clients; ?>
        </div>

    </div>

    <!-- =================================================
         CLIENT TABLE
    ================================================== -->
    <div class="inquiry-card">

        <div class="inquiry-table-wrapper">

            <table class="inquiry-table">

                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Balance</th>
                        <th>Payments</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $serial = 1;

                if ($total_clients > 0) {

                    while ($client = mysqli_fetch_assoc($clients_query)) {

                        $client_id = (int)$client['id'];

                        /* =====================================
                           PAYMENT SUMMARY
                        ===================================== */
                        $payment_stmt = mysqli_prepare(
                            $conn,
                            "SELECT
                                COALESCE(SUM(paid_amount), 0) AS total_paid
                             FROM tbl_solar_payment
                             WHERE client_id = ?"
                        );

                        mysqli_stmt_bind_param(
                            $payment_stmt,
                            "i",
                            $client_id
                        );

                        mysqli_stmt_execute($payment_stmt);

                        $payment_result = mysqli_stmt_get_result($payment_stmt);
                        $payment_summary = mysqli_fetch_assoc($payment_result);

                        mysqli_stmt_close($payment_stmt);

                        $total_paid = (float)($payment_summary['total_paid'] ?? 0);

                        /* =====================================
                           GST CALCULATION
                           Same as old vendor page: 18%
                        ===================================== */
                        $base_input = (float)($client['total_amount'] ?? 0);
                        $gst_rate = 0.18;

                        if ((int)$client['apply_gst'] === 1) {

                            if ($client['gst_type'] === 'inclusive') {

                                $grand_total = $base_input;
                                $base_amount = $grand_total / (1 + $gst_rate);
                                $gst_amount = $grand_total - $base_amount;

                            } else {

                                $base_amount = $base_input;
                                $gst_amount = $base_amount * $gst_rate;
                                $grand_total = $base_amount + $gst_amount;
                            }

                        } else {

                            $base_amount = $base_input;
                            $gst_amount = 0;
                            $grand_total = $base_input;
                        }

                        $balance = max(
                            0,
                            round($grand_total - $total_paid, 2)
                        );

                        /* =====================================
                           FETCH PAYMENT HISTORY
                        ===================================== */
                        $history_stmt = mysqli_prepare(
                            $conn,
                            "SELECT *
                             FROM tbl_solar_payment
                             WHERE client_id = ?
                             ORDER BY payment_date DESC, id DESC"
                        );

                        mysqli_stmt_bind_param(
                            $history_stmt,
                            "i",
                            $client_id
                        );

                        mysqli_stmt_execute($history_stmt);

                        $payments = mysqli_stmt_get_result($history_stmt);

                        $payment_count = mysqli_num_rows($payments);
                ?>

                    <tr>

                        <td class="inquiry-sr">
                            <?= $serial++; ?>
                        </td>

                        <td class="inquiry-name">
                            <a href="solar-view-client-detail.php?client_id=<?= $client_id; ?>">
                                <?= e($client['cust_name']); ?>
                            </a>
                        </td>

                        <td class="inquiry-phone">
                            <?= e($client['cust_phone']); ?>
                        </td>

                        <td>
                            <strong>
                                ₹<?= number_format($balance, 2); ?>
                            </strong>
                        </td>

                        <td>
                            <button
                                type="button"
                                class="action-btn primary1"
                                onclick="togglePayment(<?= $client_id; ?>)"
                            >
                                <i class="fa fa-money"></i>
                                View Payment (<?= $payment_count; ?>)
                            </button>
                        </td>

                        <td class="inquiry-action-cell">

                            <a
                                href="solar-add-invoice.php?client_id=<?= $client_id; ?>"
                                class="action-btn primary2"
                            >
                                <i class="fa fa-file-text"></i>
                                Add GST Invoice
                            </a>

                        </td>

                    </tr>

                    <!-- =====================================
                         EXPANDABLE PAYMENT DETAILS
                    ====================================== -->
                    <tr
                        id="paymentRow<?= $client_id; ?>"
                        style="display:none;"
                    >

                        <td colspan="6">

                            <div class="inquiry-card mt-3 mb-3">

                                <div class="refollowup-header">
                                    <h4 class="refollowup-title">
                                        Payment Details -
                                        <?= e($client['cust_name']); ?>
                                    </h4>
                                </div>

                                <div class="p-3">

                                    <!-- AMOUNT SUMMARY -->
                                    <div class="row mb-3">

                                        <div class="col-md-3 mb-3">
                                            <strong>Base Amount:</strong>
                                            <p>
                                                ₹<?= number_format($base_amount, 2); ?>
                                            </p>
                                        </div>

                                        <?php if ((int)$client['apply_gst'] === 1) { ?>

                                            <div class="col-md-3 mb-3">
                                                <strong>GST (18%):</strong>
                                                <p>
                                                    ₹<?= number_format($gst_amount, 2); ?>
                                                </p>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <strong>Grand Total:</strong>
                                                <p>
                                                    ₹<?= number_format($grand_total, 2); ?>
                                                </p>
                                            </div>

                                        <?php } else { ?>

                                            <div class="col-md-3 mb-3">
                                                <strong>Total Amount:</strong>
                                                <p>
                                                    ₹<?= number_format($grand_total, 2); ?>
                                                </p>
                                            </div>

                                        <?php } ?>

                                        <div class="col-md-3 mb-3">
                                            <strong>Total Paid:</strong>
                                            <p>
                                                ₹<?= number_format($total_paid, 2); ?>
                                            </p>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <strong>Balance:</strong>
                                            <p>
                                                ₹<?= number_format($balance, 2); ?>
                                            </p>
                                        </div>

                                    </div>

                                    <!-- PAYMENT HISTORY TABLE -->
                                    <div class="inquiry-table-wrapper">

                                        <table class="inquiry-table">

                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>Payment Date</th>
                                                    <th>Paid Amount</th>
                                                    <th>Payment Gateway</th>
                                                </tr>
                                            </thead>

                                            <tbody>

                                            <?php
                                            $srno = 1;

                                            if ($payment_count > 0) {

                                                while ($payment = mysqli_fetch_assoc($payments)) {
                                            ?>

                                                <tr>
                                                    <td><?= $srno++; ?></td>

                                                    <td>
                                                        <?= !empty($payment['payment_date'])
                                                            ? date('d-m-Y', strtotime($payment['payment_date']))
                                                            : '-'; ?>
                                                    </td>

                                                    <td>
                                                        ₹<?= number_format((float)$payment['paid_amount'], 2); ?>
                                                    </td>

                                                    <td>
                                                        <?= e($payment['payment_gateway'] ?: '-'); ?>
                                                    </td>
                                                </tr>

                                            <?php
                                                }

                                            } else {
                                            ?>

                                                <tr>
                                                    <td colspan="4" class="text-center">
                                                        No Payment Records Found
                                                    </td>
                                                </tr>

                                            <?php } ?>

                                            </tbody>

                                        </table>

                                    </div>

                                </div>

                            </div>

                        </td>

                    </tr>

                <?php
                        mysqli_stmt_close($history_stmt);
                    }

                } else {
                ?>

                    <tr>
                        <td colspan="6" class="text-center">
                            No clients added by this vendor.
                        </td>
                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

    <div class="mt-3">
        <a href="view-vendor.php" class="action-btn primary2">
            <i class="fa fa-arrow-left"></i> Back to Vendors
        </a>
    </div>

</div>

</section>
</section>

<script>
function togglePayment(id) {

    const row = document.getElementById('paymentRow' + id);

    if (!row) return;

    if (row.style.display === 'none' || row.style.display === '') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>

<?php
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

<!-- FOOTER SCRIPTS -->
<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>

</body>
</html>
