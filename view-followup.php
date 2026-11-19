<?php
session_start();

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
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


/* =========================================================
   FETCH RE-FOLLOWUP
========================================================= */

$sql = "SELECT *
        FROM tbl_student_inquiries
        WHERE follow_up IS NOT NULL
        AND inquiry_date IS NOT NULL
        ORDER BY follow_up";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}


/* =========================================================
   TOTAL RECORDS
========================================================= */

$total_records = mysqli_num_rows($result);

?>

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
                            🔄
                        </span>

                        View Re-Followup

                    </h1>

                    <p class="view-clients-subtitle">
                        View and manage all re-followup records
                    </p>

                </div>


                <!-- RECORD COUNT -->

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
                     SEARCH
                ================================================== -->

                <div class="view-clients-filter">

                    <div class="view-client-search-wrap">

                        <span class="view-client-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            id="txt_searchall"
                            class="view-client-search"
                            placeholder="Search name, phone, email..."
                            autocomplete="off"
                        >

                    </div>

                </div>


                <!-- =================================================
                     TABLE WRAPPER
                ================================================== -->

                <div
                    class="view-clients-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="view-clients-table"
                        id="refollowupTable"
                    >

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Name</th>

                                <th>Phone</th>

                                <th>Email</th>

                                <th>Address</th>

                                <th>Message</th>

                                <th>Followup Date</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        if ($total_records > 0) {

                            $sr = 1;

                            while ($row = mysqli_fetch_assoc($result)) {

                                $followup_date =
                                    $row['follow_up'] ?? '';

                                $is_today_or_due =
                                    ($followup_date <= date("Y-m-d"));

                                ?>

                                <tr>


                                    <!-- SR NO -->

                                    <td class="view-client-sr">

                                        <?= $sr++; ?>

                                    </td>


                                    <!-- NAME -->

                                    <td class="view-client-name">

                                        <?= htmlspecialchars(
                                            $row['name'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- PHONE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['phone'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- EMAIL -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['email'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- ADDRESS -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['address'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- MESSAGE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['message'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- FOLLOWUP DATE -->

                                    <td
                                        class="<?= $is_today_or_due
                                            ? 'refollowup-due-date'
                                            : ''; ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $followup_date,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <div
                                            class="view-client-action-buttons"
                                            style="
                                                display:flex;
                                                gap:6px;
                                                flex-wrap:wrap;
                                            "
                                        >

                                            <a
                                                href="followup.php?id=<?= (int)$row['Id']; ?>"
                                                class="view-client-view-btn"
                                            >
                                                Re-followup
                                            </a>

                                            <a
                                                href="add_admission.php?id=<?= (int)$row['Id']; ?>"
                                                class="view-client-admission-btn"
                                            >
                                                Proceed for Admission
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                                <?php

                            }

                        } else {

                            ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="view-client-no-data"
                                >

                                    No re-followup records found.

                                </td>

                            </tr>

                            <?php

                        }

                        ?>

                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     BOTTOM HORIZONTAL SCROLLBAR
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
     PAGE-SPECIFIC DATE STYLE
========================================================= -->

<style>

.refollowup-due-date {
    background: #eacbcb !important;
    font-weight: 600;
}

.view-client-admission-btn {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 4px;
    background: #28a745;
    color: #fff !important;
    text-decoration: none;
    font-size: 13px;
    white-space: nowrap;
}

.view-client-admission-btn:hover {
    background: #218838;
    color: #fff !important;
    text-decoration: none;
}

</style>


<!-- =========================================================
     SEARCH JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const searchInput =
            document.getElementById(
                "txt_searchall"
            );

        const table =
            document.getElementById(
                "refollowupTable"
            );

        if (
            !searchInput ||
            !table
        ) {
            return;
        }


        const tbody =
            table.querySelector("tbody");


        function filterRows() {

            const filter =
                searchInput.value
                    .toLowerCase()
                    .trim();

            const rows =
                tbody.querySelectorAll("tr");


            rows.forEach(
                function (row) {

                    const noData =
                        row.querySelector(
                            ".view-client-no-data"
                        );

                    if (noData) {
                        return;
                    }


                    const rowText =
                        row.innerText
                            .toLowerCase();


                    if (
                        filter === "" ||
                        rowText.includes(filter)
                    ) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                }
            );

        }


        searchInput.addEventListener(
            "input",
            filterRows
        );

    }
);


/* =========================================================
   BOTTOM SCROLLBAR SYNC
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

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
            !bottomScroll ||
            !bottomInner ||
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


        bottomScroll.addEventListener(
            "scroll",
            function () {

                tableWrapper.scrollLeft =
                    bottomScroll.scrollLeft;

            }
        );


        tableWrapper.addEventListener(
            "scroll",
            function () {

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
<script src="js/search.js"></script>

</body>
</html>

<?php
mysqli_close($conn);
?>