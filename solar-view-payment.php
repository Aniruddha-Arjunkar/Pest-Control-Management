
<?php
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   SEARCH & DATE FILTER
========================= */
$search = trim($_GET['search'] ?? '');
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

/* =========================
   PAYMENT HISTORY QUERY
========================= */
$query = "
    SELECT
        c.cust_name,
        c.cust_phone,
        p.paid_amount,
        p.payment_date,
        p.payment_gateway,
        p.id AS payment_id,
        c.id AS client_id
    FROM tbl_solar_payment p
    INNER JOIN tbl_solar_client c ON c.id = p.client_id
    WHERE p.paid_amount > 0
";

$params = [];
$types = "";

/* Search Filter */
if ($search !== '') {
    $query .= " AND (
        c.cust_name LIKE ?
        OR c.cust_phone LIKE ?
        OR c.cust_email LIKE ?
    )";

    $search_value = "%{$search}%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sss";
}

/* Date Filter */
if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND p.payment_date BETWEEN ? AND ?";

    $params[] = $start_date;
    $params[] = $end_date;

    $types .= "ss";
}

$query .= " ORDER BY p.payment_date DESC, p.id DESC";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    die("Query Error: " . e(mysqli_error($conn)));
}

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);

$query_run = mysqli_stmt_get_result($stmt);

/* =========================
   TOTAL PAID AMOUNT
========================= */
$total_paid_amount = 0;

if ($query_run) {
    while ($row = mysqli_fetch_assoc($query_run)) {
        $total_paid_amount += (float)$row['paid_amount'];
    }

    mysqli_data_seek($query_run, 0);
}

include('header.php');
?>

<section id="main-content">
<section class="wrapper">

<div class="inquiry-page">

    <!-- PAGE HEADER -->
    <div class="inquiry-page-header">
        <div>
            <h2>Payment History</h2>
            <p>View all client payment records</p>
        </div>

        <div class="inquiry-count">
            Total Paid: ₹<?= number_format($total_paid_amount, 2); ?>
        </div>
    </div>

    <div class="inquiry-card">

        <!-- SEARCH + DATE FILTER -->
        <div class="followup-top-area">

            <form method="GET" class="inquiry-search-wrapper">

                <span class="inquiry-search-icon">
                    <i class="fa fa-search"></i>
                </span>

                <input
                    type="text"
                    name="search"
                    class="inquiry-search"
                    placeholder="Search Client Name, Phone, Email..."
                    value="<?= e($search); ?>"
                >

                <input
                    type="date"
                    name="start_date"
                    class="form-control"
                    value="<?= e($start_date); ?>"
                    style="max-width:160px;"
                >

                <input
                    type="date"
                    name="end_date"
                    class="form-control"
                    value="<?= e($end_date); ?>"
                    style="max-width:160px;"
                >

                <button type="submit" class="action-btn primary1">
                    Search
                </button>

                <a href="solar-payment-history.php" class="action-btn primary2">
                    Reset
                </a>

            </form>

        </div>

        <!-- PAYMENT TABLE -->
        <div class="inquiry-table-wrapper">

            <table class="inquiry-table">

                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Paid Amount</th>
                        <th>Payment Date</th>
                        <th>Payment Gateway</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $serial_no = 1;

                if ($query_run && mysqli_num_rows($query_run) > 0) {

                    while ($row = mysqli_fetch_assoc($query_run)) {
                ?>

                    <tr>
                        <td class="inquiry-sr">
                            <?= $serial_no++; ?>
                        </td>

                        <td class="inquiry-name">
                            <a href="solar-view-client-detail.php?client_id=<?= (int)$row['client_id']; ?>">
                                <?= e($row['cust_name']); ?>
                            </a>
                        </td>

                        <td class="inquiry-phone">
                            <?= e($row['cust_phone']); ?>
                        </td>

                        <td>
                            <strong>
                                ₹<?= number_format((float)$row['paid_amount'], 2); ?>
                            </strong>
                        </td>

                        <td>
                            <?= !empty($row['payment_date'])
                                ? date('d-m-Y', strtotime($row['payment_date']))
                                : '-'; ?>
                        </td>

                        <td>
                            <?= e($row['payment_gateway'] ?: '-'); ?>
                        </td>
                    </tr>

                <?php
                    }

                } else {
                ?>

                    <tr>
                        <td colspan="6" class="text-center">
                            No Payments Found
                        </td>
                    </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

        <!-- TOTAL -->
        <div class="mt-3 text-end">
            <h4>
                Total Paid Amount:
                <strong>₹<?= number_format($total_paid_amount, 2); ?></strong>
            </h4>
        </div>

    </div>

</div>

</section>
</section>

<?php
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>

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
