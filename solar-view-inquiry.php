
<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';


// =========================================================
// SEARCH
// =========================================================

$search = trim($_GET['search'] ?? '');


// =========================================================
// TOTAL INQUIRY COUNT
// =========================================================

$count_sql = "
    SELECT COUNT(*) AS total
    FROM tbl_solar_inquiry
    WHERE re_followup_date IS NULL
    AND visit_date IS NULL
";

$count_result = mysqli_query($conn, $count_sql);

$total_inquiries = 0;

if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_inquiries = (int)$count_row['total'];
}


// =========================================================
// GET INQUIRIES
// =========================================================

if ($search !== '') {

    $sql = "
        SELECT *
        FROM tbl_solar_inquiry
        WHERE
        (
            name LIKE ?
            OR phone LIKE ?
            OR email LIKE ?
            OR address LIKE ?
            OR message LIKE ?
        )
        AND re_followup_date IS NULL
        AND visit_date IS NULL
        ORDER BY id DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);

    $search_value = "%{$search}%";

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

} else {

    $sql = "
        SELECT *
        FROM tbl_solar_inquiry
        WHERE re_followup_date IS NULL
        AND visit_date IS NULL
        ORDER BY id DESC
    ";

    $result = mysqli_query($conn, $sql);
}

?>

<section id="main-content">

<section class="wrapper">

<div class="inquiry-page">

    <!-- PAGE HEADER -->

    <div class="inquiry-page-header">

        <h1>💬 View Solar Inquiries</h1>

        <p>
            Showing all customer inquiries

            <span class="inquiry-count">
                <?= $total_inquiries; ?> Records
            </span>
        </p>

    </div>


    <!-- INQUIRY CARD -->

    <div class="inquiry-card">


        <!-- SEARCH -->

        <form method="GET" action="">

            <div class="inquiry-search-wrapper">

                <span class="inquiry-search-icon">
                    🔍
                </span>

                <input
                    type="text"
                    name="search"
                    class="inquiry-search"
                    placeholder="Search by name, phone, email or message..."
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                    autocomplete="off"
                >

            </div>

        </form>


        <!-- TABLE -->

        <div
            class="inquiry-table-wrapper refollowup-table-wrapper"
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

                ?>

                    <tr>

                        <!-- SERIAL NUMBER -->

                        <td class="inquiry-sr">
                            <?= $sr_no++; ?>
                        </td>


                        <!-- NAME -->

                        <td class="inquiry-name">
                            <?= htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </td>


                        <!-- PHONE -->

                        <td class="inquiry-phone">
                            <?= htmlspecialchars($row['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </td>


                        <!-- EMAIL -->

                        <td>
                            <?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </td>


                        <!-- ADDRESS -->

                        <td>
                            <?= htmlspecialchars($row['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </td>


                        <!-- MESSAGE -->

                        <td class="inquiry-message">
                            <?= htmlspecialchars($row['message'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </td>


                        <!-- DATE -->

                        <td class="inquiry-date">
                            <?= htmlspecialchars($row['inq_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
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

                                <a
                                    href="update-inquiry.php?id=<?= $id; ?>&type=refollowup"
                                    class="primary1"
                                >
                                    Re-Followup
                                </a>


                                <a
                                    href="update-inquiry.php?id=<?= $id; ?>&type=visit"
                                    class="primary2"
                                >
                                    Visit
                                </a>


                                <a
                                    href="update-inquiry.php?id=<?= $id; ?>&type=service"
                                    class="primary1"
                                >
                                    Service
                                </a>


                                <a
                                    href="update-inquiry.php?id=<?= $id; ?>&type=schedule"
                                    class="primary2"
                                >
                                    Schedule
                                </a>


                                <!-- QUOTATION -->

                                <div class="quotation-dropdown">

                                    <button
                                        type="button"
                                        class="quotation-btn"
                                        onclick="toggleQuotation(this)"
                                    >
                                        📄 Send Quotation ▼
                                    </button>

                                    <div
                                        class="quotation-menu"
                                        style="display:none;"
                                    >

                                        <a
                                            href="add_quotation.php?inquiry_id=<?= $id; ?>"
                                            class="add-option"
                                        >
                                            Add Quotation
                                        </a>

                                        <a
                                            href="fabrication_quotation.php?inquiry_id=<?= $id; ?>"
                                            class="fabrication-option"
                                        >
                                            Fabrication
                                        </a>

                                    </div>

                                </div>


                                <!-- DELETE -->

                                <a
                                    href="delete-inquiry.php?id=<?= $id; ?>"
                                    class="primary3"
                                    onclick="return confirm('Are you sure you want to delete this inquiry?');"
                                >
                                    Delete
                                </a>


                                <!-- VIEW QUOTATION -->

                                <?php

                                $qStmt = mysqli_prepare(
                                    $conn,
                                    "SELECT id FROM tbl_quotation WHERE inquiry_id = ? LIMIT 1"
                                );

                                mysqli_stmt_bind_param($qStmt, "i", $id);

                                mysqli_stmt_execute($qStmt);

                                $qRes = mysqli_stmt_get_result($qStmt);

                                if ($qRes && mysqli_num_rows($qRes) > 0) {

                                    $q = mysqli_fetch_assoc($qRes);

                                ?>

                                    <a
                                        href="generate_quotation.php?id=<?= (int)$q['id']; ?>"
                                        target="_blank"
                                        class="primary2"
                                    >
                                        View Quotation
                                    </a>

                                <?php

                                } else {

                                    echo '<span class="quotation-not-added">Quotation Not Added</span>';

                                }

                                ?>

                            </div>

                        </td>

                    </tr>

                <?php

                    }

                } else {

                ?>

                    <tr>

                        <td colspan="8" class="no-inquiry">
                            No inquiries found.
                        </td>

                    </tr>

                <?php

                }

                ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</section>

</section>


<!-- BOTTOM SCROLLBAR -->

<div class="scroll-sync-wrapper">

    <div class="dummy-scrollbar"></div>

</div>


<!-- JAVASCRIPT -->

<script>

/* ACTION DROPDOWN */

function toggleActions(button) {

    const container = button.nextElementSibling;

    document.querySelectorAll('.action-buttons').forEach(function(menu) {

        if (menu !== container) {
            menu.style.display = 'none';
        }

    });

    container.style.display =
        container.style.display === 'none' ? 'flex' : 'none';

    button.textContent =
        container.style.display === 'flex' ? 'Actions' : 'Action';
}


/* QUOTATION DROPDOWN */

function toggleQuotation(button) {

    const menu = button.nextElementSibling;

    menu.style.display =
        menu.style.display === 'none' ? 'block' : 'none';

}


/* CLOSE ACTION MENU */

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


/* ESCAPE KEY */

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


/* SYNCHRONIZED SCROLLBAR */

const dummyScroll = document.querySelector('.scroll-sync-wrapper');

const realScroll = document.querySelector('#scrollable-content');

if (dummyScroll && realScroll) {

    const dummyBar = dummyScroll.querySelector('.dummy-scrollbar');

    function updateScrollWidth() {
        dummyBar.style.width = realScroll.scrollWidth + 'px';
    }

    updateScrollWidth();

    dummyScroll.addEventListener('scroll', function() {
        realScroll.scrollLeft = dummyScroll.scrollLeft;
    });

    realScroll.addEventListener('scroll', function() {
        dummyScroll.scrollLeft = realScroll.scrollLeft;
    });

    window.addEventListener('resize', updateScrollWidth);

}

</script>


<!-- EXISTING JS -->

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
if (isset($stmt) && $search !== '') {
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>
