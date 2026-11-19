<?php
session_start();
ob_start();

/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['email'])) {

    $_SESSION['msg'] = "You must log in first";

    header('location: login.php');

    exit();
}


/* =========================
   LOGOUT
========================= */

if (isset($_GET['logout'])) {

    session_destroy();

    unset($_SESSION['email']);

    header('location: login.php');

    exit();
}


/* =========================
   DATABASE
========================= */

require_once 'db_connect.php';

include 'header.php';


/* =========================
   GET CLIENT ID
========================= */

$client_id = isset($_GET['client_id'])
    ? intval($_GET['client_id'])
    : 0;


/* =========================
   CHECK CLIENT ID
========================= */

if ($client_id <= 0) {

    echo "
    <script>
        alert('Invalid client ID.');
        window.location.href='view-complaints.php';
    </script>
    ";

    exit();
}


/* =========================================================
   FETCH CLIENT
========================================================= */

$client = [];

$sql_client = "
    SELECT *
    FROM tbl_client
    WHERE Id = ?
    LIMIT 1
";

$stmt_client = $conn->prepare($sql_client);

if (!$stmt_client) {

    die("Database error: " . $conn->error);
}

$stmt_client->bind_param(
    "i",
    $client_id
);

$stmt_client->execute();

$result_client = $stmt_client->get_result();


if ($result_client->num_rows == 0) {

    echo "
    <script>
        alert('Client not found.');
        window.location.href='view-complaints.php';
    </script>
    ";

    exit();
}


$client = $result_client->fetch_assoc();

$stmt_client->close();


/* =========================================================
   HANDLE FORM SUBMISSION
========================================================= */

if (isset($_POST['submit'])) {


    $details = trim(
        $_POST['details'] ?? ''
    );


    $complaint_date =
        $_POST['complaint_date'] ?? '';


    /* =====================================================
       BASIC VALIDATION
    ===================================================== */

    if (
        empty($details) ||
        empty($complaint_date)
    ) {

        echo "
        <script>
            alert('Please fill all required fields.');
            history.back();
        </script>
        ";

        exit();
    }


    /* =====================================================
       CHECK IF COMPLAINT ALREADY EXISTS
    ===================================================== */

    $checkSql = "
        SELECT COUNT(*)
        FROM tbl_complaints
        WHERE client_id = ?
    ";


    $checkStmt =
        $conn->prepare($checkSql);


    if (!$checkStmt) {

        die(
            "Database error: "
            . $conn->error
        );
    }


    $checkStmt->bind_param(
        "i",
        $client_id
    );


    $checkStmt->execute();


    $checkStmt->bind_result(
        $complaintExists
    );


    $checkStmt->fetch();


    $checkStmt->close();


    /* =====================================================
       ALREADY EXISTS
    ===================================================== */

    if ($complaintExists > 0) {

        echo "
        <script>
            alert('This client has already submitted a complaint.');
            window.location.href='view-complaints.php';
        </script>
        ";

        exit();
    }


    /* =====================================================
       INSERT COMPLAINT
    ===================================================== */

    $sql = "
        INSERT INTO tbl_complaints
        (
            client_id,
            details,
            complaint_date
        )
        VALUES (?, ?, ?)
    ";


    $stmt =
        $conn->prepare($sql);


    if (!$stmt) {

        die(
            "Database error: "
            . $conn->error
        );
    }


    $stmt->bind_param(
        "iss",
        $client_id,
        $details,
        $complaint_date
    );


    if ($stmt->execute()) {

        echo "
        <script>
            alert('Complaint added successfully');
            window.location.href='view-complaints.php';
        </script>
        ";

        exit();

    } else {

        echo "
        <script>
            alert('Error adding complaint: "
            . addslashes($stmt->error)
            . "');
        </script>
        ";
    }


    $stmt->close();
}

?>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">


                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">
                            ⚠️
                        </span>

                        <div>

                            <h2>
                                Add Complaint
                            </h2>

                            <p>
                                Register a new customer complaint
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     FORM
                ================================================== -->

                <form
                    class="refollowup-form"
                    action=""
                    method="post"
                    onsubmit="return validateForm();"
                >


                    <!-- =================================================
                         CLIENT NAME
                    ================================================== -->

                    <div class="form-group">

                        <label for="client_name">

                            Client Name

                        </label>


                        <input
                            type="text"
                            id="client_name"
                            value="<?= htmlspecialchars(
                                $client['cust_name'] ?? ''
                            ); ?>"
                            readonly
                        >

                    </div>


                    <!-- =================================================
                         CLIENT ID
                    ================================================== -->

                    <input
                        type="hidden"
                        name="client_id"
                        value="<?= htmlspecialchars(
                            $client_id
                        ); ?>"
                    >


                    <!-- =================================================
                         COMPLAINT DETAILS
                    ================================================== -->

                    <div class="form-group">

                        <label for="details">

                            Complaint Details
                            <span>*</span>

                        </label>


                        <textarea
                            name="details"
                            id="details"
                            placeholder="Enter complaint details..."
                            rows="4"
                            required
                        ></textarea>

                    </div>


                    <!-- =================================================
                         COMPLAINT DATE
                    ================================================== -->

                    <div class="form-group">

                        <label for="complaint_date">

                            Complaint Date
                            <span>*</span>

                        </label>


                        <input
                            type="date"
                            name="complaint_date"
                            id="complaint_date"
                            value="<?= date('Y-m-d'); ?>"
                            required
                        >

                    </div>


                    <!-- =================================================
                         SUBMIT
                    ================================================== -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>
                            ✓
                        </span>

                        Add Complaint

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

function validateForm() {

    const details =
        document.getElementById('details')
            .value
            .trim();


    const complaintDate =
        document.getElementById('complaint_date')
            .value;


    if (!details) {

        alert(
            'Please enter the complaint details.'
        );

        document
            .getElementById('details')
            .focus();

        return false;
    }


    if (!complaintDate) {

        alert(
            'Please select a complaint date.'
        );

        document
            .getElementById('complaint_date')
            .focus();

        return false;
    }


    return true;
}

</script>


<!-- =====================================================
     JAVASCRIPT FILES
===================================================== -->

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

ob_end_flush();

?>