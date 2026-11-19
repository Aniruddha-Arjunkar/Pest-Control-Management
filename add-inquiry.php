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


/* =========================
   ADD INQUIRY
========================= */

if (isset($_POST['submit'])) {

    /* =========================
       GET FORM DATA
    ========================= */

    $services = mysqli_real_escape_string(
        $conn,
        trim($_POST['services'] ?? '')
    );

    $cust_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_name'] ?? '')
    );

    $cust_phone = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_phone'] ?? '')
    );

    $cust_email = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_email'] ?? '')
    );

    $cust_address = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_address'] ?? '')
    );

    $cust_message = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_message'] ?? '')
    );


    /* =========================
       VALIDATION
    ========================= */

    if (
        empty($services) ||
        empty($cust_name) ||
        empty($cust_phone)
    ) {

        echo "<script>
            alert('Please fill all required fields');
        </script>";

    } else {

        /* =========================
           INSERT INQUIRY
        ========================= */

        $sql = "INSERT INTO tbl_inquiry
                (
                    services,
                    cust_name,
                    cust_phone,
                    cust_email,
                    cust_address,
                    cust_message,
                    inq_date
                )
                VALUES
                (
                    '$services',
                    '$cust_name',
                    '$cust_phone',
                    '$cust_email',
                    '$cust_address',
                    '$cust_message',
                    NOW()
                )";


        if (mysqli_query($conn, $sql)) {

            echo "<script>
                alert('Inquiry added successfully...');
                window.location.href='view-inquiry.php';
            </script>";

            exit;

        } else {

            echo "<script>
                alert('Inquiry failed: " .
                addslashes(mysqli_error($conn)) .
                "');
            </script>";
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

            <!-- =================================
                 RE-FOLLOWUP DESIGN CARD
            ================================== -->

            <div class="refollowup-card">


                <!-- =================================
                     HEADER
                ================================== -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">📞</span>

                        <div>

                            <h2>Add Inquiry</h2>

                            <p>
                                Add new customer inquiry details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    action="inquiry.php"
                    method="POST"
                    class="refollowup-form"
                >


                    <!-- =================================
                         SERVICE
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Services <span>*</span>
                        </label>

                        <select
                            name="services"
                            required
                        >

                            <option value="">
                                Select Service
                            </option>

                            <option value="Termite Management">
                                Termite Management
                            </option>

                            <option value="Mosquito Management">
                                Mosquito Management
                            </option>

                            <option value="Rodent Control">
                                Rodent Control
                            </option>

                            <option value="General Pest Control">
                                General Pest Control
                            </option>

                            <option value="Anti-Termite">
                                Anti-Termite
                            </option>

                        </select>

                    </div>


                    <!-- =================================
                         CUSTOMER NAME
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Customer Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="cust_name"
                            placeholder="Customer Name"
                            required
                        >

                    </div>


                    <!-- =================================
                         PHONE + EMAIL
                    ================================== -->

                    <div class="form-row">


                        <!-- PHONE -->

                        <div class="form-group">

                            <label>
                                Phone <span>*</span>
                            </label>

                            <input
                                type="tel"
                                name="cust_phone"
                                maxlength="10"
                                placeholder="Phone Number"
                                required
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="form-group">

                            <label>
                                Email
                            </label>

                            <input
                                type="email"
                                name="cust_email"
                                placeholder="Email Address"
                            >

                        </div>

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
                        >

                    </div>


                    <!-- =================================
                         MESSAGE
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Message
                        </label>

                        <textarea
                            name="cust_message"
                            placeholder="Message"
                            rows="4"
                        ></textarea>

                    </div>


                    <!-- =================================
                         SUBMIT BUTTON
                    ================================== -->

                    <button
                        name="submit"
                        type="submit"
                        class="submit-btn"
                        value="Submit"
                    >

                        <span>📞</span>

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