<?php
session_start();
require_once 'db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// =========================================================
// CHECK LOGIN
// =========================================================

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}


// =========================================================
// INITIALIZE DATE FILTER
// =========================================================

$fromDate = '';
$endDate  = '';
$branch   = '';


// =========================================================
// GET POSTED DATES
// =========================================================

if (isset($_POST['but_search'])) {

    $fromDate = $_POST['fromDate'] ?? '';
    $endDate  = $_POST['endDate'] ?? '';

}


// =========================================================
// GET BRANCH
// =========================================================

if (isset($_POST['branch_search'])) {

    $branch = $_POST['branch_search'] ?? '';

}


// =========================================================
// PAYMENT QUERY
// =========================================================

$params = [];

$sql = "
    SELECT
        c1.Id,
        c1.branch,
        c1.services,
        c1.cust_name,
        p1.paid_amount,
        p1.payment_date,
        p1.payment_gateway,
        c1.source
    FROM tbl_client c1

    INNER JOIN tbl_payment p1
        ON c1.Id = p1.client_Id

    WHERE p1.paid_amount IS NOT NULL
      AND p1.payment_date IS NOT NULL
      AND p1.payment_gateway IS NOT NULL
";


// =========================================================
// DATE FILTER - FIRST QUERY
// =========================================================

if (!empty($fromDate) && !empty($endDate)) {

    $sql .= "
        AND p1.payment_date BETWEEN ? AND ?
    ";

    $params[] = $fromDate;
    $params[] = $endDate;
}


// =========================================================
// SECOND QUERY - CONSULTANCY
// =========================================================

$sql .= "

    UNION

    SELECT
        p1.client_Id,
        'Nagpur' AS branch,
        'Consultancy' AS services,

        CASE
            WHEN p1.payment_type = 'Company Pay'
            THEN pl.company_name
            ELSE pl.candidate_name
        END AS cust_name,

        p1.paid_amount,
        p1.payment_date,
        p1.payment_gateway,
        NULL AS source

    FROM tbl_payment p1

    INNER JOIN tbl_placement pl
        ON pl.interview_id = p1.interview_id

    WHERE p1.paid_amount IS NOT NULL
      AND p1.payment_date IS NOT NULL
      AND p1.payment_gateway IS NOT NULL
";


// =========================================================
// DATE FILTER - SECOND QUERY
// =========================================================

if (!empty($fromDate) && !empty($endDate)) {

    $sql .= "
        AND p1.payment_date BETWEEN ? AND ?
    ";

    $params[] = $fromDate;
    $params[] = $endDate;
}


// =========================================================
// THIRD QUERY - CONSULTANCY
// =========================================================

$sql .= "

    UNION ALL

    SELECT
        p1.client_Id,
        'Nagpur' AS branch,
        'Consultancy' AS services,

        CASE
            WHEN p1.payment_type = 'Company Pay'
            THEN pl.company_name
            ELSE pl.candidate_name
        END AS cust_name,

        p1.paid_amount,
        p1.payment_date,
        p1.payment_gateway,
        NULL AS source

    FROM tbl_payment p1

    INNER JOIN tbl_placement pl
        ON pl.interview_id = p1.interview_id

    WHERE p1.paid_amount IS NOT NULL
      AND p1.payment_date IS NOT NULL
      AND p1.payment_gateway IS NOT NULL
";


// =========================================================
// DATE FILTER - THIRD QUERY
// =========================================================

if (!empty($fromDate) && !empty($endDate)) {

    $sql .= "
        AND p1.payment_date BETWEEN ? AND ?
    ";

    $params[] = $fromDate;
    $params[] = $endDate;
}


// =========================================================
// FOURTH QUERY - INTERNSHIP
// =========================================================

$sql .= "

    UNION

    SELECT
        ts.student_id,
        'Nagpur' AS branch,
        'Internship' AS services,
        ts.name,
        p1.paid_amount,
        p1.payment_date,
        p1.payment_gateway,
        NULL AS source

    FROM tbl_student_admissions ts

    INNER JOIN tbl_payment p1
        ON ts.student_id = p1.client_Id

    WHERE p1.paid_amount IS NOT NULL
      AND p1.payment_date IS NOT NULL
      AND p1.payment_gateway IS NOT NULL
";


// =========================================================
// DATE FILTER - FOURTH QUERY
// =========================================================

if (!empty($fromDate) && !empty($endDate)) {

    $sql .= "
        AND p1.payment_date BETWEEN ? AND ?
    ";

    $params[] = $fromDate;
    $params[] = $endDate;
}


// =========================================================
// ORDER
// =========================================================

$sql .= "
    ORDER BY payment_date DESC
";


// =========================================================
// EXECUTE QUERY
// =========================================================

$result = false;

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    if (!empty($params)) {

        $types = str_repeat(
            's',
            count($params)
        );

        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );
    }


    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

} else {

    die(
        "Error preparing statement: "
        . mysqli_error($conn)
    );

}


// =========================================================
// TOTAL PAYMENT RECORD COUNT
// =========================================================

$total_records = 0;

if ($result) {

    $total_records = mysqli_num_rows($result);

}


// =========================================================
// HEADER
// =========================================================

include 'header.php';

?>



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
                            💳
                        </span>

                        Payment History

                    </h1>


                    <p class="view-clients-subtitle">
                        View and manage payment history
                    </p>

                </div>


                <!-- =================================================
                     RECORD COUNT
                ================================================== -->

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
                            placeholder="Search by Name, Branch, Services, or Gateway"
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


                    <!-- PRINT BUTTON -->

                    <button
                        id="btnPrint"
                        type="button"
                        onclick="printPayments()"
                        class="view-client-excel-btn"
                        style="display: <?= (!empty($fromDate) && !empty($endDate))
                            ? 'inline-block'
                            : 'none'; ?>;"
                    >

                        🖨 Print

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
                                    Branch
                                </th>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Paid Amount
                                </th>

                                <th>
                                    Payment Date
                                </th>

                                <th>
                                    Gateway
                                </th>

                                <th>
                                    Source
                                </th>

                            </tr>

                        </thead>


                        <!-- =================================================
                             TABLE BODY
                        ================================================== -->

                        <tbody>

                        <?php

                        if (
                            $result
                            &&
                            mysqli_num_rows($result) > 0
                        ) {

                            while (
                                $row =
                                mysqli_fetch_assoc($result)
                            ) {


                                /* =================================================
                                   BRANCH COLOR LOGIC
                                ================================================= */

                                $branchClass = 'blue';


                                $branchLower = strtolower(
                                    trim(
                                        $row['branch'] ?? ''
                                    )
                                );


                                /* NAGPUR */

                                if (
                                    $branchLower === 'nagpur'
                                ) {

                                    $branchClass = 'blue';

                                }


                                /* BRAMHAPURI */

                                elseif (
                                    $branchLower === 'bramhapuri'
                                ) {

                                    $branchClass = 'green';

                                }


                                /* CHANDRAPUR / CHANDREAPUR */

                                elseif (
                                    $branchLower === 'chandrapur'
                                    ||
                                    $branchLower === 'chandreapur'
                                ) {

                                    $branchClass = 'orange';

                                }


                                /* DEFAULT */

                                else {

                                    $branchClass = 'blue';

                                }

                                ?>


                                <tr>


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

                                            <?= htmlspecialchars(
                                                $row["branch"] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>

                                        </span>

                                    </td>


                                    <!-- =================================================
                                         SERVICE
                                    ================================================== -->

                                    <td class="view-client-service">

                                        <?= htmlspecialchars(
                                            $row["services"] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- =================================================
                                         NAME
                                    ================================================== -->

                                    <td class="view-client-name">

                                        <?= htmlspecialchars(
                                            $row["cust_name"] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- =================================================
                                         PAID AMOUNT
                                    ================================================== -->

                                    <td class="view-client-paid-amount">

                                        ₹ <?= htmlspecialchars(
                                            $row["paid_amount"] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- =================================================
                                         PAYMENT DATE
                                    ================================================== -->

                                    <td class="view-client-date-cell">

                                        <?= htmlspecialchars(
                                            $row["payment_date"] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- =================================================
                                         GATEWAY
                                    ================================================== -->

                                    <td class="view-client-payment-gateway">

                                        <?= htmlspecialchars(
                                            $row["payment_gateway"] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- =================================================
                                         SOURCE
                                    ================================================== -->

                                    <td class="view-client-source">

                                        <?= htmlspecialchars(
                                            (string)(
                                                $row["source"] ?? ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

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

                                    No results found

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


                <!-- =================================================
                     TOTAL
                ================================================== -->

                <section id="total-paid-amount">

                    <h4>

                        Total Paid Amount (INR):

                        <span id="total-paid">
                            0.00
                        </span>

                    </h4>

                </section>


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
   FUNCTION TO CALCULATE TOTAL
========================================================= */

function calculateTotalPaid() {

    let totalPaid = 0;


    document
        .querySelectorAll(
            '#payments-table tbody tr'
        )
        .forEach(
            row => {

                if (
                    row.style.display !== 'none'
                ) {


                    let paidAmountCell =
                        row.querySelector(
                            '.view-client-paid-amount'
                        );


                    if (!paidAmountCell) {
                        return;
                    }


                    let paidAmount =
                        parseFloat(
                            paidAmountCell.textContent
                                .replace(/,/g, '')
                                .replace(/₹/g, '')
                                .trim()
                        ) || 0;


                    totalPaid += paidAmount;

                }

            }
        );


    document.getElementById(
        'total-paid'
    ).textContent =
        totalPaid.toFixed(2);

}


/* =========================================================
   SEARCH FUNCTION
========================================================= */

function searchPayments() {

    const searchInput =
        document.getElementById(
            'txt_searchall'
        );


    if (!searchInput) {
        return;
    }


    const query =
        searchInput.value
            .toLowerCase()
            .trim();


    const rows =
        document.querySelectorAll(
            '#payments-table tbody tr'
        );


    rows.forEach(
        row => {


            const noData =
                row.querySelector(
                    '.view-client-no-data'
                );


            if (noData) {
                return;
            }


            const rowText =
                row.textContent
                    .toLowerCase();


            if (
                query === ''
                ||
                rowText.includes(query)
            ) {

                row.style.display = '';

            }

            else {

                row.style.display = 'none';

            }

        }
    );


    calculateTotalPaid();

}


/* =========================================================
   SEARCH EVENT
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const searchInput =
            document.getElementById(
                'txt_searchall'
            );


        if (searchInput) {

            searchInput.addEventListener(
                'input',
                searchPayments
            );

        }


    }
);


/* =========================================================
   PAGE LOAD
========================================================= */

window.addEventListener(
    'load',
    function () {

        calculateTotalPaid();

    }
);


/* =========================================================
   DATE VALUES
========================================================= */

let fromDate =
    "<?= htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8'); ?>";


let endDate =
    "<?= htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8'); ?>";


/* =========================================================
   PRINT
========================================================= */

function printPayments() {


    let table =
        document.getElementById(
            'payments-table'
        );


    if (!table) {
        return;
    }


    let printTable =
        table.cloneNode(true);


    /* =====================================================
       REMOVE SOURCE COLUMN
    ===================================================== */

    let allRows =
        printTable.querySelectorAll(
            'tr'
        );


    allRows.forEach(
        row => {

            if (
                row.cells.length > 0
            ) {

                row.deleteCell(-1);

            }

        }
    );


    /* =====================================================
       ADD S.NO
    ===================================================== */

    let thead =
        printTable.querySelector(
            'thead tr'
        );


    let snoHeader =
        document.createElement(
            'th'
        );


    snoHeader.textContent =
        'S.No';


    thead.insertBefore(
        snoHeader,
        thead.firstChild
    );


    /* =====================================================
       ADD S.NO TO ROWS
    ===================================================== */

    let printRows =
        printTable.querySelectorAll(
            'tbody tr'
        );


    let sno = 1;


    printRows.forEach(
        row => {


            if (
                row.style.display === 'none'
            ) {

                return;

            }


            let cell =
                document.createElement(
                    'td'
                );


            cell.textContent =
                sno++;


            row.insertBefore(
                cell,
                row.firstChild
            );


        }
    );


    /* =====================================================
       TOTAL
    ===================================================== */

    let totalPaid =
        document.getElementById(
            'total-paid'
        ).textContent;


    /* =====================================================
       OPEN PRINT WINDOW
    ===================================================== */

    let printTab =
        window.open(
            '',
            '_blank'
        );


    if (!printTab) {

        alert(
            'Please allow pop-ups to print.'
        );

        return;

    }


    printTab.document.write(
        '<html><head><title>Payment History</title>'
    );


    printTab.document.write(`

<style>

body {
    font-family: Arial;
    padding: 20px;
}

h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 5px;
}

p {
    text-align: center;
    color: #555;
}

h4 {
    text-align: right;
    color: green;
    margin-top: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th {
    background: #34495e;
    color: #fff;
    padding: 10px;
    border: 1px solid #ddd;
}

td {
    padding: 8px;
    text-align: center;
    border: 1px solid #ddd;
}

tr:nth-child(even) {
    background: #f2f2f2;
}

</style>

`);


    printTab.document.write(
        '</head><body>'
    );


    printTab.document.write(`

<h2>Payment History</h2>

<p
    style="
        text-align:center;
        font-size:14px;
        margin-top:5px;
    "
>

    <b>From:</b>
    ${fromDate || 'All'}

    &nbsp;&nbsp;

    <b>To:</b>
    ${endDate || 'All'}

</p>

`);


    printTab.document.write(
        printTable.outerHTML
    );


    printTab.document.write(
        '<h4>Total Paid Amount (INR): '
        +
        totalPaid
        +
        '</h4>'
    );


    printTab.document.write(
        '</body></html>'
    );


    printTab.document.close();


    printTab.focus();


    setTimeout(
        function () {

            printTab.print();

        },
        300
    );

}


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
                tableWrapper.scrollWidth
                +
                "px";

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


<?php if (
    !empty($fromDate)
    &&
    !empty($endDate)
) : ?>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const printButton =
            document.getElementById(
                'btnPrint'
            );

        if (printButton) {

            printButton.style.display =
                'inline-block';

        }

    }
);

</script>

<?php endif; ?>


<?php

if (isset($stmt) && $stmt) {

    mysqli_stmt_close($stmt);

}

?>

</body>
</html>