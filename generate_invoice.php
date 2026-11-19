<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
ob_start();

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

/* =========================================
   GET DATA
========================================= */

$client_id = $_GET['client_id'] ?? '';
$source_type = strtolower(trim($_GET['source_type'] ?? ''));
$amountFromUrl = $_GET['amount'] ?? '';

$success = '';
$error = '';

$gst_type_selected = $_POST['gst_type'] ?? '';

$baseAmount = $amountFromUrl;

$commentPrefill = '';

$placement_interview_id = null;


/* =========================================
   FETCH AMOUNT
========================================= */

if (!empty($client_id) && !empty($source_type)) {

    $safe_client_id = intval($client_id);

    switch ($source_type) {

        /* =========================
           PEST CONTROL
        ========================= */

        case 'pest control':

            $sql = "
                SELECT final_amount, other_information
                FROM tbl_client
                WHERE Id = $safe_client_id
                LIMIT 1
            ";

            $result = mysqli_query($conn, $sql);

            if ($result && $row = mysqli_fetch_assoc($result)) {

                $baseAmount = $row['final_amount'];

                $commentPrefill =
                    $row['other_information'];
            }

            break;


        /* =========================
           PLACEMENT
        ========================= */

        case 'placement':

            if (empty($baseAmount)) {

                $sql = "
                    SELECT candidate_pay_amount, interview_id
                    FROM tbl_placement
                    WHERE candidate_id = $safe_client_id
                    LIMIT 1
                ";

                $result = mysqli_query($conn, $sql);

                if ($result && $row = mysqli_fetch_assoc($result)) {

                    $baseAmount =
                        $row['candidate_pay_amount'];

                    $placement_interview_id =
                        $row['interview_id'];
                }
            }

            break;


        /* =========================
           CONSULTANT
        ========================= */

        case 'consultant':

            if (empty($baseAmount)) {

                $sql = "
                    SELECT
                        p.company_pay_amount,
                        p.interview_id
                    FROM tbl_associate a
                    LEFT JOIN tbl_placement p
                        ON p.company_name = a.company_name
                    WHERE a.Id = $safe_client_id
                    LIMIT 1
                ";

                $result = mysqli_query($conn, $sql);

                if ($result && $row = mysqli_fetch_assoc($result)) {

                    $baseAmount =
                        $row['company_pay_amount'] ?? 0;

                    $placement_interview_id =
                        $row['interview_id'] ?? null;
                }
            }

            break;
    }
}


/* =========================================
   FORM SUBMIT
========================================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $entity_id =
        intval($_POST['client_id'] ?? 0);

    $sourceType =
        mysqli_real_escape_string(
            $conn,
            $_POST['source_type'] ?? ''
        );

    $amount =
        floatval($_POST['amount'] ?? 0);

    $gst_type =
        $_POST['gst_type'] ?? '';

    $invoice_date =
        mysqli_real_escape_string(
            $conn,
            $_POST['invoice_date'] ?? ''
        );

    $comments =
        mysqli_real_escape_string(
            $conn,
            $_POST['comments'] ?? ''
        );

    $igst = 0;

    $placement_interview_id =
        !empty($_POST['placement_interview_id'])
            ? intval($_POST['placement_interview_id'])
            : null;

    $placement_interview_id_sql =
        $placement_interview_id !== null
            ? $placement_interview_id
            : "NULL";


    /* =========================================
       VALIDATION
    ========================================= */

    if (empty($sourceType)) {

        $error = "Missing source type.";

    } elseif ($entity_id <= 0) {

        $error = "Invalid client ID.";

    } elseif ($amount <= 0) {

        $error = "Please enter a valid amount.";

    } elseif (empty($invoice_date)) {

        $error = "Please select invoice date.";
    }


    /* =========================================
       GST CALCULATION
    ========================================= */

    if (empty($error)) {

        if ($gst_type === 'exclusive') {

            $basicAmount = $amount;

            $cgst =
                $basicAmount * 0.09;

            $sgst =
                $basicAmount * 0.09;

            $totalAmount =
                $basicAmount + $cgst + $sgst;

        }

        elseif ($gst_type === 'inclusive') {

            $basicAmount =
                $amount / 1.18;

            $cgst =
                $basicAmount * 0.09;

            $sgst =
                $basicAmount * 0.09;

            $totalAmount =
                $basicAmount + $cgst + $sgst;

        }

        elseif ($gst_type === 'normal') {

            $basicAmount = $amount;

            $cgst = 0;

            $sgst = 0;

            $totalAmount =
                $basicAmount;

        }

        elseif ($gst_type === 'igst') {

            $basicAmount = $amount;

            $cgst = 0;

            $sgst = 0;

            $igst =
                $basicAmount * 0.18;

            $totalAmount =
                $basicAmount + $igst;

        }

        else {

            $error = "Invalid GST type.";
        }
    }


    /* =========================================
       SAME AMOUNT VALIDATION
    ========================================= */

    if (empty($error)) {

        $oldSql = "
            SELECT final_amount
            FROM tbl_client
            WHERE Id = $entity_id
            LIMIT 1
        ";

        $oldRes =
            mysqli_query($conn, $oldSql);

        $oldRow =
            $oldRes
                ? mysqli_fetch_assoc($oldRes)
                : null;

        $oldAmount =
            floatval(
                $oldRow['final_amount'] ?? 0
            );


        $lastInvSql = "
            SELECT total_amount
            FROM tbl_gst_invoice
            WHERE entity_id = $entity_id
            AND source_type = '$sourceType'
            ORDER BY id DESC
            LIMIT 1
        ";

        $lastInvRes =
            mysqli_query(
                $conn,
                $lastInvSql
            );

        $lastInvRow =
            $lastInvRes
                ? mysqli_fetch_assoc($lastInvRes)
                : null;

        $lastInvoiceAmount =
            floatval(
                $lastInvRow['total_amount'] ?? 0
            );


        if (
            $lastInvoiceAmount > 0 &&
            abs($oldAmount - $totalAmount) < 0.01
        ) {

            $error =
                "Same amount GST invoice already generated. Change amount to generate new invoice.";
        }
    }


    /* =========================================
       INSERT INVOICE
    ========================================= */

    if (empty($error)) {

        $res = mysqli_query(
            $conn,
            "
            SELECT MAX(invoice_number) AS last_inv
            FROM tbl_gst_invoice
            "
        );

        $row =
            $res
                ? mysqli_fetch_assoc($res)
                : null;

        $lastInvoice =
            intval($row['last_inv'] ?? 0);

        $newInvoiceNumber =
            ($lastInvoice > 0)
                ? $lastInvoice + 1
                : 201;


        /* =====================================
           INSERT
        ===================================== */

        $sql = "
            INSERT INTO tbl_gst_invoice
            (
                entity_id,
                source_type,
                placement_interview_id,
                invoice_number,
                gst_type,
                amount,
                cgst,
                sgst,
                igst,
                total_amount,
                invoice_date,
                comments
            )
            VALUES
            (
                $entity_id,
                '$sourceType',
                $placement_interview_id_sql,
                $newInvoiceNumber,
                '$gst_type',
                $basicAmount,
                $cgst,
                $sgst,
                $igst,
                $totalAmount,
                '$invoice_date',
                '$comments'
            )
        ";


        if (mysqli_query($conn, $sql)) {

            echo "
                <script>
                    window.open(
                        'print-tax-invoice-edit.php?invoice_number=$newInvoiceNumber',
                        '_blank'
                    );

                    window.location.href =
                        'view-gst-client.php';
                </script>
            ";

            exit();

        } else {

            $error =
                "Invoice insert failed: " .
                mysqli_error($conn);
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

                        <span class="phone-icon">📞</span>

                        <div>

                            <h2>
                                Generate GST Invoice
                            </h2>

                            <p>
                                Create customer tax invoice
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    method="post"
                    action=""
                    class="refollowup-form"
                >


                    <!-- HIDDEN CLIENT ID -->

                    <input
                        type="hidden"
                        name="client_id"
                        value="<?= htmlspecialchars($client_id) ?>"
                    >


                    <!-- HIDDEN SOURCE TYPE -->

                    <input
                        type="hidden"
                        name="source_type"
                        value="<?= htmlspecialchars($source_type) ?>"
                    >


                    <!-- HIDDEN INTERVIEW ID -->

                    <input
                        type="hidden"
                        name="placement_interview_id"
                        value="<?= isset($placement_interview_id)
                            ? intval($placement_interview_id)
                            : '' ?>"
                    >


                    <!-- =================================
                         ERROR MESSAGE
                    ================================== -->

                    <?php if ($error): ?>

                        <div class="alert alert-danger">

                            <?= htmlspecialchars($error) ?>

                        </div>

                    <?php endif; ?>


                    <!-- =================================
                         SUCCESS MESSAGE
                    ================================== -->

                    <?php if ($success): ?>

                        <div class="alert alert-success">

                            <?= htmlspecialchars($success) ?>

                        </div>

                    <?php endif; ?>


                    <!-- =================================
                         BASE AMOUNT
                    ================================== -->

                    <div class="form-row">

                        <div class="form-group full-width">

                            <label>
                                Base Amount (₹)
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="amount"
                                id="amount"
                                value="<?= htmlspecialchars($baseAmount) ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- =================================
                         GST TYPE
                    ================================== -->

                    <div class="form-row">

                        <div class="form-group full-width">

                            <label>
                                GST Type
                            </label>

                            <select
                                name="gst_type"
                                id="gst_type"
                                required
                            >

                                <option value="">
                                    Select GST Type
                                </option>

                                <option
                                    value="exclusive"
                                    <?= (
                                        $gst_type_selected
                                        == 'exclusive'
                                    )
                                    ? 'selected'
                                    : '' ?>
                                >
                                    Add GST (18%)
                                </option>

                                <option
                                    value="inclusive"
                                    <?= (
                                        $gst_type_selected
                                        == 'inclusive'
                                    )
                                    ? 'selected'
                                    : '' ?>
                                >
                                    GST Included (Back Calculate)
                                </option>

                                <option
                                    value="normal"
                                    <?= (
                                        $gst_type_selected
                                        == 'normal'
                                    )
                                    ? 'selected'
                                    : '' ?>
                                >
                                    No GST
                                </option>

                                <option
                                    value="igst"
                                    <?= (
                                        $gst_type_selected
                                        == 'igst'
                                    )
                                    ? 'selected'
                                    : '' ?>
                                >
                                    Add IGST (18%)
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- =================================
                         GST SUMMARY
                    ================================== -->

                    <div class="form-row">

                        <div class="form-group full-width">

                            <h3>
                                GST Summary
                            </h3>

                            <p class="main-total">

                                <strong>
                                    Total Amount:
                                </strong>

                                ₹<span id="total_val">
                                    0.00
                                </span>

                            </p>


                            <!-- CGST -->

                            <p id="cgst_row">

                                <strong>
                                    CGST (9%):
                                </strong>

                                ₹<span id="cgst_val">
                                    0.00
                                </span>

                            </p>


                            <!-- SGST -->

                            <p id="sgst_row">

                                <strong>
                                    SGST (9%):
                                </strong>

                               ₹<span id="sgst_val">
                                    0.00
                                </span>

                            </p>


                            <!-- IGST -->

                            <p id="igst_row" hidden>

                                <strong>
                                    IGST (18%):
                                </strong>

                                ₹<span id="igst_val">
                                    0.00
                                </span>

                            </p>


                            <!-- BASE -->

                            <p>

                                <strong>
                                    Base Amount:
                                </strong>

                               ₹<span id="base_val">
                                    0.00
                                </span>

                            </p>

                        </div>

                    </div>


                    <!-- =================================
                         INVOICE DATE
                    ================================== -->

                    <div class="form-row">

                        <div class="form-group full-width">

                            <label>
                                Invoice Date
                            </label>

                            <input
                                type="date"
                                name="invoice_date"
                                value="<?= !empty($_POST['invoice_date'])
                                    ? htmlspecialchars($_POST['invoice_date'])
                                    : date('Y-m-d') ?>"
                                max="<?= date('Y-m-d') ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- =================================
                         COMMENTS
                    ================================== -->

                    <div class="form-row">

                        <div class="form-group full-width">

                            <label>
                                Comments
                            </label>

                            <textarea
                                name="comments"
                                rows="3"
                                placeholder="Enter comments..."
                            ><?= htmlspecialchars(
                                !empty($_POST['comments'])
                                    ? $_POST['comments']
                                    : $commentPrefill
                            ) ?></textarea>

                        </div>

                    </div>


                    <!-- =================================
                         SUBMIT
                    ================================== -->

                    <div class="form-row">

                        <div class="form-group full-width">

                            <button
                                type="submit"
                                class="submit-btn"
                            >
                                Generate Invoice
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </section>

</section>


<!-- =========================================
     GST CALCULATION JAVASCRIPT
========================================= -->

<script>

const amountInput =
    document.getElementById('amount');

const gstTypeSelect =
    document.getElementById('gst_type');

const totalVal =
    document.getElementById('total_val');

const baseVal =
    document.getElementById('base_val');

const cgstVal =
    document.getElementById('cgst_val');

const sgstVal =
    document.getElementById('sgst_val');

const igstVal =
    document.getElementById('igst_val');


const cgstRow =
    document.getElementById('cgst_row');

const sgstRow =
    document.getElementById('sgst_row');

const igstRow =
    document.getElementById('igst_row');


/* =========================================
   CALCULATE GST
========================================= */

function calculateTotal() {

    const amount =
        parseFloat(amountInput.value) || 0;

    const gstType =
        gstTypeSelect.value;


    let base = amount;

    let cgst = 0;

    let sgst = 0;

    let igst = 0;

    let total = 0;


    /* Hide GST rows */

    cgstRow.hidden = true;

    sgstRow.hidden = true;

    igstRow.hidden = true;


    /* =====================================
       EXCLUSIVE GST
    ===================================== */

    if (gstType === 'exclusive') {

        base = amount;

        cgst =
            base * 0.09;

        sgst =
            base * 0.09;

        total =
            base + cgst + sgst;


        cgstRow.hidden = false;

        sgstRow.hidden = false;
    }


    /* =====================================
       INCLUSIVE GST
    ===================================== */

    else if (gstType === 'inclusive') {

        base =
            amount / 1.18;

        cgst =
            base * 0.09;

        sgst =
            base * 0.09;

        total =
            amount;


        cgstRow.hidden = false;

        sgstRow.hidden = false;
    }


    /* =====================================
       NORMAL
    ===================================== */

    else if (gstType === 'normal') {

        base =
            amount;

        total =
            base;
    }


    /* =====================================
       IGST
    ===================================== */

    else if (gstType === 'igst') {

        base =
            amount;

        igst =
            base * 0.18;

        total =
            base + igst;


        igstRow.hidden = false;
    }


    /* =====================================
       DISPLAY VALUES
    ===================================== */

    totalVal.textContent =
        total.toFixed(2);

    baseVal.textContent =
        base.toFixed(2);

    cgstVal.textContent =
        cgst.toFixed(2);

    sgstVal.textContent =
        sgst.toFixed(2);

    igstVal.textContent =
        igst.toFixed(2);
}


/* =========================================
   AMOUNT CHANGE
========================================= */

amountInput.addEventListener(
    'input',
    calculateTotal
);


/* =========================================
   GST TYPE CHANGE
========================================= */

gstTypeSelect.addEventListener(
    'change',
    calculateTotal
);


/* =========================================
   PAGE LOAD
========================================= */

window.addEventListener(
    'load',
    function () {

        if (!gstTypeSelect.value) {

            gstTypeSelect.value =
                'exclusive';
        }

        calculateTotal();

    }
);

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
<script src="js/search.js"></script>

</body>
</html>

<?php
ob_end_flush();
?>