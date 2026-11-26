<?php
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

/* =========================================================
   CLIENT ID
========================================================= */
$client_id = (int)($_GET['client_id'] ?? 0);

if ($client_id <= 0) {
    die("Invalid Client ID");
}

/* =========================================================
   FETCH SOLAR CLIENT
========================================================= */
$clientStmt = mysqli_prepare(
    $conn,
    "SELECT * FROM tbl_solar_client WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($clientStmt, "i", $client_id);
mysqli_stmt_execute($clientStmt);

$client = mysqli_fetch_assoc(mysqli_stmt_get_result($clientStmt));

mysqli_stmt_close($clientStmt);

if (!$client) {
    die("Solar Client Not Found");
}

/* =========================================================
   PAYMENT SUMMARY
========================================================= */
$paymentStmt = mysqli_prepare(
    $conn,
    "SELECT
        SUM(paid_amount) AS total_paid,
        MAX(due_date) AS last_due_date
     FROM tbl_solar_payment
     WHERE client_id = ?"
);

mysqli_stmt_bind_param($paymentStmt, "i", $client_id);
mysqli_stmt_execute($paymentStmt);

$payment_summary = mysqli_fetch_assoc(
    mysqli_stmt_get_result($paymentStmt)
);

mysqli_stmt_close($paymentStmt);

/* =========================================================
   PAYMENT CALCULATION
========================================================= */
$base_input = (float)($client['total_amount'] ?? 0);
$gst_rate = (float)($client['gst_rate'] ?? 18);

if (!in_array($gst_rate, [5.0, 18.0], true)) {
    $gst_rate = 18.0;
}

$gst_amount = 0;
$grand_total = $base_input;
$base_amount = $base_input;

if ((int)($client['apply_gst'] ?? 0) === 1) {

    if (($client['gst_type'] ?? 'exclusive') === 'inclusive') {

        $grand_total = $base_input;

        $base_amount = round(
            $grand_total / (1 + ($gst_rate / 100)),
            2
        );

        $gst_amount = round(
            $grand_total - $base_amount,
            2
        );

    } else {

        $base_amount = $base_input;

        $gst_amount = round(
            $base_amount * $gst_rate / 100,
            2
        );

        $grand_total = round(
            $base_amount + $gst_amount,
            2
        );
    }
}

$total_paid = (float)($payment_summary['total_paid'] ?? 0);
$balance = round($grand_total - $total_paid, 2);

$is_trader = (($client['added_by'] ?? '') === 'trader');

/* =========================================================
   FETCH PAYMENT HISTORY
========================================================= */
$paymentHistoryStmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM tbl_solar_payment
     WHERE client_id = ?
     ORDER BY payment_date DESC, id DESC"
);

mysqli_stmt_bind_param($paymentHistoryStmt, "i", $client_id);
mysqli_stmt_execute($paymentHistoryStmt);

$payments = mysqli_stmt_get_result($paymentHistoryStmt);

/* =========================================================
   FETCH INVOICE
========================================================= */
$invoiceStmt = mysqli_prepare(
    $conn,
    "SELECT gst_invoice_no, created_at, pdf_file
     FROM tbl_solar_invoice
     WHERE client_id = ?
     ORDER BY id DESC
     LIMIT 1"
);

if (!$invoiceStmt) {
    die("Invoice Query Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($invoiceStmt, "i", $client_id);
mysqli_stmt_execute($invoiceStmt);

$invoice = mysqli_fetch_assoc(
    mysqli_stmt_get_result($invoiceStmt)
);

/* =========================================================
   DOCUMENTS
========================================================= */
$documents = !empty($client['documents'])
    ? json_decode($client['documents'], true)
    : [];

if (!is_array($documents)) {
    $documents = [];
}

/* =========================================================
   FIXED PENDING WORKS
========================================================= */
$fixed_works = [
    "Structure Installation",
    "Panel Fitting",
    "Wiring & Inverter Installation",
    "Released Order",
    "Generation Meter Fitting and Net Metering"
];

/* =========================================================
   HEADER
========================================================= */
include('header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<section id="main-content">
<section class="wrapper">

<div class="inquiry-page">

    <!-- PAGE HEADER -->
    <div class="inquiry-page-header">

        <h1>☀️ Solar Client Details</h1>

        <p>
            View and manage Solar client information

            <span class="inquiry-count">
                Client ID: <?= $client_id; ?>
            </span>
        </p>

    </div>

    <!-- CLIENT DETAILS -->
    <div class="inquiry-card">

        <div class="inquiry-page-header">
            <h2>Client Details</h2>
        </div>

        <div class="card-body">

            <p><strong>Name:</strong> <?= e($client['cust_name']); ?></p>

            <p><strong>Phone:</strong> <?= e($client['cust_phone']); ?></p>

            <p><strong>Email:</strong> <?= e($client['cust_email']); ?></p>

            <p>
                <strong>Address:</strong>
                <?= nl2br(e($client['cust_address'])); ?>
            </p>

            <a href="solar-update-client.php?client_id=<?= (int)$client['id']; ?>"
               class="action-btn primary1">
                Update Client
            </a>

            <button
                type="button"
                class="action-btn primary2"
                onclick="openUploadPopup()">

                <i class="fa-solid fa-file-upload"></i>
                Upload Docs

            </button>

            <button
                type="button"
                class="action-btn primary4"
                id="toggleDocumentsButton">

                Uploaded Documents
                <i class="fas fa-chevron-down" id="toggleIcon"></i>

            </button>

            <!-- DOCUMENT LIST -->
            <div class="mt-3" id="documents-list">

                <div id="documentsContent" style="display:none;">

                    <?php if (empty($documents)) { ?>

                        <p class="text-muted">
                            No Documents Uploaded
                        </p>

                    <?php } else { ?>

                        <ul class="list-group list-group-flush">

                            <?php foreach ($documents as $doc) {

                                $fileName = basename($doc);
                                $filePath = "uploads/solar_client_docs/" . rawurlencode($fileName);

                                $displayName = $fileName;

                                if (strpos($displayName, '_') !== false) {
                                    $displayName = substr(
                                        $displayName,
                                        strpos($displayName, '_') + 1
                                    );
                                }
                            ?>

                                <li class="list-group-item d-flex justify-content-between align-items-center">

                                    <?= e($displayName); ?>

                                    <a
                                        href="<?= e($filePath); ?>"
                                        target="_blank"
                                        class="action-btn primary2">

                                        View

                                    </a>

                                </li>

                            <?php } ?>

                        </ul>

                    <?php } ?>

                </div>

            </div>

            <!-- GST INVOICE BUTTONS -->
            <?php if ((int)($client['apply_gst'] ?? 0) === 1) { ?>

                <?php if ($invoice) { ?>

                    <?php if (
                        !empty($invoice['pdf_file']) &&
                        file_exists($invoice['pdf_file'])
                    ) { ?>

                        <button
                            type="button"
                            class="action-btn primary1"
                            onclick="window.open('<?= e($invoice['pdf_file']); ?>', '_blank')">

                            <i class="bi bi-eye"></i>
                            View Invoice

                        </button>

                    <?php } else { ?>

                        <a
                            href="solar-generate-invoice.php?client_id=<?= $client_id; ?>&created_at=<?= urlencode($invoice['created_at']); ?>"
                            class="action-btn primary2">

                            <i class="bi bi-receipt"></i>
                            Generate Invoice

                        </a>

                    <?php } ?>

                <?php } else { ?>

                    <a
                        href="solar-add-invoice.php?client_id=<?= $client_id; ?>"
                        class="action-btn primary2">

                        <i class="bi bi-receipt"></i>
                        Generate Invoice

                    </a>

                <?php } ?>

            <?php } ?>

        </div>

    </div>

    <!-- SERVICE / TRADER DETAILS -->
    <div class="inquiry-card">

        <div class="inquiry-page-header">

            <h2>
                <?= $is_trader ? 'Trader Details' : 'Service Details'; ?>
            </h2>

        </div>

        <div class="card-body">

            <?php if ($is_trader) { ?>

                <p>
                    <strong>Description:</strong><br>
                    <?= nl2br(e($client['service_covered'] ?? '')); ?>
                </p>

                <p>
                    <strong>Terms &amp; Conditions:</strong><br>
                    <?= nl2br(e($client['service_term_condition'] ?? '')); ?>
                </p>

                <p>
                    <strong>Amount:</strong>
                    ₹<?= number_format((float)($client['total_amount'] ?? 0), 2); ?>
                </p>

            <?php } else { ?>

                <p>
                    <strong>Service:</strong>
                    <?= e($client['service'] ?? ''); ?>
                </p>

                <p>
                    <strong>Service Date:</strong>
                    <?= e($client['service_date'] ?? ''); ?>
                </p>

                <p>
                    <strong>Warranty:</strong>
                    <?= e($client['warranty'] ?? ''); ?>
                </p>

                <p>
                    <strong>Service Covered:</strong><br>
                    <?= nl2br(e($client['service_covered'] ?? '')); ?>
                </p>

                <p>
                    <strong>Terms &amp; Conditions:</strong><br>
                    <?= nl2br(e($client['service_term_condition'] ?? '')); ?>
                </p>

                <p>
                    <strong>Consumer No:</strong>
                    <?= e($client['consumer_no'] ?? ''); ?>
                </p>

                <p>
                    <strong>Subdivision:</strong>
                    <?= e($client['subdivision'] ?? ''); ?>
                </p>

                <p>
                    <strong>Technician Name:</strong>
                    <?= e($client['technical_name'] ?? ''); ?>
                </p>

            <?php } ?>

        </div>

    </div>

    <!-- PENDING WORK DETAILS -->
    <div class="inquiry-card">

        <div class="inquiry-page-header">
            <h2>Pending Work</h2>
        </div>

        <div class="card-body">

            <div class="inquiry-table-wrapper mt-3">

                <table class="inquiry-table">

                    <thead>
                        <tr>
                            <th>Sr.No.</th>
                            <th>Work Description</th>
                            <th>Assigned To</th>
                            <th>Service Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php

                    $count = 1;

                    foreach ($fixed_works as $work_name) {

                        $stmt = mysqli_prepare($conn, "
                            SELECT *
                            FROM tbl_solar_pending_work
                            WHERE client_id = ?
                            AND work_description = ?
                            LIMIT 1
                        ");

                        mysqli_stmt_bind_param(
                            $stmt,
                            "is",
                            $client_id,
                            $work_name
                        );

                        mysqli_stmt_execute($stmt);

                        $result = mysqli_stmt_get_result($stmt);
                        $row = mysqli_fetch_assoc($result);

                        mysqli_stmt_close($stmt);

                        $assigned_to = $row['assigned_to'] ?? '';
                        $service_date = $row['due_date'] ?? '';
                        $status = $row['status'] ?? 'Pending';

                    ?>

                        <tr class="data-row">

                            <td><?= $count++; ?></td>

                            <td><?= e($work_name); ?></td>

                            <td>
                                <?= e($assigned_to ?: '-'); ?>
                            </td>

                            <td>
                                <?= e($service_date ?: '-'); ?>
                            </td>

                            <td>

                                <?php if ($status === 'Completed') { ?>

                                    <span class="badge badge-success">
                                        Completed
                                    </span>

                                <?php } else { ?>

                                    <span class="badge badge-warning">
                                        Pending
                                    </span>

                                <?php } ?>

                            </td>

                            <td>

                                <a
                                    href="solar-update-pending-work.php?client_id=<?= $client_id; ?>&work=<?= urlencode($work_name); ?>"
                                    class="action-btn primary2">

                                    Update

                                </a>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- GST DETAILS -->
    <?php if ((int)($client['apply_gst'] ?? 0) === 1) { ?>

        <div class="inquiry-card">

            <div class="inquiry-page-header">
                <h2>GST Details</h2>
            </div>

            <div class="card-body">

                <p>
                    <strong>GST Applied:</strong>
                    Yes (<?= rtrim(rtrim(number_format($gst_rate, 2, '.', ''), '0'), '.'); ?>%)
                </p>

                <p>
                    <strong>GST Type:</strong>
                    <?= ($client['gst_type'] ?? '') === 'inclusive'
                        ? 'Inclusive - GST Included'
                        : 'Exclusive - GST Extra'; ?>
                </p>

                <p>
                    <strong>GST Amount:</strong>
                    ₹<?= number_format($gst_amount, 2); ?>
                </p>

                <p>
                    <strong>GST Number (GSTIN):</strong>
                    <?= e($client['gst_number'] ?: 'Not Added'); ?>
                </p>

                <p>
                    <strong>Billing Address:</strong><br>
                    <?= nl2br(e($client['billing_address'] ?? '')); ?>
                </p>

                <p>
                    <strong>Shipping Address:</strong><br>
                    <?= nl2br(e($client['shipping_address'] ?? '')); ?>
                </p>

            </div>

        </div>

    <?php } ?>

    <!-- PAYMENT DETAILS -->
    <div class="inquiry-card">

        <div class="inquiry-page-header">
            <h2>Payment Details</h2>
        </div>

        <div class="card-body">

            <div class="row mb-3">

                <div class="col-md-4 mb-3">
                    <strong>Base Amount:</strong>
                    ₹<?= number_format($base_amount, 2); ?>
                </div>

                <?php if ((int)($client['apply_gst'] ?? 0) === 1) { ?>

                    <div class="col-md-4 mb-3">

                        <strong>
                            GST (<?= rtrim(rtrim(number_format($gst_rate, 2, '.', ''), '0'), '.'); ?>%):
                        </strong>

                        ₹<?= number_format($gst_amount, 2); ?>

                    </div>

                <?php } ?>

                <div class="col-md-4 mb-3">
                    <strong>Grand Total:</strong>
                    ₹<?= number_format($grand_total, 2); ?>
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Total Paid:</strong>
                    ₹<?= number_format($total_paid, 2); ?>
                </div>

                <div class="col-md-4 mb-3">
                    <strong>Balance:</strong>
                    ₹<?= number_format($balance, 2); ?>
                </div>

            </div>

            <!-- PAYMENT HISTORY -->
            <div class="inquiry-table-wrapper">

                <table class="inquiry-table">

                    <thead>
                        <tr>
                            <th>Sr.No.</th>
                            <th>Payment Date</th>
                            <th>Paid Amount</th>
                            <th>Payment Gateway</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php

                    $serial_no = 1;
                    $rows_shown = 0;

                    if (mysqli_num_rows($payments) > 0) {

                        while ($row = mysqli_fetch_assoc($payments)) {

                            if (
                                !isset($row['paid_amount']) ||
                                (float)$row['paid_amount'] <= 0
                            ) {
                                continue;
                            }

                            $rows_shown++;

                    ?>

                        <tr class="data-row">

                            <td><?= $serial_no++; ?></td>

                            <td><?= e($row['payment_date']); ?></td>

                            <td>
                                ₹<?= number_format((float)$row['paid_amount'], 2); ?>
                            </td>

                            <td><?= e($row['payment_gateway']); ?></td>

                        </tr>

                    <?php
                        }

                        if ($rows_shown === 0) {
                            echo '<tr><td colspan="4" style="text-align:center;">No Payment Records Found</td></tr>';
                        }

                    } else {

                        echo '<tr><td colspan="4" style="text-align:center;">No Payment Records Found</td></tr>';

                    }
                    ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- UPLOAD DOCUMENT POPUP -->
<div id="uploadPopup" class="popup-overlay">

    <div class="popup-box">

        <h2>Upload Solar Documents</h2>

        <p class="subtitle">
            Select document file and upload
        </p>

        <form
            id="uploadForm"
            action="solar-upload-client-docs.php?id=<?= $client_id; ?>"
            method="POST"
            enctype="multipart/form-data">

            <label class="input-label">
                Upload Document
            </label>

            <input
                type="file"
                name="documents[]"
                multiple
                class="file-input"
                required>

            <input
                type="hidden"
                name="client_id"
                value="<?= $client_id; ?>">

            <button type="submit" class="upload-btn">
                Upload
            </button>

        </form>

        <button
            type="button"
            class="close-btn"
            onclick="closeUploadPopup()">

            Close

        </button>

    </div>

</div>

<!-- JAVASCRIPT -->
<script>

function openUploadPopup() {
    document.getElementById("uploadPopup").style.display = "flex";
}

function closeUploadPopup() {
    document.getElementById("uploadPopup").style.display = "none";
}

/* DOCUMENT TOGGLE */
const toggleButton = document.getElementById('toggleDocumentsButton');
const documentsContent = document.getElementById('documentsContent');
const toggleIcon = document.getElementById('toggleIcon');

if (toggleButton && documentsContent) {

    toggleButton.addEventListener('click', function() {

        if (
            documentsContent.style.display === 'none' ||
            documentsContent.style.display === ''
        ) {

            documentsContent.style.display = 'block';

            if (toggleIcon) {
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
            }

        } else {

            documentsContent.style.display = 'none';

            if (toggleIcon) {
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            }

        }

    });

}

</script>

<!-- FOOTER SCRIPTS -->
<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>

</section>
</section>

</body>
</html>

<?php

mysqli_stmt_close($paymentHistoryStmt);
mysqli_stmt_close($invoiceStmt);

mysqli_close($conn);

?>
