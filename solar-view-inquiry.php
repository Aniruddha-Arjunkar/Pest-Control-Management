<?php

session_start();


// =========================================================
// LOGIN CHECK
// =========================================================

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {

    $_SESSION['msg'] = "You must log in first";

    header('location:login.php');
    exit();
}


// =========================================================
// LOGOUT
// =========================================================

if (isset($_GET['logout'])) {

    session_destroy();

    header('location:login.php');
    exit();
}


// =========================================================
// DATABASE & HEADER
// =========================================================

require_once 'db_connect.php';

include 'header.php';


// =========================================================
// SEARCH LOGIC
// =========================================================

$search = trim($_GET['search'] ?? '');

$where = "WHERE re_followup_date IS NULL
          AND visit_date IS NULL";

$params = [];
$types = '';

if ($search !== '') {

    $where .= " AND (
        name LIKE ?
        OR phone LIKE ?
        OR email LIKE ?
        OR address LIKE ?
        OR message LIKE ?
    )";

    $searchValue = "%{$search}%";

    $params = [
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    ];

    $types = "sssss";
}


// =========================================================
// TOTAL RECORDS
// =========================================================

$count_sql = "
    SELECT COUNT(*) AS total
    FROM tbl_solar_inquiry
    $where
";

$count_stmt = mysqli_prepare($conn, $count_sql);

if ($types !== '') {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}

mysqli_stmt_execute($count_stmt);

$count_result = mysqli_stmt_get_result($count_stmt);

$count_row = mysqli_fetch_assoc($count_result);

$total_records = (int)($count_row['total'] ?? 0);


// =========================================================
// GET INQUIRIES
// =========================================================

$sql = "
    SELECT
        id,
        name,
        phone,
        email,
        address,
        message,
        inq_date
    FROM tbl_solar_inquiry
    $where
    ORDER BY id DESC
";

$stmt = mysqli_prepare($conn, $sql);

if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


// =========================================================
// ESCAPE FUNCTION
// =========================================================

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<section id="main-content">

    <section class="wrapper">

        <div class="inquiry-page">

            <!-- PAGE HEADER -->

            <div class="inquiry-page-header">

                <h1>📋 View Inquiries</h1>

                <p>
                    Showing all new inquiry records

                    <span class="inquiry-count">
                        <?= $total_records; ?> Records
                    </span>
                </p>

            </div>


            <!-- MAIN CARD -->

            <div class="inquiry-card">

                <!-- TOP AREA -->

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

                  
                </div>


                <!-- TABLE -->

                <div
                    class="inquiry-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="inquiry-table"
                        id="payments-table"
                    >

                        <thead>

                            <tr>

                                <th>SR. NO.</th>

                                <th>NAME</th>

                                <th>PHONE</th>

                                <th>EMAIL</th>

                                <th>ADDRESS</th>

                                <th>MESSAGE</th>

                                <th>INQUIRY DATE</th>

                                <th>ACTION</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        $sr_no = 1;

                        if ($result && mysqli_num_rows($result) > 0) {

                            while ($row = mysqli_fetch_assoc($result)) {

                                $id = (int)$row['id'];

                                // Check quotation

                                $qStmt = mysqli_prepare(
                                    $conn,
                                    "SELECT id FROM tbl_solar_quotations
                                     WHERE inquiry_id = ?
                                     LIMIT 1"
                                );

                                mysqli_stmt_bind_param(
                                    $qStmt,
                                    "i",
                                    $id
                                );

                                mysqli_stmt_execute($qStmt);

                                $qRes = mysqli_stmt_get_result($qStmt);

                                $quotation = mysqli_fetch_assoc($qRes);

                        ?>

                            <tr class="data-row">

                                <td class="inquiry-sr">
                                    <?= $sr_no++; ?>
                                </td>

                                <td class="inquiry-name">
                                    <?= e($row['name']); ?>
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

                                <td class="inquiry-message">
                                    <?= e($row['message']); ?>
                                </td>

                                <td class="inquiry-date">
                                    <?= e($row['inq_date']); ?>
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
                                        >
                                        <a
                                            href="solar-update-inquiry.php?id=<?= $id; ?>&type=refollowup"
                                        >
                                            Re-Followup
                                        </a>
                                    </button>


                                         <button
                                            type="button"
                                            class="primary2"
                                        >

                                        <a
                                            href="solar-update-inquiry.php?id=<?= $id; ?>&type=visit"
                                            
                                        >
                                            Visit
                                        </a>
                                    </button>

                                     <button
                                            type="button"
                                            class="primary5"
                                        >

                                        <a
                                            href="solar-update-inquiry.php?id=<?= $id; ?>&type=service"
                                        >
                                            Service
                                        </a>
                                    </button>

                                        
                                         <button
                                            type="button"
                                            class="primary4"
                                        >
                                        <a
                                            href="solar-update-inquiry.php?id=<?= $id; ?>&type=schedule"
                                        >
                                            Schedule
                                        </a>
                                    </button>


                                        <!-- SEND QUOTATION -->

                                        <a
                                            href="add_quotation.php?inquiry_id=<?= $id; ?>"
                                            class="primary6"
                                        >
                                            Add Quotation
                                        </a>


                                        <!-- FABRICATION -->

                                        <a
                                            href="fabrication_quotation.php?inquiry_id=<?= $id; ?>"
                                            class="primary6"
                                        >
                                            Fabrication
                                        </a>


                                        <!-- VIEW QUOTATION -->

                                        <?php if ($quotation) { ?>

                                            <a
                                                href="generate_quotation.php?id=<?= (int)$quotation['id']; ?>"
                                                target="_blank"
                                                class="primary2"
                                            >
                                                View Quotation
                                            </a>

                                        <?php } else { ?>

                                            <span class="quotation-not-added">
                                                Quotation Not Added
                                            </span>

                                        <?php } ?>


                                        <!-- DELETE -->

                                        <a
                                            href="delete-inquiry.php?id=<?= $id; ?>"
                                            class="primary3"
                                            onclick="return confirm('Are you sure you want to delete this inquiry?');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php

                            }

                        } else {

                        ?>

                            <tr class="no-data-row">

                                <td colspan="8" class="no-inquiry">
                                    No Inquiry Found.
                                </td>

                            </tr>

                        <?php } ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </section>

</section>


<!-- =====================================================
     ACTION MENU JAVASCRIPT
===================================================== -->

<script>

function toggleActions(button) {

    const container = button.nextElementSibling;

    document.querySelectorAll('.action-buttons').forEach(function(menu) {

        if (menu !== container) {
            menu.style.display = 'none';
        }

    });

    document.querySelectorAll('.action-btn').forEach(function(btn) {

        if (btn !== button) {
            btn.textContent = 'Action';
        }

    });

    if (
        container.style.display === 'none' ||
        container.style.display === ''
    ) {

        container.style.display = 'flex';
        button.textContent = 'Actions';

    } else {

        container.style.display = 'none';
        button.textContent = 'Action';

    }

}


// CLOSE MENU ON OUTSIDE CLICK

document.addEventListener('click', function(event) {

    if (!event.target.closest('.inquiry-action-cell')) {

        document.querySelectorAll('.action-buttons').forEach(function(menu) {
            menu.style.display = 'none';
        });

        document.querySelectorAll('.action-btn').forEach(function(button) {
            button.textContent = 'Action';
        });

    }

});


// ESC KEY

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        document.querySelectorAll('.action-buttons').forEach(function(menu) {
            menu.style.display = 'none';
        });

        document.querySelectorAll('.action-btn').forEach(function(button) {
            button.textContent = 'Action';
        });

    }

});

</script>


<!-- EXISTING JAVASCRIPT FILES -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>

<script src="js/search.js"></script>

</body>
</html>
