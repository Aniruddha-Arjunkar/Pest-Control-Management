
<?php
ob_start();
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

/* =========================
   DATABASE
========================= */
require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

/* =========================
   GET CLIENT & WORK
========================= */
$client_id = (int)($_GET['client_id'] ?? 0);
$work_description = $_GET['work'] ?? '';

/* =========================
   FIXED WORKS
========================= */
$fixed_works = [
    "Structure Installation",
    "Panel Fitting",
    "Wiring & Inverter Installation",
    "Released Order",
    "Generation Meter Fitting and Net Metering"
];

if (!in_array($work_description, $fixed_works, true)) {
    die("Invalid work selected.");
}

/* =========================
   FETCH EXISTING WORK
========================= */
$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM tbl_solar_pending_work
    WHERE client_id = ? AND work_description = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "is", $client_id, $work_description);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$work = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================
   FETCH CLIENT
========================= */
$stmt2 = mysqli_prepare($conn, "
    SELECT *
    FROM tbl_solar_client
    WHERE id = ?
");

mysqli_stmt_bind_param($stmt2, "i", $client_id);
mysqli_stmt_execute($stmt2);

$client = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

mysqli_stmt_close($stmt2);

if (!$client) {
    die("Client not found.");
}

/* =========================
   DEFAULT VALUES
========================= */
$assigned_to = $work['assigned_to'] ?? '';
$due_date = $work['due_date'] ?? '';
$status = $work['status'] ?? 'Pending';

include('header.php');
?>

<section id="main-content">
<section class="wrapper">

<div class="reg-w3">

    <div class="refollowup-card">

        <div class="refollowup-header">
            <h2 class="refollowup-title">
                Update Solar Pending Work
            </h2>
        </div>

        <p>
            Client Name:
            <strong>
                <?= htmlspecialchars($client['cust_name'], ENT_QUOTES, 'UTF-8'); ?>
            </strong>
        </p>

        <form action="solar-update-pending-code-work.php"
              method="POST"
              class="refollowup-form">

            <input type="hidden"
                   name="client_id"
                   value="<?= $client_id; ?>">

            <div class="form-row">

                <div class="form-group">
                    <label>Work Description</label>

                    <input type="text"
                           name="work_description"
                           value="<?= htmlspecialchars($work_description, ENT_QUOTES, 'UTF-8'); ?>"
                           readonly>
                </div>

                <div class="form-group">
                    <label>Assigned To</label>

                    <input type="text"
                           name="assigned_to"
                           placeholder="Enter Assigned Person"
                           value="<?= htmlspecialchars($assigned_to, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label>Service Date</label>

                    <input type="date"
                           name="due_date"
                           value="<?= htmlspecialchars($due_date, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label>Status</label>

                    <select name="status" required>

                        <option value="Pending"
                            <?= $status == 'Pending' ? 'selected' : ''; ?>>
                            Pending
                        </option>

                        <option value="Completed"
                            <?= $status == 'Completed' ? 'selected' : ''; ?>>
                            Completed
                        </option>

                    </select>
                </div>

            </div>

            <div class="form-group">
                <button type="submit"
                        name="update_pending_work"
                        class="submit-btn">
                    Update Pending Work
                </button>
            </div>

        </form>

    </div>

</div>

</section>
</section>

<!-- FOOTER SCRIPTS -->
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
