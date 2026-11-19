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

    $branch_filter = " AND c.branch = '$branch' ";
}


/* =========================================================
   DATE FILTER
========================================================= */

$fromDate = $_POST['fromDate'] ?? '';
$endDate  = $_POST['endDate'] ?? '';


/* =========================================================
   MAIN QUERY
========================================================= */

$sql_pending_services = "
    SELECT
        ps.*,
        c.branch,
        c.cust_name AS client_name,
        c.services,
        c.cust_phone,
        c.cust_address,
        ps.description

    FROM tbl_pending_services ps

    JOIN tbl_client c
        ON ps.client_Id = c.Id

    WHERE ps.status = 'Pending'

    $branch_filter
";


/* =========================================================
   DATE SEARCH
========================================================= */

if (
    isset($_POST['but_search'])
    && !empty($fromDate)
    && !empty($endDate)
) {

    $safeFromDate = mysqli_real_escape_string(
        $conn,
        $fromDate
    );

    $safeEndDate = mysqli_real_escape_string(
        $conn,
        $endDate
    );

    $sql_pending_services .= "
        AND ps.service_date
        BETWEEN '$safeFromDate'
        AND '$safeEndDate'
    ";
}


/* =========================================================
   ORDER
========================================================= */

$sql_pending_services .= "
    ORDER BY ps.service_date DESC
";


$result_pending_services = mysqli_query(
    $conn,
    $sql_pending_services
);


if (!$result_pending_services) {
    die(
        "Database Error: "
        . mysqli_error($conn)
    );
}


/* =========================================================
   TOTAL RECORDS
========================================================= */

$total_records = mysqli_num_rows(
    $result_pending_services
);


/* =========================================================
   HEADER
========================================================= */

include 'header.php';

?>

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
                            🕐
                        </span>

                        Pending Services

                    </h1>

                    <p class="view-clients-subtitle">
                        View and manage all pending services
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
                     FILTER FORM
                ================================================== -->

                <form
                    method="post"
                    action=""
                    class="view-clients-filter"
                >


                    <!-- SEARCH -->

                    <div class="view-client-search-wrap">

                        <span class="view-client-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            id="txt_searchall"
                            class="view-client-search"
                            placeholder="Search pending services..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- FROM DATE -->

                    <input
                        type="date"
                        name="fromDate"
                        id="start_date"
                        class="view-client-date"
                        value="<?= htmlspecialchars($fromDate); ?>"
                    >


                    <!-- END DATE -->

                    <input
                        type="date"
                        name="endDate"
                        id="end_date"
                        class="view-client-date"
                        value="<?= htmlspecialchars($endDate); ?>"
                    >


                    <!-- SEARCH BUTTON -->

                    <button
                        type="submit"
                        name="but_search"
                        class="view-client-search-btn"
                    >
                        🔍 Search
                    </button>

                </form>


                <!-- =================================================
                     TABLE WRAPPER
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
                                    Branch
                                </th>

                                <th>
                                    Client Name
                                </th>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Phone
                                </th>

                                <th>
                                    Address
                                </th>

                                <th>
                                    Service Date
                                </th>

                                <th>
                                    Description
                                </th>

                                <th  class="view-client-action">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        if ($total_records > 0) {

                            $i = 1;

                            while (
                                $service =
                                mysqli_fetch_assoc(
                                    $result_pending_services
                                )
                            ) {


                                /* =================================================
                                   ESCAPE VALUES
                                ================================================= */

                                $branchName = htmlspecialchars(
                                    $service['branch'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                $clientName = htmlspecialchars(
                                    $service['client_name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                $services = htmlspecialchars(
                                    $service['services'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                $phone = htmlspecialchars(
                                    $service['cust_phone'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                $address = htmlspecialchars(
                                    $service['cust_address'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                $serviceDate = htmlspecialchars(
                                    $service['service_date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                $description = htmlspecialchars(
                                    $service['description'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                /* =================================================
                                   BRANCH COLOR
                                ================================================= */

                                $branchClass = 'blue';

                                $branchLower = strtolower(
                                    trim(
                                        $service['branch'] ?? ''
                                    )
                                );


                                if (
                                    $branchLower === 'nagpur'
                                ) {

                                    $branchClass = 'blue';

                                }

                                elseif (
                                    $branchLower === 'bramhapuri'
                                ) {

                                    $branchClass = 'green';

                                }

                                elseif (
                                    $branchLower === 'chandrapur'
                                    ||
                                    $branchLower === 'chandreapur'
                                ) {

                                    $branchClass = 'orange';

                                }

                                else {

                                    $branchClass = 'blue';

                                }


                                /* =================================================
                                   TODAY CHECK
                                ================================================= */

                                $todayClass = '';

                                if (
                                    !empty($service['service_date'])
                                    &&
                                    date(
                                        'Y-m-d',
                                        strtotime(
                                            $service['service_date']
                                        )
                                    )
                                    ==
                                    date('Y-m-d')
                                ) {

                                    $todayClass = 'today';

                                }

                                ?>

                                <tr>


                                    <!-- SR NO -->

                                    <td class="view-client-sr">

                                        <?= $i++; ?>

                                    </td>


                                    <!-- BRANCH -->

                                    <td>

                                        <span
                                            class="
                                                view-client-branch
                                                <?= $branchClass; ?>
                                            "
                                        >

                                            <?= $branchName; ?>

                                        </span>

                                    </td>


                                    <!-- CLIENT NAME -->

                                    <td class="view-client-name">

                                        <?= $clientName; ?>

                                    </td>


                                    <!-- SERVICE -->

                                    <td class="view-client-service">

                                        <?= $services; ?>

                                    </td>


                                    <!-- PHONE -->

                                    <td class="view-client-phone">

                                        <?= $phone; ?>

                                    </td>


                                    <!-- ADDRESS -->

                                    <td class="view-client-address">

                                        <?= $address; ?>

                                    </td>


                                    <!-- SERVICE DATE -->

                                    <td
                                        class="
                                            view-client-date-cell
                                            <?= $todayClass; ?>
                                        "
                                    >

                                        <?= $serviceDate; ?>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td class="view-client-address">

                                        <?= $description; ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td class="view-client-action">

                                        <a
                                            href="view-pending-service-detail.php?id=<?= intval($service['Id']); ?>"
                                            class="view-client-view-btn"
                                        >

                                            👁 View

                                        </a>

                                    </td>


                                </tr>

                                <?php

                            }

                        } else {

                            ?>

                            <tr>

                                <td
                                    colspan="9"
                                    class="view-client-no-data"
                                >

                                    No pending services found.

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
   DATE FILTER
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const startDate =
            document.getElementById(
                "start_date"
            );

        const endDate =
            document.getElementById(
                "end_date"
            );

        const table =
            document.getElementById(
                "payments-table"
            );


        if (
            !startDate ||
            !endDate ||
            !table
        ) {
            return;
        }


        function updateSerialNumbers() {

            const rows =
                table.querySelectorAll(
                    "tbody tr"
                );

            let serial = 1;


            rows.forEach(
                function (row) {

                    if (
                        row.style.display !==
                        "none"
                    ) {

                        const sr =
                            row.querySelector(
                                ".view-client-sr"
                            );


                        if (sr) {

                            sr.textContent =
                                serial++;

                        }

                    }

                }
            );

        }


        window.filterByDate =
            function () {

                const from =
                    startDate.value;

                const to =
                    endDate.value;


                const rows =
                    table.querySelectorAll(
                        "tbody tr"
                    );


                rows.forEach(
                    function (row) {

                        const dateCell =
                            row.querySelector(
                                ".view-client-date-cell"
                            );


                        if (!dateCell) {
                            return;
                        }


                        const dateText =
                            dateCell.textContent
                                .trim();


                        if (!dateText) {
                            return;
                        }


                        const rowDate =
                            new Date(
                                dateText
                            );


                        let hide = false;


                        if (
                            from &&
                            rowDate <
                            new Date(from)
                        ) {

                            hide = true;

                        }


                        if (
                            to &&
                            rowDate >
                            new Date(to)
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

            };

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


</body>
</html>

<?php

mysqli_close($conn);

?>