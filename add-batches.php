<?php
session_start();

/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

/* =========================
   LOGOUT
========================= */

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location:login.php');
    exit();
}

/* =========================
   DATABASE
========================= */

require_once 'db_connect.php';
include 'header.php';

$error = '';

/* =========================
   FETCH TRAINERS
========================= */

$trainer_query = "SELECT * FROM tbl_trainers ORDER BY name ASC";
$trainer_result = mysqli_query($conn, $trainer_query);


/* =========================
   ADD BATCH
========================= */

if (isset($_POST['submit'])) {

    $batch_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['batch_name'] ?? '')
    );

    $trainer_id = intval($_POST['trainer_id'] ?? 0);

    $start_date = mysqli_real_escape_string(
        $conn,
        trim($_POST['start_date'] ?? '')
    );

    $end_date = mysqli_real_escape_string(
        $conn,
        trim($_POST['end_date'] ?? '')
    );


    /* =========================
       VALIDATION
    ========================= */

    if (empty($batch_name)) {

        $error = "Batch name is required.";

    } elseif ($trainer_id <= 0) {

        $error = "Please select trainer.";

    } elseif (empty($start_date)) {

        $error = "Start date is required.";

    } elseif (empty($end_date)) {

        $error = "End date is required.";

    } elseif ($end_date < $start_date) {

        $error = "End date cannot be before start date.";

    } else {

        /* =========================
           INSERT BATCH
        ========================= */

        $sql = "INSERT INTO tbl_batches
                (batch_name, trainer_id, start_date, end_date)
                VALUES
                ('$batch_name', '$trainer_id', '$start_date', '$end_date')";


        if (mysqli_query($conn, $sql)) {

            echo "<script>
                    alert('Added Successfully');
                    window.location.href='view-batches.php';
                  </script>";

            exit();

        } else {

            $error = "Error: " . mysqli_error($conn);
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

                        <span class="phone-icon">📚</span>

                        <div>

                            <h2>Add Batches</h2>

                            <p>
                                Create a new training batch
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


                    <!-- ERROR -->

                    <?php if (!empty($error)): ?>

                        <div class="alert alert-danger text-center">

                            <?= htmlspecialchars($error); ?>

                        </div>

                    <?php endif; ?>


                    <!-- BATCH NAME -->

                    <div class="form-group">

                        <label>
                            Batch Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="batch_name"
                            placeholder="Batch Name"
                            value="<?= htmlspecialchars($_POST['batch_name'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- TRAINER -->

                    <div class="form-group">

                        <label>
                            Trainer <span>*</span>
                        </label>

                        <select
                            name="trainer_id"
                            required
                        >

                            <option value="">
                                Select Trainer
                            </option>

                            <?php
                            if ($trainer_result) {

                                while ($trainer = mysqli_fetch_assoc($trainer_result)) {
                            ?>

                                <option
                                    value="<?= $trainer['Id']; ?>"
                                    <?= (
                                        isset($_POST['trainer_id']) &&
                                        $_POST['trainer_id'] == $trainer['Id']
                                    ) ? 'selected' : ''; ?>
                                >

                                    <?= htmlspecialchars($trainer['name']); ?>

                                </option>

                            <?php
                                }
                            }
                            ?>

                        </select>

                    </div>


                    <!-- START DATE + END DATE -->

                    <div class="form-row">


                        <!-- START DATE -->

                        <div class="form-group">

                            <label>
                                Start Date <span>*</span>
                            </label>

                            <input
                                type="date"
                                name="start_date"
                                value="<?= htmlspecialchars($_POST['start_date'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <!-- END DATE -->

                        <div class="form-group">

                            <label>
                                End Date <span>*</span>
                            </label>

                            <input
                                type="date"
                                name="end_date"
                                value="<?= htmlspecialchars($_POST['end_date'] ?? ''); ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>➕</span>

                        Add Batch

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