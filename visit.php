<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('Location: login.php');
    exit;
}

require_once 'db_connect.php';
include 'header.php';


/* =========================
   GET ID
========================= */

$Id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($Id <= 0) {
    echo "<script>
        alert('Invalid inquiry ID');
        window.location.href='view-visit.php';
    </script>";
    exit;
}


/* =========================
   FETCH CUSTOMER
========================= */

$sql = "SELECT * FROM tbl_inquiry WHERE id = $Id LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<script>
        alert('Customer record not found');
        window.location.href='view-visit.php';
    </script>";
    exit;
}

$row = mysqli_fetch_assoc($result);


/* =========================
   UPDATE
========================= */

if (isset($_POST['submit'])) {

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

    $cust_address = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_address'] ?? '')
    );

    $cust_message = mysqli_real_escape_string(
        $conn,
        trim($_POST['cust_message'] ?? '')
    );

    $visit_date = mysqli_real_escape_string(
        $conn,
        trim($_POST['visit_date'] ?? '')
    );


    if (
        empty($services) ||
        empty($cust_name) ||
        empty($cust_phone) ||
        empty($cust_address) ||
        empty($visit_date)
    ) {

        echo "<script>
            alert('Please fill all required fields');
        </script>";

    } else {

        $update_sql = "UPDATE tbl_inquiry SET

            services = '$services',
            cust_name = '$cust_name',
            cust_phone = '$cust_phone',
            cust_address = '$cust_address',
            cust_message = '$cust_message',
            visit_date = '$visit_date'

            WHERE id = $Id";


        if (mysqli_query($conn, $update_sql)) {

            echo "<script>
                alert('Updated successfully...');
                window.location.href='view-visit.php';
            </script>";

            exit;

        } else {

            echo "<script>
                alert('Update failed: " . addslashes(mysqli_error($conn)) . "');
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

            <div class="refollowup-card">


                <!-- =================================
                     HEADER
                ================================== -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">📍</span>

                        <div>

                            <h2>Visit</h2>

                            <p>
                                Update customer visit details
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


                    <!-- SERVICES -->

                    <div class="form-group">

                        <label>
                            Services
                        </label>

                        <input
                            type="text"
                            name="services"
                            placeholder="Service"
                            value="<?= htmlspecialchars($row['services'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- CUSTOMER NAME -->

                    <div class="form-group">

                        <label>
                            Customer Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="cust_name"
                            placeholder="Customer Name"
                            value="<?= htmlspecialchars($row['cust_name'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label>
                            Phone <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="cust_phone"
                            placeholder="Phone"
                            value="<?= htmlspecialchars(trim($row['cust_phone'] ?? '')); ?>"
                            required
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label>
                            Address <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="cust_address"
                            placeholder="Address"
                            value="<?= htmlspecialchars($row['cust_address'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- MESSAGE -->

                    <div class="form-group">

                        <label>
                            Message
                        </label>

                        <textarea
                            name="cust_message"
                            placeholder="Message"
                            rows="4"
                            required
                        ><?= htmlspecialchars($row['cust_message'] ?? ''); ?></textarea>

                    </div>


                    <!-- VISIT DATE -->

                    <div class="form-group">

                        <label>
                            Visit Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            name="visit_date"
                            id="date"
                            value="<?= htmlspecialchars($row['visit_date'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>📍</span>

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
