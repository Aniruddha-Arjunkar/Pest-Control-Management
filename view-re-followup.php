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
   TOTAL RECORDS
========================================================= */

$count_sql = "
    SELECT COUNT(*) AS total
    FROM tbl_inquiry
    WHERE follow_up_date IS NOT NULL
    AND visit_date IS NULL
    AND service_date IS NULL
";

$count_result = mysqli_query($conn, $count_sql);

$total_records = 0;

if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_records = $count_row['total'];
}


/* =========================================================
   GET RE-FOLLOWUP RECORDS
========================================================= */

$sql = "
    SELECT *
    FROM tbl_inquiry
    WHERE follow_up_date IS NOT NULL
    AND visit_date IS NULL
    AND service_date IS NULL
    ORDER BY follow_up_date ASC
";

$result = mysqli_query($conn, $sql);

?>

<body>

<section id="main-content">

    <section class="wrapper">

        <div class="inquiry-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="inquiry-page-header">

                <h1>
                    📞 View Re-Followup
                </h1>

                <p>

                    Showing scheduled re-followups

                    <span class="inquiry-count">
                        <?= $total_records; ?> Records
                    </span>

                </p>

            </div>


            <!-- =================================================
                 CARD
            ================================================== -->

            <div class="inquiry-card">


                <!-- =================================================
                     SEARCH + DATE FILTER
                ================================================== -->

                <div class="refollowup-filter-bar">


                    <!-- SEARCH -->

                    <div class="refollowup-search-wrap">

                        <span class="refollowup-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            id="txt_searchall"
                            class="refollowup-input"
                            placeholder="Search customer..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- START DATE -->

                    <div class="refollowup-date-wrap">

                        <input
                            type="date"
                            id="start_date"
                            class="refollowup-input"
                        >

                    </div>


                    <!-- END DATE -->

                    <div class="refollowup-date-wrap">

                        <input
                            type="date"
                            id="end_date"
                            class="refollowup-input"
                        >

                    </div>


                    <!-- SEARCH BUTTON -->

                    <button
                        type="button"
                        class="refollowup-search-btn"
                        onclick="filterByDate()"
                    >
                        Search
                    </button>


                    <!-- CLEAR -->

                    <button
                        type="button"
                        class="refollowup-clear-btn"
                        onclick="clearFilters()"
                    >
                        Clear
                    </button>


                    <!-- BACK -->

                    <a
                        href="view-all-inquiry-clients.php"
                        class="refollowup-back-btn"
                    >
                        ◀ Back
                    </a>


                </div>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div
                    class="inquiry-table-wrapper refollowup-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="inquiry-table refollowup-table"
                        id="payments-table"
                    >

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>SERVICE</th>

                                <th>CUSTOMER</th>

                                <th>PHONE</th>

                                <th>ADDRESS</th>

                                <th>MESSAGE</th>

                                <th>FOLLOWUP DATE</th>

                                <th>ACTION</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        $sr_no = 1;

                        if ($result && mysqli_num_rows($result) > 0) {

                            while ($row = mysqli_fetch_assoc($result)) {

                                $id =
                                    (int)$row['Id'];

                                $service =
                                    htmlspecialchars(
                                        $row['services'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $name =
                                    htmlspecialchars(
                                        $row['cust_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $phone =
                                    htmlspecialchars(
                                        $row['cust_phone'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $address =
                                    htmlspecialchars(
                                        $row['cust_address'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $message =
                                    htmlspecialchars(
                                        $row['cust_message'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $followup_date =
                                    htmlspecialchars(
                                        $row['follow_up_date'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                /*
                                 * Check overdue / today
                                 */

                                $today_class = '';

                                if (
                                    !empty($row['follow_up_date']) &&
                                    $row['follow_up_date'] <= date('Y-m-d')
                                ) {

                                    $today_class = 'followup-today';

                                }

                        ?>

                            <tr>


                                <!-- SERIAL -->

                                <td class="inquiry-sr">

                                    <?= $sr_no++; ?>

                                </td>


                                <!-- SERVICE -->

                                <td class="inquiry-service">

                                    <?= $service; ?>

                                </td>


                                <!-- CUSTOMER -->

                                <td class="inquiry-name">

                                    <?= $name; ?>

                                </td>


                                <!-- PHONE -->

                                <td class="inquiry-phone">

                                    <?= $phone; ?>

                                </td>


                                <!-- ADDRESS -->

                                <td class="inquiry-address">

                                    <?= $address; ?>

                                </td>


                                <!-- MESSAGE -->

                                <td class="inquiry-message">

                                    <?= $message; ?>

                                </td>


                                <!-- FOLLOWUP DATE -->

                                <td
                                    class="inquiry-date <?= $today_class; ?>"
                                >

                                    <?= $followup_date; ?>

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


                                        <button
                                            type="button"
                                            class="primary1"
                                        >
                                            <a
                                                href="re-followup.php?id=<?= $id; ?>"
                                            >
                                                Re-followup
                                            </a>
                                        </button>


                                        <button
                                            type="button"
                                            class="primary2"
                                        >
                                            <a
                                                href="visit.php?id=<?= $id; ?>"
                                            >
                                                Visit
                                            </a>
                                        </button>


                                        <button
                                            type="button"
                                            class="primary5"
                                        >
                                            <a
                                                href="service.php?id=<?= $id; ?>"
                                            >
                                                Proceed For Service
                                            </a>
                                        </button>


                                        <button
                                            type="button"
                                            class="primary4"
                                        >
                                            <a
                                                href="todays-date.php?id=<?= $id; ?>"
                                            >
                                                Schedule Service
                                            </a>
                                        </button>


                                        <button
                                            type="button"
                                            class="primary3"
                                        >
                                            <a
                                                href="delete.php?id=<?= $id; ?>"
                                                onclick="return confirm('Are you sure you want to delete this record?');"
                                            >
                                                Delete
                                            </a>
                                        </button>


                                        <button
                                            type="button"
                                            class="primary6"
                                        >
                                            <a
                                                href="generate_quotation_form.php?id=<?= $id; ?>"
                                            >
                                                Send Quotation
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

                                <td
                                    colspan="8"
                                    class="no-inquiry"
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


            </div>

        </div>

    </section>

</section>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

/* =========================================================
   ACTION MENU
========================================================= */

function toggleActions(button) {

    const container =
        button.nextElementSibling;


    document
        .querySelectorAll('.action-buttons')
        .forEach(function(menu) {

            if (menu !== container) {

                menu.style.display = 'none';

            }

        });


    document
        .querySelectorAll('.action-btn')
        .forEach(function(btn) {

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


/* =========================================================
   SEARCH
========================================================= */

document
    .getElementById('txt_searchall')
    .addEventListener('input', applyFilters);


/* =========================================================
   DATE FILTER
========================================================= */

function filterByDate() {

    applyFilters();

}


/* =========================================================
   APPLY ALL FILTERS
========================================================= */

function applyFilters() {

    const searchValue =
        document
            .getElementById('txt_searchall')
            .value
            .toLowerCase()
            .trim();


    const startDate =
        document
            .getElementById('start_date')
            .value;


    const endDate =
        document
            .getElementById('end_date')
            .value;


    const rows =
        document.querySelectorAll(
            '#payments-table tbody tr'
        );


    rows.forEach(function(row) {

        if (!row.cells[6]) {
            return;
        }


        const rowText =
            row.textContent
                .toLowerCase();


        const dateText =
            row.cells[6]
                .textContent
                .trim();


        let searchMatch =
            rowText.includes(searchValue);


        let dateMatch = true;


        if (dateText) {

            const rowDate =
                new Date(dateText);


            rowDate.setHours(0, 0, 0, 0);


            if (startDate) {

                const start =
                    new Date(startDate);

                start.setHours(0, 0, 0, 0);

                if (rowDate < start) {

                    dateMatch = false;

                }

            }


            if (endDate) {

                const end =
                    new Date(endDate);

                end.setHours(0, 0, 0, 0);

                if (rowDate > end) {

                    dateMatch = false;

                }

            }

        }


        row.style.display =
            searchMatch && dateMatch
                ? ''
                : 'none';

    });


    updateSerialNumbers();

}


/* =========================================================
   CLEAR FILTERS
========================================================= */

function clearFilters() {

    document
        .getElementById('txt_searchall')
        .value = '';


    document
        .getElementById('start_date')
        .value = '';


    document
        .getElementById('end_date')
        .value = '';


    applyFilters();

}


/* =========================================================
   SERIAL NUMBER
========================================================= */

function updateSerialNumbers() {

    const rows =
        document.querySelectorAll(
            '#payments-table tbody tr'
        );


    let counter = 1;


    rows.forEach(function(row) {

        if (
            row.style.display !== 'none'
        ) {

            const sr =
                row.querySelector('.inquiry-sr');


            if (sr) {

                sr.textContent =
                    counter++;

            }

        }

    });

}


/* =========================================================
   CLOSE ACTION MENU
========================================================= */

document.addEventListener(
    'click',
    function(event) {

        if (
            !event.target.closest(
                '.inquiry-action-cell'
            )
        ) {

            document
                .querySelectorAll('.action-buttons')
                .forEach(function(menu) {

                    menu.style.display = 'none';

                });


            document
                .querySelectorAll('.action-btn')
                .forEach(function(btn) {

                    btn.textContent = 'Action';

                });

        }

    }
);


/* =========================================================
   ESC
========================================================= */

document.addEventListener(
    'keydown',
    function(event) {

        if (event.key === 'Escape') {

            document
                .querySelectorAll('.action-buttons')
                .forEach(function(menu) {

                    menu.style.display = 'none';

                });

        }

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
