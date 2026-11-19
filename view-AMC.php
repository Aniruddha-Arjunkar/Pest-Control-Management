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


/* =========================================================
   BRANCH RESTRICTION
========================================================= */

$branch_filter = '';

if (
    $_SESSION['role'] !== 'super_admin'
    && !empty($_SESSION['branch'])
) {

    $branch = mysqli_real_escape_string(
        $conn,
        $_SESSION['branch']
    );

    $branch_filter = " AND c1.branch = '$branch' ";
}


/* =========================================================
   FETCH AMC CLIENTS
========================================================= */

$sql_clients = "
    SELECT
        GROUP_CONCAT(
            c1.services
            ORDER BY c1.services ASC
        ) AS services,

        c1.services,
        c1.cust_name,
        c1.cust_phone,
        c1.cust_address,

        a1.client_Id,

        MIN(
            IF(
                a1.status = 'pending',
                a1.service_date,
                NULL
            )
        ) AS next_pending_date

    FROM tbl_client c1

    INNER JOIN tbl_amc a1
        ON c1.Id = a1.client_Id

    WHERE a1.status = 'Pending'

    AND a1.client_Id NOT IN (
        SELECT DISTINCT client_Id
        FROM tbl_amc
        WHERE status = 'DEFAULTER'
    )

    $branch_filter

    GROUP BY a1.client_Id

    ORDER BY next_pending_date ASC
";


$result_clients = mysqli_query(
    $conn,
    $sql_clients
);


if (!$result_clients) {

    die(
        "Database Error: "
        . mysqli_error($conn)
    );

}


$total_records = mysqli_num_rows(
    $result_clients
);


/* =========================================================
   HEADER
========================================================= */

include 'header.php';

?>

<style>

/* =========================================================
   AMC PAGE
========================================================= */

.amc-page{
    width:100%;
}


/* =========================================================
   TITLE
========================================================= */

.amc-title{
    display:flex;
    align-items:center;
    gap:9px;
}

.amc-title-icon{
    font-size:24px;
}


/* =========================================================
   TODAY DATE
========================================================= */

.amc-today{
    background:#fee2e2 !important;
    color:#dc2626 !important;
    font-weight:700;
}


/* =========================================================
   PENDING DATE
========================================================= */

.amc-pending-date{
    font-weight:600;
}


/* =========================================================
   NO DATA
========================================================= */

.amc-no-data{
    text-align:center !important;
    padding:40px !important;
    color:#94a3b8 !important;
}


/* =========================================================
   SERVICE BUTTON
========================================================= */

.amc-view-btn{
    display:inline-flex;
    align-items:center;
    gap:5px;

    padding:7px 12px;

    border-radius:7px;

    background:#eef2ff;

    color:#4f46e5 !important;

    text-decoration:none !important;

    font-size:11px;

    font-weight:700;

    transition:.2s;
}

.amc-view-btn:hover{
    background:#e0e7ff;
}


/* =========================================================
   BOTTOM SCROLL
========================================================= */

.amc-bottom-scroll{
    width:100%;
    overflow-x:auto;
    height:12px;
    margin-top:5px;
}

.amc-bottom-scroll-inner{
    height:1px;
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:768px){

    .amc-page{
        width:100%;
    }

}

</style>


<body>

<section id="main-content">

<section class="wrapper">


<div class="view-clients-page amc-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="view-clients-header">


        <div class="view-clients-title-wrap">


            <h1 class="view-clients-title">


                <span class="view-clients-title-icon">
                    🛠️
                </span>


                AMC Clients


            </h1>


            <p class="view-clients-subtitle">

                View and manage all AMC clients

            </p>


        </div>


        <!-- =================================================
             RECORD COUNT
        ================================================== -->

        <span class="view-clients-count">

            <?= $total_records; ?>

            <?= (
                $total_records == 1
            )
                ? 'record'
                : 'records';
            ?>

        </span>


    </div>



    <!-- =====================================================
         MAIN CARD
    ====================================================== -->

    <div class="view-clients-card">


        <!-- =================================================
             FILTER
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
                    placeholder="Search AMC clients..."
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
                onclick="filterByDate()"
                class="view-client-search-btn"
            >

                🔍 Search

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
                id="payments-table"
            >


                <!-- =================================================
                     HEADER
                ================================================== -->

                <thead>

                    <tr>


                        <th>
                            Sr. No.
                        </th>


                        <th>
                            Service
                        </th>


                        <th>
                            Name
                        </th>


                        <th>
                            Phone
                        </th>


                        <th>
                            Address
                        </th>


                        <th>
                            Pending Service
                        </th>


                        <th>
                            Actions
                        </th>


                    </tr>

                </thead>



                <!-- =================================================
                     BODY
                ================================================== -->

                <tbody>


                <?php


                if (
                    $total_records > 0
                ) {


                    $i = 1;


                    while (
                        $row =
                        mysqli_fetch_assoc(
                            $result_clients
                        )
                    ) {


                        /* =============================================
                           CLIENT ID
                        ============================================== */

                        $client_id =
                            intval(
                                $row['client_Id']
                            );



                        /* =============================================
                           PENDING DATE
                        ============================================== */

                        $next_pending_date =
                            $row[
                                'next_pending_date'
                            ];


                        $next_pending_date_display =
                            $next_pending_date
                            ?
                            date(
                                'Y-m-d',
                                strtotime(
                                    $next_pending_date
                                )
                            )
                            :
                            'N/A';



                        /* =============================================
                           TODAY CHECK
                        ============================================== */

                        $todayClass = '';


                        if (
                            !empty(
                                $next_pending_date
                            )
                            &&
                            date(
                                'Y-m-d',
                                strtotime(
                                    $next_pending_date
                                )
                            )
                            ===
                            date('Y-m-d')
                        ) {

                            $todayClass =
                                'amc-today';

                        }



                        /* =============================================
                           ESCAPE DATA
                        ============================================== */

                        $services =
                            htmlspecialchars(
                                $row['services']
                                ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );


                        $name =
                            htmlspecialchars(
                                $row['cust_name']
                                ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );


                        $phone =
                            htmlspecialchars(
                                $row['cust_phone']
                                ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );


                        $address =
                            htmlspecialchars(
                                $row['cust_address']
                                ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );


                        $pendingDate =
                            htmlspecialchars(
                                $next_pending_date_display,
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>


                        <tr>


                            <!-- SR NO -->

                            <td
                                class="view-client-sr"
                            >

                                <?= $i++; ?>

                            </td>



                            <!-- SERVICE -->

                            <td
                                class="view-client-service"
                            >

                                <?= $services; ?>

                            </td>



                            <!-- NAME -->

                            <td
                                class="view-client-name"
                            >

                                <?= $name; ?>

                            </td>



                            <!-- PHONE -->

                            <td
                                class="view-client-phone"
                            >

                                <?= $phone; ?>

                            </td>



                            <!-- ADDRESS -->

                            <td
                                class="view-client-address"
                            >

                                <?= $address; ?>

                            </td>



                            <!-- PENDING DATE -->

                            <td
                                class="
                                    view-client-date-cell
                                    <?= $todayClass; ?>
                                "
                            >

                                <span
                                    class="amc-pending-date"
                                >

                                    <?= $pendingDate; ?>

                                </span>

                            </td>



                            <!-- ACTION -->

                            <td
                                class="view-client-action"
                            >


                                <a
                                    href="
                                        view-service-dates.php?client_id=
                                        <?= $client_id; ?>
                                    "
                                    class="view-client-view-btn"
                                >

                                    👁
                                    View Service Dates

                                </a>


                            </td>


                        </tr>


                        <?php


                    }


                }

                else {


                    ?>


                    <tr>

                        <td
                            colspan="7"
                            class="view-client-no-data amc-no-data"
                        >

                            No AMC clients found.

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
            class="view-client-bottom-scroll amc-bottom-scroll"
            id="amcBottomScroll"
        >

            <div
                class="view-client-bottom-scroll-inner amc-bottom-scroll-inner"
                id="amcBottomScrollInner"
            ></div>

        </div>


    </div>


</div>


</section>

</section>



<!-- =========================================================
     EXISTING JS
========================================================= -->

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>



<script>

/* =========================================================
   DATE FILTER
========================================================= */

function filterByDate()
{

    const startDate =
        document.getElementById(
            "start_date"
        ).value;


    const endDate =
        document.getElementById(
            "end_date"
        ).value;


    const rows =
        document.querySelectorAll(
            "#payments-table tbody tr"
        );


    rows.forEach(
        function(row)
        {


            if (
                row.querySelector(
                    ".amc-no-data"
                )
            ) {

                return;

            }


            const dateText =
                row.cells[5]
                    .textContent
                    .trim();


            if (
                !dateText
                ||
                dateText === "N/A"
            ) {

                row.style.display = "";

                return;

            }


            const rowDate =
                new Date(
                    dateText
                );


            let hide = false;


            if (
                startDate
                &&
                new Date(startDate)
                >
                rowDate
            ) {

                hide = true;

            }


            if (
                endDate
                &&
                new Date(endDate)
                <
                rowDate
            ) {

                hide = true;

            }


            row.style.display =
                hide
                ? "none"
                : "";

        }
    );


    updateSerialNumbers();

}



/* =========================================================
   SERIAL NUMBER
========================================================= */

function updateSerialNumbers()
{

    const rows =
        document.querySelectorAll(
            "#payments-table tbody tr"
        );


    let srNo = 1;


    rows.forEach(
        function(row)
        {


            if (
                row.style.display !== "none"
                &&
                !row.querySelector(
                    ".amc-no-data"
                )
            ) {


                const sr =
                    row.querySelector(
                        ".view-client-sr"
                    );


                if (sr) {

                    sr.textContent =
                        srNo++;

                }

            }

        }
    );

}



/* =========================================================
   LIVE SEARCH
========================================================= */

document
    .getElementById(
        "txt_searchall"
    )
    .addEventListener(
        "input",
        function()
        {


            const searchValue =
                this.value
                    .toLowerCase()
                    .trim();


            const rows =
                document.querySelectorAll(
                    "#payments-table tbody tr"
                );


            rows.forEach(
                function(row)
                {


                    if (
                        row.querySelector(
                            ".amc-no-data"
                        )
                    ) {

                        return;

                    }


                    const rowText =
                        row.textContent
                            .toLowerCase();


                    if (
                        rowText.includes(
                            searchValue
                        )
                    ) {

                        row.style.display =
                            "";

                    }

                    else {

                        row.style.display =
                            "none";

                    }

                }
            );


            updateSerialNumbers();

        }
    );



/* =========================================================
   BOTTOM SCROLLBAR SYNC
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function()
    {


        const bottomScroll =
            document.getElementById(
                "amcBottomScroll"
            );


        const bottomInner =
            document.getElementById(
                "amcBottomScrollInner"
            );


        const tableWrapper =
            document.getElementById(
                "scrollable-content"
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


        function updateScrollWidth()
        {

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
            function()
            {

                tableWrapper.scrollLeft =
                    bottomScroll.scrollLeft;

            }
        );


        /* TABLE -> BOTTOM */

        tableWrapper.addEventListener(
            "scroll",
            function()
            {

                bottomScroll.scrollLeft =
                    tableWrapper.scrollLeft;

            }
        );


    }
);

</script>


</body>

</html>


<?php

mysqli_close($conn);

?>