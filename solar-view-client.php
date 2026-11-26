
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
   SEARCH AND FILTER
========================================================= */

$type = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');

$allowedTypes = ['', 'me', 'vendor', 'trader', 'gst'];

if (!in_array($type, $allowedTypes, true)) {
    $type = '';
}

/* =========================================================
   HEADING
========================================================= */

if ($type === 'trader') {
    $heading = 'Solar Trader Clients';
    $description = 'View all Solar Trader clients';

} elseif ($type === 'vendor') {
    $heading = 'Solar Vendor Clients';
    $description = 'View all Solar Vendor clients';

} elseif ($type === 'me') {
    $heading = 'Solar ME Non-GST Clients';
    $description = 'View all ME non-GST Solar clients';

} elseif ($type === 'gst') {
    $heading = 'Solar ME GST Clients';
    $description = 'View all ME GST Solar clients';

} else {
    $heading = 'All Solar Clients';
    $description = 'View and manage all non-GST Solar clients';
}

/* =========================================================
   WHERE CONDITIONS
========================================================= */

$where = " WHERE 1=1 ";

$params = [];
$paramTypes = "";

/* ME NON-GST CLIENTS */

if ($type === 'me') {

    $where .= " AND c.added_by = ? AND c.apply_gst = ? ";

    $params[] = 'admin';
    $params[] = 0;

    $paramTypes .= "si";
}

/* VENDOR CLIENTS */

elseif ($type === 'vendor') {

    $where .= " AND c.added_by = ? AND c.apply_gst = ? ";

    $params[] = 'vendor';
    $params[] = 0;

    $paramTypes .= "si";
}

/* TRADER CLIENTS */

elseif ($type === 'trader') {

    $where .= " AND c.added_by = ? ";

    $params[] = 'trader';

    $paramTypes .= "s";
}

/* ME GST CLIENTS */

elseif ($type === 'gst') {

    $where .= " AND c.added_by = ? AND c.apply_gst = ? ";

    $params[] = 'admin';
    $params[] = 1;

    $paramTypes .= "si";
}

/* ALL CLIENTS - DEFAULT NON GST */

else {

    $where .= " AND c.apply_gst = ? ";

    $params[] = 0;

    $paramTypes .= "i";
}

/* =========================================================
   SEARCH
========================================================= */

if ($search !== '') {

    $where .= " AND (
        c.cust_name LIKE ?
        OR c.cust_phone LIKE ?
        OR c.cust_email LIKE ?
        OR c.cust_address LIKE ?
        OR c.service LIKE ?
    ) ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $paramTypes .= "sssss";
}

/* =========================================================
   COUNT CLIENTS
========================================================= */

$countSql = "
    SELECT COUNT(*) AS total
    FROM tbl_solar_client c
    $where
";

$countStmt = mysqli_prepare($conn, $countSql);

if (!$countStmt) {
    die("Count Query Error: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($countStmt, $paramTypes, ...$params);
}

mysqli_stmt_execute($countStmt);

$countResult = mysqli_stmt_get_result($countStmt);
$countRow = mysqli_fetch_assoc($countResult);

$totalRecords = (int)($countRow['total'] ?? 0);

mysqli_stmt_close($countStmt);

/* =========================================================
   FETCH SOLAR CLIENTS
========================================================= */

$sql = "
    SELECT
        c.*,
        v.vendor_name,

        (
            SELECT p.total_amount
            FROM tbl_solar_payment p
            WHERE p.client_id = c.id
            ORDER BY p.id DESC
            LIMIT 1
        ) AS final_amount

    FROM tbl_solar_client c

    LEFT JOIN tbl_solar_vendor v
        ON c.vendor_id = v.id

    $where

    ORDER BY c.id DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Client Query Error: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
}

mysqli_stmt_execute($stmt);

$query_run = mysqli_stmt_get_result($stmt);

/* =========================================================
   HEADER
========================================================= */

include('header.php');
?>
<style>
/* ================================
   SOLAR CLIENT FILTER BUTTONS
================================ */

.solar-client-filters {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    padding: 15px 10px;
    margin-bottom: 10px;
}

.solar-client-filters a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 18px;
    border-radius: 6px;
    text-decoration: none;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 1px solid transparent;
    min-height: 40px;
}

/* ME GST */
.solar-client-filters a:nth-child(1) {
    background: #0d6efd;
}

/* ALL CLIENTS */
.solar-client-filters a:nth-child(2) {
    background: #343a40;
}

/* ME NON GST */
.solar-client-filters a:nth-child(3) {
    background: #198754;
}

/* VENDOR */
.solar-client-filters a:nth-child(4) {
    background: #f0ad4e;
    color: #212529;
}

/* TRADER */
.solar-client-filters a:nth-child(5) {
    background: #0dcaf0;
    color: #212529;
}

/* HOVER EFFECT */
.solar-client-filters a:hover {
    transform: translateY(-3px);
    opacity: 0.9;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    text-decoration: none;
    color: #fff;
}

/* MOBILE RESPONSIVE */
@media (max-width: 576px) {

    .solar-client-filters {
        gap: 8px;
    }

    .solar-client-filters a {
        flex: 1 1 45%;
        padding: 9px 10px;
        font-size: 12px;
    }
}

    
.solar-client-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.solar-client-toolbar .inquiry-search-wrapper {
    width: 260px;
    max-width: 100%;
    flex-shrink: 0;
    margin: 0;
}

.solar-client-toolbar .solar-client-filters {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    padding: 0;
    margin: 0;
}

.solar-client-toolbar .solar-client-filters a {
    padding: 9px 13px;
    min-height: 36px;
    font-size: 12px;
}

@media(max-width: 768px) {
    .solar-client-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .solar-client-toolbar .inquiry-search-wrapper {
        width: 100%;
    }

    .solar-client-toolbar .solar-client-filters {
        justify-content: flex-start;
    }
}

</style>

<section id="main-content">
<section class="wrapper">

<div class="inquiry-page">

    <!-- PAGE HEADER -->

    <div class="inquiry-page-header">

        <h1>☀️ <?= e($heading); ?></h1>

        <p>
            <?= e($description); ?>
            <span class="inquiry-count">
                <?= $totalRecords; ?> Records
            </span>
        </p>

    </div>

    <!-- CLIENT CARD -->

    <div class="inquiry-card">

        <!-- SEARCH + FILTER BUTTONS -->

       
<div class="followup-top-area solar-client-toolbar">

    <!-- LEFT SIDE SEARCH -->

    <form method="GET" class="inquiry-search-wrapper">

        <input
            type="text"
            name="search"
            id="txt_searchall"
            class="inquiry-search"
            placeholder="🔍 Search Solar Client..."
            value="<?= e($search); ?>"
        >

        <?php if ($type !== '') { ?>
            <input type="hidden" name="type" value="<?= e($type); ?>">
        <?php } ?>

    </form>

    <!-- RIGHT SIDE BUTTONS -->

    <div class="solar-client-filters">

        <a href="solar-view-gst-client.php?type=gst">ME GST</a>

        <a href="solar-view-client.php">All Clients</a>

        <a href="solar-view-client.php?type=me">ME Non-GST</a>

        <a href="solar-view-client.php?type=vendor">Vendor</a>

        <a href="solar-view-client.php?type=trader">Trader</a>

    </div>

</div>


        <!-- TABLE -->

        <div class="inquiry-table-wrapper" id="scrollable-content">

            <table class="inquiry-table" id="payments-table">

                <thead>
                    <tr>
                        <th>Sr.No.</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Service / Work</th>
                        <th>Total Amount</th>
                        <th>Added By</th>
                        <th>Vendor</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php

                $serial = 1;

                if ($query_run && mysqli_num_rows($query_run) > 0) {

                    while ($row = mysqli_fetch_assoc($query_run)) {

                ?>

                    <tr class="data-row">

                        <td class="inquiry-sr">
                            <?= $serial++; ?>
                        </td>

                        <td class="inquiry-name">
                            <?= e($row['cust_name']); ?>
                        </td>

                        <td class="inquiry-phone">
                            <?= e($row['cust_phone']); ?>
                        </td>

                        <td>
                            <?= e($row['cust_email']); ?>
                        </td>

                        <td>
                            <?= e($row['cust_address']); ?>
                        </td>

                        <td>
                            <?= e($row['service'] ?: 'N/A'); ?>
                        </td>

                        <td>
                            <?php
                            if ($row['final_amount'] !== null) {
                                echo '₹ ' . number_format(
                                    (float)$row['final_amount'],
                                    2
                                );
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>

                        <!-- ADDED BY -->

                        <td>

                            <?php if ($row['added_by'] === 'vendor') { ?>

                                <span class="badge bg-warning text-dark">
                                    Vendor
                                </span>

                            <?php } elseif ($row['added_by'] === 'trader') { ?>

                                <span class="badge bg-info text-dark">
                                    Trader
                                </span>

                            <?php } else { ?>

                                <span class="badge bg-primary">
                                    ME
                                </span>

                            <?php } ?>

                        </td>

                        <!-- VENDOR -->

                        <td>
                            <?= e($row['vendor_name'] ?: '-'); ?>
                        </td>

                        <!-- ACTION -->

                        <td class="inquiry-action-cell">

                            <button
                                type="button"
                                class="action-btn"
                                onclick="toggleActions(this)"
                            >
                                Action
                            </button>

                            <div
                                class="action-buttons"
                                style="display:none;"
                            >

                                <?php if ($row['added_by'] === 'trader') { ?>

                                    <button type="button" class="primary2">
                                        <a href="solar-view-trader-details.php?client_id=<?= (int)$row['id']; ?>">
                                            View Details
                                        </a>
                                    </button>

                                <?php } else { ?>

                                    <button type="button" class="primary2">
                                        <a href="solar-view-client-detail.php?client_id=<?= (int)$row['id']; ?>">
                                            View Details
                                        </a>
                                    </button>

                                <?php } ?>

                            </div>

                        </td>

                    </tr>

                <?php

                    }

                } else {

                    echo '<tr>
                        <td colspan="10" style="text-align:center;">
                            No Solar Clients Found
                        </td>
                    </tr>';

                }

                ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</section>
</section>

<!-- ACTION MENU SCRIPT -->

<script>

function toggleActions(button) {

    const currentCell = button.closest('.inquiry-action-cell');
    const currentMenu = currentCell.querySelector('.action-buttons');

    document.querySelectorAll('.action-buttons').forEach(function(menu) {

        if (menu !== currentMenu) {
            menu.style.display = 'none';

            const otherButton = menu.parentElement.querySelector('.action-btn');

            if (otherButton) {
                otherButton.innerText = 'Action';
            }
        }

    });

    if (currentMenu.style.display === 'flex') {

        currentMenu.style.display = 'none';
        button.innerText = 'Action';

    } else {

        currentMenu.style.display = 'flex';
        button.innerText = 'Actions';

    }
}

/* CLOSE MENU ON OUTSIDE CLICK */

document.addEventListener('click', function(event) {

    if (!event.target.closest('.inquiry-action-cell')) {

        document.querySelectorAll('.action-buttons').forEach(function(menu) {

            menu.style.display = 'none';

            const button = menu.parentElement.querySelector('.action-btn');

            if (button) {
                button.innerText = 'Action';
            }

        });

    }

});

/* CLOSE MENU ON ESCAPE */

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        document.querySelectorAll('.action-buttons').forEach(function(menu) {

            menu.style.display = 'none';

            const button = menu.parentElement.querySelector('.action-btn');

            if (button) {
                button.innerText = 'Action';
            }

        });

    }

});

</script>

<!-- SEARCH SCRIPT -->

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
