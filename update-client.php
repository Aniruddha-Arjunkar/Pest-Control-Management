<?php
ob_start();

session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

/* =========================================
   GET CLIENT ID
========================================= */

$client_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($client_id <= 0) {
    echo "<script>
        alert('Invalid client ID.');
        window.location.href='view-allclient.php';
    </script>";
    exit();
}

/* =========================================
   FETCH CLIENT
========================================= */

$sql = "SELECT * FROM tbl_client WHERE Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $client = $result->fetch_assoc();
} else {
    echo "<script>
        alert('Client not found.');
        window.location.href='view-allclient.php';
    </script>";
    exit();
}

/* =========================================
   HELPER FUNCTION
========================================= */

function getDecimal($postArray, $key, $default = 0)
{
    return isset($postArray[$key]) && is_numeric($postArray[$key])
        ? floatval($postArray[$key])
        : $default;
}

/* =========================================
   UPDATE CLIENT
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $services = mysqli_real_escape_string(
        $conn,
        trim($_POST['services'] ?? '')
    );

    $warranty = mysqli_real_escape_string(
        $conn,
        trim($_POST['warranty'] ?? '')
    );

    $cust_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_name'] ?? '')
    );

    $cust_phone = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_phone'] ?? '')
    );

    $cust_email = !empty($_POST['cust_email'])
        ? mysqli_real_escape_string($conn, trim($_POST['cust_email']))
        : null;

    $cust_address = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_address'] ?? '')
    );

    $total_amount = getDecimal(
        $_POST,
        'total_amount',
        0
    );

    $source = mysqli_real_escape_string(
        $conn,
        trim($_POST['source'] ?? '')
    );

    $due_date = mysqli_real_escape_string(
        $conn,
        trim($_POST['due_date'] ?? '')
    );

    $operator_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['operator_name'] ?? '')
    );

    $pest_covered = mysqli_real_escape_string(
        $conn,
        trim($_POST['pest_covered'] ?? '')
    );

    $other_information = mysqli_real_escape_string(
        $conn,
        trim($_POST['other_information'] ?? '')
    );

    $invoice_no = mysqli_real_escape_string(
        $conn,
        trim($_POST['invoice_no'] ?? '')
    );

    $branch = mysqli_real_escape_string(
        $conn,
        trim($_POST['branch'] ?? '')
    );

    /* GST */

    $cgst = getDecimal($_POST, 'cgst', 0);
    $sgst = getDecimal($_POST, 'sgst', 0);
    $gst_total = getDecimal($_POST, 'gst_total', 0);

    $final_amount = getDecimal(
        $_POST,
        'final_amount',
        $total_amount
    );

    $gst_number = !empty($_POST['gst_number'])
        ? mysqli_real_escape_string($conn, trim($_POST['gst_number']))
        : null;

    $gst_invoice_no = !empty($_POST['gst_invoice_no'])
        ? mysqli_real_escape_string($conn, trim($_POST['gst_invoice_no']))
        : null;

    $shipping_address = !empty($_POST['shipping_address'])
        ? mysqli_real_escape_string($conn, trim($_POST['shipping_address']))
        : null;


    /* =========================================
       AUTO GST INVOICE NUMBER
    ========================================= */

    if (
        (floatval($cgst) > 0 || floatval($sgst) > 0)
        &&
        (
            empty($gst_invoice_no)
            ||
            !ctype_digit($gst_invoice_no)
        )
    ) {

        $resultGST = $conn->query("
            SELECT MAX(CAST(gst_invoice_no AS UNSIGNED)) AS max_no
            FROM tbl_client
            WHERE gst_invoice_no REGEXP '^[0-9]+$'
        ");

        if ($resultGST && $gstRow = $resultGST->fetch_assoc()) {

            $max_no = intval($gstRow['max_no']);

            $next_no = $max_no > 0
                ? $max_no + 1
                : 175;

        } else {

            $next_no = 175;
        }

        $gst_invoice_no = strval($next_no);
    }


    /* =========================================
       UPDATE CLIENT
    ========================================= */

    $sql = "UPDATE tbl_client SET
        services = ?,
        warranty = ?,
        cust_name = ?,
        cust_phone = ?,
        cust_email = ?,
        cust_address = ?,
        total_amount = ?,
        source = ?,
        due_date = ?,
        operator_name = ?,
        pest_covered = ?,
        other_information = ?,
        invoice_no = ?,
        branch = ?,
        cgst = ?,
        sgst = ?,
        gst_total = ?,
        final_amount = ?,
        gst_number = ?,
        gst_invoice_no = ?,
        shipping_address = ?
        WHERE Id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssssssssssssssddddsssi",
        $services,
        $warranty,
        $cust_name,
        $cust_phone,
        $cust_email,
        $cust_address,
        $total_amount,
        $source,
        $due_date,
        $operator_name,
        $pest_covered,
        $other_information,
        $invoice_no,
        $branch,
        $cgst,
        $sgst,
        $gst_total,
        $final_amount,
        $gst_number,
        $gst_invoice_no,
        $shipping_address,
        $client_id
    );


    if ($stmt->execute()) {

        /* =========================================
           PAYMENT CHECK
        ========================================= */

        $sql_payment_check = "
            SELECT *
            FROM tbl_payment
            WHERE client_id = ?
        ";

        $stmt_payment_check = $conn->prepare(
            $sql_payment_check
        );

        $stmt_payment_check->bind_param(
            "i",
            $client_id
        );

        $stmt_payment_check->execute();

        $payment_result =
            $stmt_payment_check->get_result();


        /* =========================================
           UPDATE / INSERT PAYMENT
        ========================================= */

        if ($payment_result->num_rows > 0) {

            $sql_payment = "
                UPDATE tbl_payment
                SET total_amount = ?,
                    due_date = ?
                WHERE client_id = ?
            ";

            $stmt_payment =
                $conn->prepare($sql_payment);

            $stmt_payment->bind_param(
                "dsi",
                $final_amount,
                $due_date,
                $client_id
            );

        } else {

            $sql_payment = "
                INSERT INTO tbl_payment
                (
                    client_id,
                    total_amount,
                    due_date
                )
                VALUES (?, ?, ?)
            ";

            $stmt_payment =
                $conn->prepare($sql_payment);

            $stmt_payment->bind_param(
                "ids",
                $client_id,
                $final_amount,
                $due_date
            );
        }


        if ($stmt_payment->execute()) {

            echo "<script>
                alert('Updated Successfully');
                window.location.href =
                'view-allclient-details.php?client_id=$client_id';
            </script>";

            exit();

        } else {

            echo "<script>
                alert('Error updating payment: " .
                addslashes($stmt_payment->error) .
                "');
            </script>";
        }

    } else {

        echo "<script>
            alert('Error updating client: " .
            addslashes($stmt->error) .
            "');
        </script>";
    }
}


/* =========================================
   SOURCE OPTIONS
========================================= */

$sources = [
    'Old Customer',
    'Customer Reference',
    'BNI',
    'Whatsapp',
    'Brochure',
    'Banner',
    'JD',
    'Architect'
];

$currentSource = $client['source'] ?? '';

$isCustom = !in_array(
    $currentSource,
    $sources
);
?>




<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">


                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">✏️</span>

                        <div>

                            <h2>
                                Update Client
                            </h2>

                            <p>
                                Update customer and service details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     FORM
                ================================================== -->

                <form
                    class="refollowup-form"
                    method="post"
                    action=""
                >


                    <!-- INVOICE + BRANCH -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Invoice No
                            </label>

                            <input
                                type="text"
                                id="invoice_no"
                                name="invoice_no"
                                value="<?= htmlspecialchars($client['invoice_no'] ?? '') ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Branch
                            </label>

                            <select
                                id="branch"
                                name="branch"
                                required
                            >

                                <option value="">
                                    Select Branch
                                </option>

                                <option
                                    value="Bhandara"
                                    <?= ($client['branch'] ?? '') == 'Bhandara' ? 'selected' : '' ?>
                                >
                                    Bhandara
                                </option>

                                <option
                                    value="Bramhapuri"
                                    <?= ($client['branch'] ?? '') == 'Bramhapuri' ? 'selected' : '' ?>
                                >
                                    Bramhapuri
                                </option>

                                <option
                                    value="Nagpur"
                                    <?= ($client['branch'] ?? '') == 'Nagpur' ? 'selected' : '' ?>
                                >
                                    Nagpur
                                </option>

                                <option
                                    value="Chandrapur"
                                    <?= ($client['branch'] ?? '') == 'Chandrapur' ? 'selected' : '' ?>
                                >
                                    Chandrapur
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- SERVICE + WARRANTY -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Service <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="services"
                                name="services"
                                value="<?= htmlspecialchars($client['services'] ?? '') ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Warranty <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="warranty"
                                name="warranty"
                                value="<?= htmlspecialchars($client['warranty'] ?? '') ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- PEST -->

                    <div class="form-group">

                        <label>
                            Pest Covered
                        </label>

                        <input
                            type="text"
                            id="pest_covered"
                            name="pest_covered"
                            value="<?= htmlspecialchars($client['pest_covered'] ?? '') ?>"
                        >

                    </div>


                    <!-- TERMS -->

                    <div class="form-group">

                        <label>
                            Service Terms and Conditions
                        </label>

                        <textarea
                            id="other_information"
                            name="other_information"
                            rows="4"
                            maxlength="500"
                        ><?= htmlspecialchars($client['other_information'] ?? '') ?></textarea>

                    </div>


                    <!-- CUSTOMER NAME -->

                    <div class="form-group">

                        <label>
                            Customer Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="cust_name"
                            name="cust_name"
                            value="<?= htmlspecialchars($client['cust_name'] ?? '') ?>"
                            required
                        >

                    </div>


                    <!-- PHONE + EMAIL -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Phone <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="cust_phone"
                                name="cust_phone"
                                value="<?= htmlspecialchars($client['cust_phone'] ?? '') ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Email
                            </label>

                            <input
                                type="email"
                                id="cust_email"
                                name="cust_email"
                                value="<?= htmlspecialchars($client['cust_email'] ?? '') ?>"
                            >

                        </div>

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label>
                            Address <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="cust_address"
                            name="cust_address"
                            value="<?= htmlspecialchars($client['cust_address'] ?? '') ?>"
                            required
                        >

                    </div>


                    <!-- GST CHECKBOX -->

                    <div class="form-group full-width">

                        <label class="gst-checkbox">

                            <input
                                type="checkbox"
                                id="apply_gst"
                                <?= (
                                    ($client['cgst'] ?? 0) > 0
                                    ||
                                    ($client['sgst'] ?? 0) > 0
                                )
                                ? 'checked'
                                : ''
                                ?>
                            >

                            Apply GST (9% CGST + 9% SGST)

                        </label>

                    </div>


                    <!-- AMOUNT + FINAL -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Amount (Without GST)
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                id="total_amount"
                                name="total_amount"
                                value="<?= htmlspecialchars($client['total_amount'] ?? 0) ?>"
                                required
                            >

                        </div>


                        <div class="form-group final-amount-field">

                            <label>
                                Final Amount
                            </label>

                            <input
                                type="text"
                                id="final_amount"
                                name="final_amount"
                                value="<?= htmlspecialchars($client['final_amount'] ?? $client['total_amount'] ?? 0) ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- CGST + SGST -->

                    <div class="form-row gst-fields-container">

                        <div class="form-group gst-field hidden">

                            <label>
                                CGST (9%)
                            </label>

                            <input
                                type="text"
                                id="cgst"
                                name="cgst"
                                value="<?= htmlspecialchars($client['cgst'] ?? 0) ?>"
                                readonly
                            >

                        </div>


                        <div class="form-group gst-field hidden">

                            <label>
                                SGST (9%)
                            </label>

                            <input
                                type="text"
                                id="sgst"
                                name="sgst"
                                value="<?= htmlspecialchars($client['sgst'] ?? 0) ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- GST TOTAL -->

                    <div class="form-group gst-field hidden">

                        <label>
                            Total GST (18%)
                        </label>

                        <input
                            type="text"
                            id="gst_total"
                            name="gst_total"
                            value="<?= htmlspecialchars($client['gst_total'] ?? 0) ?>"
                            readonly
                        >

                    </div>


                    <!-- GST NUMBER -->

                    <div class="form-group gst-field hidden">

                        <label>
                            Customer GST Number
                        </label>

                        <input
                            type="text"
                            id="gst_number"
                            name="gst_number"
                            value="<?= htmlspecialchars($client['gst_number'] ?? '') ?>"
                        >

                    </div>


                    <!-- SHIPPING ADDRESS -->

                    <div class="form-group gst-field hidden">

                        <label>
                            Shipping Address
                        </label>

                        <input
                            type="text"
                            id="shipping_address"
                            name="shipping_address"
                            value="<?= htmlspecialchars($client['shipping_address'] ?? '') ?>"
                        >

                    </div>


                    <!-- SOURCE -->

                    <div class="form-group">

                        <label>
                            Source
                        </label>


                        <select
                            id="source"
                            name="source_select"
                            <?= $isCustom ? 'class="hidden"' : '' ?>
                        >

                            <option value="">
                                Select Source
                            </option>

                            <?php foreach ($sources as $src): ?>

                                <option
                                    value="<?= htmlspecialchars($src) ?>"
                                    <?= ($currentSource == $src) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($src) ?>
                                </option>

                            <?php endforeach; ?>


                            <option
                                value="Other"
                                <?= $isCustom ? 'selected' : '' ?>
                            >
                                Other
                            </option>

                        </select>


                        <input
                            type="text"
                            id="source_other"
                            name="source_other"
                            class="<?= $isCustom ? '' : 'hidden' ?>"
                            placeholder="Please specify other source"
                            value="<?= $isCustom ? htmlspecialchars($currentSource) : '' ?>"
                            <?= $isCustom ? 'required' : '' ?>
                        >


                        <input
                            type="hidden"
                            id="final_source"
                            name="source"
                            value="<?= htmlspecialchars($currentSource) ?>"
                        >

                    </div>


                    <!-- SERVICE DATE -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Service Date <span>*</span>
                            </label>

                            <input
                                type="date"
                                id="due_date"
                                name="due_date"
                                value="<?= htmlspecialchars($client['due_date'] ?? '') ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Operator Name
                            </label>

                            <input
                                type="text"
                                id="operator_name"
                                name="operator_name"
                                value="<?= htmlspecialchars($client['operator_name'] ?? '') ?>"
                            >

                        </div>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        <span>✏️</span>

                        Update Client

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

/* =====================================================
   GST CALCULATION
===================================================== */

function calculateGST() {

    const totalInput =
        document.getElementById('total_amount');

    const applyGST =
        document.getElementById('apply_gst');

    const cgstInput =
        document.getElementById('cgst');

    const sgstInput =
        document.getElementById('sgst');

    const gstTotalInput =
        document.getElementById('gst_total');

    const finalAmountInput =
        document.getElementById('final_amount');


    const total =
        parseFloat(totalInput.value) || 0;


    if (applyGST.checked) {

        const cgst =
            total * 0.09;

        const sgst =
            total * 0.09;

        const gstTotal =
            cgst + sgst;

        const finalAmount =
            total + gstTotal;


        cgstInput.value =
            cgst.toFixed(2);

        sgstInput.value =
            sgst.toFixed(2);

        gstTotalInput.value =
            gstTotal.toFixed(2);

        finalAmountInput.value =
            finalAmount.toFixed(2);


        document
            .querySelectorAll('.gst-field')
            .forEach(function(el) {

                el.classList.remove('hidden');

            });


    } else {

        cgstInput.value = '0';

        sgstInput.value = '0';

        gstTotalInput.value = '0';

        finalAmountInput.value =
            total.toFixed(2);


        document
            .querySelectorAll('.gst-field')
            .forEach(function(el) {

                el.classList.add('hidden');

            });
    }
}


/* GST EVENTS */

document
    .getElementById('apply_gst')
    .addEventListener(
        'change',
        calculateGST
    );


document
    .getElementById('total_amount')
    .addEventListener(
        'input',
        calculateGST
    );


/* =====================================================
   SOURCE
===================================================== */

const sourceSelect =
    document.getElementById('source');

const sourceInput =
    document.getElementById('source_other');

const finalSource =
    document.getElementById('final_source');


sourceSelect?.addEventListener(
    'change',
    function() {

        if (this.value === 'Other') {

            this.classList.add('hidden');

            sourceInput.classList.remove('hidden');

            sourceInput.required = true;

            sourceInput.focus();

            finalSource.value = '';

        } else {

            finalSource.value =
                this.value;

        }

    }
);


sourceInput?.addEventListener(
    'input',
    function() {

        finalSource.value =
            this.value.trim();


        if (this.value.trim() === '') {

            sourceInput.classList.add('hidden');

            sourceInput.required = false;

            sourceSelect.classList.remove('hidden');

            sourceSelect.value = '';

            finalSource.value = '';

        }

    }
);


/* =====================================================
   PAGE LOAD
===================================================== */

window.addEventListener(
    'load',
    function() {

        if (
            !sourceSelect.classList
                .contains('hidden')
        ) {

            finalSource.value =
                sourceSelect.value;

        } else {

            finalSource.value =
                sourceInput.value.trim();

        }


        calculateGST();

    }
);


/* =====================================================
   AUTO NUMBERING TERMS
===================================================== */

const textarea =
    document.getElementById(
        'other_information'
    );


textarea.addEventListener(
    'focus',
    function() {

        if (this.value.trim() === '') {

            this.value = '1. ';

        }

    }
);


textarea.addEventListener(
    'keydown',
    function(e) {

        if (e.key !== 'Enter') {
            return;
        }

        e.preventDefault();


        const selectionStart =
            textarea.selectionStart;

        const value =
            textarea.value;


        let lines =
            value.split('\n');


        let charCount = 0;

        let currentLineIndex = 0;

        let offsetInLine = 0;


        for (
            let i = 0;
            i < lines.length;
            i++
        ) {

            if (
                selectionStart <=
                charCount +
                lines[i].length
            ) {

                currentLineIndex = i;

                offsetInLine =
                    selectionStart -
                    charCount;

                break;

            }


            charCount +=
                lines[i].length + 1;

        }


        let cleanLines =
            lines.map(function(line) {

                return line.replace(
                    /^\d+\.\s*/,
                    ''
                );

            });


        const currentLine =
            cleanLines[currentLineIndex];


        const lineStart =
            currentLine.slice(
                0,
                offsetInLine
            );


        const lineEnd =
            currentLine.slice(
                offsetInLine
            );


        cleanLines[currentLineIndex] =
            lineStart;


        cleanLines.splice(
            currentLineIndex + 1,
            0,
            lineEnd
        );


        const numberedLines =
            cleanLines.map(
                function(line, index) {

                    return (
                        (index + 1) +
                        '. ' +
                        line
                    );

                }
            );


        textarea.value =
            numberedLines.join('\n');


        let cursorPos = 0;


        for (
            let i = 0;
            i <= currentLineIndex + 1;
            i++
        ) {

            cursorPos +=
                numberedLines[i].length + 1;

        }


        cursorPos -=
            (
                numberedLines[
                    currentLineIndex + 1
                ].length -
                cleanLines[
                    currentLineIndex + 1
                ].length -
                2
            );


        textarea.selectionStart =
            textarea.selectionEnd =
                cursorPos;

    }
);

</script>


<!-- =====================================================
     SCRIPTS
===================================================== -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>


</body>

</html>

<?php

$conn->close();

ob_end_flush();

?>
