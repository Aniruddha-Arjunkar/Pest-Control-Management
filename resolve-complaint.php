<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

// Check if complaint ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid complaint ID');
}

$complaint_id = intval($_GET['id']);

// Fetch complaint details
$sql = "SELECT * FROM tbl_complaints WHERE Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $complaint_id);
$stmt->execute();

$result = $stmt->get_result();
$complaint = $result->fetch_assoc();

if (!$complaint) {
    die('Complaint not found');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $resolver_name = mysqli_real_escape_string(
        $conn,
        $_POST['resolver_name']
    );

    $resolve_date = mysqli_real_escape_string(
        $conn,
        $_POST['resolve_date']
    );

    $updated_details = mysqli_real_escape_string(
        $conn,
        $_POST['updated_details']
    );

    // Insert resolved complaint
    $sql_insert = "
        INSERT INTO tbl_resolved_complaints
        (
            client_id,
            details,
            complaint_date,
            resolver_name,
            resolve_date
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt_insert = $conn->prepare($sql_insert);

    $stmt_insert->bind_param(
        "issss",
        $complaint['client_id'],
        $updated_details,
        $complaint['complaint_date'],
        $resolver_name,
        $resolve_date
    );

    if ($stmt_insert->execute()) {

        // Insert service material record
        $client_id = $complaint['client_id'];
        $remarks = $complaint['details'];
        $used_date = $resolve_date;

        $sql_material = "
            INSERT INTO service_materials
            (
                client_id,
                used_date,
                remarks,
                quantity_unit,
                branch_id
            )
            VALUES (?, ?, ?, 'N/A', 0)
        ";

        $stmt_material = $conn->prepare($sql_material);

        $stmt_material->bind_param(
            "iss",
            $client_id,
            $used_date,
            $remarks
        );

        $stmt_material->execute();

        // Delete complaint from active complaints
        $sql_delete = "DELETE FROM tbl_complaints WHERE Id = ?";

        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $complaint_id);

        if ($stmt_delete->execute()) {

            echo "
            <script>
                alert('Complaint resolved successfully');
                window.location.href='view-complaints.php';
            </script>
            ";

            exit();

        } else {

            echo "Error deleting complaint: " .
                 htmlspecialchars($stmt_delete->error);
        }

    } else {

        echo "Error inserting resolved complaint: " .
             htmlspecialchars($stmt_insert->error);
    }
}
?>



<body>

<!-- Main content start -->

<section id="main-content">

    <section class="wrapper">

       <div class="reg-w3">

    <div class="refollowup-card">

        <div class="refollowup-header">

            <div class="refollowup-title">

                <span class="phone-icon">✓</span>

                <div>

                    <h2>Resolve Complaint</h2>

                    <p>Update complaint resolution details</p>

                </div>

            </div>

        </div>
                <!-- Form -->

                <form
    class="refollowup-form"
    action="resolve-complaint.php?id=<?= $complaint_id; ?>"
    method="post"
>

                    <!-- Complaint Details -->

                    <div class="form-group">

                        <label for="complaint-details">
                            Complaint Details
                        </label>

                        <textarea
                            id="complaint-details"
                            name="updated_details"
                            rows="4"
                        ><?= htmlspecialchars($complaint['details']); ?></textarea>

                    </div>


                    <!-- Resolved By -->

                    <div class="form-group">

                        <label for="resolver_name">
                            Resolved By <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="resolver_name"
                            name="resolver_name"
                            placeholder="Enter resolver name"
                            required
                        >

                    </div>


                    <!-- Resolution Date -->

                    <div class="form-group">

                        <label for="resolve_date">
                            Resolution Date <span>*</span>
                        </label>

                        <input
                            type="date"
                            id="resolve_date"
                            name="resolve_date"
                            value="<?= date('Y-m-d'); ?>"
                            required
                        >

                    </div>


                    <!-- Submit -->

<div style="display: flex; gap: 12px; align-items: center; flex-wrap: nowrap;">

    <button
        type="submit"
        class="submit-btn"
    >
        <span>✓</span>
        Mark as Resolved
    </button>

    <a
        href="view-complaints.php"
        class="refollowup-back-btn"
    >
        ← Back to Complaints
    </a>

</div>

                </form>

            </div>

        </div>

    </section>

</section>

<!-- Main content end -->


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