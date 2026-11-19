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
   BRANCH RESTRICTION
========================================================= */

$branchFilter = "";

if (
    isset($_SESSION['role'])
    && $_SESSION['role'] !== 'super_admin'
    && !empty($_SESSION['branch'])
) {

    $branch = mysqli_real_escape_string(
        $conn,
        $_SESSION['branch']
    );

    $branchFilter = " AND branch = '$branch'";
}


/* =========================================================
   MAIN QUERY
========================================================= */

$sql = "
    SELECT *
    FROM tbl_employee
    WHERE 1
    $branchFilter
    ORDER BY Id
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$total_records = mysqli_num_rows($result);

?>

<style>

/* ================================
   DESKTOP
================================ */

.view-client-buttons {
    display: flex;
    flex-direction: row;
    width: 100%;
    gap: 8px;
    box-sizing: border-box;
}



.view-client-btn {
    display: flex !important;
    flex: 1 1 0;
    align-items: center;
    justify-content: center;

    height: 35px;
    padding: 10px 6px;
    margin: 0 !important;

    box-sizing: border-box;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    text-align: center;
}


/* GREEN */

.view-client-all-btn {
    background: #28a745;
    color: #fff;
}


/* YELLOW */

.view-client-exit-btn {
    background: #ffc107;
    color: #212529;
}


/* RED */

.view-client-abscond-btn {
    background: #dc3545;
    color: #fff;
}


/* ================================
   MOBILE
================================ */

@media only screen and (max-width: 600px) {

    .view-client-buttons {
        display: flex;
        flex-wrap: wrap;
        flex-direction: row;
        width: 100%;
        gap: 6px;
    }




    /* 3 BUTTONS - SECOND ROW */

    .view-client-btn {
        flex: 1 1 calc((100% - 12px) / 3) !important;
        width: calc((100% - 12px) / 3) !important;
        min-width: 0 !important;

        height: 35px;
        padding: 8px 3px !important;
        margin: 0 !important;

        font-size: 11px;
        line-height: 1.2;
        white-space: normal;
    }

}


/* ================================
   VERY SMALL MOBILE
================================ */

@media only screen and (max-width: 400px) {

    .view-client-buttons {
        gap: 4px;
    }

    .view-client-btn {
        flex: 1 1 calc((100% - 8px) / 3) !important;
        width: calc((100% - 8px) / 3) !important;
        font-size: 10px;
        padding: 7px 2px !important;
    }

}

</style>


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
                        👨‍💼
                    </span>

                    View Employee

                </h1>

                <p class="view-clients-subtitle">
                    View and manage all employees
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
                 SEARCH + ACTION BUTTONS
            ================================================== -->

<div class="view-client-buttons">

                <!-- SEARCH -->

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

                <!-- VIEW ALL -->

                <a
                    href="view-all-employee.php"
                    class="view-client-btn view-client-all-btn"
                >
                    👥 View All Employees
                </a>


                <!-- EXIT -->

                <a
                    href="exit-employee.php"
                    class="view-client-btn view-client-exit-btn"
                >
                    🚪 Exit Employee
                </a>


                <!-- ABSCOND -->

                <a
                    href="abscond-employee.php"
                    class="view-client-btn view-client-abscond-btn"
                >
                    ⚠ Abscond Employee
                </a>

            </div>
            <br>
      


            <!-- =================================================
                 TABLE WRAPPER
            ================================================== -->

            <div
                class="view-clients-table-wrapper"
                id="scrollable-content"
            >

                <table
                    class="view-clients-table"
                    id="employees-table"
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

                            <th  style= "min-width: 120px">
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


        /* =================================================
           NAME
        ================================================= */

        $title = htmlspecialchars(
            $row['title'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

        $employeeName = htmlspecialchars(
            $row['cust_name'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

        $full_name = trim(
            $title . " " . $employeeName
        );


        /* =================================================
           PHONE
        ================================================= */

        $phone = htmlspecialchars(
            $row['cust_phone'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );


        /* =================================================
           EMAIL
        ================================================= */

        $email = htmlspecialchars(
            $row['cust_email'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );


        /* =================================================
           DATE
        ================================================= */

        $date = htmlspecialchars(
            $row['date'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );


        /* =================================================
           DESIGNATION
        ================================================= */

        $designation = htmlspecialchars(
            $row['designation'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

?>

<tr>

<!-- =================================================
     SR NO
================================================== -->

<td class="view-client-sr">

    <?= $i++; ?>

</td>


<!-- =================================================
     NAME
================================================== -->

<td class="view-client-name">

    <?= $full_name; ?>

</td>


<!-- =================================================
     PHONE
================================================== -->

<td class="view-client-phone">

    <?= $phone; ?>

</td>


<!-- =================================================
     EMAIL
================================================== -->

<td class="view-client-email">

    <?= $email; ?>

</td>


<!-- =================================================
     DATE
================================================== -->

<td class="view-client-date-cell">

    <?= $date; ?>

</td>


<!-- =================================================
     DESIGNATION
================================================== -->

<td class="view-client-designation">

    <?= $designation; ?>

</td>


<!-- =================================================
     ACTION
================================================== -->

<td class="view-client-action">

    <a
        href="view-employee-detail.php?employee_id=<?= intval($row['Id']); ?>"
        class="view-client-action-btn"
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

    No employees found.

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
                "employees-table"
            );

        if (
            !searchInput
            ||
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
                            searchValue === ""
                            ||
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

                        }

                        else {

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
                tableWrapper.scrollWidth
                + "px";

        }


        updateScrollWidth();


        window.addEventListener(
            "resize",
            updateScrollWidth
        );


        /* BOTTOM -> TABLE */

        bottomScroll.addEventListener(
            "scroll",
            function () {

                tableWrapper.scrollLeft =
                    bottomScroll.scrollLeft;

            }
        );


        /* TABLE -> BOTTOM */

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
