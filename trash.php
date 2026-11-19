
<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';


/* =========================================================
   FETCH TRASH RECORDS
========================================================= */

$sql = "SELECT * FROM tbl_delete ORDER BY Id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$total_records = mysqli_num_rows($result);

?>


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
                            🗑️
                        </span>

                        Trash

                    </h1>

                    <p class="view-clients-subtitle">
                        View and manage deleted inquiry records
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
                            placeholder="Search service, name, phone, address..."
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
                        id="trashTable"
                    >

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Services</th>

                                <th>Name</th>

                                <th>Phone</th>

                                <th>Address</th>

                                <th>Message</th>

                                <th>Inquiry Date</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        if ($total_records > 0) {

                            $i = 1;

                            while ($row = mysqli_fetch_assoc($result)) {

                        ?>

                            <tr>


                                <!-- SR NO -->

                                <td class="view-client-sr">

                                    <?= $i++; ?>

                                </td>


                                <!-- SERVICES -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['services'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- NAME -->

                                <td class="view-client-name">

                                    <?= htmlspecialchars(
                                        $row['cust_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- PHONE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['cust_phone'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- ADDRESS -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['cust_address'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- MESSAGE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['cust_message'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- INQUIRY DATE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['inq_date'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- ACTION -->

                                <td class="view-client-actions">

                                    <button
                                        type="button"
                                        class="primary-btn"
                                        onclick="window.location.href='delete1.php?id=<?= $row['Id']; ?>';"
                                    >
                                        Delete
                                    </button>

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

                                    No deleted records found.

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
                "trashTable"
            );


        if (!searchInput || !table) {
            return;
        }


        const tbody =
            table.querySelector("tbody");


        /* =====================================================
           FILTER TABLE
        ===================================================== */

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

