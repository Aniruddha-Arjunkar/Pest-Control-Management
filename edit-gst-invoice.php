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
   GET INVOICE ID
========================= */
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($invoice_id <= 0) {
    echo "<script>
        alert('Invalid invoice ID');
        window.location.href='view-gst-client.php';
    </script>";
    exit();
}


/* =========================
   HANDLE FORM SUBMISSION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $invoice_id_post = intval($_POST['invoice_id'] ?? 0);

    $amount = floatval($_POST['amount'] ?? 0);

    $gst_type = trim($_POST['gst_type'] ?? '');

    $invoice_date = mysqli_real_escape_string(
        $conn,
        trim($_POST['invoice_date'] ?? '')
    );

    $comments = mysqli_real_escape_string(
        $conn,
        trim($_POST['comments'] ?? '')
    );


    /* =========================
       GST CALCULATION
    ========================= */

    $cgst = 0;
    $sgst = 0;
    $igst = 0;
    $total = 0;

    switch ($gst_type) {

        /* =====================
           EXCLUSIVE
        ===================== */

        case 'exclusive':

            $cgst = round($amount * 0.09, 2);

            $sgst = round($amount * 0.09, 2);

            $igst = 0;

            $total = $amount + $cgst + $sgst;

            break;


        /* =====================
           NORMAL
        ===================== */

        case 'normal':

            $cgst = 0;

            $sgst = 0;

            $igst = 0;

            $total = $amount;

            break;


        /* =====================
           INCLUSIVE
        ===================== */

        case 'inclusive':

            $cgst = round(
                ($amount / 1.18) * 0.09,
                2
            );

            $sgst = round(
                ($amount / 1.18) * 0.09,
                2
            );

            $igst = 0;

            $total = $amount;

            break;


        /* =====================
           IGST
        ===================== */

        case 'igst':

            $cgst = 0;

            $sgst = 0;

            $igst = round(
                $amount * 0.18,
                2
            );

            $total = $amount + $igst;

            break;


        default:

            echo "<script>
                alert('Invalid GST type');
                history.back();
            </script>";

            exit();
    }


    /* =========================
       VALIDATION
    ========================= */

    if ($invoice_id_post <= 0) {

        echo "<script>
            alert('Invalid invoice ID');
            history.back();
        </script>";

        exit();

    }


    if ($amount <= 0) {

        echo "<script>
            alert('Please enter a valid amount');
            history.back();
        </script>";

        exit();

    }


    if (empty($invoice_date)) {

        echo "<script>
            alert('Please select invoice date');
            history.back();
        </script>";

        exit();

    }


    /* =========================
       UPDATE INVOICE
    ========================= */

    $stmt = $conn->prepare("
        UPDATE tbl_gst_invoice
        SET
            gst_type = ?,
            amount = ?,
            cgst = ?,
            sgst = ?,
            igst = ?,
            total_amount = ?,
            invoice_date = ?,
            comments = ?
        WHERE id = ?
    ");

    if (!$stmt) {

        die(
            "Prepare failed: " .
            $conn->error
        );

    }


    $stmt->bind_param(
        "sdddddssi",
        $gst_type,
        $amount,
        $cgst,
        $sgst,
        $igst,
        $total,
        $invoice_date,
        $comments,
        $invoice_id_post
    );


    if (!$stmt->execute()) {

        die(
            "Update failed: " .
            $stmt->error
        );

    }


    /* =========================
       GET INVOICE NUMBER
    ========================= */

    $invNumQuery = $conn->prepare("
        SELECT invoice_number
        FROM tbl_gst_invoice
        WHERE id = ?
    ");

    $invNumQuery->bind_param(
        "i",
        $invoice_id_post
    );

    $invNumQuery->execute();

    $invNumResult =
        $invNumQuery->get_result();

    $invoiceData =
        $invNumResult->fetch_assoc();


    if (!$invoiceData) {

        die("Invoice number not found.");

    }


    $invoice_number =
        $invoiceData['invoice_number'];


    /* =========================
       REDIRECT TO PRINT
    ========================= */

    header(
        "Location: print-tax-invoice-edit.php?invoice_number=" .
        urlencode($invoice_number)
    );

    exit();
}


/* =========================
   FETCH SOURCE TYPE
========================= */

$stmt = $conn->prepare("
    SELECT source_type
    FROM tbl_gst_invoice
    WHERE id = ?
");

$stmt->bind_param(
    "i",
    $invoice_id
);

$stmt->execute();

$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    echo "<script>
        alert('Invoice not found');
        window.location.href='view-gst-client.php';
    </script>";

    exit();

}


$invoiceRow =
    $result->fetch_assoc();

$source_type =
    $invoiceRow['source_type'];


/* =========================
   FETCH RELATED ENTITY
========================= */

switch ($source_type) {

    /* =====================
       PEST CONTROL
    ===================== */

    case 'Pest Control':

        $query = "
            SELECT
                gi.*,
                c.gst_number,
                c.cust_name AS name
            FROM tbl_gst_invoice gi
            JOIN tbl_client c
                ON gi.entity_id = c.Id
            WHERE gi.id = ?
        ";

        break;


    /* =====================
       PLACEMENT
    ===================== */

    case 'Placement':

        $query = "
            SELECT
                gi.*,
                a.gst_number,
                a.name
            FROM tbl_gst_invoice gi
            JOIN tbl_agreement a
                ON gi.entity_id = a.Id
            WHERE gi.id = ?
        ";

        break;


    /* =====================
       CONSULTANT
    ===================== */

    case 'Consultant':

        $query = "
            SELECT
                gi.*,
                asoc.gst_number,
                asoc.company_name AS name
            FROM tbl_gst_invoice gi
            JOIN tbl_associate asoc
                ON gi.entity_id = asoc.Id
            WHERE gi.id = ?
        ";

        break;


    default:

        echo "<script>
            alert('Invalid source type');
            window.location.href='view-gst-client.php';
        </script>";

        exit();
}


/* =========================
   FETCH INVOICE
========================= */

$stmt = $conn->prepare($query);

$stmt->bind_param(
    "i",
    $invoice_id
);

$stmt->execute();

$result =
    $stmt->get_result();


if ($result->num_rows === 0) {

    echo "<script>
        alert('Invoice not found');
        window.location.href='view-gst-client.php';
    </script>";

    exit();

}


$row =
    $result->fetch_assoc();

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

                        <span class="phone-icon">🧾</span>

                        <div>

                            <h2>
                                Edit GST Invoice
                            </h2>

                            <p>
                                Update customer tax invoice details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    action=""
                    method="post"
                    class="refollowup-form"
                >


                    <!-- HIDDEN INVOICE ID -->

                    <input
                        type="hidden"
                        name="invoice_id"
                        value="<?= intval($row['id']); ?>"
                    >


                    <!-- =================================
                         NAME
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Name
                        </label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars($row['name'] ?? ''); ?>"
                            readonly
                        >

                    </div>


                    <!-- =================================
                         GST NUMBER
                    ================================== -->

                    <div class="form-group">

                        <label>
                            GST Number
                        </label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars($row['gst_number'] ?? ''); ?>"
                            readonly
                        >

                    </div>


                    <!-- =================================
                         INVOICE DATE + GST TYPE
                    ================================== -->

                    <div class="form-row">


                        <!-- INVOICE DATE -->

                        <div class="form-group">

                            <label>
                                Invoice Date <span>*</span>
                            </label>

                            <input
                                type="date"
                                name="invoice_date"
                                value="<?= htmlspecialchars($row['invoice_date'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <!-- GST TYPE -->

                        <div class="form-group">

                            <label>
                                GST Type <span>*</span>
                            </label>

                            <select
                                name="gst_type"
                                id="gst_type"
                                required
                            >

                                <option
                                    value="exclusive"
                                    <?= ($row['gst_type'] === 'exclusive') ? 'selected' : ''; ?>
                                >
                                    Add GST (18%)
                                </option>


                                <option
                                    value="normal"
                                    <?= ($row['gst_type'] === 'normal') ? 'selected' : ''; ?>
                                >
                                    No GST
                                </option>


                                <option
                                    value="inclusive"
                                    <?= ($row['gst_type'] === 'inclusive') ? 'selected' : ''; ?>
                                >
                                    GST Included
                                </option>


                                <option
                                    value="igst"
                                    <?= ($row['gst_type'] === 'igst') ? 'selected' : ''; ?>
                                >
                                    Add IGST (18%)
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- =================================
                         AMOUNT
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Amount (Basic) <span>*</span>
                        </label>

                        <input
                            type="number"
                            name="amount"
                            id="amount"
                            step="0.01"
                            min="0"
                            value="<?= htmlspecialchars($row['amount'] ?? '0'); ?>"
                            required
                        >

                    </div>


                    <!-- =================================
                         GST DETAILS
                    ================================== -->

                    <div class="form-row">


                        <!-- CGST -->

                        <div class="form-group">

                            <label>
                                CGST
                            </label>

                            <input
                                type="text"
                                id="cgst"
                                value="<?= number_format((float)$row['cgst'], 2); ?>"
                                readonly
                            >

                        </div>


                        <!-- SGST -->

                        <div class="form-group">

                            <label>
                                SGST
                            </label>

                            <input
                                type="text"
                                id="sgst"
                                value="<?= number_format((float)$row['sgst'], 2); ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- =================================
                         IGST + TOTAL
                    ================================== -->

                    <div class="form-row">


                        <!-- IGST -->

                        <div class="form-group">

                            <label>
                                IGST
                            </label>

                            <input
                                type="text"
                                id="igst"
                                value="<?= number_format((float)$row['igst'], 2); ?>"
                                readonly
                            >

                        </div>


                        <!-- TOTAL -->

                        <div class="form-group">

                            <label>
                                Total Amount
                            </label>

                            <input
                                type="text"
                                id="total"
                                value="<?= number_format((float)$row['total_amount'], 2); ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- =================================
                         COMMENTS
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Comments
                        </label>

                        <textarea
                            name="comments"
                            rows="4"
                            placeholder="Enter comments..."
                        ><?= htmlspecialchars($row['comments'] ?? ''); ?></textarea>

                    </div>


                    <!-- =================================
                         SUBMIT
                    ================================== -->

                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        <span>🧾</span>

                        Update & Print Invoice

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>



<!-- =========================================
     GST CALCULATION
========================================= -->

<script>

const gstType =
    document.getElementById('gst_type');

const amountInput =
    document.getElementById('amount');

const cgstInput =
    document.getElementById('cgst');

const sgstInput =
    document.getElementById('sgst');

const igstInput =
    document.getElementById('igst');

const totalInput =
    document.getElementById('total');


function calculateGST() {

    let amount =
        parseFloat(amountInput.value) || 0;

    let type =
        gstType.value;

    let cgst = 0;

    let sgst = 0;

    let igst = 0;

    let total = 0;


    /* =========================
       EXCLUSIVE
    ========================= */

    if (type === 'exclusive') {

        cgst =
            parseFloat(
                (amount * 0.09).toFixed(2)
            );

        sgst =
            parseFloat(
                (amount * 0.09).toFixed(2)
            );

        igst = 0;

        total =
            amount + cgst + sgst;

    }


    /* =========================
       NORMAL
    ========================= */

    else if (type === 'normal') {

        cgst = 0;

        sgst = 0;

        igst = 0;

        total =
            amount;

    }


    /* =========================
       INCLUSIVE
    ========================= */

    else if (type === 'inclusive') {

        const basicAmount =
            amount / 1.18;

        cgst =
            parseFloat(
                (basicAmount * 0.09).toFixed(2)
            );

        sgst =
            parseFloat(
                (basicAmount * 0.09).toFixed(2)
            );

        igst = 0;

        total =
            amount;

    }


    /* =========================
       IGST
    ========================= */

    else if (type === 'igst') {

        cgst = 0;

        sgst = 0;

        igst =
            parseFloat(
                (amount * 0.18).toFixed(2)
            );

        total =
            amount + igst;

    }


    /* =========================
       DISPLAY
    ========================= */

    cgstInput.value =
        cgst.toFixed(2);

    sgstInput.value =
        sgst.toFixed(2);

    igstInput.value =
        igst.toFixed(2);

    totalInput.value =
        total.toFixed(2);
}


/* =========================
   EVENTS
========================= */

gstType.addEventListener(
    'change',
    calculateGST
);

amountInput.addEventListener(
    'input',
    calculateGST
);


/* =========================
   INITIAL CALCULATION
========================= */

calculateGST();

</script>



<!-- =========================================
     OTHER JS
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