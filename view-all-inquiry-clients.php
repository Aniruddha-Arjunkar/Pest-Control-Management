<?php

session_start();


// =========================================================
// LOGIN CHECK
// =========================================================

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {

    $_SESSION['msg'] = "You must log in first";

    header('location:login.php');

    exit();

}


// =========================================================
// LOGOUT
// =========================================================

if (isset($_GET['logout'])) {

    session_destroy();

    unset($_SESSION['email']);

    header('location:login.php');

    exit();

}


// =========================================================
// DATABASE
// =========================================================

require_once 'db_connect.php';

include 'header.php';


// =========================================================
// WHERE CONDITION
// =========================================================

$where = "WHERE (
    follow_up_date IS NOT NULL
    OR visit_date IS NOT NULL
    OR service_date IS NOT NULL
)";


// =========================================================
// TOTAL RECORDS
// =========================================================

$count_sql = "
    SELECT COUNT(*) AS total
    FROM tbl_inquiry
    $where
";

$count_result = mysqli_query($conn, $count_sql);

$total_records = 0;

if ($count_result) {

    $count_row = mysqli_fetch_assoc($count_result);

    $total_records = (int)$count_row['total'];

}


// =========================================================
// GET ALL FOLLOWUP RECORDS
// =========================================================

$sql = "
    SELECT
        Id,
        services,
        cust_name,
        cust_phone,
        cust_message,
        follow_up_date,
        visit_date,
        service_date
    FROM tbl_inquiry
    $where
    ORDER BY Id DESC
";

$result = mysqli_query($conn, $sql);

?>


<body>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<section id="main-content">

    <section class="wrapper">


        <!-- =================================================
             INQUIRY PAGE
        ================================================== -->

        <div class="inquiry-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="inquiry-page-header">

                <h1>
                    📋 View All Followup
                </h1>

                <p>

                    Showing all followup records

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
                     TOP AREA
                ================================================== -->

                <div class="followup-top-area">


                    <!-- =================================================
                         SEARCH
                    ================================================== -->

                    <div class="inquiry-search-wrapper">

                        <span class="inquiry-search-icon">
                            🔍
                        </span>


                        <input
                            type="text"
                            id="txt_searchall"
                            class="inquiry-search"
                            placeholder="Search by name, phone, service or message..."
                            autocomplete="off"
                        >


                    </div>


                    <!-- =================================================
                         QUICK LINKS
                    ================================================== -->

                    <div class="followup-page-links">


                        <a
                            href="view-re-followup.php"
                            class="followup-link purple"
                        >
                            View Re-Followup
                        </a>


                        <a
                            href="view-visit.php"
                            class="followup-link green"
                        >
                            View Visits
                        </a>


                        <a
                            href="future-clients.php"
                            class="followup-link orange"
                        >
                            Future Clients
                        </a>


                    </div>


                </div>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div
                    class="inquiry-table-wrapper refollowup-table-wrapper"
                    id="scrollable-content"
                >


                    <table
                        class="inquiry-table"
                        id="payments-table"
                    >


                        <!-- =================================================
                             TABLE HEADER
                        ================================================== -->

                        <thead>

                            <tr>

                                <th>
                                    SR. NO.
                                </th>

                                <th>
                                    SERVICES
                                </th>

                                <th>
                                    NAME
                                </th>

                                <th style="min-width: 50px;">
                                    PHONE
                                </th>

                                <th>
                                    MESSAGE
                                </th>

                                <th>
                                    FOLLOW UP DATE
                                </th>

                                <th>
                                    VISIT DATE
                                </th>

                                <th>
                                    SERVICE DATE
                                </th>

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <!-- =================================================
                             TABLE BODY
                        ================================================== -->

                        <tbody>


                        <?php

                        $sr_no = 1;


                        if (
                            $result &&
                            mysqli_num_rows($result) > 0
                        ) {


                            while (
                                $row = mysqli_fetch_assoc($result)
                            ) {


                                // =================================================
                                // ID
                                // =================================================

                                $id = (int)$row['Id'];


                                // =================================================
                                // SERVICES
                                // =================================================

                                $services = htmlspecialchars(
                                    $row['services'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                // =================================================
                                // NAME
                                // =================================================

                                $customer_name = htmlspecialchars(
                                    $row['cust_name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                // =================================================
                                // PHONE
                                // =================================================

                                $customer_phone = htmlspecialchars(
                                    $row['cust_phone'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                // =================================================
                                // MESSAGE
                                // =================================================

                                $customer_message = htmlspecialchars(
                                    $row['cust_message'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                // =================================================
                                // FOLLOW UP DATE
                                // =================================================

                                $follow_up_date = htmlspecialchars(
                                    $row['follow_up_date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                // =================================================
                                // VISIT DATE
                                // =================================================

                                $visit_date = htmlspecialchars(
                                    $row['visit_date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                // =================================================
                                // SERVICE DATE
                                // =================================================

                                $service_date = htmlspecialchars(
                                    $row['service_date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                        ?>


                            <!-- =================================================
                                 DATA ROW
                            ================================================== -->

                            <tr class="data-row">


                                <!-- SR NO -->

                                <td class="inquiry-sr">

                                    <?= $sr_no++; ?>

                                </td>


                                <!-- SERVICES -->

                                <td class="inquiry-service">

                                    <?= $services; ?>

                                </td>


                                <!-- NAME -->

                                <td class="inquiry-name">

                                    <?= $customer_name; ?>

                                </td>


                                <!-- PHONE -->

                                <td class="inquiry-phone" style="min-width: 50px;">

                                    <?= $customer_phone; ?>

                                </td>


                                <!-- MESSAGE -->

                                <td class="inquiry-message">

                                    <?= $customer_message; ?>

                                </td>


                                <!-- FOLLOW UP DATE -->

                                <td class="inquiry-date">

                                    <?= $follow_up_date; ?>

                                </td>


                                <!-- VISIT DATE -->

                                <td class="inquiry-date">

                                    <?= $visit_date; ?>

                                </td>


                                <!-- SERVICE DATE -->

                                <td class="inquiry-date">

                                    <?= $service_date; ?>

                                </td>


                                <!-- ACTION -->

                                <td class="inquiry-action-cell">


                                    <!-- ACTION BUTTON -->

                                    <button
                                        type="button"
                                        class="action-btn"
                                        onclick="toggleActions(this)"
                                    >

                                        Action

                                    </button>


                                    <!-- ACTION MENU -->

                                    <div
                                        class="action-buttons"
                                        style="display:none;"
                                    >


                                        <!-- =================================================
                                             RE FOLLOWUP
                                        ================================================== -->

                                        <button
                                            type="button"
                                            class="primary1"
                                        >

                                            <a
                                                href="re-followup1.php?id=<?= $id; ?>"
                                            >

                                                Re-followup

                                            </a>

                                        </button>


                                        <!-- =================================================
                                             VISIT
                                        ================================================== -->

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


                                        <!-- =================================================
                                             PROCEED SERVICE
                                        ================================================== -->

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


                                        <!-- =================================================
                                             SCHEDULE SERVICE
                                        ================================================== -->

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


                                        <!-- =================================================
                                             DELETE
                                        ================================================== -->

                                        <button
                                            type="button"
                                            class="primary3"
                                        >

                                            <a
                                                href="delete.php?id=<?= $id; ?>"
                                                onclick="return confirm('Are you sure you want to delete this followup?');"
                                            >

                                                Delete

                                            </a>

                                        </button>


                                        <!-- =================================================
                                             SEND QUOTATION
                                        ================================================== -->

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


                            <!-- =================================================
                                 NO DATABASE RECORD
                            ================================================== -->

                            <tr class="no-data-row">

                                <td
                                    colspan="9"
                                    class="no-inquiry"
                                >

                                    No followup records found.

                                </td>

                            </tr>


                        <?php

                        }

                        ?>


                            <!-- =================================================
                                 SEARCH NO RESULT
                            ================================================== -->

                            <tr
                                id="search-no-result"
                                style="display:none;"
                            >

                                <td
                                    colspan="9"
                                    class="no-inquiry"
                                >

                                    No matching records found.

                                </td>

                            </tr>


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


    const container =
        button.nextElementSibling;


    // =====================================================
    // CLOSE OTHER MENUS
    // =====================================================

    document
        .querySelectorAll('.action-buttons')
        .forEach(function(menu) {

            if (menu !== container) {

                menu.style.display = 'none';

            }

        });


    // =====================================================
    // RESET OTHER BUTTONS
    // =====================================================

    document
        .querySelectorAll('.action-btn')
        .forEach(function(btn) {

            if (btn !== button) {

                btn.textContent = 'Action';

            }

        });


    // =====================================================
    // TOGGLE CURRENT MENU
    // =====================================================

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

</script>


<!-- =====================================================
     CLOSE ACTION MENU OUTSIDE CLICK
===================================================== -->

<script>

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
                .forEach(function(button) {

                    button.textContent = 'Action';

                });

        }

    }
);

</script>


<!-- =====================================================
     ESC KEY
===================================================== -->

<script>

document.addEventListener(
    'keydown',
    function(event) {


        if (event.key === 'Escape') {


            document
                .querySelectorAll('.action-buttons')
                .forEach(function(menu) {

                    menu.style.display = 'none';

                });


            document
                .querySelectorAll('.action-btn')
                .forEach(function(button) {

                    button.textContent = 'Action';

                });

        }

    }
);

</script>


<!-- =====================================================
     LIVE TABLE SEARCH
===================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function() {


        // =================================================
        // SEARCH INPUT
        // =================================================

        const searchInput =
            document.getElementById(
                'txt_searchall'
            );


        // =================================================
        // TABLE
        // =================================================

        const table =
            document.getElementById(
                'payments-table'
            );


        // =================================================
        // NO RESULT ROW
        // =================================================

        const noResult =
            document.getElementById(
                'search-no-result'
            );


        // =================================================
        // SAFETY CHECK
        // =================================================

        if (
            !searchInput ||
            !table
        ) {

            return;

        }


        // =================================================
        // SEARCH EVENT
        // =================================================

        searchInput.addEventListener(
            'input',
            function() {


                const searchValue =
                    this.value
                        .toLowerCase()
                        .trim();


                let visibleRows = 0;


                // =============================================
                // GET DATA ROWS ONLY
                // =============================================

                const rows =
                    table.querySelectorAll(
                        'tbody tr.data-row'
                    );


                // =============================================
                // LOOP THROUGH ROWS
                // =============================================

                rows.forEach(
                    function(row) {


                        const rowText =
                            row.textContent
                                .toLowerCase();


                        // =====================================
                        // MATCH
                        // =====================================

                        if (
                            searchValue === '' ||
                            rowText.includes(searchValue)
                        ) {


                            row.style.display =
                                '';


                            visibleRows++;


                        } else {


                            row.style.display =
                                'none';

                        }

                    }
                );


                // =============================================
                // NO SEARCH RESULT
                // =============================================

                if (
                    searchValue !== '' &&
                    visibleRows === 0
                ) {

                    noResult.style.display =
                        'table-row';

                } else {

                    noResult.style.display =
                        'none';

                }

            }
        );


    }
);

</script>


<!-- =====================================================
     TABLE HORIZONTAL SCROLL
===================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function() {


        const dummyScroll =
            document.querySelector(
                '.scroll-sync-wrapper'
            );


        const realScroll =
            document.querySelector(
                '#scrollable-content'
            );


        if (
            !dummyScroll ||
            !realScroll
        ) {

            return;

        }


        const table =
            document.querySelector(
                '.inquiry-table'
            );


        const dummy =
            dummyScroll.querySelector(
                '.dummy-scrollbar'
            );


        // =================================================
        // SET DUMMY SCROLL WIDTH
        // =================================================

        if (
            table &&
            dummy
        ) {

            dummy.style.width =
                table.scrollWidth + 'px';

        }


        // =================================================
        // DUMMY → TABLE
        // =================================================

        dummyScroll.addEventListener(
            'scroll',
            function() {

                realScroll.scrollLeft =
                    dummyScroll.scrollLeft;

            }
        );


        // =================================================
        // TABLE → DUMMY
        // =================================================

        realScroll.addEventListener(
            'scroll',
            function() {

                dummyScroll.scrollLeft =
                    realScroll.scrollLeft;

            }
        );


    }
);

</script>


<!-- =====================================================
     JQUERY
===================================================== -->


<script
    src="https://code.jquery.com/jquery-3.7.1.min.js">
</script>


<!-- =====================================================
     EXISTING JS FILES
===================================================== -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>


<!--[if lte IE 8]>

<script
    language="javascript"
    type="text/javascript"
    src="js/flot-chart/excanvas.min.js">
</script>

<![endif]-->


<script src="js/jquery.scrollTo.js"></script>

<script src="js/search.js"></script>


</body>

</html>
