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


/* =========================================================
   FETCH MATERIALS
========================================================= */

$sql = "SELECT * FROM materials ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$total_records = mysqli_num_rows($result);

?>

<style>
 /* =========================================================
   MATERIAL TABLE - COMPACT 5 COLUMNS
========================================================= */

.view-clients-table {
    width: 100% !important;
    min-width: 0 !important;
    table-layout: fixed;
}

/* Sr No */
.view-clients-table th:nth-child(1),
.view-clients-table td:nth-child(1) {
    width: 8%;
    text-align: center !important;
}

/* Name */
.view-clients-table th:nth-child(2),
.view-clients-table td:nth-child(2) {
    width: 32%;
}

/* Unit */
.view-clients-table th:nth-child(3),
.view-clients-table td:nth-child(3) {
    width: 17%;
}

/* Rate */
.view-clients-table th:nth-child(4),
.view-clients-table td:nth-child(4) {
    width: 23%;
}

/* Action */
.view-clients-table th:nth-child(5),
.view-clients-table td:nth-child(5) {
    width: 20%;
    text-align: center !important;
}

.view-clients-table thead th {
    padding: 10px 7px !important;
    font-size: 12px !important;
}

.view-clients-table tbody td {
    padding: 10px 7px !important;
    font-size: 13px !important;
}

.view-client-sr {
    text-align: center !important;
    font-weight: 700;
}

.view-client-view-btn {
    min-width: 65px !important;
    width: auto !important;
    height: 30px !important;
    padding: 0 10px !important;
    font-size: 11px !important;
}
</style>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

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
                            📦
                        </span>

                        View Material Lists

                    </h1>

                    <p class="view-clients-subtitle">
                        View and manage all materials
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

                <form
                    method="get"
                    action=""
                    class="view-clients-filter"
                >

                    <div class="view-client-search-wrap">

                        <span class="view-client-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            id="txt_searchall"
                            class="view-client-search"
                            placeholder="Search materials..."
                            autocomplete="off"
                        >

                    </div>

                </form>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div
                    class="view-clients-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="view-clients-table"
                        id="payments-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Sr. No.
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Unit
                                </th>

                                <th>
                                    Rate Per Unit
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        if ($total_records > 0) {

                            $i = 1;

                            while ($row = mysqli_fetch_assoc($result)) {

                                $name = htmlspecialchars(
                                    $row['name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $rate_unit = htmlspecialchars(
                                    $row['rate_unit'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                $rate_per_unit = htmlspecialchars(
                                    $row['rate_per_unit'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                                <tr>


                                    <!-- SR NO -->

                                    <td class="view-client-sr">

                                        <?= $i++; ?>

                                    </td>


                                    <!-- NAME -->

                                    <td class="view-client-name">

                                        <?= $name; ?>

                                    </td>


                                    <!-- UNIT -->

                                    <td>

                                        <?= $rate_unit; ?>

                                    </td>


                                    <!-- RATE -->

                                    <td>

                                        <?= $rate_per_unit; ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td class="view-client-action">

                                        <a
                                            href="update_material.php?id=<?= intval($row['id']); ?>"
                                            class="view-client-view-btn"
                                        >

                                            ✏️ Update

                                        </a>

                                    </td>


                                </tr>

                                <?php

                            }

                        } else {

                            ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="view-client-no-data"
                                >

                                    No materials found.

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
                "payments-table"
            );

        if (
            !searchInput ||
            !table
        ) {
            return;
        }


        searchInput.addEventListener(
            "input",
            function () {

                const searchValue =
                    this.value
                        .toLowerCase()
                        .trim();

                const rows =
                    table.querySelectorAll(
                        "tbody tr"
                    );

                let serial = 1;


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
                            row.textContent
                                .toLowerCase();


                        if (
                            searchValue === "" ||
                            rowText.includes(
                                searchValue
                            )
                        ) {

                            row.style.display = "";

                            const sr =
                                row.querySelector(
                                    ".view-client-sr"
                                );

                            if (sr) {

                                sr.textContent =
                                    serial++;

                            }

                        } else {

                            row.style.display =
                                "none";

                        }

                    }
                );

            }
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


<!-- =========================================================
     EXISTING JS
========================================================= -->

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>

</body>
</html>

