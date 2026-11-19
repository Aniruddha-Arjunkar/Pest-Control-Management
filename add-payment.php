```php
<?php
session_start();
ob_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

$payment = [];
$paymentId = null;
$clientId = null;
$client_due_amount = 0;
$show_tds = false;

/* =========================================
   FETCH PAYMENT
========================================= */

if (isset($_GET['payment_id'])) {

    $paymentId = intval($_GET['payment_id']);

    $sql = "SELECT * FROM tbl_payment WHERE Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();

    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();

    if ($payment) {

        $show_tds = (floatval($payment['tds_amount'] ?? 0) > 0);

        $clientId = $payment['client_Id'] ?? null;

        /* =========================================
           FETCH CLIENT DUE AMOUNT
        ========================================= */

        if ($clientId) {

            $sql_due = "
                SELECT
                    MAX(total_amount) AS total_amount,
                    SUM(paid_amount) AS paid,
                    SUM(tds_amount) AS tds
                FROM tbl_payment
                WHERE client_Id = ?
            ";

            $stmt_due = $conn->prepare($sql_due);
            $stmt_due->bind_param('i', $clientId);
            $stmt_due->execute();

            $result_due = $stmt_due->get_result();

            if ($row_due = $result_due->fetch_assoc()) {

                $total = floatval($row_due['total_amount'] ?? 0);
                $paid  = floatval($row_due['paid'] ?? 0);
                $tds   = floatval($row_due['tds'] ?? 0);

                $client_due_amount = $total - $paid - $tds;

                if ($client_due_amount < 0) {
                    $client_due_amount = 0;
                }
            }
        }
    }
}


/* =========================================
   PAYMENT SUBMISSION
========================================= */

if (isset($_POST['submit'])) {

    $paymentId = intval($_POST['payment_id'] ?? 0);
    $client_Id = intval($_POST['client_Id'] ?? 0);

    /* =========================================
       GET GST NUMBER
    ========================================= */

    $gst_number = '';

    $sql = "SELECT gst_number FROM tbl_client WHERE Id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_Id);
    $stmt->execute();

    $res = $stmt->get_result();

    if ($client = $res->fetch_assoc()) {
        $gst_number = $client['gst_number'] ?? '';
    }


    /* =========================================
       CHECK ASSOCIATE GST
    ========================================= */

    if (empty($gst_number)) {

        $sql = "SELECT gst_number FROM tbl_associate WHERE Id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $client_Id);
        $stmt->execute();

        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $gst_number = $row['gst_number'];
        }
    }


    /* =========================================
       CHECK AGREEMENT GST
    ========================================= */

    if (empty($gst_number)) {

        $sql = "SELECT gst_number FROM tbl_agreement WHERE Id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $client_Id);
        $stmt->execute();

        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $gst_number = $row['gst_number'];
        }
    }


    /* =========================================
       GST INVOICE CHECK
    ========================================= */

    if (!empty($gst_number)) {

        $check = "
            SELECT id
            FROM tbl_gst_invoice
            WHERE entity_id = ?
            LIMIT 1
        ";

        $stmt2 = $conn->prepare($check);
        $stmt2->bind_param("i", $client_Id);
        $stmt2->execute();

        $res2 = $stmt2->get_result();

        if ($res2->num_rows == 0) {

            echo "<script>
                alert('This client is GST registered. Please generate GST Invoice before adding payment.');
                window.location.href='due-payments.php';
            </script>";

            exit();
        }
    }


    /* =========================================
       FORM DATA
    ========================================= */

    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $paid_amount = floatval($_POST['paid_amount'] ?? 0);

    if ($paid_amount <= 0) {

        echo "<script>
            alert('Please enter Paid Amount');
            window.history.back();
        </script>";

        exit();
    }

    $payment_type = !empty($_POST['payment_type'])
        ? $_POST['payment_type']
        : null;

    $interview_id = !empty($_POST['interview_id'])
        ? intval($_POST['interview_id'])
        : null;

    $due_date = !empty($_POST['due_date'])
        ? $_POST['due_date']
        : null;

    $payment_date = $_POST['payment_date'] ?? '';
    $payment_gateway = $_POST['payment_gateway'] ?? '';

    $tds_amount = isset($_POST['tds_amount'])
        ? floatval($_POST['tds_amount'])
        : 0;

    $tds_paid = isset($_POST['mark_tds_paid'])
        ? 1
        : 0;


    /* =========================================
       FETCH TOTAL AMOUNT BY PAYMENT TYPE
    ========================================= */

    if ($payment_type && $paymentId > 0) {

        $sql = "
            SELECT total_amount
            FROM tbl_payment
            WHERE Id = ?
            AND payment_type = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $paymentId, $payment_type);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $total_amount = $row['total_amount'];
        }
    }


    /* =========================================
       CALCULATE DUE
    ========================================= */

    if ($tds_paid == 1) {

        $due_amount = $total_amount - $paid_amount;

    } else {

        $due_amount =
            $total_amount
            - $paid_amount
            - $tds_amount;
    }

    if ($due_amount < 0) {
        $due_amount = 0;
    }


    /* =========================================
       UPDATE EXISTING PAYMENT
    ========================================= */

    if (!empty($payment)) {

        $sql = "
            UPDATE tbl_payment SET

                interview_id = ?,
                total_amount = ?,
                paid_amount = ?,
                tds_amount = ?,
                tds_paid = ?,
                due_amount = ?,
                due_date = ?,
                payment_date = ?,
                payment_gateway = ?,
                payment_type = ?

            WHERE Id = ?
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            'idddidssssi',
            $interview_id,
            $total_amount,
            $paid_amount,
            $tds_amount,
            $tds_paid,
            $due_amount,
            $due_date,
            $payment_date,
            $payment_gateway,
            $payment_type,
            $paymentId
        );


    } else {

        /* =========================================
           DUPLICATE CHECK
        ========================================= */

        $check_sql = "
            SELECT *
            FROM tbl_payment
            WHERE client_Id = ?
            AND payment_date = ?
            AND payment_gateway = ?
        ";

        $check_stmt = $conn->prepare($check_sql);

        $check_stmt->bind_param(
            'iss',
            $client_Id,
            $payment_date,
            $payment_gateway
        );

        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {

            echo "<script>
                alert('Duplicate entry: This client already has a payment on this date.');
                window.location.href='add-payment.php';
            </script>";

            exit();
        }


        /* =========================================
           INSERT PAYMENT
        ========================================= */

        $sql = "
            INSERT INTO tbl_payment
            (
                client_Id,
                interview_id,
                total_amount,
                paid_amount,
                tds_amount,
                tds_paid,
                due_amount,
                due_date,
                payment_date,
                payment_gateway,
                payment_type
            )

            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            'iiddidsssss',
            $client_Id,
            $interview_id,
            $total_amount,
            $paid_amount,
            $tds_amount,
            $tds_paid,
            $due_amount,
            $due_date,
            $payment_date,
            $payment_gateway,
            $payment_type
        );
    }


    /* =========================================
       EXECUTE
    ========================================= */

    if ($stmt->execute()) {

        echo "<script>
            alert('Payment details updated successfully');
            window.location.href='due-payments.php';
        </script>";

        exit();

    } else {

        echo "<script>
            alert('Error: " .
            addslashes($stmt->error) .
            "');
        </script>";
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

                        <span class="phone-icon">💳</span>

                        <div>

                            <h2>Add Payment</h2>

                            <p>
                                Add and update payment details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    action="add-payment.php<?= $paymentId ? '?payment_id=' . $paymentId : ''; ?>"
                    method="post"
                    class="refollowup-form"
                    onsubmit="return confirmPayment();"
                >

                    <input
                        type="hidden"
                        name="payment_id"
                        value="<?= htmlspecialchars($payment['Id'] ?? ''); ?>"
                    >

                    <input
                        type="hidden"
                        name="client_Id"
                        value="<?= htmlspecialchars($payment['client_Id'] ?? ''); ?>"
                        required
                    >

                    <input
                        type="hidden"
                        name="interview_id"
                        value="<?= htmlspecialchars($payment['interview_id'] ?? ''); ?>"
                    >

                    <input
                        type="hidden"
                        name="total_amount"
                        value="<?= htmlspecialchars($payment['total_amount'] ?? '0'); ?>"
                    >


                    <!-- DUE AMOUNT -->

                    <div class="form-group">

                        <label>
                            Due Amount
                        </label>

                        <input
                            type="text"
                            name="current_due"
                            value="<?= htmlspecialchars($client_due_amount); ?>"
                            readonly
                        >

                    </div>


                    <!-- PAID AMOUNT -->

                    <div class="form-group">

                        <label>
                            Paid Amount <span>*</span>
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="paid_amount"
                            placeholder="Paid Amount"
                            value="<?= htmlspecialchars($payment['paid_amount'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- TDS -->

                    <div class="form-group">

                        <label class="checkbox-label">

                            <input
                                type="checkbox"
                                id="apply_tds"
                                name="apply_tds"
                                <?= $show_tds ? 'checked' : ''; ?>
                            >

                            Apply TDS

                        </label>

                    </div>


                    <div
                        id="tds_section"
                        style="display: <?= $show_tds ? 'block' : 'none'; ?>;"
                    >

                        <div class="form-group">

                            <label>
                                TDS Amount
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="tds_amount"
                                id="tds_amount"
                                value="<?= htmlspecialchars($payment['tds_amount'] ?? '0'); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label class="checkbox-label">

                                <input
                                    type="checkbox"
                                    name="mark_tds_paid"
                                    id="mark_tds_paid"
                                    <?= (floatval($payment['tds_amount'] ?? 0) <= 0) ? 'disabled' : ''; ?>
                                >

                                Mark TDS Paid

                            </label>

                        </div>

                    </div>


                    <!-- PAYMENT DATE + DUE DATE -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Payment Date <span>*</span>
                            </label>

                            <input
                                type="date"
                                name="payment_date"
                                value="<?= htmlspecialchars($payment['payment_date'] ?? date('Y-m-d')); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Due Date
                            </label>

                            <input
                                type="date"
                                name="due_date"
                                value="<?= htmlspecialchars($payment['due_date'] ?? ''); ?>"
                            >

                        </div>

                    </div>


                    <!-- PAYMENT GATEWAY -->

                    <div class="form-group">

                        <label>
                            Payment Gateway <span>*</span>
                        </label>

                        <select
                            name="payment_gateway"
                            required
                        >

                            <option value="">
                                Select Payment Gateway
                            </option>

                            <?php

                            $gateways = [
                                'Cash',
                                'UPI',
                                'NEFT',
                                'Cheque',
                                'IMPS',
                                'Other'
                            ];

                            foreach ($gateways as $gateway):

                                $selected =
                                    (
                                        isset($payment['payment_gateway'])
                                        &&
                                        $payment['payment_gateway'] === $gateway
                                    )
                                    ? 'selected'
                                    : '';

                            ?>

                                <option
                                    value="<?= htmlspecialchars($gateway); ?>"
                                    <?= $selected; ?>
                                >
                                    <?= htmlspecialchars($gateway); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <?php

                    /* =================================
                       PAYMENT TYPE
                    ================================= */

                    if ($clientId):

                        $sql_check = "
                            SELECT DISTINCT payment_type

                            FROM tbl_payment

                            WHERE client_Id = ?

                            AND payment_type IS NOT NULL
                        ";

                        $stmt_check = $conn->prepare($sql_check);
                        $stmt_check->bind_param('i', $clientId);
                        $stmt_check->execute();

                        $result_check = $stmt_check->get_result();


                        if ($result_check->num_rows > 0):

                    ?>

                        <div class="form-group">

                            <label>
                                Payment Type <span>*</span>
                            </label>

                            <select
                                name="payment_type"
                                required
                            >

                                <option value="">
                                    Select Payment Type
                                </option>

                                <?php while ($row = $result_check->fetch_assoc()): ?>

                                    <option
                                        value="<?= htmlspecialchars($row['payment_type']); ?>"
                                        <?= (
                                            isset($payment['payment_type'])
                                            &&
                                            $payment['payment_type'] == $row['payment_type']
                                        )
                                        ? 'selected'
                                        : ''; ?>
                                    >

                                        <?= htmlspecialchars($row['payment_type']); ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>


                        <!-- INTERVIEW -->

                        <div class="form-group">

                            <label>
                                Interview
                            </label>

                            <select
                                name="interview_id"
                            >

                                <option value="">
                                    Select Interview
                                </option>

                                <?php

                                $sql_int = "
                                    SELECT
                                        interview_id,
                                        candidate_name,
                                        company_name

                                    FROM tbl_placement

                                    WHERE candidate_id = ?
                                ";

                                $stmt_int = $conn->prepare($sql_int);
                                $stmt_int->bind_param('i', $clientId);
                                $stmt_int->execute();

                                $res_int = $stmt_int->get_result();


                                while ($int_row = $res_int->fetch_assoc()):

                                    $selected =
                                        (
                                            ($payment['interview_id'] ?? 0)
                                            ==
                                            $int_row['interview_id']
                                        )
                                        ? 'selected'
                                        : '';

                                ?>

                                    <option
                                        value="<?= htmlspecialchars($int_row['interview_id']); ?>"
                                        <?= $selected; ?>
                                    >

                                        <?= htmlspecialchars($int_row['candidate_name']); ?>

                                        (
                                        <?= htmlspecialchars($int_row['company_name']); ?>
                                        )

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>

                    <?php

                        endif;

                    endif;

                    ?>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>💳</span>

                        Submit

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =========================================
     JAVASCRIPT
========================================= -->

<script>

const checkbox = document.getElementById("apply_tds");

const tdsSection = document.getElementById("tds_section");

const tdsAmount = document.getElementById("tds_amount");

const markTdsPaid = document.getElementById("mark_tds_paid");


if (checkbox) {

    checkbox.addEventListener("change", function () {

        tdsSection.style.display =
            this.checked ? "block" : "none";

    });

}


if (tdsAmount && markTdsPaid) {

    tdsAmount.addEventListener("input", function () {

        markTdsPaid.disabled =
            parseFloat(this.value || 0) <= 0;

        if (markTdsPaid.disabled) {
            markTdsPaid.checked = false;
        }

    });

}


/* =========================================
   PAYMENT CONFIRMATION
========================================= */

function confirmPayment() {

    const paidAmount =
        document.querySelector('[name="paid_amount"]').value;

    const paymentDate =
        document.querySelector('[name="payment_date"]').value;

    const paymentGateway =
        document.querySelector('[name="payment_gateway"]').value;


    const message =
        "Please confirm the payment details:\n\n" +

        "Paid Amount : " +
        paidAmount +
        "\n" +

        "Payment Date : " +
        paymentDate +
        "\n" +

        "Payment Gateway : " +
        paymentGateway +
        "\n\n" +

        "Click OK to save or Cancel to edit.";


    return confirm(message);

}

</script>


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
```
