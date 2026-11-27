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
   FILTER VALUES
========================================================= */

$search = trim($_GET['search'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

/* =========================================================
   NORMAL CLIENT QUERY
========================================================= */

$normalQuery = "
    SELECT
        c.id AS client_id,
        v.vendor_name,
        c.cust_name,
        c.cust_phone,

        MAX(p.total_amount) AS total_amount,

        IFNULL(SUM(p.paid_amount), 0) AS total_paid,

        (
            MAX(p.total_amount) -
            IFNULL(SUM(p.paid_amount), 0)
        ) AS due_amount,

        MAX(p.due_date) AS due_date,

        NULL AS group_id,
        'normal' AS payment_type

    FROM tbl_solar_client c

    LEFT JOIN tbl_solar_vendor v
        ON c.vendor_id = v.id

    LEFT JOIN tbl_solar_payment p
        ON c.id = p.client_id

    WHERE LOWER(TRIM(c.added_by)) != 'trader'
";

$params = [];
$paramTypes = "";

/* NORMAL DATE FILTER */

if ($start_date !== '') {
    $normalQuery .= " AND p.payment_date >= ? ";
    $params[] = $start_date;
    $paramTypes .= "s";
}

if ($end_date !== '') {
    $normalQuery .= " AND p.payment_date <= ? ";
    $params[] = $end_date;
    $paramTypes .= "s";
}

/* NORMAL SEARCH */

if ($search !== '') {

    $normalQuery .= "
        AND (
            c.cust_name LIKE ?
            OR c.cust_phone LIKE ?
            OR c.cust_email LIKE ?
        )
    ";

    $searchTerm = "%" . $search . "%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;

    $paramTypes .= "sss";
}

$normalQuery .= "
    GROUP BY
        c.id,
        v.vendor_name,
        c.cust_name,
        c.cust_phone

    HAVING due_amount > 0
";

/* =========================================================
   TRADER QUERY
========================================================= */

$traderQuery = "
    SELECT
        c.id AS client_id,
        v.vendor_name,
        c.cust_name,
        c.cust_phone,

        MAX(main_payment.total_amount) AS total_amount,

        COALESCE(SUM(history.paid_amount), 0) AS total_paid,

        (
            MAX(main_payment.total_amount) -
            COALESCE(SUM(history.paid_amount), 0)
        ) AS due_amount,

        MAX(main_payment.due_date) AS due_date,

        main_payment.group_id AS group_id,
        'trader' AS payment_type

    FROM tbl_solar_client c

    LEFT JOIN tbl_solar_vendor v
        ON c.vendor_id = v.id

    INNER JOIN tbl_solar_payment main_payment
        ON main_payment.client_id = c.id

    LEFT JOIN tbl_solar_payment history
        ON history.client_id = main_payment.client_id
        AND history.group_id = main_payment.group_id
        AND history.payment_date IS NOT NULL

    WHERE LOWER(TRIM(c.added_by)) = 'trader'

    AND main_payment.group_id IS NOT NULL

    AND main_payment.payment_date IS NULL
";

/* TRADER DATE FILTER */

if ($start_date !== '') {
    $traderQuery .= " AND main_payment.due_date >= ? ";
    $params[] = $start_date;
    $paramTypes .= "s";
}

if ($end_date !== '') {
    $traderQuery .= " AND main_payment.due_date <= ? ";
    $params[] = $end_date;
    $paramTypes .= "s";
}

/* TRADER SEARCH */

if ($search !== '') {

    $traderQuery .= "
        AND (
            c.cust_name LIKE ?
            OR c.cust_phone LIKE ?
            OR c.cust_email LIKE ?
        )
    ";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;

    $paramTypes .= "sss";
}

$traderQuery .= "
    GROUP BY
        c.id,
        v.vendor_name,
        c.cust_name,
        c.cust_phone,
        main_payment.group_id

    HAVING due_amount > 0
";

/* =========================================================
   UNION BOTH QUERIES
========================================================= */

$sql = "
    $normalQuery

    UNION ALL

    $traderQuery

    ORDER BY due_date DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Due Payment Query Error: " . mysqli_error($conn));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
}

mysqli_stmt_execute($stmt);

$query_run = mysqli_stmt_get_result($stmt);

/* =========================================================
   TOTAL DUE CALCULATION
========================================================= */

$total_due_amount = 0;
$totalRecords = mysqli_num_rows($query_run);

$dueRows = [];

if ($query_run) {
    while ($row = mysqli_fetch_assoc($query_run)) {
        $dueRows[] = $row;
        $total_due_amount += (float)$row['due_amount'];
    }
}

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

        <h1>☀️ Solar Due Payments</h1>

        <p>
            View and manage all pending Solar payments

            <span class="inquiry-count">
                <?= $totalRecords; ?> Records
            </span>
        </p>

    </div>

    <!-- DUE PAYMENT CARD -->

    <div class="inquiry-card">

        <!-- SEARCH AND DATE FILTER -->

        <div class="followup-top-area">

            <form method="GET" class="inquiry-search-wrapper">

                <span class="inquiry-search-icon">🔍</span>

                <input
                    type="text"
                    name="search"
                    id="txt_searchall"
                    class="inquiry-search"
                    placeholder="Search Client Name, Phone, Email..."
                    value="<?= e($search); ?>"
                >

                <input
                    type="date"
                    name="start_date"
                    value="<?= e($start_date); ?>"
                    class="form-control"
                    style="max-width:170px;"
                >

                <input
                    type="date"
                    name="end_date"
                    value="<?= e($end_date); ?>"
                    class="form-control"
                    style="max-width:170px;"
                >

                <button type="submit" class="action-btn">
                    Search
                </button>

                <a
                    href="view-due-payments.php"
                    class="action-btn"
                    style="text-decoration:none;"
                >
                    Reset
                </a>

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
                        <th>Total Paid</th>
                        <th>Due Amount</th>
                        <th>Due Date</th>
                        <th>Payment Type</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php

                $serial = 1;

                if (!empty($dueRows)) {

                    foreach ($dueRows as $row) {

                        $client_id = (int)$row['client_id'];
                        $group_id = (int)($row['group_id'] ?? 0);

                ?>

                    <tr class="data-row">

                        <td class="inquiry-sr">
                            <?= $serial++; ?>
                        </td>

                        <td class="inquiry-name">

                            <?php if (!empty($row['vendor_name'])) { ?>

                                <span class="badge bg-warning text-dark">
                                    <?= e($row['vendor_name']); ?>
                                </span>

                            <?php } ?>

                            <a href="solar-view-client-detail.php?client_id=<?= $client_id; ?>">
                                <?= e($row['cust_name']); ?>
                            </a>

                        </td>

                        <td class="inquiry-phone">
                            <?= e($row['cust_phone']); ?>
                        </td>

                        <td>
                            ₹ <?= number_format((float)$row['total_paid'], 2); ?>
                        </td>

                        <td>
                            <strong>
                                ₹ <?= number_format((float)$row['due_amount'], 2); ?>
                            </strong>
                        </td>

                        <td>
                            <?= e($row['due_date']); ?>
                        </td>

                        <td>
                            <?= $row['payment_type'] === 'trader' ? 'Trader' : 'Normal'; ?>
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

                                <?php if ($row['payment_type'] === 'trader') { ?>

                                    <button
                                        type="button"
                                        class="primary1"
                                        onclick="window.location.href='update-payment.php?id=<?= $client_id; ?>&group_id=<?= $group_id; ?>'"
                                    >
                                        Update Payment
                                    </button>

                                <?php } else { ?>

                                    <button
                                        type="button"
                                        class="primary1"
                                        onclick="window.location.href='solar-update-payment.php?id=<?= $client_id; ?>'"
                                    >
                                        Update Payment
                                    </button>

                                <?php } ?>

                            </div>

                        </td>

                    </tr>

                <?php

                    }

                } else {

                    echo '<tr>
                        <td colspan="8" style="text-align:center;">
                            No Due Payments Found
                        </td>
                    </tr>';

                }

                ?>

                </tbody>

                <!-- TOTAL DUE -->

                <tfoot>

                    <tr>

                        <th colspan="4" style="text-align:right;">
                            Total Due Amount
                        </th>

                        <th>
                            ₹ <?= number_format($total_due_amount, 2); ?>
                        </th>

                        <th colspan="3"></th>

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
