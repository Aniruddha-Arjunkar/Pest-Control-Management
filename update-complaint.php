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
   LOGOUT
========================= */
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('Location: login.php');
    exit();
}

/* =========================
   DATABASE
========================= */
require_once 'db_connect.php';
include 'header.php';

/* =========================
   GET COMPLAINT ID
========================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>
        alert('Invalid complaint ID.');
        window.location.href='view-complaint-details.php';
    </script>";
    exit();
}

$complaint_id = intval($_GET['id']);

/* =========================
   FETCH COMPLAINT DETAILS
========================= */

$sql = "SELECT 
            c.Id,
            c.details,
            c.complaint_date,
            cl.cust_name,
            cl.services,
            cl.cust_phone,
            cl.cust_address,
            cl.due_date,
            cl.operator_name,
            rc.resolver_name,
            rc.resolve_date
        FROM tbl_complaints c
        JOIN tbl_client cl 
            ON c.client_id = cl.Id
        LEFT JOIN tbl_resolved_complaints rc 
            ON c.Id = rc.client_id 
            AND rc.complaint_date = c.complaint_date
        WHERE c.Id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $complaint_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $complaint = $result->fetch_assoc();

} else {

    echo "<script>
        alert('Complaint not found.');
        window.location.href='view-complaint-details.php';
    </script>";
    exit();
}

$stmt->close();


/* =========================
   UPDATE COMPLAINT
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $updated_details = trim($_POST['details'] ?? '');
    $updated_complaint_date = trim($_POST['complaint_date'] ?? '');

    /* Required validation */

    if ($updated_details === '' || $updated_complaint_date === '') {

        echo "<script>
            alert('Please fill all required fields.');
        </script>";

    } else {

        $updateSql = "
            UPDATE tbl_complaints 
            SET 
                details = ?,
                complaint_date = ?
            WHERE Id = ?
        ";

        $updateStmt = $conn->prepare($updateSql);

        if (!$updateStmt) {

            echo "<script>
                alert('Database error: " . addslashes($conn->error) . "');
            </script>";

        } else {

            $updateStmt->bind_param(
                "ssi",
                $updated_details,
                $updated_complaint_date,
                $complaint_id
            );

            if ($updateStmt->execute()) {

                echo "<script>
                    alert('Complaint updated successfully');
                    window.location.href='view-complaint-details.php?id={$complaint_id}';
                </script>";

                exit();

            } else {

                echo "<script>
                    alert('Update failed: " . addslashes($updateStmt->error) . "');
                </script>";
            }

            $updateStmt->close();
        }
    }
}
?>


<style>

/* =========================================================
   UPDATE COMPLAINT PAGE
========================================================= */

.update-complaint-page {

    width: 100%;

    min-height: calc(100vh - 70px);

    background: #f3f6fb;

    display: flex;

    justify-content: center;

    align-items: flex-start;

    padding: 30px 20px 40px;

    box-sizing: border-box;
}


/* =========================================================
   CARD
========================================================= */

.update-complaint-card {

    width: 100%;

    max-width: 500px;

    background: #ffffff;

    border-radius: 15px;

    overflow: hidden;

    border: 3px solid #294bd4;

    box-shadow:
        0 12px 30px rgba(37, 65, 150, 0.15);

    box-sizing: border-box;
}


/* =========================================================
   HEADER
========================================================= */

.update-complaint-header {

    min-height: 78px;

    background: linear-gradient(
        110deg,
        #1749cf 0%,
        #3946dc 48%,
        #7135e8 100%
    );

    display: flex;

    align-items: center;

    justify-content: center;

    text-align: center;

    padding: 10px 20px;

    box-sizing: border-box;
}


/* Header Group */

.update-complaint-title {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 10px;

    text-align: center;
}


/* Icon */

.update-complaint-icon {

    font-size: 21px;

    line-height: 1;
}


/* Heading */

.update-complaint-header h2 {

    margin: 0 !important;

    padding: 0 !important;

    color: #ffffff !important;

    font-size: 20px !important;

    font-weight: 700;

    line-height: 25px;

    text-align: center !important;
}


/* Subtitle */

.update-complaint-header p {

    margin: 2px 0 0;

    color: rgba(255,255,255,0.95);

    font-size: 10px;

    text-align: center;
}


/* =========================================================
   FORM
========================================================= */

.update-complaint-form {

    padding: 15px 22px 20px;

    background: #ffffff;

    box-sizing: border-box;
}


/* =========================================================
   FORM GROUP
========================================================= */

.update-complaint-form .complaint-form-group {

    margin-bottom: 10px;

    padding: 8px 10px;

    background: #f7f9fc;

    border: 1px solid #e5eaf2;

    border-radius: 9px;

    box-sizing: border-box;
}


/* =========================================================
   LABEL
========================================================= */

.update-complaint-form .complaint-form-group label {

    display: block;

    margin-bottom: 4px;

    color: #52627a;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: .3px;

    text-transform: uppercase;
}


/* Required */

.update-complaint-form .required {

    color: #e33b3b;

    margin-left: 2px;
}


/* =========================================================
   INPUT + TEXTAREA
========================================================= */

.update-complaint-form input[type="date"],
.update-complaint-form textarea {

    width: 100%;

    display: block;

    box-sizing: border-box;

    border: 1px solid #d4dce8;

    border-radius: 7px;

    background: #ffffff;

    color: #14264d;

    font-family: Arial, sans-serif;

    font-size: 11px;

    outline: none;

    transition: all .2s ease;
}


/* Date */

.update-complaint-form input[type="date"] {

    height: 32px;

    padding: 5px 9px;
}


/* Textarea */

.update-complaint-form textarea {

    min-height: 100px;

    padding: 8px 9px;

    resize: vertical;

    line-height: 17px;
}


/* =========================================================
   FOCUS
========================================================= */

.update-complaint-form input[type="date"]:focus,
.update-complaint-form textarea:focus {

    border-color: #4656df;

    box-shadow:
        0 0 0 2px rgba(70,86,223,.10);

    outline: none;
}


/* =========================================================
   BUTTON ROW
========================================================= */

.complaint-button-row {

    display: flex;

    gap: 8px;

    margin-top: 8px;
}


/* =========================================================
   UPDATE BUTTON
========================================================= */

.complaint-update-btn {

    flex: 1;

    height: 35px;

    border: 0;

    border-radius: 8px;

    background: linear-gradient(
        100deg,
        #4b3de4,
        #194fd4
    );

    color: #ffffff;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 5px 12px rgba(50,66,210,.22);

    transition: all .2s ease;
}


.complaint-update-btn:hover {

    transform: translateY(-1px);

    box-shadow:
        0 7px 16px rgba(50,66,210,.30);
}


.complaint-update-btn:active {

    transform: translateY(0);
}


/* =========================================================
   BACK BUTTON
========================================================= */

.complaint-back-btn {

    flex: 1;

    height: 35px;

    display: flex;

    align-items: center;

    justify-content: center;

    box-sizing: border-box;

    border: 1px solid #4656df;

    border-radius: 8px;

    background: #ffffff;

    color: #3549bb;

    font-size: 11px;

    font-weight: 700;

    text-decoration: none;

    cursor: pointer;

    transition: all .2s ease;
}


.complaint-back-btn:hover {

    background: #eef1ff;

    border-color: #3549bb;

    color: #3549bb;

    text-decoration: none;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 600px) {

    .update-complaint-page {

        padding: 15px 10px 30px;
    }


    .update-complaint-card {

        max-width: 100%;

        border-radius: 12px;
    }


    .update-complaint-form {

        padding: 12px 14px 18px;
    }


    .update-complaint-header {

        min-height: 65px;

        padding: 10px 15px;
    }


    .update-complaint-header h2 {

        font-size: 18px !important;

        line-height: 24px;
    }


    .complaint-button-row {

        flex-direction: column;

        gap: 7px;
    }


    .complaint-update-btn,
    .complaint-back-btn {

        width: 100%;

        flex: none;
    }
}

</style>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<section id="main-content">

    <section class="wrapper">

        <div class="update-complaint-page">

            <div class="update-complaint-card">


                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="update-complaint-header">

                    <div class="update-complaint-title">

                        <span class="update-complaint-icon">
                            📝
                        </span>

                        <div>

                            <h2>
                                Update Complaint
                            </h2>

                            <p>
                                Update complaint details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     FORM
                ================================================== -->

                <form
                    action=""
                    method="post"
                    class="update-complaint-form"
                >


                    <!-- COMPLAINT DETAILS -->

                    <div class="complaint-form-group">

                        <label for="details">
                            Complaint Details
                            <span class="required">*</span>
                        </label>

                        <textarea
                            name="details"
                            id="details"
                            rows="5"
                            required
                            placeholder="Enter complaint details"
                        ><?= htmlspecialchars($complaint['details'] ?? ''); ?></textarea>

                    </div>


                    <!-- COMPLAINT DATE -->

                    <div class="complaint-form-group">

                        <label for="complaint_date">
                            Complaint Date
                            <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            name="complaint_date"
                            id="complaint_date"
                            value="<?= htmlspecialchars($complaint['complaint_date'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- BUTTONS -->

                    <div class="complaint-button-row">

                        <button
                            type="submit"
                            name="update"
                            class="complaint-update-btn"
                        >
                            ✓ &nbsp; Update Complaint
                        </button>


                        <a
                            href="view-complaint-details.php?id=<?= intval($complaint['Id']); ?>"
                            class="complaint-back-btn"
                        >
                            ← &nbsp; Back to Details
                        </a>

                    </div>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

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
