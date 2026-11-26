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
   DATABASE
========================================================= */
require_once 'db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

$message = "";

/* =========================================================
   CLIENT ID AND GROUP ID
========================================================= */
$client_id = (int)($_GET['id'] ?? $_POST['client_id'] ?? 0);
$group_id = (int)($_GET['group_id'] ?? $_POST['group_id'] ?? 0);

$is_trader = ($group_id > 0);

if ($client_id <= 0) {
    die("Invalid Client ID");
}

/* =========================================================
   FETCH SOLAR CLIENT
========================================================= */
$clientStmt = $conn->prepare("
    SELECT *
    FROM tbl_solar_client
    WHERE id = ?
    LIMIT 1
");

$clientStmt->bind_param("i", $client_id);
$clientStmt->execute();

$client = $clientStmt->get_result()->fetch_assoc();

$clientStmt->close();

if (!$client) {
    die("Solar Client Not Found");
}

/* =========================================================
   FETCH PAYMENT SUMMARY
========================================================= */
if ($is_trader) {

    // TRADER: Selected group payment only
    $summaryStmt = $conn->prepare("
        SELECT
            COALESCE(MAX(total_amount), 0) AS total_amount,
            COALESCE(SUM(paid_amount), 0) AS total_paid
        FROM tbl_solar_payment
        WHERE client_id = ?
        AND group_id = ?
    ");

    $summaryStmt->bind_param("ii", $client_id, $group_id);

} else {

    // NORMAL CLIENT
    $summaryStmt = $conn->prepare("
        SELECT
            COALESCE(MAX(total_amount), 0) AS total_amount,
            COALESCE(SUM(paid_amount), 0) AS total_paid
        FROM tbl_solar_payment
        WHERE client_id = ?
    ");

    $summaryStmt->bind_param("i", $client_id);
}

$summaryStmt->execute();

$payment_summary = $summaryStmt->get_result()->fetch_assoc();

$summaryStmt->close();

$total_amount = (float)($payment_summary['total_amount'] ?? 0);
$total_paid = (float)($payment_summary['total_paid'] ?? 0);

$due_amount = max(0, round($total_amount - $total_paid, 2));

/* =========================================================
   HANDLE FORM SUBMISSION
========================================================= */
if (isset($_POST['submit'])) {

    $new_paid = (float)($_POST['paid_amount'] ?? 0);
    $payment_date = trim($_POST['payment_date'] ?? '');
    $due_date = trim($_POST['due_date'] ?? '');
    $payment_gateway = trim($_POST['payment_gateway'] ?? '');

    /* VALIDATE PAYMENT METHOD */
    if ($payment_gateway === '') {

        $message = "Please select or enter payment method!";

    } elseif ($new_paid <= 0) {

        $message = "Please enter a valid payment amount!";

    } elseif ($new_paid > $due_amount) {

        $message = "Payment amount cannot be greater than due amount!";

    } elseif ($payment_date === '') {

        $message = "Please select payment date!";

    } else {

        /* NEW DUE AFTER PAYMENT */
        $updated_due = round(
            $total_amount - ($total_paid + $new_paid),
            2
        );

        $due_date_value = ($due_date !== '') ? $due_date : null;

        /* INSERT PAYMENT */
        if ($is_trader) {

            $insertStmt = $conn->prepare("
                INSERT INTO tbl_solar_payment
                (
                    client_id,
                    group_id,
                    total_amount,
                    paid_amount,
                    due_amount,
                    payment_date,
                    due_date,
                    payment_gateway
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->bind_param(
                "iidddsss",
                $client_id,
                $group_id,
                $total_amount,
                $new_paid,
                $updated_due,
                $payment_date,
                $due_date_value,
                $payment_gateway
            );

        } else {

            $insertStmt = $conn->prepare("
                INSERT INTO tbl_solar_payment
                (
                    client_id,
                    total_amount,
                    paid_amount,
                    due_amount,
                    payment_date,
                    due_date,
                    payment_gateway
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $insertStmt->bind_param(
                "idddsss",
                $client_id,
                $total_amount,
                $new_paid,
                $updated_due,
                $payment_date,
                $due_date_value,
                $payment_gateway
            );
        }

        if ($insertStmt->execute()) {

            $insertStmt->close();

            $_SESSION['status'] = "Solar Payment Recorded Successfully";

            header("Location: solar-due-payment.php");
            exit();

        } else {

            $message = "Error: Payment could not be recorded.";
            $insertStmt->close();
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
                Add Solar Payment
            </h2>

        </div>

        <div class="card-body">

            <p>
                <strong>Client Name:</strong>
                <?= htmlspecialchars($client['cust_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <?php if ($message !== '') { ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>

            <?php } ?>

            <form method="POST" action="" class="refollowup-form">

                <input
                    type="hidden"
                    name="client_id"
                    value="<?= $client_id; ?>"
                >

                <?php if ($is_trader) { ?>

                    <input
                        type="hidden"
                        name="group_id"
                        value="<?= $group_id; ?>"
                    >

                <?php } ?>

                <!-- TOTAL AMOUNT -->
                <div class="form-row">

                    <div class="form-group">

                        <label>Total Amount</label>

                        <input
                            type="text"
                            value="<?= number_format($total_amount, 2); ?>"
                            readonly
                        >

                    </div>

                    <!-- TOTAL PAID -->
                    <div class="form-group">

                        <label>Total Paid So Far</label>

                        <input
                            type="text"
                            value="<?= number_format($total_paid, 2); ?>"
                            readonly
                        >

                    </div>

                </div>

                <!-- DUE AMOUNT AND NEW PAYMENT -->
                <div class="form-row">

                    <div class="form-group">

                        <label>Due Amount</label>

                        <input
                            type="text"
                            value="<?= number_format($due_amount, 2); ?>"
                            readonly
                        >

                    </div>

                    <div class="form-group">

                        <label>New Payment <span style="color:red;">*</span></label>

                        <input
                            type="number"
                            name="paid_amount"
                            placeholder="Enter Paid Amount"
                            min="0.01"
                            max="<?= $due_amount; ?>"
                            step="0.01"
                            required
                        >

                    </div>

                </div>

                <!-- PAYMENT DATE AND DUE DATE -->
                <div class="form-row">

                    <div class="form-group">

                        <label>Payment Date <span style="color:red;">*</span></label>

                        <input
                            type="date"
                            name="payment_date"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label>Due Date</label>

                        <input
                            type="date"
                            name="due_date"
                        >

                    </div>

                </div>

                <!-- PAYMENT METHOD -->
                <div class="form-group">

                    <label>
                        Payment Method <span style="color:red;">*</span>
                    </label>

                    <div id="payment_gateway_container">

                        <select
                            name="payment_gateway"
                            id="payment_gateway"
                            required
                        >

                            <option value="">
                                -- Select Payment Method --
                            </option>

                            <option value="Cash">Cash</option>

                            <option value="Google Pay">Google Pay</option>

                            <option value="PhonePe">PhonePe</option>

                            <option value="Bank Transfer">Bank Transfer</option>

                            <option value="Cheque">Cheque</option>

                            <option value="Other">Other</option>

                        </select>

                    </div>

                </div>

                <!-- SUBMIT BUTTON -->
                <div class="form-group">

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                        <?= ($due_amount <= 0) ? 'disabled' : ''; ?>
                    >

                        Add Payment

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</section>
</section>

<!-- PAYMENT GATEWAY SCRIPT -->
<script>

document.addEventListener("DOMContentLoaded", function () {

    const container = document.getElementById(
        "payment_gateway_container"
    );

    container.addEventListener("change", function (event) {

        if (
            event.target &&
            event.target.id === "payment_gateway" &&
            event.target.value === "Other"
        ) {

            container.innerHTML = `
                <input
                    type="text"
                    name="payment_gateway"
                    id="payment_gateway"
                    placeholder="Enter Payment Method"
                    required
                    autofocus
                >
            `;

        }

    });

});

</script>

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
$conn->close();
?>
