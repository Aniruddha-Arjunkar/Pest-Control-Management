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

$message = "";

/* =========================
   FETCH EMPLOYEES
========================= */
$employees = mysqli_query(
    $conn,
    "SELECT Id, cust_name, package 
     FROM tbl_employee 
     WHERE resignation_type IS NULL 
     OR resignation_type = ''"
);

/* =========================
   FORM SUBMISSION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employee_id = intval($_POST['employee_id'] ?? 0);
    $advance = floatval($_POST['advance'] ?? 0);

    $advance_date = !empty($_POST['advance_date'])
        ? $_POST['advance_date']
        : NULL;

    $remark = trim($_POST['remark'] ?? '');

    /* =========================
       VALIDATION
    ========================= */
    if ($employee_id <= 0) {

        $message = "<div class='alert alert-danger'>
            Please select employee.
        </div>";

    } elseif ($advance <= 0) {

        $message = "<div class='alert alert-danger'>
            Please enter valid advance amount.
        </div>";

    } elseif (empty($advance_date)) {

        $message = "<div class='alert alert-danger'>
            Please select advance date.
        </div>";

    } else {

        /* =========================
           INSERT ADVANCE
        ========================= */

        $stmt = $conn->prepare(
            "INSERT INTO tbl_salary
            (
                employee_id,
                payment_type,
                advance,
                advance_date,
                remark
            )
            VALUES
            (
                ?,
                'Advance',
                ?,
                ?,
                ?
            )"
        );

        $stmt->bind_param(
            "idss",
            $employee_id,
            $advance,
            $advance_date,
            $remark
        );

        if ($stmt->execute()) {

            $message = "<div class='alert alert-success'>
                Advance added successfully.
            </div>";

        } else {

            $message = "<div class='alert alert-danger'>
                Error: " . htmlspecialchars($stmt->error) . "
            </div>";
        }

        $stmt->close();
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

                        <span class="phone-icon">💰</span>

                        <div>

                            <h2>Add Employee Advance</h2>

                            <p>
                                Add advance payment details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    method="post"
                    class="refollowup-form"
                >

                    <?= $message ?>


                    <!-- EMPLOYEE -->

                    <div class="form-group">

                        <label>
                            Select Employee <span>*</span>
                        </label>

                        <select
                            name="employee_id"
                            class="employee-select"
                            required
                        >

                            <option value="">
                                -- Select Employee --
                            </option>

                            <?php
                            if ($employees) {

                                while ($emp = mysqli_fetch_assoc($employees)) {
                            ?>

                                <option
                                    value="<?= htmlspecialchars($emp['Id']); ?>"
                                    <?= (
                                        isset($_POST['employee_id']) &&
                                        $_POST['employee_id'] == $emp['Id']
                                    ) ? 'selected' : ''; ?>
                                >

                                    <?= htmlspecialchars($emp['cust_name']); ?>

                                    (Salary:
                                    ₹<?= htmlspecialchars($emp['package']); ?>)

                                </option>

                            <?php
                                }

                            } else {
                            ?>

                                <option value="">
                                    No employees found
                                </option>

                            <?php
                            }
                            ?>

                        </select>

                    </div>


                    <!-- ADVANCE AMOUNT -->

                    <div class="form-group">

                        <label>
                            Advance Amount <span>*</span>
                        </label>

                        <input
                            type="number"
                            name="advance"
                            placeholder="Enter Advance Amount"
                            step="0.01"
                            min="0.01"
                            value="<?= htmlspecialchars($_POST['advance'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- DATE -->

                    <div class="form-group">

                        <label>
                            Advance Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            name="advance_date"
                            value="<?= htmlspecialchars($_POST['advance_date'] ?? date('Y-m-d')); ?>"
                            required
                        >

                    </div>


                    <!-- REMARK -->

                    <div class="form-group">

                        <label>
                            Advance Remark
                        </label>

                        <textarea
                            name="remark"
                            placeholder="Enter remark"
                            rows="4"
                        ><?= htmlspecialchars($_POST['remark'] ?? ''); ?></textarea>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>💰</span>

                        Save Advance

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