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
   DATABASE
========================= */

require_once 'db_connect.php';

/* =========================
   GET INQUIRY DATA
========================= */

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';

$allowed_types = ['refollowup', 'visit', 'service', 'schedule'];

if ($id <= 0 || !in_array($type, $allowed_types, true)) {
    echo "<script>alert('Invalid inquiry details'); window.location.href='solar-view-inquiry.php';</script>";
    exit;
}

$stmt = $conn->prepare("
    SELECT * FROM tbl_solar_inquiry WHERE id = ? LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

$stmt->close();

if (!$data) {
    echo "<script>alert('Solar inquiry not found'); window.location.href='solar-view-inquiry.php';</script>";
    exit;
}

include 'header.php';
?>

<!-- =========================================
     MAIN CONTENT
========================================= -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">

                <!-- HEADER -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">☀️</span>

                        <div>
                            <h2><?= ucfirst($type); ?> Solar Inquiry</h2>

                            <p>
                                Update solar customer inquiry details
                            </p>
                        </div>

                    </div>

                </div>

                <!-- FORM -->

                <form
                    action="update-solar-inquiry-code.php"
                    method="POST"
                    class="refollowup-form"
                >

                    <input type="hidden" name="id"
                           value="<?= (int)$data['id']; ?>">

                    <input type="hidden" name="type"
                           value="<?= htmlspecialchars($type); ?>">

                    <!-- CUSTOMER DETAILS -->

                    <h3>Customer Details</h3>

                    <div class="form-group">

                        <label>Customer Name <span>*</span></label>

                        <input
                            type="text"
                            name="name"
                            value="<?= htmlspecialchars($data['name'] ?? ''); ?>"
                            placeholder="Enter Customer Name"
                            required
                        >

                    </div>

                    <div class="form-row">

                        <div class="form-group">

                            <label>Phone Number <span>*</span></label>

                            <input
                                type="tel"
                                name="phone"
                                maxlength="15"
                                value="<?= htmlspecialchars($data['phone'] ?? ''); ?>"
                                placeholder="Enter Phone Number"
                                required
                            >

                        </div>

                        <div class="form-group">

                            <label>Email Address</label>

                            <input
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($data['email'] ?? ''); ?>"
                                placeholder="Enter Email Address"
                            >

                        </div>

                    </div>

                    <div class="form-group">

                        <label>Address</label>

                        <input
                            type="text"
                            name="address"
                            value="<?= htmlspecialchars($data['address'] ?? ''); ?>"
                            placeholder="Enter Customer Address"
                        >

                    </div>

                    <!-- REFOLLOWUP -->

                    <?php if ($type == "refollowup") { ?>

                        <h3>Re-Followup Details</h3>

                        <div class="form-group">

                            <label>Re-Followup Remark <span>*</span></label>

                            <textarea
                                name="message"
                                rows="4"
                                placeholder="Enter Re-Followup Remark"
                                required
                            ><?= htmlspecialchars($data['message'] ?? ''); ?></textarea>

                        </div>

                        <div class="form-group">

                            <label>Re-Followup Date <span>*</span></label>

                            <input type="date" name="date" required>

                        </div>

                    <?php } ?>

                    <!-- VISIT -->

                    <?php if ($type == "visit") { ?>

                        <h3>Visit Details</h3>

                        <div class="form-group">

                            <label>Visit Remark <span>*</span></label>

                            <textarea
                                name="message"
                                rows="4"
                                placeholder="Enter Visit Remark"
                                required
                            ><?= htmlspecialchars($data['message'] ?? ''); ?></textarea>

                        </div>

                        <div class="form-group">

                            <label>Visit Date <span>*</span></label>

                            <input type="date" name="date" required>

                        </div>

                    <?php } ?>

                    <!-- SERVICE -->

                    <?php if ($type == "service") { ?>

                        <h3>Service Details</h3>

                        <div class="form-group">

                            <label>Service Remark <span>*</span></label>

                            <textarea
                                name="message"
                                rows="4"
                                placeholder="Enter Service Remark"
                                required
                            ><?= htmlspecialchars($data['message'] ?? ''); ?></textarea>

                        </div>

                        <div class="form-group">

                            <label>Service Date <span>*</span></label>

                            <input type="date" name="date" required>

                        </div>

                    <?php } ?>

                    <!-- SCHEDULE -->

                    <?php if ($type == "schedule") { ?>

                        <h3>Schedule Details</h3>

                        <div class="form-group">

                            <label>Schedule Remark <span>*</span></label>

                            <textarea
                                name="message"
                                rows="4"
                                placeholder="Enter Schedule Remark"
                                required
                            ><?= htmlspecialchars($data['message'] ?? ''); ?></textarea>

                        </div>

                        <div class="form-group">

                            <label>Schedule Date <span>*</span></label>

                            <input type="date" name="date" required>

                        </div>

                    <?php } ?>

                    <!-- BUTTONS -->

                    <div class="form-row">

                        <button
                            type="submit"
                            name="submit"
                            value="Submit"
                            class="submit-btn"
                        >
                            ☀️ Update Inquiry
                        </button>

                        <a href="solar-view-inquiry.php"
                           class="submit-btn"
                           style="text-decoration:none; text-align:center;">
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </section>

</section>

<!-- JAVASCRIPT -->

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>

</body>
</html>

<?php
$conn->close();
?>
