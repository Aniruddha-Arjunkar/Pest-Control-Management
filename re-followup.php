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
   GET INQUIRY ID
========================= */
$Id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($Id <= 0) {
    echo "<script>
        alert('Invalid inquiry ID');
        window.location.href='view-re-followup.php';
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
        window.location.href='view-re-followup.php';
    </script>";
    exit;
}

$row = mysqli_fetch_assoc($result);

/* =========================
   UPDATE FORM
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

    $follow_up_date = mysqli_real_escape_string(
        $conn,
        trim($_POST['follow_up_date'] ?? '')
    );

    /* Required fields */
    if (
        empty($services) ||
        empty($cust_name) ||
        empty($cust_phone) ||
        empty($follow_up_date)
    ) {
        echo "<script>
            alert('Please fill all required fields');
        </script>";
    } else {

        $update_sql = "UPDATE tbl_inquiry SET
            services = '$services',
            cust_name = '$cust_name',
            cust_phone = '$cust_phone',
            cust_email = '$cust_email',
            cust_address = '$cust_address',
            cust_message = '$cust_message',
            follow_up_date = '$follow_up_date'
            WHERE id = $Id";

        if (mysqli_query($conn, $update_sql)) {

            echo "<script>
                alert('Updated successfully...');
                window.location.href='view-re-followup.php';
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

                        <span class="phone-icon">📞</span>

                        <div>

                            <h2>Re-Followup</h2>

                            <p>
                                Update customer follow-up details
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


                    <!-- PHONE + EMAIL -->

                    <div class="form-row">


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


                        <!-- EMAIL -->

                        <div class="form-group">

                            <label>
                                Email
                            </label>

                            <input
                                type="email"
                                name="cust_email"
                                placeholder="Email"
                                value="<?= htmlspecialchars($row['cust_email'] ?? ''); ?>"
                            >

                        </div>

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label>
                            Address
                        </label>

                        <input
                            type="text"
                            name="cust_address"
                            placeholder="Address"
                            value="<?= htmlspecialchars($row['cust_address'] ?? ''); ?>"
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


                    <!-- FOLLOW UP DATE -->

                    <div class="form-group">

                        <label>
                            Follow Up Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            name="follow_up_date"
                            value="<?= htmlspecialchars($row['follow_up_date'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
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
