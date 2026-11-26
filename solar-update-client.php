
<?php
ob_start();
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit;
}

/* =========================
   DATABASE
========================= */
require_once 'db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   GET CLIENT ID
========================= */
$client_id = (int)($_GET['client_id'] ?? $_GET['id'] ?? 0);

if ($client_id <= 0) {
    die("Invalid Solar Client ID");
}

/* =========================
   FETCH CLIENT
========================= */
$stmt = $conn->prepare("SELECT * FROM tbl_solar_client WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

if (!$client) {
    die("Solar Client not found");
}

/* =========================
   NEXT GST INVOICE NUMBER
========================= */
function getNextSolarInvoiceNumber($conn) {

    $result = mysqli_query(
        $conn,
        "SELECT gst_invoice_no
         FROM tbl_solar_client
         WHERE gst_invoice_no IS NOT NULL
         AND gst_invoice_no != ''
         ORDER BY id DESC
         LIMIT 1"
    );

    $row = mysqli_fetch_assoc($result);

    if ($row && !empty($row['gst_invoice_no'])) {
        $lastNumber = (int)filter_var(
            $row['gst_invoice_no'],
            FILTER_SANITIZE_NUMBER_INT
        );
        return (string)($lastNumber + 1);
    }

    return "1001";
}

/* =========================
   UPDATE CLIENT
========================= */
$error = "";

if (isset($_POST['update'])) {

    $cust_name = trim($_POST['cust_name'] ?? '');
    $cust_phone = trim($_POST['cust_phone'] ?? '');
    $cust_email = trim($_POST['cust_email'] ?? '');
    $cust_address = trim($_POST['cust_address'] ?? '');

    $service = trim($_POST['service'] ?? '');
    $service_date = $_POST['service_date'] ?? '';

    $warranty = trim($_POST['warranty'] ?? '');
    $service_covered = trim($_POST['service_covered'] ?? '');
    $terms = trim($_POST['service_term_condition'] ?? '');

    $total_amount = (float)($_POST['total_amount'] ?? 0);

    $consumer_no = trim($_POST['consumer_no'] ?? '');
    $subdivision = trim($_POST['subdivision'] ?? '');
    $technical_name = trim($_POST['technical_name'] ?? '');

    $apply_gst = isset($_POST['apply_gst']) ? 1 : 0;

    $gst_type = $_POST['gst_type'] ?? 'exclusive';

    if (!in_array($gst_type, ['inclusive', 'exclusive'], true)) {
        $gst_type = 'exclusive';
    }

    $gst_rate = (float)($_POST['gst_rate'] ?? 18);

    if (!in_array($gst_rate, [5, 18], true)) {
        $gst_rate = 18;
    }

    $billing_address = trim($_POST['billing_address'] ?? '');
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $gst_number = strtoupper(trim($_POST['gst_number'] ?? ''));

    $gst_invoice_no = $client['gst_invoice_no'] ?? '';

    if ($apply_gst && empty($gst_invoice_no)) {
        $gst_invoice_no = getNextSolarInvoiceNumber($conn);
    }

    /* =========================
       GST CALCULATION
    ========================= */
    if ($apply_gst) {

        if ($gst_type === 'inclusive') {

            $base_amount = round(
                $total_amount / (1 + ($gst_rate / 100)),
                2
            );

            $gst_amount = round($total_amount - $base_amount, 2);
            $final_amount = $total_amount;

        } else {

            $base_amount = $total_amount;

            $gst_amount = round(
                $base_amount * ($gst_rate / 100),
                2
            );

            $final_amount = round(
                $base_amount + $gst_amount,
                2
            );
        }

    } else {

        $base_amount = $total_amount;
        $gst_amount = 0;
        $final_amount = $total_amount;
    }

    /* =========================
       UPDATE SOLAR CLIENT
    ========================= */
    $updateStmt = $conn->prepare("
        UPDATE tbl_solar_client SET

            cust_name = ?,
            cust_phone = ?,
            cust_email = ?,
            cust_address = ?,
            service = ?,
            service_date = ?,
            warranty = ?,
            service_covered = ?,
            service_term_condition = ?,
            total_amount = ?,
            consumer_no = ?,
            subdivision = ?,
            technical_name = ?,
            apply_gst = ?,
            gst_type = ?,
            gst_amount = ?,
            billing_address = ?,
            shipping_address = ?,
            gst_number = ?,
            gst_invoice_no = ?,
            gst_rate = ?

        WHERE id = ?
    ");

    if (!$updateStmt) {
        $error = "Update query error: " . $conn->error;

    } else {

        $updateStmt->bind_param(
            "sssssssssdssssdssssdii",
            $cust_name,
            $cust_phone,
            $cust_email,
            $cust_address,
            $service,
            $service_date,
            $warranty,
            $service_covered,
            $terms,
            $total_amount,
            $consumer_no,
            $subdivision,
            $technical_name,
            $apply_gst,
            $gst_type,
            $gst_amount,
            $billing_address,
            $shipping_address,
            $gst_number,
            $gst_invoice_no,
            $gst_rate,
            $client_id
        );

        // Correct parameter type string without spaces
        // 9 strings, 1 double, 3 strings, 1 integer,
        // 1 string, 1 double, 4 strings, 1 double, 1 integer
        // See corrected binding below.
        $updateStmt->close();

        $updateStmt = $conn->prepare("
            UPDATE tbl_solar_client SET
                cust_name = ?,
                cust_phone = ?,
                cust_email = ?,
                cust_address = ?,
                service = ?,
                service_date = ?,
                warranty = ?,
                service_covered = ?,
                service_term_condition = ?,
                total_amount = ?,
                consumer_no = ?,
                subdivision = ?,
                technical_name = ?,
                apply_gst = ?,
                gst_type = ?,
                gst_amount = ?,
                billing_address = ?,
                shipping_address = ?,
                gst_number = ?,
                gst_invoice_no = ?,
                gst_rate = ?
            WHERE id = ?
        ");

        $updateStmt->bind_param(
            "sssssssssdsssisdssssdi",
            $cust_name,
            $cust_phone,
            $cust_email,
            $cust_address,
            $service,
            $service_date,
            $warranty,
            $service_covered,
            $terms,
            $total_amount,
            $consumer_no,
            $subdivision,
            $technical_name,
            $apply_gst,
            $gst_type,
            $gst_amount,
            $billing_address,
            $shipping_address,
            $gst_number,
            $gst_invoice_no,
            $gst_rate,
            $client_id
        );

        if ($updateStmt->execute()) {

            /* =========================
               UPDATE SOLAR PAYMENT
            ========================= */
            $checkStmt = $conn->prepare("
                SELECT id
                FROM tbl_solar_payment
                WHERE client_id = ?
                LIMIT 1
            ");

            $checkStmt->bind_param("i", $client_id);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {

                $paymentUpdate = $conn->prepare("
                    UPDATE tbl_solar_payment
                    SET total_amount = ?,
                        due_amount = ?
                    WHERE client_id = ?
                ");

                $paymentUpdate->bind_param(
                    "ddi",
                    $final_amount,
                    $final_amount,
                    $client_id
                );

                $paymentUpdate->execute();
                $paymentUpdate->close();

            } else {

                $paymentInsert = $conn->prepare("
                    INSERT INTO tbl_solar_payment
                    (client_id, total_amount, due_amount)
                    VALUES (?, ?, ?)
                ");

                $paymentInsert->bind_param(
                    "idd",
                    $client_id,
                    $final_amount,
                    $final_amount
                );

                $paymentInsert->execute();
                $paymentInsert->close();
            }

            $checkStmt->close();
            $updateStmt->close();

            $_SESSION['status'] = "Solar Client Updated Successfully!";

          header("Location: solar-view-client-detail.php?client_id=" . $client_id);
            exit;

        } else {

            $error = "Update Error: " . $updateStmt->error;
            $updateStmt->close();
        }
    }

    // Keep submitted values on the form if an error occurs.
    $client = array_merge($client, $_POST);
    $client['apply_gst'] = $apply_gst;
    $client['gst_type'] = $gst_type;
    $client['gst_rate'] = $gst_rate;
    $client['gst_amount'] = $gst_amount;
    $client['gst_invoice_no'] = $gst_invoice_no;
}

include 'header.php';
?>

<style>
.refollowup-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    margin: 20px auto;
    max-width: 1100px;
    overflow: hidden;
}

.refollowup-header {
    background: linear-gradient(120deg,#4776e6,#6b45b5);
    padding: 22px 28px;
    color: #fff;
}

.refollowup-title {
    display: flex;
    align-items: center;
    gap: 15px;
}

.refollowup-title h2 {
    color: #fff;
    margin: 0;
    font-size: 24px;
}

.refollowup-title p {
    margin: 5px 0 0;
    color: #eee;
}

.phone-icon {
    font-size: 35px;
}

.refollowup-form {
    padding: 25px;
}

.refollowup-form .form-group {
    margin-bottom: 18px;
}

.refollowup-form label {
    display: block;
    font-weight: 600;
    margin-bottom: 7px;
    color: #333;
}

.refollowup-form label span {
    color: red;
}

.refollowup-form input,
.refollowup-form textarea,
.refollowup-form select {
    width: 100%;
    padding: 11px 13px;
    border: 1px solid #d8dce5;
    border-radius: 7px;
    font-size: 14px;
    box-sizing: border-box;
}

.refollowup-form textarea {
    min-height: 90px;
    resize: vertical;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.gst-section {
    border: 1px solid #e2e5ec;
    padding: 20px;
    border-radius: 9px;
    margin: 20px 0;
    background: #fafbff;
}

.gst-section h3 {
    margin-top: 0;
    color: #5142a5;
}

.gst-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
}

.gst-checkbox input {
    width: auto;
}

.submit-btn {
    background: linear-gradient(120deg,#4776e6,#6b45b5);
    color: white;
    border: 0;
    border-radius: 7px;
    padding: 12px 25px;
    font-size: 15px;
    cursor: pointer;
}

.submit-btn:hover {
    opacity: .9;
}

.error-message {
    background: #ffe8e8;
    color: #b42318;
    padding: 12px;
    border-radius: 6px;
    margin: 20px 25px 0;
}

@media(max-width:700px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .refollowup-form {
        padding: 18px;
    }
}
</style>

<section id="main-content">
<section class="wrapper">

<div class="reg-w3">

    <div class="refollowup-card">

        <div class="refollowup-header">
            <div class="refollowup-title">
                <span class="phone-icon">☀️</span>
                <div>
                    <h2>Update Solar Client Details</h2>
                    <p>Update solar customer, service and GST details</p>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="refollowup-form">

            <div class="form-group">
                <label>Customer Name <span>*</span></label>
                <input type="text" name="cust_name"
                       value="<?= e($client['cust_name']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number <span>*</span></label>
                    <input type="text" name="cust_phone"
                           value="<?= e($client['cust_phone']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="cust_email"
                           value="<?= e($client['cust_email']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Customer Address</label>
                <textarea name="cust_address"><?= e($client['cust_address']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Service <span>*</span></label>
                <input type="text" name="service"
                       value="<?= e($client['service']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Service Date <span>*</span></label>
                    <input type="date" name="service_date"
                           value="<?= e($client['service_date']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Warranty</label>
                    <input type="text" name="warranty"
                           value="<?= e($client['warranty']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Service Covered</label>
                <textarea name="service_covered"><?= e($client['service_covered']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Terms & Conditions</label>
                <textarea name="service_term_condition"><?= e($client['service_term_condition']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Total Amount <span>*</span></label>
                <input type="number" step="0.01" min="0"
                       name="total_amount"
                       value="<?= e($client['total_amount']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Consumer Number</label>
                    <input type="text" name="consumer_no"
                           value="<?= e($client['consumer_no']) ?>">
                </div>

                <div class="form-group">
                    <label>Subdivision</label>
                    <input type="text" name="subdivision"
                           value="<?= e($client['subdivision']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Technical Name</label>
                <input type="text" name="technical_name"
                       value="<?= e($client['technical_name']) ?>">
            </div>

            <div class="gst-section">

                <h3>GST Details</h3>

                <div class="gst-checkbox">
                    <input type="checkbox"
                           id="apply_gst"
                           name="apply_gst"
                           value="1"
                           <?= !empty($client['apply_gst']) ? 'checked' : '' ?>
                           onchange="toggleGSTFields()">

                    <label for="apply_gst">Apply GST</label>
                </div>

                <div id="gst_fields"
                     style="display:<?= !empty($client['apply_gst']) ? 'block' : 'none' ?>;">

                    <div class="form-row">

                        <div class="form-group">
                            <label>GST Rate</label>
                            <select name="gst_rate">
                                <option value="5" <?= (float)($client['gst_rate'] ?? 18) == 5 ? 'selected' : '' ?>>5%</option>
                                <option value="18" <?= (float)($client['gst_rate'] ?? 18) == 18 ? 'selected' : '' ?>>18%</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>GST Type</label>
                            <select name="gst_type">
                                <option value="exclusive" <?= ($client['gst_type'] ?? '') == 'exclusive' ? 'selected' : '' ?>>Exclusive GST</option>
                                <option value="inclusive" <?= ($client['gst_type'] ?? '') == 'inclusive' ? 'selected' : '' ?>>Inclusive GST</option>
                            </select>
                        </div>

                    </div>

                    <div class="form-group">
                        <label>Billing Address</label>
                        <textarea name="billing_address"><?= e($client['billing_address']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Shipping Address</label>
                        <textarea name="shipping_address"><?= e($client['shipping_address']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>GST Number (GSTIN)</label>
                        <input type="text" name="gst_number"
                               maxlength="15"
                               style="text-transform:uppercase"
                               value="<?= e($client['gst_number']) ?>">
                    </div>

                    <div class="form-group">
                        <label>GST Invoice Number</label>
                        <input type="text" value="<?= e($client['gst_invoice_no']) ?>" readonly>
                    </div>

                </div>
            </div>

            <button type="submit" name="update" value="1" class="submit-btn">
                ☀️ Update Solar Client
            </button>

        </form>

    </div>
</div>

</section>
</section>

<script>
function toggleGSTFields() {
    const checkbox = document.getElementById('apply_gst');
    const fields = document.getElementById('gst_fields');

    fields.style.display = checkbox.checked ? 'block' : 'none';
}
</script>

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
ob_end_flush();
?>
