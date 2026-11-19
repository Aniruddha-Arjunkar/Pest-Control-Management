
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
   ROLE
========================================================= */

$role = $_SESSION['role'] ?? '';

/* =========================================================
   BRANCH FILTER
========================================================= */

$branch_filter = '';

if ($role !== 'super_admin' && !empty($_SESSION['branch'])) {

    $branch = mysqli_real_escape_string(
        $conn,
        $_SESSION['branch']
    );

    $branch_filter = " AND branch = '$branch'";
}

/* =========================================================
   MAIN QUERY
========================================================= */

$sql = "
    SELECT *
    FROM tbl_employee
    WHERE resignation_type = 'Abscond'
    $branch_filter
    ORDER BY Id
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



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<section id="main-content">

<section class="wrapper">

<div class="view-clients-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="view-clients-header">

        <div class="view-clients-title-wrap">

            <h1 class="view-clients-title">

                <span class="view-clients-title-icon">
                    👤
                </span>

                Absconded Employee

            </h1>

            <p class="view-clients-subtitle">
                View all employees marked as absconded
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


    <!-- =====================================================
         CARD
    ====================================================== -->

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
                    placeholder="Search employees..."
                    autocomplete="off"
                >

            </div>

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
                id="employee-table"
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
                            Phone
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Designation
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

                        /* =====================================
                           FULL NAME
                        ====================================== */

                        $full_name =
                            ($row['title'] ?? '') .
                            ' ' .
                            ($row['cust_name'] ?? '');

                        ?>

                        <tr>


                            <!-- SR NO -->

                            <td class="view-client-sr">

                                <?= $i++; ?>

                            </td>


                            <!-- NAME -->

                            <td class="view-client-name">

                                <?= htmlspecialchars(
                                    trim($full_name),
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


                            <!-- EMAIL -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['cust_email'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </td>


                            <!-- DESIGNATION -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['designation'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <a
                                    href="view-employee-detail.php?employee_id=<?= intval($row['Id']); ?>"
                                    class="view-client-view-btn"
                                >

                                    👁 View All Details

                                </a>

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

                            No absconded employees found.

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
     SEARCH
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
                "employee-table"
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

</script>


<!-- =========================================================
     BOTTOM SCROLL SYNC
========================================================= -->

<script>

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

