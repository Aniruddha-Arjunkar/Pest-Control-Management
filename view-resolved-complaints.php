<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';


/* =========================================================
   BRANCH FILTER
========================================================= */

$branch_filter = "";

if (
    $_SESSION['role'] !== 'super_admin'
    && !empty($_SESSION['branch'])
) {

    $branch = mysqli_real_escape_string(
        $conn,
        $_SESSION['branch']
    );

    $branch_filter = " AND c.branch = '$branch'";
}


/* =========================================================
   FETCH RESOLVED COMPLAINTS
========================================================= */

$sql = "
    SELECT
        rc.Id,
        rc.client_id,
        rc.details,
        rc.complaint_date,
        rc.resolver_name,
        rc.resolve_date,
        c.cust_name AS client_name,
        c.services
    FROM tbl_resolved_complaints rc

    JOIN tbl_client c
        ON rc.client_id = c.Id

    WHERE 1
    $branch_filter

    ORDER BY rc.resolve_date DESC
";


$result = mysqli_query($conn, $sql);


if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}


/* =========================================================
   TOTAL RECORDS
========================================================= */

$total_records = mysqli_num_rows($result);

?>




<body>

<section id="main-content">

    <section class="wrapper">

        <div class="view-clients-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="view-clients-header">

                <div class="view-clients-title-wrap">

                    <h1 class="view-clients-title">

                        <span class="view-clients-title-icon">
                            ⚠️
                        </span>

                        Resolved Complaints

                    </h1>

                    <p class="view-clients-subtitle">
                        View and manage all resolved complaints
                    </p>

                </div>


                <span class="view-clients-count">

                    <?= $total_records; ?>

                    <?= ($total_records == 1)
                        ? 'record'
                        : 'records';
                    ?>

                </span>

            </div>


            <!-- =================================================
                 CARD
            ================================================== -->

            <div class="view-clients-card">


                <!-- =================================================
                     FILTER FORM
                ================================================== -->

                <div class="view-clients-filter">


                    <!-- SEARCH -->

                    <div class="view-client-search-wrap">

                        <span class="view-client-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            id="txt_searchall"
                            class="view-client-search"
                            placeholder="Search complaints..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- START DATE -->

                    <input
                        type="date"
                        id="start_date"
                        class="view-client-date"
                    >


                    <!-- END DATE -->

                    <input
                        type="date"
                        id="end_date"
                        class="view-client-date"
                    >


                    <!-- SEARCH BUTTON -->

                    <button
                        type="button"
                        class="view-client-search-btn"
                        onclick="filterComplaints()"
                    >
                        🔍 Search
                    </button>


                    <!-- CLEAR -->

                    <button
                        type="button"
                        class="view-client-clear-btn"
                        onclick="clearFilters()"
                    >
                        Clear
                    </button>

                </div>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div
                    class="view-clients-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="view-clients-table"
                        id="complaints-table"
                    >

                        <thead>

                            <tr>

                                <th class="view-client-sr">
                                    Sr. No.
                                </th>

                                <th class="view-client-name">
                                    Client Name
                                </th>

                                <th class="view-client-service">
                                    Service
                                </th>

                                <th class="view-client-details">
                                    Details
                                </th>

                                <th class="view-client-complaint-date">
                                    Complaint Date
                                </th>

                                <th class="view-client-resolved-by">
                                    Resolved By
                                </th>

                                <th class="view-client-resolution-date">
                                    Resolution Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        if ($total_records > 0) {

                            $i = 1;

                            while ($row = mysqli_fetch_assoc($result)) {

                                $clientName = htmlspecialchars(
                                    $row['client_name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $service = htmlspecialchars(
                                    $row['services'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $details = htmlspecialchars(
                                    $row['details'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $complaintDate = htmlspecialchars(
                                    $row['complaint_date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $resolvedBy = htmlspecialchars(
                                    $row['resolver_name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $resolveDate = htmlspecialchars(
                                    $row['resolve_date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                /* =================================================
                                   DATE ONLY
                                ================================================== */

                                $complaintDateOnly = '';

                                if (!empty($row['complaint_date'])) {

                                    $complaintDateOnly =
                                        date(
                                            'Y-m-d',
                                            strtotime(
                                                $row['complaint_date']
                                            )
                                        );
                                }


                                $resolveDateOnly = '';

                                if (!empty($row['resolve_date'])) {

                                    $resolveDateOnly =
                                        date(
                                            'Y-m-d',
                                            strtotime(
                                                $row['resolve_date']
                                            )
                                        );
                                }


                                /* =================================================
                                   TODAY CHECK
                                ================================================== */

                                $complaintTodayClass = '';

                                if (
                                    $complaintDateOnly === date('Y-m-d')
                                ) {

                                    $complaintTodayClass =
                                        'view-client-date-today';
                                }


                                ?>

                                <tr>


                                    <!-- SR NO -->

                                    <td class="view-client-sr">

                                        <?= $i++; ?>

                                    </td>


                                    <!-- CLIENT -->

                                    <td class="view-client-name">

                                        <?= $clientName; ?>

                                    </td>


                                    <!-- SERVICE -->

                                    <td class="view-client-service">

                                        <?= $service; ?>

                                    </td>


                                    <!-- DETAILS -->

                                    <td class="view-client-details">

                                        <?= $details; ?>

                                    </td>


                                    <!-- COMPLAINT DATE -->

                                    <td
                                        class="
                                            view-client-complaint-date
                                            <?= $complaintTodayClass; ?>
                                        "
                                        data-date="<?= $complaintDateOnly; ?>"
                                    >

                                        <?= $complaintDate; ?>

                                    </td>


                                    <!-- RESOLVED BY -->

                                    <td class="view-client-resolved-by">

                                        <?= $resolvedBy; ?>

                                    </td>


                                    <!-- RESOLUTION DATE -->

                                    <td
                                        class="view-client-resolution-date"
                                        data-date="<?= $resolveDateOnly; ?>"
                                    >

                                        <?= $resolveDate; ?>

                                    </td>


                                </tr>

                                <?php
                            }

                        } else {

                            ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="view-client-no-data"
                                >
                                    No resolved complaints found.
                                </td>

                            </tr>

                            <?php
                        }

                        ?>

                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     BOTTOM HORIZONTAL SCROLL
                ================================================== -->

                <div
                    class="view-client-bottom-scroll"
                    id="viewClientBottomScroll"
                >

                    <div
                        class="view-client-bottom-scroll-inner"
                        id="viewClientBottomScrollInner"
                    ></div>

                </div>


            </div>

        </div>

    </section>

</section>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   SEARCH + DATE FILTER
========================================================= */

function filterComplaints() {

    const searchInput =
        document.getElementById("txt_searchall");

    const startInput =
        document.getElementById("start_date");

    const endInput =
        document.getElementById("end_date");

    const searchText =
        searchInput.value
            .toLowerCase()
            .trim();

    const startDate =
        startInput.value;

    const endDate =
        endInput.value;

    const rows =
        document.querySelectorAll(
            "#complaints-table tbody tr"
        );


    let serial = 1;


    rows.forEach(function(row) {

        const noData =
            row.querySelector(
                ".view-client-no-data"
            );

        if (noData) {
            return;
        }


        const rowText =
            row.textContent.toLowerCase();


        const dateCell =
            row.querySelector(
                ".view-client-resolution-date"
            );


        const rowDate =
            dateCell
                ? dateCell.getAttribute("data-date")
                : "";


        let searchMatch = true;
        let dateMatch = true;


        /* SEARCH */

        if (
            searchText !== ""
            &&
            !rowText.includes(searchText)
        ) {

            searchMatch = false;

        }


        /* DATE */

        if (startDate && rowDate) {

            if (rowDate < startDate) {
                dateMatch = false;
            }

        }


        if (endDate && rowDate) {

            if (rowDate > endDate) {
                dateMatch = false;
            }

        }


        if (searchMatch && dateMatch) {

            row.style.display = "";

            const srCell =
                row.querySelector(
                    ".view-client-sr"
                );

            if (srCell) {

                srCell.textContent =
                    serial++;

            }

        } else {

            row.style.display =
                "none";
        }

    });

}


/* =========================================================
   LIVE SEARCH
========================================================= */

document
    .getElementById("txt_searchall")
    .addEventListener(
        "input",
        filterComplaints
    );


/* =========================================================
   CLEAR FILTERS
========================================================= */

function clearFilters() {

    document.getElementById(
        "txt_searchall"
    ).value = "";

    document.getElementById(
        "start_date"
    ).value = "";

    document.getElementById(
        "end_date"
    ).value = "";


    filterComplaints();

}


/* =========================================================
   BOTTOM SCROLLBAR SYNC
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const bottomScroll =
            document.getElementById(
                "viewClientBottomScroll"
            );

        const bottomInner =
            document.getElementById(
                "viewClientBottomScrollInner"
            );

        const tableWrapper =
            document.querySelector(
                ".view-clients-table-wrapper"
            );


        if (
            !bottomScroll
            ||
            !bottomInner
            ||
            !tableWrapper
        ) {
            return;
        }


        function updateScrollWidth() {

            bottomInner.style.width =
                tableWrapper.scrollWidth + "px";
        }


        updateScrollWidth();


        window.addEventListener(
            "resize",
            updateScrollWidth
        );


        /* BOTTOM -> TABLE */

        bottomScroll.addEventListener(
            "scroll",
            function() {

                tableWrapper.scrollLeft =
                    bottomScroll.scrollLeft;

            }
        );


        /* TABLE -> BOTTOM */

        tableWrapper.addEventListener(
            "scroll",
            function() {

                bottomScroll.scrollLeft =
                    tableWrapper.scrollLeft;

            }
        );

    }
);

</script>


<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>

</body>
</html>

<?php
mysqli_close($conn);
?>