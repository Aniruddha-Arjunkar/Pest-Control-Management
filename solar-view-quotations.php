<?php

session_start();

// =========================================================
// LOGIN CHECK
// =========================================================

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {

    $_SESSION['msg'] = "You must log in first";

    header('Location: login.php');
    exit();
}

// =========================================================
// LOGOUT
// =========================================================

if (isset($_GET['logout'])) {

    session_destroy();

    header('Location: login.php');
    exit();
}

// =========================================================
// DATABASE & HEADER
// =========================================================

require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

include 'header.php';

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

// =========================================================
// SEARCH LOGIC
// =========================================================

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        q.id,
        q.pdf_file,
        q.quotation_date,
        q.ref_no,
        q.quotation_type,
        i.name AS client_name,
        i.phone AS client_phone
    FROM tbl_solar_quotation q
    INNER JOIN tbl_solar_inquiry i
        ON i.id = q.inquiry_id
    WHERE 1=1
";

$params = [];
$types = '';

if ($search !== '') {

    $sql .= "
        AND (
            i.name LIKE ?
            OR i.phone LIKE ?
            OR q.ref_no LIKE ?
            OR DATE_FORMAT(q.quotation_date, '%d-%m-%Y') LIKE ?
            OR q.quotation_date LIKE ?
            OR q.quotation_type LIKE ?
        )
    ";

    $searchValue = "%{$search}%";

    $params = [
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    ];

    $types = "ssssss";
}

$sql .= " ORDER BY q.id DESC";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query Prepare Failed: " . mysqli_error($conn));
}

if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// =========================================================
// TOTAL RECORDS
// =========================================================

$total_records = $result ? mysqli_num_rows($result) : 0;

?>

<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<section id="main-content">

    <section class="wrapper">

        <div class="inquiry-page">

            <!-- PAGE HEADER -->

            <div class="inquiry-page-header">

                <h1>📄 Generated Quotations</h1>

                <p>
                    Showing all generated Solar quotations

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

                    <form method="GET" class="inquiry-search-wrapper">

                        <span class="inquiry-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            name="search"
                            id="txt_searchall"
                            class="inquiry-search"
                            placeholder="Search by name, phone, quotation date or ref no..."
                            value="<?= e($search); ?>"
                            autocomplete="off"
                        >

                    </form>

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

                                <th>REF. NO.</th>

                                <th>CLIENT NAME</th>

                                <th>PHONE</th>

                                <th>QUOTATION DATE</th>

                                <th>QUOTATION TYPE</th>

                                <th>ACTION</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php

                        $sr_no = 1;

                        if ($result && mysqli_num_rows($result) > 0) {

                            while ($row = mysqli_fetch_assoc($result)) {

                                $quotation_id = (int)$row['id'];

                                $pdf_file = trim($row['pdf_file'] ?? '');

                                $quotation_type = $row['quotation_type'] ?? 'quotation';

                                $type_label = (
                                    strtolower($quotation_type) === 'fabrication'
                                ) ? 'Fabrication' : 'Quotation';

                                $quotation_date = '';

                                if (!empty($row['quotation_date'])) {

                                    $timestamp = strtotime($row['quotation_date']);

                                    if ($timestamp !== false) {
                                        $quotation_date = date('d-m-Y', $timestamp);
                                    }
                                }

                        ?>

                            <tr class="data-row">

                                <td class="inquiry-sr">
                                    <?= $sr_no++; ?>
                                </td>

                                <td class="inquiry-name">
                                    <?= e($row['ref_no']); ?>
                                </td>

                                <td class="inquiry-name">
                                    <?= e($row['client_name']); ?>
                                </td>

                                <td class="inquiry-phone">
                                    <?= e($row['client_phone']); ?>
                                </td>

                                <td class="inquiry-date">
                                    <?= e($quotation_date); ?>
                                </td>

                                <td>
                                    <?= e($type_label); ?>
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

                                        <!-- VIEW PDF -->

                                        <?php if ($pdf_file !== '') { ?>

                                            <button type="button" class="primary2">
                                            <a
                                                href="<?= e($pdf_file); ?>"
                                                target="_blank"
                                            >
                                                View PDF
                                            </a>
                                        </button>

                                        <?php } else { ?>

                                            <span class="quotation-not-added">
                                                PDF Not Available
                                            </span>

                                        <?php } ?>


                                        <!-- SEND QUOTATION -->
                                        <button type="button" class="primary1">

                                        <a
                                            href="send-quotation-email.php?quotation_id=<?= $quotation_id; ?>"
                                        >
                                            Send Quotation
                                        </a>
                                    </button>


                                        <!-- DELETE -->

                                        <form
                                            method="POST"
                                            action="solar-delete-quotation.php"
                                            onsubmit="return confirm('Are you sure you want to delete this quotation?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="quotation_id"
                                                value="<?= $quotation_id; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="primary3"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php

                            }

                        } else {

                        ?>

                            <tr class="no-data-row">

                                <td colspan="7" class="no-inquiry">
                                    No Quotations Found.
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

</body>
</html>
