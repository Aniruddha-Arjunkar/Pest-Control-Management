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
   SEARCH
========================================================= */

$search = trim($_GET['search'] ?? '');

$where = " WHERE c.apply_gst = 1 ";

$params = [];
$paramTypes = "";

/* =========================================================
   SEARCH CONDITION
========================================================= */

if ($search !== '') {

    $where .= " AND (
        c.cust_name LIKE ?
        OR c.cust_phone LIKE ?
        OR c.cust_email LIKE ?
    ) ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $paramTypes .= "sss";
}

/* =========================================================
   COUNT GST CLIENTS
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
   FETCH SOLAR GST CLIENTS
========================================================= */

$sql = "
    SELECT
        c.*,

        (
            SELECT p.total_amount
            FROM tbl_solar_payment p
            WHERE p.client_id = c.id
            ORDER BY p.id DESC
            LIMIT 1
        ) AS final_amount

    FROM tbl_solar_client c

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

<section id="main-content">
<section class="wrapper">

<div class="inquiry-page">

    <!-- PAGE HEADER -->

    <div class="inquiry-page-header">

        <h1>☀️ Solar GST Clients</h1>

        <p>
            View and manage all Solar GST clients

            <span class="inquiry-count">
                <?= $totalRecords; ?> Records
            </span>
        </p>

    </div>

    <!-- CLIENT CARD -->

    <div class="inquiry-card">

        <!-- SEARCH -->

        <div class="followup-top-area">

            <form method="GET" class="inquiry-search-wrapper">

                <span class="inquiry-search-icon">🔍</span>

                <input
                    type="text"
                    name="search"
                    id="txt_searchall"
                    class="inquiry-search"
                    placeholder="Search Solar GST Client..."
                    value="<?= e($search); ?>"
                >

            </form>

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
                        <th>Service</th>
                        <th>Total Amount</th>
                        <th>Service Date</th>
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

                        <td>
                            <?= e($row['service_date']); ?>
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

                                <button type="button" class="primary2">
                                    <a href="solar-view-client-detail.php?client_id=<?= (int)$row['id']; ?>">
                                        View Details
                                    </a>
                                </button>

                                <button type="button" class="primary1">
                                    <a href="solar-add-invoice.php?client_id=<?= (int)$row['id']; ?>">
                                        <i class="bi bi-printer"></i>
                                        Generate Invoice
                                    </a>
                                </button>

                            </div>

                        </td>

                    </tr>

                <?php

                    }

                } else {

                    echo '<tr>
                        <td colspan="9" style="text-align:center;">
                            No Solar GST Clients Found
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

<!-- PAGE SCRIPTS -->

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
