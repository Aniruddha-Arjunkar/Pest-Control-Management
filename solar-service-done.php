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
    header("Location: login.php");
    exit();
}

/* =========================
   DATABASE
========================= */

require_once 'db_connect.php';

/* =========================
   GET SOLAR INQUIRY
========================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = $_GET['type'] ?? 'service';

$allowedTypes = ['service', 'trader'];

if (!in_array($type, $allowedTypes, true)) {
    $type = 'service';
}

if ($id <= 0) {
    die("Invalid Solar Inquiry ID");
}

$stmt = $conn->prepare("
    SELECT *
    FROM tbl_solar_inquiry
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

$stmt->close();

if (!$data) {
    die("Solar Inquiry not found");
}

/* HTML ESCAPE */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   NEXT INVOICE NUMBER
========================= */

function getNextInvoiceNumber($conn) {

    $result = mysqli_query(
        $conn,
        "SELECT gst_invoice_no FROM tbl_solar_client ORDER BY id DESC LIMIT 1"
    );

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        if ($row && !empty($row['gst_invoice_no'])) {

            $lastNumber = (int) filter_var(
                $row['gst_invoice_no'],
                FILTER_SANITIZE_NUMBER_INT
            );

            $nextNumber = $lastNumber + 1;

        } else {

            $nextNumber = 1;
        }

    } else {

        $nextNumber = 1;
    }

    return (string)$nextNumber;
}

include 'header.php';
?>

<!-- =========================================
     MAIN CONTENT
========================================= -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <!-- DESIGN CARD -->
            <div class="refollowup-card">

                <!-- HEADER -->
                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">☀️</span>

                        <div>

                            <h2>Complete Solar Service & Add Client</h2>

                            <p>
                                Complete Solar customer service details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- FORM -->
                <form
                    action="solar-service-done-code.php"
                    method="POST"
                    class="refollowup-form"
                >

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int)$data['id']; ?>"
                    >

                    <input
                        type="hidden"
                        name="client_type"
                        value="<?= e($type); ?>"
                    >


                    <!-- CUSTOMER DETAILS -->
                    <hr>

                    <h5 class="text-primary">Customer Details</h5>

                    <hr>


                    <!-- CUSTOMER NAME -->
                    <div class="form-group">

                        <label>Name</label>

                        <input
                            type="text"
                            value="<?= e($data['name']); ?>"
                            readonly
                        >

                    </div>


                    <!-- PHONE + EMAIL -->
                    <div class="form-row">

                        <div class="form-group">

                            <label>Phone</label>

                            <input
                                type="text"
                                value="<?= e($data['phone']); ?>"
                                readonly
                            >

                        </div>


                        <div class="form-group">

                            <label>Email</label>

                            <input
                                type="text"
                                value="<?= e($data['email']); ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <!-- ADDRESS -->
                    <div class="form-group">

                        <label>Address</label>

                        <textarea readonly rows="3"><?= e($data['address']); ?></textarea>

                    </div>


                    <?php if ($type === 'trader') { ?>

                        <!-- =================================
                             TRADER DETAILS
                        ================================== -->

                        <hr>

                        <h5 class="text-success">Trader Details</h5>

                        <hr>


                        <!-- TERMS & CONDITIONS -->
                        <div class="form-group">

                            <label>Terms &amp; Conditions</label>

                            <textarea
                                name="service_term_condition"
                                rows="4"
                                required
                            ></textarea>

                        </div>


                        <!-- DESCRIPTION -->
                        <div class="form-group">

                            <label>Description</label>

                            <textarea
                                name="service_covered"
                                rows="4"
                                required
                            ></textarea>

                        </div>


                        <!-- AMOUNT -->
                        <div class="form-group">

                            <label>Amount (₹)</label>

                            <input
                                type="number"
                                name="total_amount"
                                min="0.01"
                                step="0.01"
                                required
                            >

                        </div>


                        <!-- GST DETAILS -->
                        <hr>

                        <h5 class="text-warning">GST Details</h5>

                        <hr>


                        <div class="form-group">

                            <label>
                                <input
                                    type="checkbox"
                                    id="apply_gst"
                                    name="apply_gst"
                                    value="1"
                                    onclick="toggleGSTFields()"
                                >

                                Apply GST
                            </label>

                        </div>


                        <div id="gst_fields" style="display:none;">

                            <div class="form-row">

                                <div class="form-group">

                                    <label>GST Rate</label>

                                    <select name="gst_rate">
                                        <option value="5">GST 5%</option>
                                        <option value="18" selected>GST 18%</option>
                                    </select>

                                </div>


                                <div class="form-group">

                                    <label>GST Type</label>

                                    <select name="gst_type">

                                        <option value="exclusive">
                                            Exclusive - GST Extra
                                        </option>

                                        <option value="inclusive">
                                            Inclusive - GST Included
                                        </option>

                                    </select>

                                </div>

                            </div>


                            <div class="form-group">

                                <label>GST Number</label>

                                <input
                                    type="text"
                                    name="gst_number"
                                    maxlength="15"
                                >

                            </div>


                            <div class="form-group">

                                <label>Billing Address</label>

                                <textarea name="billing_address" rows="3"></textarea>

                            </div>


                            <div class="form-group">

                                <label>Shipping Address</label>

                                <textarea name="shipping_address" rows="3"></textarea>

                            </div>

                        </div>


                    <?php } else { ?>

                        <!-- =================================
                             SERVICE DETAILS
                        ================================== -->

                        <hr>

                        <h5 class="text-success">Solar Service Details</h5>

                        <hr>


                        <!-- SERVICE NAME -->
                        <div class="form-group">

                            <label>Service Name</label>

                            <input
                                type="text"
                                name="service"
                                required
                            >

                        </div>


                        <!-- SERVICE DATE -->
                        <div class="form-group">

                            <label>Service Date</label>

                            <input
                                type="date"
                                name="service_date"
                                required
                            >

                        </div>


                        <!-- CONSUMER NO + SUBDIVISION -->
                        <div class="form-row">

                            <div class="form-group">

                                <label>Consumer No</label>

                                <input
                                    type="text"
                                    name="consumer_no"
                                >

                            </div>


                            <div class="form-group">

                                <label>Subdivision</label>

                                <input
                                    type="text"
                                    name="subdivision"
                                >

                            </div>

                        </div>


                        <!-- TECHNICAL NAME -->
                        <div class="form-group">

                            <label>Technical Name</label>

                            <input
                                type="text"
                                name="technical_name"
                            >

                        </div>


                        <!-- TERMS & CONDITIONS -->
                        <div class="form-group">

                            <label>Terms &amp; Conditions</label>

                            <textarea
                                name="service_term_condition"
                                rows="4"
                                required
                            ></textarea>

                        </div>


                        <!-- TOTAL AMOUNT -->
                        <div class="form-group">

                            <label>Total Amount</label>

                            <input
                                type="number"
                                name="total_amount"
                                min="0.01"
                                step="0.01"
                                required
                            >

                        </div>


                        <!-- WARRANTY -->
                        <div class="form-group">

                            <label>Warranty</label>

                            <input
                                type="text"
                                name="warranty"
                                required
                            >

                        </div>


                        <!-- SERVICE COVERED -->
                        <div class="form-group">

                            <label>Service Covered</label>

                            <textarea
                                name="service_covered"
                                rows="4"
                                required
                            ></textarea>

                        </div>


                        <!-- GST DETAILS -->
                        <hr>

                        <h5 class="text-warning">GST Details</h5>

                        <hr>


                        <div class="form-group">

                            <label>
                                <input
                                    type="checkbox"
                                    id="apply_gst"
                                    name="apply_gst"
                                    value="1"
                                    onclick="toggleGSTFields()"
                                >

                                Apply GST (18%)
                            </label>

                        </div>


                        <div id="gst_fields" style="display:none;">

                            <div class="form-group">

                                <label>GST Type</label>

                                <select
                                    name="gst_type"
                                    id="gst_type"
                                >

                                    <option value="exclusive">
                                        Exclusive - GST Extra
                                    </option>

                                    <option value="inclusive">
                                        Inclusive - GST Included
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label>Billing Address</label>

                                <textarea
                                    name="billing_address"
                                    rows="3"
                                ></textarea>

                            </div>


                            <div class="form-group">

                                <label>Shipping Address</label>

                                <textarea
                                    name="shipping_address"
                                    rows="3"
                                ></textarea>

                            </div>

                        </div>

                    <?php } ?>


                    <!-- SUBMIT BUTTON -->
                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        <span>☀️</span>

                        Save Solar Client

                    </button>

                </form>

            </div>

        </div>

    </section>

</section>


<!-- =========================================
     JAVASCRIPT
========================================= -->

<script>

function toggleGSTFields() {

    var checkbox = document.getElementById('apply_gst');

    var gstFields = document.getElementById('gst_fields');

    if (checkbox.checked) {

        gstFields.style.display = 'block';

    } else {

        gstFields.style.display = 'none';

    }
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
?>
