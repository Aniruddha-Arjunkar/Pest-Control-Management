<?php
session_start();

/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

/* =========================================================
   LOGOUT
========================================================= */

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

/* =========================================================
   DATABASE
========================================================= */

require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   FIXED WORKS
========================================================= */

$fixed_works = [
    "Structure Installation",
    "Panel Fitting",
    "Wiring & Inverter Installation",
    "Released Order",
    "Generation Meter Fitting and Net Metering"
];

/* =========================================================
   SEARCH CLIENT
========================================================= */

$search = trim($_GET['search'] ?? '');

$stmt = mysqli_prepare($conn, "
    SELECT
        id,
        cust_name,
        cust_phone,
        cust_email
    FROM tbl_solar_client
    WHERE added_by = 'admin'
    AND (
        cust_name LIKE ?
        OR cust_phone LIKE ?
        OR cust_email LIKE ?
    )
    ORDER BY cust_name ASC
");

if (!$stmt) {
    die("Client Query Error: " . mysqli_error($conn));
}

$search_value = "%" . $search . "%";

mysqli_stmt_bind_param(
    $stmt,
    "sss",
    $search_value,
    $search_value,
    $search_value
);

mysqli_stmt_execute($stmt);

$clients = mysqli_stmt_get_result($stmt);

$client_pending = [];

/* =========================================================
   FETCH CLIENT-WISE PENDING WORK
========================================================= */

while ($client = mysqli_fetch_assoc($clients)) {

    $client_id = (int)$client['id'];

    /* FETCH EXISTING WORK RECORDS */

    $work_stmt = mysqli_prepare($conn, "
        SELECT *
        FROM tbl_solar_pending_work
        WHERE client_id = ?
    ");

    if (!$work_stmt) {
        die("Pending Work Query Error: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($work_stmt, "i", $client_id);

    mysqli_stmt_execute($work_stmt);

    $result = mysqli_stmt_get_result($work_stmt);

    $existing = [];

    while ($record = mysqli_fetch_assoc($result)) {
        $existing[] = $record;
    }

    mysqli_stmt_close($work_stmt);

    $pending_list = [];
    $matched_descriptions = [];

    /* =====================================================
       FIXED 5 WORK POINTS
    ===================================================== */

    foreach ($fixed_works as $work_name) {

        $found = null;

        foreach ($existing as $record) {

            if ($record['work_description'] === $work_name) {
                $found = $record;
                break;
            }
        }

        if ($found) {

            $matched_descriptions[] = $work_name;

            // Skip completed work
            if ($found['status'] === 'Completed') {
                continue;
            }

            $pending_list[] = [
                'id' => $found['id'],
                'work_description' => $work_name,
                'assigned_to' => $found['assigned_to'],
                'due_date' => $found['due_date'],
                'status' => $found['status'],
                'fixed' => true
            ];

        } else {

            // Virtual pending work (not saved in database)
            $pending_list[] = [
                'id' => 0,
                'work_description' => $work_name,
                'assigned_to' => '',
                'due_date' => '',
                'status' => 'Pending',
                'fixed' => true
            ];
        }
    }

    /* =====================================================
       OLD MANUALLY ADDED PENDING WORK
    ===================================================== */

    foreach ($existing as $record) {

        if (in_array($record['work_description'], $matched_descriptions)) {
            continue;
        }

        if ($record['status'] === 'Completed') {
            continue;
        }

        $pending_list[] = [
            'id' => $record['id'],
            'work_description' => $record['work_description'],
            'assigned_to' => $record['assigned_to'],
            'due_date' => $record['due_date'],
            'status' => $record['status'],
            'fixed' => false
        ];
    }

    /* =====================================================
       ADD ONLY CLIENTS HAVING PENDING WORK
    ===================================================== */

    if (!empty($pending_list)) {

        $client_pending[] = [
            'id' => $client_id,
            'name' => $client['cust_name'],
            'phone' => $client['cust_phone'],
            'email' => $client['cust_email'],
            'works' => $pending_list
        ];
    }
}

$totalClients = count($client_pending);

$totalPendingWorks = 0;

foreach ($client_pending as $client) {
    $totalPendingWorks += count($client['works']);
}

/* =========================================================
   HEADER
========================================================= */

include('header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">


<section id="main-content">
<section class="wrapper">

<div class="inquiry-page">

    <!-- PAGE HEADER -->

    <div class="inquiry-page-header">

        <h1>☀️ All Pending Work</h1>

        <p>
            View and manage all pending Solar client work

            <span class="inquiry-count">
                <?= $totalClients; ?> Clients
            </span>

            <span class="inquiry-count">
                <?= $totalPendingWorks; ?> Pending Works
            </span>
        </p>

    </div>

  

        <!-- SEARCH -->

        <div class="followup-top-area">

            <form method="GET" class="inquiry-search-wrapper">

                <input
                    type="text"
                    name="search"
                    id="txt_searchall"
                    class="inquiry-search"
                    placeholder="🔍 Search Solar Client..."
                    value="<?= e($search); ?>"
                >

            </form>

        </div>

        <!-- MAIN CLIENT TABLE -->

        <div class="inquiry-table-wrapper">

            <table class="inquiry-table">

                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Pending Work</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($client_pending)): ?>

                    <?php foreach ($client_pending as $index => $client): ?>

                        <?php
                        $collapse_id = "clientWork" . $client['id'];
                        ?>

                        <!-- CLIENT ROW -->

                        <tr class="data-row">

                            <td class="inquiry-sr">
                                <?= $index + 1; ?>
                            </td>

                            <td class="inquiry-name">
                                <?= e($client['name']); ?>
                            </td>

                            <td class="inquiry-phone">
                                <?= e($client['phone']); ?>
                            </td>

                            <td>
                                <?= e($client['email'] ?: '-'); ?>
                            </td>

                            <td>
                                <span class="pending-badge">
                                    <?= count($client['works']); ?> Pending
                                </span>
                            </td>

                            <td>

                                <button
                                    class="action-btn"
                                    type="button"
                                    data-toggle="collapse"
                                    data-target="#<?= e($collapse_id); ?>"
                                    aria-expanded="false"
                                    aria-controls="<?= e($collapse_id); ?>"
                                >
                                    <i class="bi bi-eye"></i>
                                    View Pending Work
                                </button>

                            </td>

                        </tr>

                        <!-- CLIENT WORK DETAILS -->

                        <tr class="collapse pending-work-row"
                            id="<?= e($collapse_id); ?>">

                            <td colspan="6">

                                <div class="pending-work-inner">

                                    <div class="pending-work-heading">
                                        <i class="bi bi-list-check"></i>
                                        Pending Work Details -
                                        <?= e($client['name']); ?>
                                    </div>

                                    <div class="inquiry-table-wrapper">

                                        <table class="pending-work-table">

                                            <thead>
                                                <tr>
                                                    <th>Sr. No.</th>
                                                    <th>Work Description</th>
                                                    <th>Assigned To</th>
                                                    <th>Service Date</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>

                                            <tbody>

                                            <?php foreach ($client['works'] as $i => $work): ?>

                                                <tr>

                                                    <td>
                                                        <?= $i + 1; ?>
                                                    </td>

                                                    <td class="pending-work-description">
                                                        <?= e($work['work_description']); ?>
                                                    </td>

                                                    <td>
                                                        <?= e($work['assigned_to'] ?: '-'); ?>
                                                    </td>

                                                    <td>
                                                        <?= e($work['due_date'] ?: '-'); ?>
                                                    </td>

                                                    <td>
                                                        <span class="status-badge">
                                                            <?= e($work['status']); ?>
                                                        </span>
                                                    </td>

                                                    <td>

                                                        <?php if ($work['fixed']): ?>

                                                            <a
                                                                href="solar-update-pending-work.php?client_id=<?= (int)$client['id']; ?>&work=<?= urlencode($work['work_description']); ?>"
                                                                class="primary2"
                                                            >
                                                                <i class="bi bi-pencil-square"></i>
                                                                Update
                                                            </a>

                                                        <?php else: ?>

                                                            <a
                                                                href="solar-update-pending-work.php?work_id=<?= (int)$work['id']; ?>"
                                                                class="primary2"
                                                            >
                                                                <i class="bi bi-pencil-square"></i>
                                                                Update
                                                            </a>

                                                        <?php endif; ?>

                                                    </td>

                                                </tr>

                                            <?php endforeach; ?>

                                            </tbody>

                                        </table>

                                    </div>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="pending-empty">
                            <i class="bi bi-inbox" style="font-size:25px;"></i>
                            <br>
                            No Pending Work Found
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

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
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
