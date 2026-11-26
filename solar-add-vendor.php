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
   DATABASE
========================= */
require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

$message = "";

/* =========================
   SAVE SOLAR VENDOR
========================= */
if (isset($_POST['save_vendor'])) {

    $name    = trim($_POST['vendor_name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '' || $phone === '' || $address === '') {
        $message = "Please fill all required fields.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO tbl_solar_vendor
            (vendor_name, phone, email, address)
            VALUES (?, ?, ?, ?)"
        );

        if ($stmt) {
            $stmt->bind_param("ssss", $name, $phone, $email, $address);

            if ($stmt->execute()) {
                $stmt->close();

                $_SESSION['status'] = "Solar Vendor Added Successfully";
                header("Location: solar-view-vendor.php");
                exit();
            } else {
                $message = "Error: Vendor could not be added.";
            }

            $stmt->close();
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
            <h2 class="refollowup-title">Add Solar Vendor</h2>
        </div>

        <?php if ($message != '') { ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>

        <form method="POST" action="" class="refollowup-form">

            <div class="form-row">

                <div class="form-group">
                    <label>Vendor Name <span style="color:red;">*</span></label>
                    <input type="text"
                           name="vendor_name"
                           placeholder="Enter Vendor Name"
                           value="<?= htmlspecialchars($_POST['vendor_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Phone Number <span style="color:red;">*</span></label>
                    <input type="text"
                           name="phone"
                           placeholder="Enter Phone Number"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email"
                           name="email"
                           placeholder="Enter Email Address"
                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label>Address <span style="color:red;">*</span></label>
                    <textarea name="address"
                              placeholder="Enter Address"
                              required><?= htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

            </div>

            <div class="form-group">
                <button type="submit" name="save_vendor" class="submit-btn">
                    Submit Vendor
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
