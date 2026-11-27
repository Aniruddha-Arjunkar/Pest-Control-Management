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

$sql = "
    SELECT c.*
    FROM tbl_solar_client c
    WHERE c.added_by = 'trader'
";

if ($search !== '') {
    $sql .= "
        AND (
            c.cust_name LIKE ?
            OR c.cust_phone LIKE ?
            OR c.cust_email LIKE ?
        )
    ";
}

$sql .= " ORDER BY c.id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Trader Query Error: " . mysqli_error($conn));
}

if ($search !== '') {

    $searchTerm = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $searchTerm,
        $searchTerm,
        $searchTerm
    );
}

mysqli_stmt_execute($stmt);

$query_run = mysqli_stmt_get_result($stmt);

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

        <h1>☀️ Solar Traders</h1>

        <p>
            View and manage all Solar Traders

            <span class="inquiry-count">
                <?= $totalRecords; ?> Records
            </span>
        </p>

    </div>

    <!-- TRADER CARD -->

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
                    placeholder="Search Solar Trader..."
                    value="<?= e($search); ?>"
                >

            </form>

        </div>

        <!-- TABLE -->

        <div class="inquiry-table-wrapper" id="scrollable-content">

            <table class="inquiry-table" id="traderTable">

                <thead>
                    <tr>
                        <th>Sr.No.</th>
                        <th>Trader Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php

                $serial_no = 1;

                if ($query_run && mysqli_num_rows($query_run) > 0) {

                    while ($row = mysqli_fetch_assoc($query_run)) {

                ?>

                    <tr class="data-row">

                        <td class="inquiry-sr">
                            <?= $serial_no++; ?>
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

                                <button
                                    type="button"
                                    class="primary2"
                                    onclick="window.location.href='solar-view-trader-details.php?client_id=<?= (int)$row['id']; ?>'"
                                >
                                    View Details
                                </button>

                            </div>

                        </td>

                    </tr>

                <?php

                    }

                } else {

                    echo '<tr>
                        <td colspan="7" style="text-align:center;">
                            No Solar Traders Found
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
