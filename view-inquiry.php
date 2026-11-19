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


// =========================================================
// TOTAL INQUIRY COUNT
// =========================================================

$count_sql = "
    SELECT COUNT(*) AS total
    FROM tbl_inquiry
    WHERE follow_up_date IS NULL
    AND visit_date IS NULL
";

$count_result = mysqli_query($conn, $count_sql);

$total_inquiries = 0;

if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_inquiries = $count_row['total'];
}


// =========================================================
// GET INQUIRIES
// =========================================================

$sql = "
    SELECT *
    FROM tbl_inquiry
    WHERE follow_up_date IS NULL
    AND visit_date IS NULL
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
                    💬 View Inquiry
                </h1>

                <p>
                    Showing all customer inquiries

                    <span class="inquiry-count">
                        <?= $total_inquiries; ?> Records
                    </span>
                </p>

            </div>


            <!-- =================================================
                 CARD
            ================================================== -->

            <div class="inquiry-card">


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
                        placeholder="Search by name, phone or message..."
                        autocomplete="off"
                    >

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


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>
                                    SR. NO.
                                </th>

                                <th>
                                    NAME
                                </th>

                                <th>
                                    PHONE
                                </th>

                                <th>
                                    MESSAGE
                                </th>

                                <th>
                                    INQUIRY DATE
                                </th>

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody>

                        <?php

                        $sr_no = 1;

                        if ($result && mysqli_num_rows($result) > 0) {

                            while ($row = mysqli_fetch_assoc($result)) {

                                $customer_name =
                                    htmlspecialchars(
                                        $row['cust_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $customer_phone =
                                    htmlspecialchars(
                                        $row['cust_phone'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $customer_message =
                                    htmlspecialchars(
                                        $row['cust_message'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $inquiry_date =
                                    htmlspecialchars(
                                        $row['inq_date'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                $inquiry_id =
                                    (int)$row['Id'];

                        ?>


                            <tr>


                                <!-- SERIAL NUMBER -->

                                <td class="inquiry-sr">

                                    <?= $sr_no++; ?>

                                </td>


                                <!-- NAME -->

                                <td class="inquiry-name">

                                    <?= $customer_name; ?>

                                </td>


                                <!-- PHONE -->

                                <td class="inquiry-phone">

                                    <?= $customer_phone; ?>

                                </td>


                                <!-- MESSAGE -->

                                <td class="inquiry-message">

                                    <?= $customer_message; ?>

                                </td>


                                <!-- DATE -->

                                <td class="inquiry-date">

                                    <?= $inquiry_date; ?>

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


                                        <!-- RE-FOLLOWUP -->

                                        <button
                                            type="button"
                                            class="primary1"
                                        >

                                            <a
                                                href="re-followup.php?id=<?= $inquiry_id; ?>"
                                            >
                                                Re-followup
                                            </a>

                                        </button>


                                        <!-- VISIT -->

                                        <button
                                            type="button"
                                            class="primary2"
                                        >

                                            <a
                                                href="visit.php?id=<?= $inquiry_id; ?>"
                                            >
                                                Visit
                                            </a>

                                        </button>


                                        <!-- DELETE -->

                                        <button
                                            type="button"
                                            class="primary3"
                                        >

                                            <a
                                                href="delete.php?id=<?= $inquiry_id; ?>"
                                                onclick="return confirm('Are you sure you want to delete this inquiry?');"
                                            >
                                                Delete
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
                                    colspan="6"
                                    class="no-inquiry"
                                >
                                    No inquiries found.
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
     BOTTOM HORIZONTAL SCROLL
===================================================== -->

<div class="scroll-sync-wrapper">

    <div class="dummy-scrollbar"></div>

</div>


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


    /*
     * Close all other action menus
     */

    document
        .querySelectorAll('.action-buttons')
        .forEach(function(menu) {

            if (menu !== container) {

                menu.style.display = 'none';

            }

        });


    /*
     * Toggle current menu
     */

    if (
        container.style.display === "none" ||
        container.style.display === ""
    ) {

        container.style.display = "flex";

        button.textContent = "Actions";

    } else {

        container.style.display = "none";

        button.textContent = "Action";

    }

}


/* =========================================================
   CLOSE ACTION MENU WHEN CLICK OUTSIDE
========================================================= */

document.addEventListener(
    'click',
    function(event) {

        if (
            !event.target.closest('.inquiry-action-cell')
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


/* =========================================================
   SEARCH
========================================================= */

const searchInput =
    document.getElementById('txt_searchall');

if (searchInput) {

    searchInput.addEventListener(
        'keyup',
        function() {

            const searchValue =
                this.value
                    .toLowerCase()
                    .trim();


            const rows =
                document.querySelectorAll(
                    '#payments-table tbody tr'
                );


            rows.forEach(function(row) {

                const rowText =
                    row.textContent
                        .toLowerCase();


                if (
                    rowText.includes(searchValue)
                ) {

                    row.style.display = '';

                } else {

                    row.style.display = 'none';

                }

            });

        }
    );

}


/* =========================================================
   SYNCHRONIZED BOTTOM SCROLLBAR
========================================================= */

const dummyScroll =
    document.querySelector(
        '.scroll-sync-wrapper'
    );

const realScroll =
    document.querySelector(
        '#scrollable-content'
    );


if (dummyScroll && realScroll) {


    /*
     * Dummy scrollbar → Table
     */

    dummyScroll.addEventListener(
        'scroll',
        function() {

            realScroll.scrollLeft =
                dummyScroll.scrollLeft;

        }
    );


    /*
     * Table → Dummy scrollbar
     */

    realScroll.addEventListener(
        'scroll',
        function() {

            dummyScroll.scrollLeft =
                realScroll.scrollLeft;

        }
    );

}


/* =========================================================
   CLOSE MENU ON ESC
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
