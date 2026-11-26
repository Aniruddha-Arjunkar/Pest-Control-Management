
<?php
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

/* =========================
   DATABASE CONNECTION
========================= */
require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

$message = "";

/* =========================
   SAVE SOLAR TRADER
========================= */
if (isset($_POST['add_trader'])) {

    $cust_name = trim($_POST['cust_name'] ?? '');
    $cust_phone = trim($_POST['cust_phone'] ?? '');
    $cust_email = trim($_POST['cust_email'] ?? '');
    $cust_address = trim($_POST['cust_address'] ?? '');
    $gst_number = trim($_POST['gst_number'] ?? '');
    $service_date = trim($_POST['service_date'] ?? '');
    $shipping_address = trim($_POST['shipping_address'] ?? '');

    $billing_address = $cust_address;
    $added_by = 'trader';

    if ($cust_name === '' || $cust_phone === '' || $service_date === '') {

        $message = "Please fill all required fields.";

    } else {

        $stmt = $conn->prepare("
            INSERT INTO tbl_solar_client (
                cust_name,
                cust_phone,
                cust_email,
                cust_address,
                gst_number,
                service_date,
                billing_address,
                shipping_address,
                added_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt) {

            $stmt->bind_param(
                "sssssssss",
                $cust_name,
                $cust_phone,
                $cust_email,
                $cust_address,
                $gst_number,
                $service_date,
                $billing_address,
                $shipping_address,
                $added_by
            );

            if ($stmt->execute()) {

                $stmt->close();

                $_SESSION['status'] = "Solar Trader Added Successfully!";
                $_SESSION['status_code'] = "success";

                header("Location: solar-view-trader.php");
                exit();

            } else {

                $message = "Error: " . $stmt->error;
                $stmt->close();
            }

        } else {

            $message = "Database query preparation failed.";
        }
    }
}

include('header.php');
?>

<section id="main-content">
<section class="wrapper">

<div class="reg-w3">
    <div class="refollowup-card">

        <div class="refollowup-header">
            <h2 class="refollowup-title">Add New Solar Trader</h2>
        </div>

        <?php if ($message != '') { ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>

        <form method="POST" action="" class="refollowup-form">

            <h5 class="text-primary">Trader Details</h5>
            <hr>

            <div class="form-row">

                <div class="form-group">
                    <label>Trader Name <span style="color:red;">*</span></label>
                    <input type="text"
                           name="cust_name"
                           placeholder="Enter Trader Name"
                           value="<?= htmlspecialchars($_POST['cust_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Mobile Number <span style="color:red;">*</span></label>
                    <input type="text"
                           name="cust_phone"
                           placeholder="Enter Mobile Number"
                           value="<?= htmlspecialchars($_POST['cust_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Email</label>
                    <input type="email"
                           name="cust_email"
                           placeholder="Enter Email"
                           value="<?= htmlspecialchars($_POST['cust_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label>GST Number</label>
                    <input type="text"
                           name="gst_number"
                           maxlength="15"
                           placeholder="Enter GST Number"
                           value="<?= htmlspecialchars($_POST['gst_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="cust_address"
                              placeholder="Enter Address"><?= htmlspecialchars($_POST['cust_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Shipping Address</label>
                    <textarea name="shipping_address"
                              placeholder="Enter Shipping Address"><?= htmlspecialchars($_POST['shipping_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Date <span style="color:red;">*</span></label>
                    <input type="date"
                           name="service_date"
                           value="<?= htmlspecialchars($_POST['service_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                </div>

            </div>

            <div class="form-group">
                <button type="submit"
                        name="add_trader"
                        class="submit-btn">
                    Save Trader
                </button>
            </div>

        </form>

    </div>
</div>

</section>
</section>

<!-- FOOTER SCRIPTS -->
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
$conn->close();
?>
