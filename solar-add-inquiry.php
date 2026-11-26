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
   ADD SOLAR INQUIRY
========================= */

if (isset($_POST['submit'])) {

    /* =========================
       GET FORM DATA
    ========================= */

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $inq_date = trim($_POST['inq_date'] ?? '');

    /* =========================
       VALIDATION
    ========================= */

    if (
        empty($name) ||
        empty($phone) ||
        empty($inq_date)
    ) {

        echo "<script>
            alert('Please fill all required fields');
        </script>";

    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $inq_date) ||
        !checkdate(
            (int)substr($inq_date, 5, 2),
            (int)substr($inq_date, 8, 2),
            (int)substr($inq_date, 0, 4)
        )
    ) {

        echo "<script>
            alert('Please enter a valid inquiry date');
        </script>";

    } else {

        /* =========================
           INSERT INQUIRY
        ========================= */

        $stmt = $conn->prepare("
            INSERT INTO tbl_solar_inquiry
            (
                name,
                phone,
                email,
                address,
                message,
                inq_date
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if ($stmt) {

            $stmt->bind_param(
                "ssssss",
                $name,
                $phone,
                $email,
                $address,
                $message,
                $inq_date
            );

            if ($stmt->execute()) {

                echo "<script>
                    alert('Solar Inquiry added successfully...');
                    window.location.href='solar-view-inquiry.php';
                </script>";
                $stmt->close();
                exit;
            } else {
                echo "<script>
                    alert('Inquiry failed: " .
                    addslashes($stmt->error) .
                    "');
                </script>";
            }
            $stmt->close();
        } else {
            echo "<script>
                alert('Database query failed');
            </script>";
        }
    }
}
include 'header.php';
?>


<!-- =========================================
     MAIN CONTENT
========================================= -->

<section id="solar-main-content">
    <section class="outer-wrapper">
        <div class="solar-reg-w3">
            <!-- DESIGN CARD -->
            <div class="solar-form-card">
                <!-- HEADER -->
                <div class="form-header">
                    <div class="form-card-title">
                        <span class="form-header-icon">☀️</span>
                        <div>
                            <h2>Add Solar Inquiry</h2>
                            <p>
                                Add new solar customer inquiry details
                            </p>
                        </div>
                    </div>
                </div>


                <!-- FORM -->

                <form
                    action="solar-add-inquiry.php"
                    method="POST"
                    class="actual-form"
                >

                    <!-- CUSTOMER NAME -->
                    <div class="actual-form-group">

                        <label>
                            Customer Name <span>*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            placeholder="Enter Customer Name"
                            required
                        >
                    </div>

                    <!-- PHONE + EMAIL -->
                    <div class="actual-form-row">
                        <!-- PHONE -->
                        <div class="actual-form-group">
                            <label>
                                Phone Number <span>*</span>
                            </label>
                            <input
                                type="tel"
                                name="phone"
                                maxlength="15"
                                placeholder="Enter Phone Number"
                                required
                            >
                        </div>

                        <!-- EMAIL -->
                        <div class="actual-form-group">
                            <label>
                                Email Address
                            </label>
                            <input
                                type="email"
                                name="email"
                                placeholder="Enter Email Address"
                            >
                        </div>
                    </div>

                    <!-- INQUIRY DATE -->
                    <div class="actual-form-group">
                        <label>
                            Inquiry Date <span>*</span>
                        </label>
                        <input
                            type="date"
                            name="inq_date"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>

                    <!-- ADDRESS -->
                    <div class="actual-form-group">
                        <label>
                            Address
                        </label>
                        <input
                            type="text"
                            name="address"
                            placeholder="Enter Customer Address"
                        >
                    </div>

                    <!-- MESSAGE -->
                    <div class="actual-form-group">
                        <label>
                            Solar Requirement / Message
                        </label>
                        <textarea
                            name="message"
                            placeholder="Enter Solar Inquiry Details"
                            rows="4"
                        ></textarea>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <button
                        name="submit"
                        type="submit"
                        class="from-submit-btn"
                        value="Submit"
                    >
                        <span>☀️</span>
                        Submit Inquiry
                    </button>
                </form>
            </div>
        </div>
    </section>
</section>

<!-- ================== JAVASCRIPT ===================== -->
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
