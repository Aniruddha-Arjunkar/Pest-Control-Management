
<?php
ob_start();

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
   GET CLIENT ID
========================================================= */
$client_id = (int)($_GET['client_id'] ?? 0);

if ($client_id <= 0) {
    die("Invalid Client ID");
}

/* =========================================================
   FETCH SOLAR CLIENT DETAILS
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM tbl_solar_client WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);

$client_result = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($client_result);

mysqli_stmt_close($stmt);

if (!$client) {
    die("Client Not Found");
}

/* =========================================================
   TRADING HISTORY - GROUP WISE
   Existing trading tables and logic retained
========================================================= */
$trading_stmt = mysqli_prepare($conn, "
    SELECT
        g.id AS group_id,
        g.client_id,
        g.trading_date,
        COUNT(t.Id) AS total_items,

        COALESCE(
            GROUP_CONCAT(
                t.item_name
                ORDER BY t.Id ASC
                SEPARATOR ', '
            ), ''
        ) AS item_names,

        COALESCE(SUM(t.base_amount), 0) AS base_amount,
        COALESCE(SUM(t.gst_amount), 0) AS gst_amount,
        COALESCE(SUM(t.grand_total), 0) AS grand_total,

        COALESCE(p.paid_amount, 0) AS paid_amount,

        (
            COALESCE(SUM(t.grand_total), 0)
            - COALESCE(p.paid_amount, 0)
        ) AS balance

    FROM tbl_solar_trading_groups g

    LEFT JOIN tbl_solar_trading_history t
        ON t.group_id = g.id
        AND t.client_id = g.client_id

    LEFT JOIN (
        SELECT
            group_id,
            client_id,
            SUM(paid_amount) AS paid_amount
        FROM tbl_solar_payment
        WHERE group_id IS NOT NULL
        GROUP BY group_id, client_id
    ) p
        ON p.group_id = g.id
        AND p.client_id = g.client_id

    WHERE g.client_id = ?

    GROUP BY
        g.id,
        g.client_id,
        g.trading_date,
        p.paid_amount

    ORDER BY g.trading_date DESC, g.id DESC
");

mysqli_stmt_bind_param($trading_stmt, "i", $client_id);
mysqli_stmt_execute($trading_stmt);

$trading_history = mysqli_stmt_get_result($trading_stmt);

/* =========================================================
   TOTAL TRADING SUMMARY
   Existing calculation retained
========================================================= */
$total_stmt = mysqli_prepare($conn, "
    SELECT
        COALESCE(SUM(t.grand_total), 0) AS total_trading,
        COALESCE(SUM(COALESCE(p.paid_amount, 0)), 0) AS total_paid

    FROM tbl_solar_trading_history t

    LEFT JOIN (
        SELECT
            trading_history_id,
            SUM(COALESCE(paid_amount, 0)) AS paid_amount
        FROM tbl_solar_payment
        GROUP BY trading_history_id
    ) p ON p.trading_history_id = t.Id

    WHERE t.client_id = ?
");

mysqli_stmt_bind_param($total_stmt, "i", $client_id);
mysqli_stmt_execute($total_stmt);

$summary_result = mysqli_stmt_get_result($total_stmt);
$summary = mysqli_fetch_assoc($summary_result);

$total_due = (float)$summary['total_trading'] - (float)$summary['total_paid'];

/* =========================================================
   FETCH SOLAR GENERATED INVOICES
========================================================= */
$invoice_stmt = mysqli_prepare($conn, "
    SELECT
        i.gst_invoice_no,
        i.client_id,
        i.cust_name,
        i.cust_phone,
        i.invoice_date,
        i.pdf_file,
        i.created_at,
        c.added_by,
        COUNT(i.id) AS total_items,

        GROUP_CONCAT(
            i.item_name
            ORDER BY i.id ASC
            SEPARATOR ', '
        ) AS item_names

    FROM tbl_solar_invoice i

    LEFT JOIN tbl_solar_client c
        ON c.id = i.client_id

    WHERE i.client_id = ?

    GROUP BY
        i.gst_invoice_no,
        i.client_id,
        i.created_at,
        i.cust_name,
        i.cust_phone,
        i.invoice_date,
        i.pdf_file,
        c.added_by

    ORDER BY i.created_at DESC
");

mysqli_stmt_bind_param($invoice_stmt, "i", $client_id);
mysqli_stmt_execute($invoice_stmt);

$client_invoices = mysqli_stmt_get_result($invoice_stmt);

include('header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
.client-detail {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.detail-box {
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 18px;
}

.detail-box p {
    margin-bottom: 18px;
    overflow-wrap: anywhere;
}

.detail-box p:last-child {
    margin-bottom: 0;
}

.detail-box strong {
    display: inline-block;
    margin-bottom: 5px;
}

.detail-value {
    color: #555;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.summary-box {
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 15px;
    height: 100%;
}

.summary-box strong {
    display: block;
    margin-bottom: 8px;
}

.summary-box p {
    margin: 0;
    font-size: 17px;
    font-weight: 600;
}

.action-menu {
    min-width: 190px;
}

.action-menu form {
    margin: 0;
}

.action-menu .dropdown-item {
    padding: 9px 14px;
}

.modal-table {
    min-width: 850px;
}

@media(max-width: 767px) {
    .client-detail {
        grid-template-columns: 1fr;
    }

    .section-heading {
        align-items: flex-start;
    }
}
</style>

<section id="main-content">
<section class="wrapper">

<div class="inquiry-page">

    <!-- =================================================
         PAGE HEADER
    ================================================== -->
    <div class="inquiry-page-header">

        <div>
            <h2>Client Details</h2>
            <p>Client information, trading history and invoices</p>
        </div>

        <a href="javascript:history.back()" class="action-btn primary2">
            <i class="fa fa-arrow-left"></i> Back
        </a>

    </div>

    <!-- =================================================
         CLIENT DETAILS
    ================================================== -->
    <div class="inquiry-card mb-4">

        <div class="refollowup-header">
            <h3 class="refollowup-title">
                <i class="bi bi-person-circle"></i>
                Client Information
            </h3>
        </div>

        <div class="p-3">

            <div class="client-detail">

                <div class="detail-box">

                    <p>
                        <strong>Client Name:</strong><br>
                        <span class="detail-value"><?= e($client['cust_name']); ?></span>
                    </p>

                    <p>
                        <strong>Email:</strong><br>
                        <span class="detail-value"><?= e($client['cust_email'] ?: '-'); ?></span>
                    </p>

                    <p>
                        <strong>GST Number:</strong><br>
                        <span class="detail-value"><?= e($client['gst_number'] ?: 'Not Added'); ?></span>
                    </p>

                    <p>
                        <strong>Client Type:</strong><br>
                        <span class="detail-value"><?= e(ucfirst($client['added_by'] ?? '-')); ?></span>
                    </p>

                </div>

                <div class="detail-box">

                    <p>
                        <strong>Phone:</strong><br>
                        <span class="detail-value"><?= e($client['cust_phone']); ?></span>
                    </p>

                    <p>
                        <strong>Address:</strong><br>
                        <span class="detail-value"><?= nl2br(e($client['cust_address'])); ?></span>
                    </p>

                    <p>
                        <strong>Billing Address:</strong><br>
                        <span class="detail-value"><?= nl2br(e($client['billing_address'] ?? '')); ?></span>
                    </p>

                    <p>
                        <strong>Shipping Address:</strong><br>
                        <span class="detail-value"><?= nl2br(e($client['shipping_address'] ?? '')); ?></span>
                    </p>

                </div>

            </div>

            <a
                href="solar-update-client.php?client_id=<?= $client_id; ?>"
                class="action-btn primary1 mt-3"
            >
                <i class="bi bi-pencil-square"></i>
                Update Client
            </a>

        </div>

    </div>

    <!-- =================================================
         TRADING HISTORY
    ================================================== -->
    <div class="inquiry-page-header">

        <div>
            <h3>Trading History</h3>
            <p>All trading records of this client</p>
        </div>

        <a
            href="solar-add-trading.php?client_id=<?= $client_id; ?>"
            class="action-btn primary1"
        >
            <i class="bi bi-plus-circle"></i>
            Add Trading
        </a>

    </div>

    <div class="inquiry-card mb-4">

        <div class="inquiry-table-wrapper">

            <table class="inquiry-table">

                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Date</th>
                        <th>Item Name</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($trading_history) > 0): ?>

                    <?php $i = mysqli_num_rows($trading_history); ?>

                    <?php while ($row = mysqli_fetch_assoc($trading_history)): ?>

                        <tr>

                            <td class="inquiry-sr"><?= $i--; ?></td>

                            <td>
                                <?= !empty($row['trading_date'])
                                    ? date('d-m-Y', strtotime($row['trading_date']))
                                    : '-'; ?>
                            </td>

                            <td class="inquiry-name">
                                <strong>
                                    <?= e(explode(',', $row['item_names'])[0] ?? ''); ?>
                                </strong>

                                <?php if ($row['total_items'] > 1): ?>
                                    <br>
                                    <small class="text-muted">
                                        + <?= (int)$row['total_items'] - 1; ?> More Items
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong>
                                    ₹<?= number_format((float)$row['grand_total'], 2); ?>
                                </strong>
                            </td>

                            <td>
                                <strong class="text-success">
                                    ₹<?= number_format((float)$row['paid_amount'], 2); ?>
                                </strong>
                            </td>

                            <td>
                                <strong class="<?= $row['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                    ₹<?= number_format((float)$row['balance'], 2); ?>
                                </strong>
                            </td>

                            <td class="inquiry-action-cell">

                                <div class="dropdown">

                                    <button
                                        class="action-btn primary2 dropdown-toggle"
                                        type="button"
                                        data-toggle="dropdown"
                                    >
                                        <i class="bi bi-three-dots"></i>
                                        Action
                                    </button>

                                    <div class="dropdown-menu action-menu">

                                        <!-- VIEW ITEMS -->
                                        <button
                                            type="button"
                                            class="dropdown-item text-info"
                                            data-toggle="modal"
                                            data-target="#tradingModal<?= (int)$row['group_id']; ?>"
                                        >
                                            <i class="bi bi-eye"></i>
                                            View Items
                                        </button>

                                        <!-- EDIT TRADING -->
                                        <a
                                            class="dropdown-item"
                                            href="add-trading.php?group_id=<?= (int)$row['group_id']; ?>&client_id=<?= $client_id; ?>"
                                        >
                                            <i class="bi bi-pencil-square text-primary"></i>
                                            Edit Item
                                        </a>

                                        <!-- GENERATE INVOICE -->
                                        <a
                                            class="dropdown-item"
                                            href="solar-add-invoice.php?client_id=<?= $client_id; ?>&group_id=<?= (int)$row['group_id']; ?>"
                                        >
                                            <i class="bi bi-receipt text-success"></i>
                                            Generate Invoice
                                        </a>

                                        <div class="dropdown-divider"></div>

                                        <!-- DELETE TRADING -->
                                        <form
                                            action="solar-add-trading-code.php"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this complete trading group?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="group_id"
                                                value="<?= (int)$row['group_id']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="client_id"
                                                value="<?= $client_id; ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete_trading_group"
                                                value="1"
                                                class="dropdown-item text-danger"
                                            >
                                                <i class="bi bi-trash"></i>
                                                Delete Trading
                                            </button>

                                        </form>

                                    </div>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="text-center">
                            No Trading History Found
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <!-- =================================================
         TRADING ITEM MODALS
    ================================================== -->
    <?php
    $modalStmt = mysqli_prepare($conn, "
        SELECT id, trading_date
        FROM tbl_solar_trading_groups
        WHERE client_id = ?
        ORDER BY trading_date DESC, id DESC
    ");

    mysqli_stmt_bind_param($modalStmt, "i", $client_id);
    mysqli_stmt_execute($modalStmt);

    $modalGroups = mysqli_stmt_get_result($modalStmt);
    ?>

    <?php while ($modal = mysqli_fetch_assoc($modalGroups)): ?>

        <div
            class="modal fade"
            id="tradingModal<?= (int)$modal['id']; ?>"
            tabindex="-1"
            role="dialog"
            aria-hidden="true"
        >

            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

                <div class="modal-content">

                    <div class="modal-header bg-info text-white">

                        <h5 class="modal-title">
                            Trading Details -
                            <?= date('d-m-Y', strtotime($modal['trading_date'])); ?>
                        </h5>

                        <button
                            type="button"
                            class="close text-white"
                            data-dismiss="modal"
                        >
                            <span>&times;</span>
                        </button>

                    </div>

                    <div class="modal-body">

                        <?php
                        $detailStmt = mysqli_prepare($conn, "
                            SELECT *
                            FROM tbl_solar_trading_history
                            WHERE group_id = ?
                            AND client_id = ?
                            ORDER BY Id ASC
                        ");

                        mysqli_stmt_bind_param(
                            $detailStmt,
                            "ii",
                            $modal['id'],
                            $client_id
                        );

                        mysqli_stmt_execute($detailStmt);
                        $details = mysqli_stmt_get_result($detailStmt);
                        ?>

                        <div class="table-responsive">

                            <table class="table table-bordered table-striped text-center modal-table">

                                <thead class="table-light">
                                    <tr>
                                        <th>Sr.</th>
                                        <th>Item Name</th>
                                        <th>HSN</th>
                                        <th>Qty</th>
                                        <th>Rate</th>
                                        <th>GST</th>
                                        <th>GST Type</th>
                                        <th>GST Amount</th>
                                        <th>Total</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>

                                <tbody>

                                <?php $sr = 1; ?>

                                <?php while ($item = mysqli_fetch_assoc($details)): ?>

                                    <tr>
                                        <td><?= $sr++; ?></td>
                                        <td><?= e($item['item_name']); ?></td>
                                        <td><?= e($item['hsn_code']); ?></td>
                                        <td><?= number_format((float)$item['qty'], 2); ?></td>
                                        <td>₹<?= number_format((float)$item['amount'], 2); ?></td>
                                        <td><?= number_format((float)$item['gst_rate'], 2); ?>%</td>
                                        <td><?= e(ucfirst($item['gst_type'])); ?></td>
                                        <td>₹<?= number_format((float)$item['gst_amount'], 2); ?></td>
                                        <td>
                                            <strong>
                                                ₹<?= number_format((float)$item['grand_total'], 2); ?>
                                            </strong>
                                        </td>
                                        <td><?= e($item['description']); ?></td>
                                    </tr>

                                <?php endwhile; ?>

                                <?php mysqli_stmt_close($detailStmt); ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="action-btn primary2"
                            data-dismiss="modal"
                        >
                            Close
                        </button>

                    </div>

                </div>

            </div>

        </div>

    <?php endwhile; ?>

    <?php mysqli_stmt_close($modalStmt); ?>

    <!-- =================================================
         GENERATED INVOICES
    ================================================== -->
    <div class="inquiry-page-header">

        <div>
            <h3>Generated Invoices</h3>
            <p>All invoices generated for this client</p>
        </div>

    </div>

    <div class="inquiry-card mb-4">

        <div class="inquiry-table-wrapper">

            <table class="inquiry-table">

                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Invoice No.</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Invoice Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (mysqli_num_rows($client_invoices) > 0): ?>

                    <?php $sr = 1; ?>

                    <?php while ($invoice = mysqli_fetch_assoc($client_invoices)): ?>

                        <tr>

                            <td class="inquiry-sr"><?= $sr++; ?></td>

                            <td>
                                <strong><?= e($invoice['gst_invoice_no']); ?></strong>
                            </td>

                            <td class="inquiry-name">
                                <?= e($invoice['cust_name']); ?>
                            </td>

                            <td><?= e($invoice['cust_phone']); ?></td>

                            <td>
                                <?= !empty($invoice['invoice_date'])
                                    ? date('d-m-Y', strtotime($invoice['invoice_date']))
                                    : '-'; ?>
                            </td>

                            <td class="inquiry-action-cell">

                                <?php
                                $pdfPath = $invoice['pdf_file'] ?? '';

                                if (!empty($pdfPath) && file_exists($pdfPath)):
                                ?>

                                    <div class="dropdown">

                                        <button
                                            class="action-btn primary2 dropdown-toggle"
                                            type="button"
                                            data-toggle="dropdown"
                                        >
                                            <i class="bi bi-three-dots"></i>
                                            Action
                                        </button>

                                        <div class="dropdown-menu action-menu">

                                            <!-- VIEW PDF -->
                                            <a
                                                class="dropdown-item"
                                                href="solar-generate-invoice.php?client_id=<?= (int)$invoice['client_id']; ?>&created_at=<?= urlencode($invoice['created_at']); ?>"
                                                target="_blank"
                                            >
                                                <i class="bi bi-file-earmark-pdf text-success"></i>
                                                View Invoice
                                            </a>

                                            <!-- EDIT INVOICE -->
                                            <a
                                                class="dropdown-item"
                                                href="solar-add-invoice.php?client_id=<?= (int)$invoice['client_id']; ?>&edit=1"
                                            >
                                                <i class="bi bi-pencil-square text-primary"></i>
                                                Edit Invoice
                                            </a>

                                            <!-- SEND INVOICE -->
                                            <a
                                                class="dropdown-item"
                                                href="send-invoice-email.php?client_id=<?= (int)$invoice['client_id']; ?>&created_at=<?= urlencode($invoice['created_at']); ?>"
                                                onclick="return confirm('Send invoice to client email?');"
                                            >
                                                <i class="bi bi-envelope text-dark"></i>
                                                Send Invoice
                                            </a>

                                            <div class="dropdown-divider"></div>

                                            <!-- DELETE INVOICE -->
                                            <form
                                                action="save-invoice.php"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this invoice?');"
                                            >

                                                <input type="hidden" name="action" value="delete">

                                                <input
                                                    type="hidden"
                                                    name="client_id"
                                                    value="<?= (int)$invoice['client_id']; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="created_at"
                                                    value="<?= e($invoice['created_at']); ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="dropdown-item text-danger"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                    Delete Invoice
                                                </button>

                                            </form>

                                        </div>

                                    </div>

                                <?php else: ?>

                                    <span class="text-danger">
                                        PDF Not Available
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-center">
                            No Invoices Generated For This Client
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</section>
</section>

<?php
mysqli_stmt_close($invoice_stmt);
mysqli_stmt_close($total_stmt);
mysqli_stmt_close($trading_stmt);

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

<?php
ob_end_flush();
?>
