<?php

session_start();

// LOGIN CHECK

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {

    $_SESSION['msg'] = "You must log in first";

    header('location:login.php');

    exit();

}

// LOGOUT

if (isset($_GET['logout'])) {

    session_destroy();

    unset($_SESSION['email']);

    header('location:login.php');

    exit();

}

// DATABASE

require_once 'db_connect.php';

include 'header.php';


// =========================================================
// FILTER & SEARCH
// =========================================================

$type = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');


// =========================================================
// PAGE HEADING
// =========================================================

if ($type === 'trader') {
    $heading = 'View Trader Clients';
} elseif ($type === 'vendor') {
    $heading = 'View Vendor Clients';
} elseif ($type === 'me') {
    $heading = 'View ME Clients';
} else {
    $heading = 'View All Clients';
}


// =========================================================
// DATABASE QUERY
// =========================================================

$finalQuery = "
    SELECT
        c.*,
        v.vendor_name,
        (
            SELECT p.total_amount
            FROM tbl_payment p
            WHERE p.client_id = c.id
            ORDER BY p.id DESC
            LIMIT 1
        ) AS final_amount
    FROM tbl_client c
    LEFT JOIN tbl_vendor v ON c.vendor_id = v.id
    WHERE 1=1
";


// ME CLIENTS
if ($type === 'me') {

    $finalQuery .= "
        AND c.added_by = 'admin'
        AND c.apply_gst = 0
    ";

}

// VENDOR CLIENTS
elseif ($type === 'vendor') {

    $finalQuery .= "
        AND c.added_by = 'vendor'
        AND c.apply_gst = 0
    ";

}

// TRADER CLIENTS
elseif ($type === 'trader') {

    $finalQuery .= "
        AND c.added_by = 'trader'
    ";

}

// ALL NON GST CLIENTS
elseif ($type === '') {

    $finalQuery .= "
        AND c.apply_gst = 0
    ";

}


// SEARCH
if ($search !== '') {

    $safe_search = mysqli_real_escape_string(
        $conn,
        $search
    );

    $finalQuery .= "
        AND (
            c.cust_name LIKE '%$safe_search%'
            OR c.cust_phone LIKE '%$safe_search%'
            OR c.cust_email LIKE '%$safe_search%'
        )
    ";

}


$finalQuery .= " ORDER BY c.id DESC";


$query_run = mysqli_query($conn, $finalQuery);


if (!$query_run) {

    die("Query Error: " . mysqli_error($conn));

}


$total_records = mysqli_num_rows($query_run);

?>

<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<section id="main-content">

    <section class="wrapper">

        <div class="inquiry-page">

            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="inquiry-page-header">

                <h1>
                    👥 <?= htmlspecialchars($heading); ?>
                </h1>

                <p>

                    Showing all client records

                    <span class="inquiry-count">
                        <?= $total_records; ?> Records
                    </span>

                </p>

            </div>


            <!-- =================================================
                 CLIENT CARD
            ================================================== -->

            <div class="inquiry-card">


                <!-- =================================================
                     TOP AREA
                ================================================== -->

                <div class="followup-top-area">


                    <!-- SEARCH -->

                    <div class="inquiry-search-wrapper">

                        <span class="inquiry-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            id="txt_searchall"
                            class="inquiry-search"
                            placeholder="Search by name, phone or email..."
                            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                            autocomplete="off"
                        >

                    </div>


                    <!-- FILTER LINKS -->

                    <div class="followup-page-links">

                        <a
                            href="view-gst-client.php"
                            class="followup-link purple"
                        >
                            GST Clients
                        </a>

                        <a
                            href="view-client.php"
                            class="followup-link green"
                        >
                            All Clients
                        </a>

                        <a
                            href="view-client.php?type=me"
                            class="followup-link blue"
                        >
                            ME Clients
                        </a>

                        <a
                            href="view-client.php?type=vendor"
                            class="followup-link orange"
                        >
                            Vendor Clients
                        </a>

                        <a
                            href="view-client.php?type=trader"
                            class="followup-link purple"
                        >
                            Trader Clients
                        </a>

                    </div>

                </div>


                <!-- =================================================
                     TABLE WRAPPER
                ================================================== -->

                <div
                    class="inquiry-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="inquiry-table"
                        id="payments-table"
                    >

                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>SR. NO.</th>

                                <th>NAME</th>

                                <th>PHONE</th>

                                <th>EMAIL</th>

                                <th>ADDRESS</th>

                                <th>SERVICE / WORK</th>

                                <th>TOTAL AMOUNT</th>

                                <th>ADDED BY</th>

                                <th>VENDOR</th>

                                <th>ACTION</th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody>

                        <?php

                        $serial = 1;

                        if ($total_records > 0) {

                            while ($row = mysqli_fetch_assoc($query_run)) {

                                $id = (int)$row['id'];

                                $name = htmlspecialchars(
                                    $row['cust_name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $phone = htmlspecialchars(
                                    $row['cust_phone'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $email = htmlspecialchars(
                                    $row['cust_email'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $address = htmlspecialchars(
                                    $row['cust_address'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $service = htmlspecialchars(
                                    $row['service'] ?? 'N/A',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $amount = htmlspecialchars(
                                    (string)($row['final_amount'] ?? 'N/A'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $vendor = htmlspecialchars(
                                    $row['vendor_name'] ?? '-',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $added_by = $row['added_by'] ?? '';

                        ?>

                            <tr class="data-row">

                                <!-- SERIAL -->

                                <td class="inquiry-sr">
                                    <?= $serial++; ?>
                                </td>


                                <!-- NAME -->

                                <td class="inquiry-name">
                                    <?= $name; ?>
                                </td>


                                <!-- PHONE -->

                                <td class="inquiry-phone">
                                    <?= $phone; ?>
                                </td>


                                <!-- EMAIL -->

                                <td>
                                    <?= $email; ?>
                                </td>


                                <!-- ADDRESS -->

                                <td class="inquiry-message">
                                    <?= $address; ?>
                                </td>


                                <!-- SERVICE -->

                                <td class="inquiry-service">
                                    <?= $service; ?>
                                </td>


                                <!-- AMOUNT -->

                                <td class="inquiry-date">
                                    <?= $amount; ?>
                                </td>


                                <!-- ADDED BY -->

                                <td>

                                    <?php if ($added_by === 'vendor') { ?>

                                        <span class="badge bg-warning text-dark">
                                            Vendor
                                        </span>

                                    <?php } elseif ($added_by === 'trader') { ?>

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
                                    <?= $vendor; ?>
                                </td>


                                <!-- ACTION -->

                                <td class="inquiry-action-cell">

                                    <a
                                        href="view-client-detail.php?client_id=<?= $id; ?>"
                                        class="followup-link green"
                                    >
                                        View Details
                                    </a>

                                </td>

                            </tr>

                        <?php

                            }

                        } else {

                        ?>

                            <tr class="no-data-row">

                                <td colspan="10" class="no-inquiry">

                                    No Clients Found.

                                </td>

                            </tr>

                        <?php

                        }

                        ?>


                        <!-- SEARCH NO RESULT -->

                        <tr
                            id="search-no-result"
                            style="display:none;"
                        >

                            <td colspan="10" class="no-inquiry">

                                No matching records found.

                            </td>

                        </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </section>

</section>


<!-- =====================================================
     LIVE TABLE SEARCH
===================================================== -->

<script>

document.addEventListener('DOMContentLoaded', function() {

    const searchInput = document.getElementById('txt_searchall');

    const table = document.getElementById('payments-table');

    const noResult = document.getElementById('search-no-result');

    if (!searchInput || !table || !noResult) {
        return;
    }

    searchInput.addEventListener('input', function() {

        const searchValue = this.value.toLowerCase().trim();

        let visibleRows = 0;

        const rows = table.querySelectorAll('tbody tr.data-row');

        rows.forEach(function(row) {

            const rowText = row.textContent.toLowerCase();

            if (
                searchValue === '' ||
                rowText.includes(searchValue)
            ) {

                row.style.display = '';

                visibleRows++;

            } else {

                row.style.display = 'none';

            }

        });

        noResult.style.display =
            searchValue !== '' && visibleRows === 0
                ? 'table-row'
                : 'none';

    });

});

</script>


<!-- =====================================================
     EXISTING JS FILES
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