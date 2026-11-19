<?php
session_start();

require_once 'db_connect.php';
include 'header.php';

/* =========================
   GET CUSTOMER DATA
========================= */

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM tbl_inquiry WHERE Id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) {
    echo "<script>
        alert('Customer record not found');
        window.location.href='view-inquiry.php';
    </script>";
    exit;
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

                        <span class="phone-icon">📄</span>

                        <div>

                            <h2>Generate Quotation</h2>

                            <p>
                                Customer: <?= htmlspecialchars($customer['cust_name']); ?>
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     QUOTATION FORM
                ================================== -->

                <form
                    action="generate_quotation.php?id=<?= $id ?>"
                    method="POST"
                    class="refollowup-form"
                >


                    <!-- =================================
                         CUSTOMER NAME
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Customer Name
                        </label>

                        <input
                            type="text"
                            name="cust_name"
                            placeholder="Customer Name"
                            value="<?= htmlspecialchars($customer['cust_name']); ?>"
                        >

                    </div>


                    <!-- =================================
                         ADDRESS
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Address
                        </label>

                        <input
                            type="text"
                            name="cust_address"
                            placeholder="Address"
                            value="<?= htmlspecialchars($customer['cust_address']); ?>"
                        >

                    </div>


                    <!-- =================================
                         PHONE
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Phone
                        </label>

                        <input
                            type="text"
                            name="cust_phone"
                            placeholder="Phone"
                            value="<?= htmlspecialchars($customer['cust_phone']); ?>"
                        >

                    </div>


                    <!-- =================================
                         SERVICE ROWS
                    ================================== -->

                    <div id="serviceRows">


                        <div class="service-row">


                            <!-- SERVICE -->

                            <div class="form-group">

                                <label>
                                    Select Service
                                </label>

                                <select
                                    class="form-select"
                                    name="service[]"
                                    required
                                >

                                    <option value="">
                                        -- Select Service --
                                    </option>

                                    <option>Anti-Termite</option>

                                    <option>
                                        Anti Termite Pre Column & Plinth
                                    </option>

                                    <option>
                                        Anti-Termite (Pre-Plinth)
                                    </option>

                                    <option>
                                        Anti-Termite (Pre-Column)
                                    </option>

                                    <option>
                                        Anti Termite 3 Step
                                    </option>

                                    <option>
                                        Anti-Termite (Drilling)
                                    </option>

                                    <option>
                                        Anti-Termite (Plinth-Drilling)
                                    </option>

                                    <option>
                                        Mosquito Control
                                    </option>

                                    <option>
                                        General Pest Control
                                    </option>

                                    <option>
                                        Rodent Control
                                    </option>

                                    <option>
                                        Bedbug Control
                                    </option>

                                    <option>
                                        Prophylactic Spray Treatment
                                    </option>

                                    <option>
                                        Cockroach Control
                                    </option>

                                    <option>
                                        Ply Sheet
                                    </option>

                                </select>

                            </div>


                            <!-- PEST COVERED -->

                            <div class="form-group">

                                <label>
                                    Pest Covered
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="pest[]"
                                    placeholder="Pest Covered"
                                    required
                                >

                            </div>


                            <!-- CHEMICAL -->

                            <div class="form-group">

                                <label>
                                    Chemical Used
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="chemical[]"
                                    placeholder="Chemical Used"
                                    required
                                >

                            </div>


                            <!-- WARRANTY -->

                            <div class="form-group">

                                <label>
                                    Warranty
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="warranty[]"
                                    placeholder="Warranty"
                                    required
                                >

                            </div>


                            <!-- AREA / QTY -->

                            <div class="form-group area-field">

                                <label class="area-label">
                                    Area / Qty
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="area[]"
                                    placeholder="Area / Qty"
                                >

                            </div>


                            <!-- SERVICE CHARGES -->

                            <div class="form-group charges-field">

                                <label>
                                    Service Charges (₹)
                                </label>

                                <input
                                    type="number"
                                    class="form-control charges"
                                    name="charges[]"
                                    placeholder="Service Charges"
                                    required
                                >

                            </div>


                            <!-- DISCOUNT -->

                            <div class="form-group">

                                <label>
                                    Special Discount (₹)
                                </label>

                                <input
                                    type="number"
                                    class="form-control discount"
                                    name="discount[]"
                                    value="0"
                                    placeholder="Discount"
                                >

                            </div>


                            <!-- TOTAL -->

                            <div class="form-group">

                                <label>
                                    Total Amount (₹)
                                </label>

                                <input
                                    type="number"
                                    class="form-control total"
                                    name="total[]"
                                    placeholder="Total Amount"
                                    readonly
                                    required
                                >

                            </div>


                            <!-- =================================
                                 PAYMENT TERMS
                            ================================== -->

                            <div class="form-group">

                                <label>
                                    Payment Terms
                                </label>

                                <textarea
                                    name="payment_terms"
                                    rows="4"
                                    placeholder="Enter payment terms here..."
                                ></textarea>

                            </div>


                        </div>

                    </div>


                    <!-- =================================
                         MERGE CHARGES
                    ================================== -->

                    <div class="form-group">

                        <label
                            style="display:flex; align-items:center; gap:10px; font-weight:600;"
                        >

                            <input
                                type="checkbox"
                                name="merge_charges"
                                value="1"
                                style="width:auto;"
                            >

                            Merge Service Charges
                            (Show single total in center)

                        </label>

                    </div>


                    <!-- =================================
                         BUTTONS
                    ================================== -->

                    <div class="form-group">

                        <button
                            type="button"
                            id="addRow"
                            class="submit-btn"
                        >
                            + Add Service
                        </button>


                        <button
                            type="submit"
                            class="submit-btn"
                        >
                           📄 Generate Quotation PDF
                        </button>

                    </div>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =========================================
     JAVASCRIPT
========================================= -->

<script>

/* =========================================
   ADD NEW SERVICE ROW
========================================= */

document.getElementById('addRow').addEventListener('click', function () {

    let clone = document
        .querySelector('.service-row')
        .cloneNode(true);


    /* Reset input values */

    clone.querySelectorAll('input').forEach(function (input) {

        if (
            input.classList.contains('discount')
        ) {
            input.value = '0';
        } else {
            input.value = '';
        }

    });


    /* Reset select */

    clone.querySelector('select').value = '';


    /* Reset area label */

    clone.querySelector('.area-label').innerText =
        'Area / Qty';


    /* Reset charges label */

    clone.querySelector('.charges-field label').innerText =
        'Service Charges (₹)';


    /* Reset area placeholder */

    clone.querySelector('.area-field input').placeholder =
        'Area / Qty';


    /* Add row */

    document
        .getElementById('serviceRows')
        .appendChild(clone);

});


/* =========================================
   SERVICE CHANGE
========================================= */

document.addEventListener('change', function (e) {

    if (
        e.target.matches('select[name="service[]"]')
    ) {

        const row = e.target.closest('.service-row');

        const areaField =
            row.querySelector('.area-field');

        const areaLabel =
            row.querySelector('.area-label');

        const areaInput =
            row.querySelector('.area-field input');

        const chargesLabel =
            row.querySelector('.charges-field label');


        /* =========================
           PLY SHEET
        ========================= */

        if (
            e.target.value.toLowerCase() === 'ply sheet'
        ) {

            areaField.style.display = 'block';

            areaLabel.innerText =
                'Qty (Sheets)';

            areaInput.placeholder =
                'Qty (Sheets)';

            chargesLabel.innerText =
                'Amount (₹)';

        }


        /* =========================
           OTHER SERVICES
        ========================= */

        else {

            areaField.style.display = 'block';

            areaLabel.innerText =
                'Area / Qty';

            areaInput.placeholder =
                'Area / Qty';

            chargesLabel.innerText =
                'Service Charges (₹)';

        }

    }

});


/* =========================================
   CALCULATE TOTAL
========================================= */

document.addEventListener('input', function (e) {

    if (
        e.target.classList.contains('charges') ||
        e.target.classList.contains('discount')
    ) {

        const row =
            e.target.closest('.service-row');


        const charges =
            parseFloat(
                row.querySelector('.charges').value
            ) || 0;


        const discount =
            parseFloat(
                row.querySelector('.discount').value
            ) || 0;


        row.querySelector('.total').value =
            (charges - discount).toFixed(2);

    }

});

</script>


<!-- =========================================
     OTHER SCRIPTS
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
mysqli_close($conn);
?>

