<?php
session_start();

/* LOGIN CHECK */
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

/* LOGOUT */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
include 'header.php';

/* HTML ESCAPE */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* SEARCH */
$search = trim($_GET['search'] ?? '');

$where = "WHERE service_date IS NOT NULL
          AND schedule_date IS NULL";

if ($search !== '') {

    $sql = "SELECT id, name, phone, email, address, message, service_date
            FROM tbl_solar_inquiry
            $where
            AND (
                name LIKE ? OR phone LIKE ? OR email LIKE ?
                OR address LIKE ? OR message LIKE ?
            )
            ORDER BY id DESC";

    $stmt = $conn->prepare($sql);

    $like = "%".$search."%";
    $stmt->bind_param("sssss", $like, $like, $like, $like, $like);

} else {

    $sql = "SELECT id, name, phone, email, address, message, service_date
            FROM tbl_solar_inquiry
            $where
            ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

/* TOTAL COUNT */
$count_result = $conn->query(
    "SELECT COUNT(*) AS total FROM tbl_solar_inquiry $where"
);

$total_count = $count_result->fetch_assoc()['total'] ?? 0;
?>

<section id="main-content">
<section class="wrapper">

<div class="inquiry-page">

    <!-- PAGE HEADER -->
    <div class="inquiry-page-header">

        <div>
            <h1>☀️ Solar Future Services</h1>

            <p>
                Manage your Solar service inquiries
                <span class="inquiry-count">
                    <?= e($total_count) ?> Records
                </span>
            </p>
        </div>

    </div>


    <!-- MAIN CARD -->
    <div class="inquiry-card">

        <!-- SEARCH AREA -->
        <div class="followup-top-area">

            <form method="GET" class="inquiry-search-wrapper">

                <span class="inquiry-search-icon">🔍</span>

                <input
                    type="text"
                    name="search"
                    id="txt_searchall"
                    class="inquiry-search"
                    placeholder="Search name, phone, email..."
                    value="<?= e($search) ?>"
                >

            </form>

        </div>


        <!-- TABLE -->
        <div class="inquiry-table-wrapper" id="scrollable-content">

            <table class="inquiry-table" id="payments-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Service Message</th>
                        <th>Service Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                if ($result && $result->num_rows > 0) {

                    $i = 1;

                    while ($row = $result->fetch_assoc()) {

                        $id = (int)$row['id'];
                ?>

                    <tr class="data-row">

                        <td class="inquiry-sr">
                            <?= $i++ ?>
                        </td>

                        <td class="inquiry-name">
                            <?= e($row['name']) ?>
                        </td>

                        <td class="inquiry-phone">
                            <?= e($row['phone']) ?>
                        </td>

                        <td>
                            <?= e($row['email']) ?>
                        </td>

                        <td>
                            <?= e($row['address']) ?>
                        </td>

                        <td class="inquiry-message">
                            <?= e($row['message']) ?>
                        </td>

                        <td class="inquiry-date">
                            <?= !empty($row['service_date'])
                                ? date('d-m-Y', strtotime($row['service_date']))
                                : '-' ?>
                        </td>


                        <!-- ACTION MENU -->
                        <td class="inquiry-action-cell">

                            <button
                                type="button"
                                class="action-btn"
                                onclick="toggleActions(this)">
                                Action
                            </button>

                            <div class="action-buttons" style="display:none;">

                                <!-- 1. Edit Service -->
                                <button type="button" class="primary5">
                                    <a href="solar-update-inquiry.php?id=<?= $id; ?>&type=service">
                                        Edit Service
                                    </a>
                                </button>

                                <!-- 2. Schedule -->
                                <button type="button" class="primary4">
                                    <a href="solar-update-inquiry.php?id=<?= $id; ?>&type=schedule">
                                        Schedule
                                    </a>
                                </button>

                                <!-- 3. Move to Visit -->
                                <button type="button" class="primary2">
                                    <a href="solar-update-inquiry.php?id=<?= $id; ?>&type=visit">
                                        Move to Visit
                                    </a>
                                </button>

                                <!-- 4. Re-Followup -->
                                <button type="button" class="primary1">
                                    <a href="solar-update-inquiry.php?id=<?= $id; ?>&type=refollowup">
                                        Re-Followup
                                    </a>
                                </button>

                            </div>

                        </td>

                    </tr>

                <?php
                    }

                } else {
                ?>

                    <tr>
                        <td colspan="8" style="text-align:center;">
                            No Solar service inquiries found.
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


<!-- ACTION MENU SCRIPT -->
<script>
function toggleActions(button) {

    const currentCell = button.closest('.inquiry-action-cell');
    const currentMenu = currentCell.querySelector('.action-buttons');

    const isOpen = currentMenu.style.display === 'flex';

    document.querySelectorAll('.action-buttons').forEach(function(menu) {
        menu.style.display = 'none';
    });

    if (!isOpen) {
        currentMenu.style.display = 'flex';
    }
}

/* Close menu when clicking outside */
document.addEventListener('click', function(event) {

    if (!event.target.closest('.inquiry-action-cell')) {

        document.querySelectorAll('.action-buttons').forEach(function(menu) {
            menu.style.display = 'none';
        });

    }

});

/* Close menu on Escape */
document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {

        document.querySelectorAll('.action-buttons').forEach(function(menu) {
            menu.style.display = 'none';
        });

    }

});
</script>


<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>

<?php
$stmt->close();
$conn->close();
?>
