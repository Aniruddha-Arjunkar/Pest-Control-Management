
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

$sql = "SELECT * FROM tbl_solar_vendor WHERE 1=1";

if ($search !== '') {
    $sql .= " AND (
        vendor_name LIKE ?
        OR phone LIKE ?
        OR email LIKE ?
    )";
}

$sql .= " ORDER BY id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Vendor Query Error: " . mysqli_error($conn));
}

if ($search !== '') {
    $searchValue = "%" . $search . "%";
    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $searchValue,
        $searchValue,
        $searchValue
    );
}

mysqli_stmt_execute($stmt);

$query_run = mysqli_stmt_get_result($stmt);

/* =========================================================
   TOTAL VENDORS
========================================================= */

$totalRecords = mysqli_num_rows($query_run);

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

        <h1>☀️ Solar Vendors</h1>

        <p>
            View and manage all Solar Vendors

            <span class="inquiry-count">
                <?= $totalRecords; ?> Records
            </span>
        </p>

    </div>

    <!-- VENDOR CARD -->

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
                    placeholder="Search Solar Vendor..."
                    value="<?= e($search); ?>"
                >

            </form>

        </div>

        <!-- TABLE -->

        <div class="inquiry-table-wrapper" id="scrollable-content">

            <table class="inquiry-table" id="vendorTable">

                <thead>
                    <tr>
                        <th>Sr.No.</th>
                        <th>Vendor Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php

                $serial_no = 1;
                $total_balance = 0;

                if ($query_run && mysqli_num_rows($query_run) > 0) {

                    while ($row = mysqli_fetch_assoc($query_run)) {

                        $vendor_id = (int)$row['id'];
                        $vendor_balance = 0;

                        /* =====================================
                           FETCH VENDOR CLIENTS
                        ===================================== */

                        $clientStmt = mysqli_prepare($conn, "
                            SELECT *
                            FROM tbl_solar_client
                            WHERE vendor_id = ?
                            AND added_by = 'vendor'
                        ");

                        mysqli_stmt_bind_param(
                            $clientStmt,
                            "i",
                            $vendor_id
                        );

                        mysqli_stmt_execute($clientStmt);

                        $client_query = mysqli_stmt_get_result($clientStmt);

                        while ($client = mysqli_fetch_assoc($client_query)) {

                            $amount = (float)($client['total_amount'] ?? 0);
                            $gst_amount = (float)($client['gst_amount'] ?? 0);

                            /* =================================
                               GST CALCULATION
                            ================================= */

                            if ((int)$client['apply_gst'] === 1) {

                                if ($client['gst_type'] === 'inclusive') {
                                    $grand_total = $amount;
                                } else {
                                    $grand_total = $amount + $gst_amount;
                                }

                            } else {

                                $grand_total = $amount;
                            }

                            /* =================================
                               FETCH PAID AMOUNT
                            ================================= */

                            $paymentStmt = mysqli_prepare($conn, "
                                SELECT IFNULL(SUM(paid_amount), 0) AS paid
                                FROM tbl_solar_payment
                                WHERE client_id = ?
                            ");

                            mysqli_stmt_bind_param(
                                $paymentStmt,
                                "i",
                                $client['id']
                            );

                            mysqli_stmt_execute($paymentStmt);

                            $paymentResult = mysqli_stmt_get_result($paymentStmt);
                            $payment = mysqli_fetch_assoc($paymentResult);

                            $paid = (float)($payment['paid'] ?? 0);

                            mysqli_stmt_close($paymentStmt);

                            /* =================================
                               VENDOR BALANCE
                            ================================= */

                            $vendor_balance += ($grand_total - $paid);
                        }

                        mysqli_stmt_close($clientStmt);

                        $total_balance += $vendor_balance;

                ?>

                    <tr class="data-row">

                        <td class="inquiry-sr">
                            <?= $serial_no++; ?>
                        </td>

                        <td class="inquiry-name">
                            <?= e($row['vendor_name']); ?>
                        </td>

                        <td class="inquiry-phone">
                            <?= e($row['phone']); ?>
                        </td>

                        <td>
                            <?= e($row['email']); ?>
                        </td>

                        <td>
                            <?= e($row['address']); ?>
                        </td>

                        <td>
                            ₹ <?= number_format($vendor_balance, 2); ?>
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

                                <button
                                    type="button"
                                    class="primary1"
                                    onclick="window.location.href='solar-add-vendor-client.php?vendor_id=<?= $vendor_id; ?>'"
                                >
                                    Add Client
                                </button>

                                <button
    type="button"
    class="primary2"
    onclick="window.location.href='solar-view-vendor-client.php?id=<?= (int)$vendor_id; ?>'"
>
    <i class="fa fa-eye"></i> View Details
</button>

                            </div>

                        </td>

                    </tr>

                <?php

                    }

                } else {

                    echo '<tr>
                        <td colspan="7" style="text-align:center;">
                            No Solar Vendors Found
                        </td>
                    </tr>';

                }

                ?>

                </tbody>

                <!-- TOTAL BALANCE -->

                <tfoot>
                    <tr>
                        <th colspan="5" style="text-align:right;">
                            Total Balance
                        </th>

                        <th>
                            ₹ <?= number_format($total_balance, 2); ?>
                        </th>

                        <th></th>
                    </tr>
                </tfoot>

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
