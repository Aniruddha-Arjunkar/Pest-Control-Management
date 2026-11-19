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
   FETCH EMPLOYEES WHO HAVE TAKEN ADVANCE
========================================================= */

$employees_query = "
    SELECT DISTINCT e.Id, e.cust_name
    FROM tbl_employee e
    JOIN tbl_salary s ON e.Id = s.employee_id
    WHERE s.payment_type = 'Advance'
    ORDER BY e.cust_name ASC
";

$employees_result = mysqli_query($conn, $employees_query);

if (!$employees_result) {
    die("SQL Error: " . mysqli_error($conn));
}

?>
<style>
    #payments-table {
    width: 100% !important;
    min-width: 550px !important;
}

#payments-table th:nth-child(1),
#payments-table td:nth-child(1) {
    width: 45px !important;
    min-width: 45px !important;
}

#payments-table th:nth-child(2),
#payments-table td:nth-child(2) {
    width: 200px !important;
    min-width: 160px !important;
}

#payments-table th:nth-child(3),
#payments-table td:nth-child(3) {
    width: 120px !important;
    min-width: 120px !important;
}

#payments-table th:nth-child(4),
#payments-table td:nth-child(4) {
    width: 90px !important;
    min-width: 90px !important;
}
</style>


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
                            💰
                        </span>

                        Employee Pending Advance Details

                    </h1>

                    <p class="view-clients-subtitle">
                        View and manage pending employee advances
                    </p>

                </div>


                <!-- RECORD COUNT -->

                <?php

                /*
                 * Count only employees having pending advance.
                 */

                $count_pending = 0;

                mysqli_data_seek($employees_result, 0);

                while ($count_emp = mysqli_fetch_assoc($employees_result)) {

                    $count_emp_id = $count_emp['Id'];

                    $count_adv_query = mysqli_query(
                        $conn,
                        "
                        SELECT SUM(advance) AS total_advance
                        FROM tbl_salary
                        WHERE employee_id='$count_emp_id'
                        AND payment_type='Advance'
                        "
                    );

                    $count_adv_data =
                        mysqli_fetch_assoc($count_adv_query);

                    $count_total_advance =
                        (float)($count_adv_data['total_advance'] ?? 0);


                    $count_ded_query = mysqli_query(
                        $conn,
                        "
                        SELECT SUM(advance) AS total_deducted
                        FROM tbl_salary
                        WHERE employee_id='$count_emp_id'
                        AND payment_type IN ('Salary','PerDay')
                        AND advance > 0
                        "
                    );

                    $count_ded_data =
                        mysqli_fetch_assoc($count_ded_query);

                    $count_total_deducted =
                        (float)($count_ded_data['total_deducted'] ?? 0);


                    $count_pending_amount =
                        $count_total_advance -
                        $count_total_deducted;


                    if ($count_pending_amount > 0) {
                        $count_pending++;
                    }

                }

                /*
                 * Reset result pointer.
                 */

                mysqli_data_seek($employees_result, 0);

                ?>

                <span class="view-clients-count">

                    <?= $count_pending ?>

                    <?= ($count_pending == 1)
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
                        id="payments-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Sr. No.
                                </th>

                                <th>
                                    Employee Name
                                </th>

                                <th>
                                    Pending (₹)
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        $i = 1;
                        $has_pending = false;

                        while (
                            $emp =
                            mysqli_fetch_assoc(
                                $employees_result
                            )
                        ):

                            $emp_id = $emp['Id'];


                            /* =================================================
                               TOTAL ADVANCE
                            ================================================== */

                            $adv_query = mysqli_query(
                                $conn,
                                "
                                SELECT SUM(advance) AS total_advance
                                FROM tbl_salary
                                WHERE employee_id='$emp_id'
                                AND payment_type='Advance'
                                "
                            );

                            $adv_data =
                                mysqli_fetch_assoc(
                                    $adv_query
                                );

                            $total_advance =
                                (float)(
                                    $adv_data['total_advance']
                                    ?? 0
                                );


                            /* =================================================
                               TOTAL DEDUCTION
                            ================================================== */

                            $ded_query = mysqli_query(
                                $conn,
                                "
                                SELECT SUM(advance) AS total_deducted
                                FROM tbl_salary
                                WHERE employee_id='$emp_id'
                                AND payment_type IN ('Salary','PerDay')
                                AND advance > 0
                                "
                            );

                            $ded_data =
                                mysqli_fetch_assoc(
                                    $ded_query
                                );

                            $total_deducted =
                                (float)(
                                    $ded_data['total_deducted']
                                    ?? 0
                                );


                            /* =================================================
                               PENDING
                            ================================================== */

                            $pending =
                                $total_advance -
                                $total_deducted;


                            /*
                             * Skip fully cleared employees.
                             */

                            if ($pending <= 0) {
                                continue;
                            }

                            $has_pending = true;

                        ?>

                        <tr>

                            <!-- SR NO -->

                            <td class="view-client-sr">

                                <?= $i++ ?>

                            </td>


                            <!-- EMPLOYEE NAME -->

                            <td class="view-client-name">

                                <?= htmlspecialchars(
                                    $emp['cust_name']
                                ) ?>

                            </td>


                            <!-- PENDING -->

                            <td class="view-client-pending">

                                ₹ <?= number_format(
                                    $pending,
                                    2
                                ) ?>

                            </td>


                            <!-- ACTION -->

                            <td class="view-client-action">

                                <a
                                    href="view_advance_details.php?id=<?= intval($emp_id) ?>"
                                    class="view-client-view-btn"
                                >
                                    👁 View
                                </a>

                            </td>

                        </tr>

                        <?php endwhile; ?>


                        <?php if (!$has_pending): ?>

                        <tr>

                            <td
                                colspan="4"
                                class="view-client-no-data"
                            >

                                ✅ All employee advances are fully cleared!

                            </td>

                        </tr>

                        <?php endif; ?>

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
            !bottomScroll ||
            !bottomInner ||
            !tableWrapper
        ) {
            return;
        }


        function updateScrollWidth() {

            bottomInner.style.width =
                tableWrapper.scrollWidth +
                "px";

        }


        updateScrollWidth();


        window.addEventListener(
            "resize",
            updateScrollWidth
        );


        /* TABLE -> BOTTOM */

        bottomScroll.addEventListener(
            "scroll",
            function () {

                tableWrapper.scrollLeft =
                    bottomScroll.scrollLeft;

            }
        );


        /* BOTTOM -> TABLE */

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

