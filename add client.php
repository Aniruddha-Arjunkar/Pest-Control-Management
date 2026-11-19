<?php

session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';


/* =====================================================
   INVOICE NUMBER
===================================================== */

$starting_invoice_no = 2089;
$starting_gst_invoice_no = 175;


/* Normal Invoice */

$sql = "SELECT MAX(invoice_no) AS max_invoice FROM tbl_client";

$result = mysqli_query($conn, $sql);

$invoice_no = $starting_invoice_no;

if ($result && $row_invoice = mysqli_fetch_assoc($result)) {

    $max_invoice = $row_invoice['max_invoice'];

    if (
        $max_invoice !== null &&
        $max_invoice >= $starting_invoice_no
    ) {
        $invoice_no = $max_invoice + 1;
    }
}


/* GST Invoice */

$sql_gst = "
    SELECT MAX(gst_invoice_no) AS max_gst_invoice
    FROM tbl_client
    WHERE gst_invoice_no IS NOT NULL
";

$result_gst = mysqli_query($conn, $sql_gst);

$gst_invoice_no = $starting_gst_invoice_no;

if ($result_gst && $row_gst = mysqli_fetch_assoc($result_gst)) {

    $max_gst_invoice = $row_gst['max_gst_invoice'];

    if (
        $max_gst_invoice !== null &&
        $max_gst_invoice >= $starting_gst_invoice_no
    ) {
        $gst_invoice_no = $max_gst_invoice + 1;
    }
}


/* =====================================================
   GET INQUIRY ID
===================================================== */

$Id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;


/* =====================================================
   GET INQUIRY DATA
===================================================== */

$row = null;

if ($Id > 0) {

    $stmt = $conn->prepare("
        SELECT 
            i.*,
            c.operator_name
        FROM tbl_inquiry i
        LEFT JOIN tbl_client c 
            ON c.client_Id = i.id
        WHERE i.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $Id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {

        $row = $result->fetch_assoc();

    }

    $stmt->close();
}

?>




<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">


                <!-- =========================
                     HEADER
                ========================== -->

                <div class="add-client-header">

                    <div class="add-client-title">

                        <div class="client-icon">
                            ðŸ‘¤
                        </div>

                        <div>

                            <h2>
                                Add Client
                            </h2>

                            <p>
                                Create customer and billing details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =========================
                     FORM
                ========================== -->

                <form
                    action="add-client.php"
                    method="post"
                    class="add-client-form"
                >

                    <div class="form-row">


                        <?php

                        if ($row) {

                        ?>


                        <!-- =========================
                             SERVICE DETAILS
                        ========================== -->

                        <div class="form-section-title">
                            Service Details
                        </div>


                        <!-- SERVICE -->

                        <div class="form-group">

                            <label for="services">
                                Service
                            </label>

                            <input
                                type="text"
                                id="services"
                                name="services"
                                value="<?= htmlspecialchars(
                                    $row['services'] ?? ''
                                ); ?>"
                            >

                        </div>


                        <!-- WARRANTY -->

                        <div class="form-group">

                            <label for="warranty">
                                Warranty
                            </label>

                            <input
                                type="text"
                                id="warranty"
                                name="warranty"
                                placeholder="Warranty"
                            >

                        </div>


                        <!-- INVOICE -->

                        <div class="form-group">

                            <label for="invoice_no">
                                Invoice No
                            </label>

                            <input
                                type="text"
                                id="invoice_no"
                                name="invoice_no"
                                value="<?= htmlspecialchars(
                                    $invoice_no
                                ); ?>"
                                readonly
                                required
                            >

                        </div>


                        <!-- BRANCH -->

                        <div class="form-group">

                            <label for="branch">
                                Branch
                            </label>

                            <select
                                id="branch"
                                name="branch"
                            >

                                <option value="">
                                    Select Branch
                                </option>

                                <option value="Bhandara">
                                    Bhandara
                                </option>

                                <option value="Bramhapuri">
                                    Bramhapuri
                                </option>

                                <option value="Nagpur">
                                    Nagpur
                                </option>

                                <option value="Chandrapur">
                                    Chandrapur
                                </option>

                            </select>

                        </div>


                        <!-- PEST COVERED -->

                        <div class="form-group">

                            <label for="pest_covered">
                                Pest Covered
                            </label>

                            <input
                                type="text"
                                id="pest_covered"
                                name="pest_covered"
                                placeholder="Pest Covered"
                            >

                        </div>


                        <!-- OPERATOR -->

                        <div class="form-group">

                            <label for="operator_name">
                                Operator Name
                            </label>

                            <input
                                type="text"
                                id="operator_name"
                                name="operator_name"
                                value="<?= htmlspecialchars(
                                    $row['operator_name'] ?? ''
                                ); ?>"
                            >

                        </div>


                        <!-- SERVICE TERMS -->

                        <div class="form-group full-width">

                            <label for="other_information">
                                Service Terms & Conditions
                            </label>

                            <textarea
                                id="other_information"
                                name="other_information"
                                rows="5"
                                maxlength="500"
                            >1. (e.g. Area)- Treated for (e.g. Service)
2. Problem resolution period will be valid till (e.g. Year) from the date of service execution.
3. No warranty on infrastructure changes.
4. 1 Month cooling period from date of service execution to get complete solution.</textarea>

                        </div>


                        <!-- =========================
                             CUSTOMER DETAILS
                        ========================== -->

                        <div class="form-section-title">
                            Customer Details
                        </div>


                        <!-- CUSTOMER NAME -->

                        <div class="form-group">

                            <label for="cust_name">
                                Customer Name
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                id="cust_name"
                                name="cust_name"
                                placeholder="Customer Name"
                                value="<?= htmlspecialchars(
                                    $row['cust_name'] ?? ''
                                ); ?>"
                                required
                            >

                        </div>


                        <!-- PHONE -->

                        <div class="form-group">

                            <label for="cust_phone">
                                Phone
                                <span class="required">*</span>
                            </label>

                            <input
                                maxlength="10"
                                type="text"
                                id="cust_phone"
                                name="cust_phone"
                                placeholder="Phone"
                                value="<?= htmlspecialchars(
                                    $row['cust_phone'] ?? ''
                                ); ?>"
                                required
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="form-group">

                            <label for="cust_email">
                                Email
                            </label>

                            <input
                                type="email"
                                id="cust_email"
                                name="cust_email"
                                placeholder="Email"
                                value="<?= htmlspecialchars(
                                    $row['cust_email'] ?? ''
                                ); ?>"
                            >

                        </div>


                        <!-- ADDRESS -->

                        <div class="form-group">

                            <label for="cust_address">
                                Address
                            </label>

                            <input
                                type="text"
                                id="cust_address"
                                name="cust_address"
                                placeholder="Address"
                                value="<?= htmlspecialchars(
                                    $row['cust_address'] ?? ''
                                ); ?>"
                            >

                        </div>


                        <!-- =========================
                             BILLING DETAILS
                        ========================== -->

                        <div class="form-section-title">
                            Billing Details
                        </div>


                        <!-- GST -->

                        <div class="form-group full-width gst-checkbox-group">

                            <label class="gst-checkbox">

                                <input
                                    type="checkbox"
                                    id="apply_gst"
                                    name="apply_gst"
                                >

                                <span>
                                    Apply GST (9% CGST + 9% SGST)
                                </span>

                            </label>

                        </div>


                        <!-- AMOUNT -->

                        <div class="form-group">

                            <label for="total_amount">
                                Amount Without GST
                                <span class="required">*</span>
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                id="total_amount"
                                name="total_amount"
                                placeholder="0.00"
                                required
                            >

                        </div>


                        <!-- FINAL AMOUNT -->

                        <div class="form-group">

                            <label for="final_amount">
                                Final Amount
                            </label>

                            <input
                                type="text"
                                id="final_amount"
                                name="final_amount"
                                value="0.00"
                                readonly
                            >

                        </div>


                        <!-- CGST -->

                        <div class="form-group gst-field hidden">

                            <label for="cgst">
                                CGST (9%)
                            </label>

                            <input
                                type="text"
                                id="cgst"
                                name="cgst"
                                readonly
                            >

                        </div>


                        <!-- SGST -->

                        <div class="form-group gst-field hidden">

                            <label for="sgst">
                                SGST (9%)
                            </label>

                            <input
                                type="text"
                                id="sgst"
                                name="sgst"
                                readonly
                            >

                        </div>


                        <!-- GST TOTAL -->

                        <div class="form-group gst-field hidden">

                            <label for="gst_total">
                                Total GST (18%)
                            </label>

                            <input
                                type="text"
                                id="gst_total"
                                name="gst_total"
                                readonly
                            >

                        </div>


                        <!-- GST NUMBER -->

                        <div class="form-group gst-field hidden">

                            <label for="gst_number">
                                Customer GST Number
                            </label>

                            <input
                                type="text"
                                id="gst_number"
                                name="gst_number"
                                placeholder="GST Number"
                            >

                        </div>


                        <!-- GST INVOICE -->

                        <div class="form-group gst-field hidden">

                            <label for="gst_invoice_no">
                                GST Invoice Number
                            </label>

                            <input
                                type="text"
                                id="gst_invoice_no"
                                name="gst_invoice_no"
                                value="<?= htmlspecialchars(
                                    $gst_invoice_no
                                ); ?>"
                                readonly
                            >

                        </div>


                        <!-- SHIPPING ADDRESS -->

                        <div class="form-group gst-field hidden">

                            <label for="shipping_address">
                                Shipping Address
                            </label>

                            <input
                                type="text"
                                id="shipping_address"
                                name="shipping_address"
                                placeholder="Shipping Address"
                                value="<?= htmlspecialchars(
                                    $row['shipping_address'] ?? ''
                                ); ?>"
                            >

                        </div>


                        <!-- =========================
                             OTHER DETAILS
                        ========================== -->

                        <div class="form-section-title">
                            Other Details
                        </div>


                        <!-- SOURCE -->

                        <div class="form-group">

                            <label for="source">
                                Source
                                <span class="required">*</span>
                            </label>

                            <select
                                id="source"
                                name="source_select"
                                required
                            >

                                <option value="">
                                    Select Source
                                </option>

                                <option value="Old Customer">
                                    Old Customer
                                </option>

                                <option value="Customer Reference">
                                    Customer Reference
                                </option>

                                <option value="BNI">
                                    BNI
                                </option>

                                <option value="Whatsapp">
                                    Whatsapp
                                </option>

                                <option value="Brochure">
                                    Brochure
                                </option>

                                <option value="Banner">
                                    Banner
                                </option>

                                <option value="JD">
                                    JD
                                </option>

                                <option value="Architect">
                                    Architect
                                </option>

                                <option value="Other">
                                    Other
                                </option>

                            </select>


                            <input
                                type="text"
                                id="source_other"
                                name="source_other"
                                class="hidden"
                                placeholder="Please specify other source"
                            >


                            <input
                                type="hidden"
                                id="final_source"
                                name="source"
                                required
                            >

                        </div>


                        <!-- SERVICE DATE -->

                        <div class="form-group">

                            <label for="due_date">
                                Service Date
                                <span class="required">*</span>
                            </label>

                            <input
                                type="date"
                                id="due_date"
                                name="due_date"
                                required
                            >

                        </div>


                        <!-- SUBMIT -->

                        <div class="form-group full-width">

                            <input
                                name="submit"
                                type="submit"
                                class="submit-btn"
                                value="Submit Client"
                            >

                        </div>


                        <?php

                        } else {

                            ?>

                            <div class="form-group full-width">

                                <p style="
                                    margin:0;
                                    color:#d33;
                                    text-align:center;
                                ">
                                    No inquiry record found.
                                </p>

                            </div>

                            <?php

                        }

                        ?>

                    </div>

                </form>

            </div>

        </div>

    </section>

</section>


<script>

/* =====================================================
   GST CALCULATION
===================================================== */

function calculateGST() {

    const totalElement =
        document.getElementById('total_amount');

    const applyGST =
        document.getElementById('apply_gst');

    const total =
        parseFloat(totalElement.value) || 0;


    if (applyGST.checked) {

        const cgst = total * 0.09;

        const sgst = total * 0.09;

        const gstTotal = cgst + sgst;

        const finalAmount = total + gstTotal;


        document.getElementById('cgst').value =
            cgst.toFixed(2);

        document.getElementById('sgst').value =
            sgst.toFixed(2);

        document.getElementById('gst_total').value =
            gstTotal.toFixed(2);

        document.getElementById('final_amount').value =
            finalAmount.toFixed(2);


        document
            .querySelectorAll('.gst-field')
            .forEach(function(el) {

                el.classList.remove('hidden');

            });

    } else {

        document.getElementById('cgst').value = '';

        document.getElementById('sgst').value = '';

        document.getElementById('gst_total').value = '';

        document.getElementById('final_amount').value =
            total.toFixed(2);


        document
            .querySelectorAll('.gst-field')
            .forEach(function(el) {

                el.classList.add('hidden');

            });
    }
}


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


window.addEventListener(
    'load',
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


sourceSelect.addEventListener(
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


sourceInput.addEventListener(
    'input',
    function() {

        finalSource.value =
            this.value.trim();

    }
);


/* =====================================================
   SERVICE TERMS AUTO NUMBERING
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
                charCount + lines[i].length
            ) {

                currentLineIndex = i;

                offsetInLine =
                    selectionStart - charCount;

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
            cursorPos;

        textarea.selectionEnd =
            cursorPos;

    }
);

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

?>
