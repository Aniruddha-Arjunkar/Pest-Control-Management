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
   EXCEL EXPORT
   ========================================================= */

   if (isset($_POST['download_excel'])) {

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=clients_data.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "Sr. No.\tBranch\tService\tName\tPhone\tAddress\tService Date\n";

    $sql = "SELECT * FROM tbl_client WHERE 1";


    /* =====================================================
       BRANCH RESTRICTION
       ===================================================== */

       if (
        $_SESSION['role'] !== 'super_admin'
        && !empty($_SESSION['branch'])
    ) {

         $branches = explode(',', $_SESSION['branch']);
         $branchCondition = [];

         foreach ($branches as $b) {
            $b = mysqli_real_escape_string($conn, trim($b));

            if ($b !== '') {
                $branchCondition[] = "branch = '$b'";
            }
        }

        if (!empty($branchCondition)) {
            $sql .= " AND (" . implode(" OR ", $branchCondition) . ")";
        }
    }


    /* =====================================================
       DATE FILTER
       ===================================================== */

       $fromDate = $_POST['fromDate'] ?? '';
       $endDate  = $_POST['endDate'] ?? '';

       if (
        !empty($fromDate)
        && !empty($endDate)
    ) {

        $fromDate = mysqli_real_escape_string(
            $conn,
            $fromDate
        );

        $endDate = mysqli_real_escape_string(
            $conn,
            $endDate
        );

        $sql .= " AND due_date BETWEEN '$fromDate' AND '$endDate'";
    }


    $sql .= " ORDER BY due_date ASC";


    $result = mysqli_query($conn, $sql);

    $i = 1;


    while ($row = mysqli_fetch_assoc($result)) {

        echo $i++ . "\t" .
        $row['branch'] . "\t" .
        $row['services'] . "\t" .
        $row['cust_name'] . "\t" .
        $row['cust_phone'] . "\t" .
        $row['cust_address'] . "\t" .
        $row['due_date'] . "\n";
    }

    exit();
}


/* =========================================================
   GET CLIENT ID
   ========================================================= */

   $client_id = isset($_GET['client_id'])
   ? intval($_GET['client_id'])
   : 0;


/* =========================================================
   MAIN QUERY
   ========================================================= */

   $sql = "SELECT * FROM tbl_client WHERE 1";


/* =========================================================
   CLIENT ID
   ========================================================= */

   if ($client_id > 0) {

    $sql .= " AND Id = '$client_id'";
}


/* =========================================================
   BRANCH RESTRICTION
   ========================================================= */

   if (
    $_SESSION['role'] !== 'super_admin'
    && !empty($_SESSION['branch'])
) {

    $branches = explode(',', $_SESSION['branch']);
    $branchCondition = [];

    foreach ($branches as $b) {
        $b = mysqli_real_escape_string($conn, trim($b));

        if ($b !== '') {
            $branchCondition[] = "branch = '$b'";
        }
    }

    if (!empty($branchCondition)) {
        $sql .= " AND (" . implode(" OR ", $branchCondition) . ")";
    }
}


/* =========================================================
   DATE FILTER
   ========================================================= */

   $fromDate = $_POST['fromDate'] ?? '';
   $endDate  = $_POST['endDate'] ?? '';

   if (
    isset($_POST['but_search'])
    && !empty($fromDate)
    && !empty($endDate)
) {

    $fromDate = mysqli_real_escape_string(
        $conn,
        $fromDate
    );

    $endDate = mysqli_real_escape_string(
        $conn,
        $endDate
    );

    $sql .= " AND due_date BETWEEN '$fromDate' AND '$endDate'";
}


/* =========================================================
   ORDER
   ========================================================= */

   $sql .= " ORDER BY due_date DESC";


   $result = mysqli_query($conn, $sql);

   if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}


/* =========================================================
   TOTAL RECORDS
   ========================================================= */

   $total_records = mysqli_num_rows($result);


/* =========================================================
   HEADER
   ========================================================= */

   include 'header.php';

   ?>
   <style>
    .view-client-whatsapp-btn {
        display: inline-block;
        margin-left: 5px;
        padding: 7px 12px;
        background: #25D366;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
    }

    .view-client-whatsapp-btn:hover {
        background: #1ebe5d;
        color: #fff;
    }

</style>

<body>

    <section id="main-content">

        <section class="wrapper">

        <!-- =================================================
             VIEW CLIENTS PAGE
        ================================================== -->

        <div class="view-clients-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="view-clients-header">

                <div class="view-clients-title-wrap">

                    <h1 class="view-clients-title">

                        <span class="view-clients-title-icon">
                            👥
                        </span>

                        View Clients

                    </h1>


                    <p class="view-clients-subtitle">
                        View and manage all clients
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
                    placeholder="Search clients..."
                    autocomplete="off"
                    >

                </div>


                <!-- FROM DATE -->

                <input
                type="date"
                name="fromDate"
                class="view-client-date"
                value="<?= htmlspecialchars($fromDate); ?>"
                >


                <!-- END DATE -->

                <input
                type="date"
                name="endDate"
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


            <!-- EXCEL -->

            <button
            type="submit"
            name="download_excel"
            class="view-client-excel-btn"
            >
            📊 Download Excel
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

                        <!-- =================================================
                             TABLE HEADER
                        ================================================== -->

                        <thead>

                            <tr>

                                <th>
                                    Sr. No.
                                </th>

                                <th>
                                    Branch
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
                                    Service Date
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <!-- =================================================
                             TABLE BODY
                        ================================================== -->

                        <tbody>

                            <?php

                            if ($total_records > 0) {

                                $i = 1;


                                while ($row = mysqli_fetch_assoc($result)) {


                                /* =================================================
                                   ESCAPE VALUES
                                   ================================================= */

                                   $branchName = htmlspecialchars(
                                    $row['branch'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                   $service = htmlspecialchars(
                                    $row['services'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                   $name = htmlspecialchars(
                                    $row['cust_name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                   $phone = htmlspecialchars(
                                    $row['cust_phone'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                   $address = htmlspecialchars(
                                    $row['cust_address'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                   $due_date = htmlspecialchars(
                                    $row['due_date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                                /* =================================================
                                   BRANCH COLOR
                                   ================================================= */

                                   $branchClass = 'blue';

                                   $branchLower = strtolower(
                                    trim(
                                        $row['branch'] ?? ''
                                    )
                                );


                                /*
                                 * NAGPUR
                                 */

                                if (
                                    $branchLower === 'nagpur'
                                ) {

                                    $branchClass = 'blue';

                                }


                                /*
                                 * BRAMHAPURI
                                 */

                                elseif (
                                    $branchLower === 'bramhapuri'
                                ) {

                                    $branchClass = 'green';

                                }


                                /*
                                 * CHANDRAPUR
                                 * Chandreapur spelling bhi support
                                 */

                                elseif (
                                    $branchLower === 'chandrapur'
                                    || $branchLower === 'chandreapur'
                                ) {

                                    $branchClass = 'orange';

                                }


                                /*
                                 * DEFAULT
                                 */

                                else {

                                    $branchClass = 'blue';

                                }


                                /* =================================================
                                   TODAY CHECK
                                   ================================================= */

                                   $todayClass = '';

                                   if (
                                    !empty($row['due_date'])
                                    &&
                                    date(
                                        'Y-m-d',
                                        strtotime(
                                            $row['due_date']
                                        )
                                    )
                                    ==
                                    date('Y-m-d')
                                ) {

                                    $todayClass = 'today';

                                }

                                ?>

                                <tr>


                                    <!-- =================================================
                                         SR NO
                                    ================================================== -->

                                    <td class="view-client-sr">

                                        <?= $i++; ?>

                                    </td>


                                    <!-- =================================================
                                         BRANCH
                                    ================================================== -->

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


                                    <!-- =================================================
                                         SERVICE
                                    ================================================== -->

                                    <td class="view-client-service">

                                        <?= $service; ?>

                                    </td>


                                    <!-- =================================================
                                         NAME
                                    ================================================== -->

                                    <td class="view-client-name">

                                        <?= $name; ?>

                                    </td>


                                    <!-- =================================================
                                         PHONE
                                    ================================================== -->

                                    <td class="view-client-phone">

                                        <?= $phone; ?>

                                    </td>


                                    <!-- =================================================
                                         ADDRESS
                                    ================================================== -->

                                    <td class="view-client-address">

                                        <?= $address; ?>

                                    </td>


                                    <!-- =================================================
                                         SERVICE DATE
                                    ================================================== -->

                                    <td
                                    class="
                                    view-client-date-cell
                                    <?= $todayClass; ?>
                                    "
                                    >

                                    <?= $due_date; ?>

                                </td>


                                    <!-- =================================================
                                         ACTION
                                    ================================================== -->

                                    <td class="view-client-action">

                                        <a
                                        href="view-allclient-details.php?client_id=<?= intval($row['Id']); ?>"
                                        class="view-client-view-btn"
                                        >
                                        👁 View
                                    </a>

                                    <?php

// Customer phone number
                                    $whatsappPhone = preg_replace(
                                        '/[^0-9]/',
                                        '',
                                        $row['cust_phone'] ?? ''
                                    );

// India number ke liye 91 add karna
                                    if (strlen($whatsappPhone) == 10) {
                                        $whatsappPhone = '91' . $whatsappPhone;
                                    }


// WhatsApp Message
                                    $whatsappMessage = <<<MSG
                                    Dear Sir/Ma'am.

                                    *Greetings from Shul Ventures Pvt. Ltd.!!!*

                                    *Pest Control Services.*

                                    We provide service to control Termite 🐜, Ants 🐜, Spiders 🕷️, Cockroach 🪳, Rats 🐁, Mosquitoes 🦟, Bedbugs & other Pest for Residential & Commercial spaces (Factories, Hotel, Hospitals, Schools etc.).

                                    ✅ Professional & Hygienic Service
                                    ✅ Odourless chemical
                                    ✅ Licence Holder.
                                    ✅ Certified chemical.
                                    ✅ Treatment with Warranty.

                                    For reliable pest control and hygiene maintenance, please feel free to contact us.

                                    *7038360235 / 9767713742.*

                                    Thank You 🙏

                                    *Team Shul Ventures Pvt. Ltd.*
                                    MSG;

                                    ?>

                                    <?php if (!empty($whatsappPhone)) { ?>

                                        <a
                                        href="https://wa.me/<?= htmlspecialchars($whatsappPhone) ?>?text=<?= urlencode($whatsappMessage) ?>"
                                        target="_blank"
                                        class="view-client-whatsapp-btn"
                                        >
                                        💬 WhatsApp
                                    </a>

                                <?php } ?>

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

                        No clients found.

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
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   SEARCH CLIENTS
========================================================= */

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
                !searchInput
                || !table
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


        /* =================================================
           UPDATE WIDTH
        ================================================= */

        function updateScrollWidth() {

            bottomInner.style.width =
            tableWrapper.scrollWidth + "px";

        }


        updateScrollWidth();


        window.addEventListener(
            "resize",
            updateScrollWidth
            );


        /* =================================================
           BOTTOM -> TABLE
        ================================================= */

        bottomScroll.addEventListener(
            "scroll",
            function () {

                tableWrapper.scrollLeft =
                bottomScroll.scrollLeft;

            }
            );


        /* =================================================
           TABLE -> BOTTOM
        ================================================= */

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
