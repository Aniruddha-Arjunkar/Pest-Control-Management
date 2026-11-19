<?php
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: login.php');
    exit;
}

/* =========================
   LOGOUT
========================= */
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('Location: login.php');
    exit;
}

/* =========================
   DATABASE
========================= */
require_once 'db_connect.php';
include 'header.php';
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

                        <span class="phone-icon">💰</span>

                        <div>

                            <h2>Add Expenses</h2>

                            <p>
                                Add and manage your expense details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    action="expenses.php"
                    method="post"
                    class="refollowup-form"
                >

                    <!-- =========================
                         BRANCH
                    ========================== -->

                    <div class="form-group">

                        <label>
                            Branch <span>*</span>
                        </label>

                        <select
                            id="branch"
                            name="branch"
                            required
                        >

                            <option value="">
                                Select Branch
                            </option>

                            <option value="Nagpur">
                                Nagpur
                            </option>

                            <option value="Bramhapuri">
                                Bramhapuri
                            </option>

                            <option value="Chandrapur">
                                Chandrapur
                            </option>

                        </select>

                    </div>


                    <!-- =========================
                         SERVICE
                    ========================== -->

                    <div class="form-group">

                        <label>
                            Service <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="services"
                            placeholder="Enter service"
                            required
                        >

                    </div>


                    <!-- =========================
                         EXPENSE
                    ========================== -->

                    <div class="form-group">

                        <label>
                            Expense <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="expenses"
                            placeholder="Enter expense"
                            required
                        >

                    </div>


                    <!-- =========================
                         AMOUNT + DATE
                    ========================== -->

                    <div class="form-row">

                        <!-- AMOUNT -->

                        <div class="form-group">

                            <label>
                                Amount <span>*</span>
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="amount"
                                placeholder="Enter amount"
                                required
                            >

                        </div>


                        <!-- DATE -->

                        <div class="form-group">

                            <label>
                                Date <span>*</span>
                            </label>

                            <input
                                type="date"
                                name="date"
                                value="<?= date('Y-m-d'); ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- =========================
                         PAYMENT GATEWAY
                    ========================== -->

                    <div class="form-group">

                        <label>
                            Payment Gateway <span>*</span>
                        </label>


                        <!-- PAYMENT GATEWAY SELECT -->

                        <select
                            id="payment_gateway"
                            name="payment_gateway_select"
                            required
                        >

                            <option value="">
                                -- Select Payment Gateway --
                            </option>

                            <option value="Cash">
                                Cash
                            </option>

                            <option value="UPI">
                                UPI
                            </option>

                            <option value="NEFT">
                                NEFT
                            </option>

                            <option value="Cheque">
                                Cheque
                            </option>

                            <option value="IMPS">
                                IMPS
                            </option>

                            <option value="Other">
                                Other
                            </option>

                        </select>


                        <!-- =========================
                             OTHER PAYMENT GATEWAY
                        ========================== -->

                        <input
                            type="text"
                            id="payment_gateway_other"
                            name="payment_gateway_other"
                            class="hidden"
                            placeholder="Please specify payment gateway"
                        >


                        <!-- =========================
                             FINAL PAYMENT GATEWAY
                        ========================== -->

                        <input
                            type="hidden"
                            id="final_payment_gateway"
                            name="payment_gateway"
                            required
                        >

                    </div>


                    <!-- =========================
                         SUBMIT
                    ========================== -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>💰</span>

                        Submit

                    </button>

                </form>

            </div>

        </div>

    </section>

</section>


<!-- =========================================
     PAYMENT GATEWAY JAVASCRIPT
========================================= -->

<script>

const paymentGateway =
    document.getElementById('payment_gateway');

const paymentGatewayOther =
    document.getElementById('payment_gateway_other');

const finalPaymentGateway =
    document.getElementById('final_payment_gateway');


paymentGateway.addEventListener(
    'change',
    function() {

        if (this.value === 'Other') {

            /*
             * Same logic as Reference Code
             */

            // Hide dropdown
            this.classList.add('hidden');

            // Show Other text field
            paymentGatewayOther.classList.remove('hidden');

            // Make text field required
            paymentGatewayOther.required = true;

            // Focus text field
            paymentGatewayOther.focus();

            // Clear final value
            finalPaymentGateway.value = '';

        } else {

            /*
             * Normal payment gateway
             */

            finalPaymentGateway.value =
                this.value;

        }

    }
);


paymentGatewayOther.addEventListener(
    'input',
    function() {

        /*
         * Whatever user types in Other
         * will be submitted as payment_gateway
         */

        finalPaymentGateway.value =
            this.value.trim();

    }
);

</script>


<!-- =========================================
     FOOTER JAVASCRIPT
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

?>
