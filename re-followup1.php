<?php

session_start();

/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['email'])) {

    $_SESSION['msg'] = "You must log in first";

    header('Location: login.php');

    exit();
}


/* =========================
   DATABASE
========================= */

require_once 'db_connect.php';

include 'header.php';


/* =========================
   GET ID
========================= */

$Id = isset($_GET['id']) ? intval($_GET['id']) : 0;


if ($Id <= 0) {

    echo "<script>
        alert('Invalid request.');
        window.location.href='view-all-inquiry-clients.php';
    </script>";

    exit();
}


/* =========================
   FETCH RECORD
========================= */

$sql = "SELECT * FROM tbl_inquiry WHERE id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {

    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'i', $Id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$row) {

    echo "<script>
        alert('No record found.');
        window.location.href='view-all-inquiry-clients.php';
    </script>";

    exit();
}


/* =====================================================
   FORM SUBMIT
===================================================== */

if (isset($_POST['submit'])) {

    $follow_up_date = trim($_POST['follow_up_date'] ?? '');

    $cust_message = trim($_POST['cust_message'] ?? '');


    /* =========================
       VALIDATION
    ========================= */

    if (empty($follow_up_date)) {

        echo "<script>
            alert('Please select follow-up date.');
        </script>";

    } else {


        /* =================================================
           START TRANSACTION
        ================================================= */

        mysqli_begin_transaction($conn);

        try {


            /* =============================================
               1. UPDATE CURRENT RECORD
            ============================================= */

            $update_sql = "UPDATE tbl_inquiry SET

                follow_up_date = ?,

                service_date = NULL,

                visit_date = NULL,

                cust_message = ?

                WHERE id = ?";


            $stmt_update = mysqli_prepare(
                $conn,
                $update_sql
            );


            if (!$stmt_update) {

                throw new Exception(
                    "Update prepare error: " .
                    mysqli_error($conn)
                );
            }


            mysqli_stmt_bind_param(
                $stmt_update,
                'ssi',
                $follow_up_date,
                $cust_message,
                $Id
            );


            if (!mysqli_stmt_execute($stmt_update)) {

                throw new Exception(
                    "Update error: " .
                    mysqli_stmt_error($stmt_update)
                );
            }


            mysqli_stmt_close($stmt_update);


            /* =============================================
               2. COPY RECORD
                  tbl_inquiry -> tbl_inquiry
            ============================================= */

            $insert_sql = "INSERT INTO tbl_inquiry
                (
                    services,
                    cust_name,
                    cust_phone,
                    cust_email,
                    cust_address,
                    follow_up_date,
                    service_date,
                    visit_date,
                    cust_message
                )

                SELECT

                    services,
                    cust_name,
                    cust_phone,
                    cust_email,
                    cust_address,
                    follow_up_date,
                    service_date,
                    visit_date,
                    cust_message

                FROM tbl_inquiry

                WHERE id = ?";


            $stmt_insert = mysqli_prepare(
                $conn,
                $insert_sql
            );


            if (!$stmt_insert) {

                throw new Exception(
                    "Insert prepare error: " .
                    mysqli_error($conn)
                );
            }


            mysqli_stmt_bind_param(
                $stmt_insert,
                'i',
                $Id
            );


            if (!mysqli_stmt_execute($stmt_insert)) {

                throw new Exception(
                    "Insert error: " .
                    mysqli_stmt_error($stmt_insert)
                );
            }


            mysqli_stmt_close($stmt_insert);


            /* =============================================
               3. DELETE OLD RECORD
            ============================================= */

            $delete_sql =
                "DELETE FROM tbl_inquiry WHERE id = ?";


            $stmt_delete = mysqli_prepare(
                $conn,
                $delete_sql
            );


            if (!$stmt_delete) {

                throw new Exception(
                    "Delete prepare error: " .
                    mysqli_error($conn)
                );
            }


            mysqli_stmt_bind_param(
                $stmt_delete,
                'i',
                $Id
            );


            if (!mysqli_stmt_execute($stmt_delete)) {

                throw new Exception(
                    "Delete error: " .
                    mysqli_stmt_error($stmt_delete)
                );
            }


            mysqli_stmt_close($stmt_delete);


            /* =============================================
               COMMIT
            ============================================= */

            mysqli_commit($conn);


            echo "<script>

                alert(
                    'Follow-up updated successfully.'
                );

                window.location.href =
                    'view-all-inquiry-clients.php';

            </script>";

            exit();


        } catch (Exception $e) {


            /* =============================================
               ROLLBACK
            ============================================= */

            mysqli_rollback($conn);


            echo "<script>

                alert(
                    'Operation failed: " .
                    addslashes($e->getMessage()) .
                    "'
                );

            </script>";
        }
    }
}

?>




<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">


                <!-- =========================
                     HEADER
                ========================== -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">
                            📞
                        </span>

                        <div>

                            <h2>
                                Re-Followup
                            </h2>

                            <p>
                                Schedule next customer follow-up
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =========================
                     FORM
                ========================== -->

                <form
                    action=""
                    method="post"
                    class="refollowup-form"
                >


                    <!-- CUSTOMER NAME -->

                    <div class="form-group">

                        <label>
                            Customer Name
                        </label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars(
                                $row['cust_name'] ?? ''
                            ); ?>"
                            readonly
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label>
                            Phone
                        </label>

                        <input
                            type="text"
                            value="<?= htmlspecialchars(
                                $row['cust_phone'] ?? ''
                            ); ?>"
                            readonly
                        >

                    </div>


                    <!-- FOLLOW UP DATE -->

                    <div class="form-group">

                        <label>
                            Follow-Up Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            name="follow_up_date"
                            value="<?= htmlspecialchars(
                                $row['follow_up_date'] ?? ''
                            ); ?>"
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
                            rows="4"
                            placeholder="Enter follow-up message..."
                        ><?= htmlspecialchars(
                            $row['cust_message'] ?? ''
                        ); ?></textarea>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>📞</span>

                        Update Follow-Up

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

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
