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


// =========================================================
// HANDLE MOVE COMPLAINT TO RESOLVED
// =========================================================

if (isset($_GET['resolve_id'])) {

    $resolve_id = intval($_GET['resolve_id']);

    // Fetch complaint
    $sql_select = "
        SELECT *
        FROM tbl_complaints
        WHERE Id = ?
    ";

    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $resolve_id);
    $stmt_select->execute();

    $complaint =
        $stmt_select
        ->get_result()
        ->fetch_assoc();


    if ($complaint) {

        // Insert resolved complaint
        $sql_insert = "
            INSERT INTO tbl_resolved_complaints
            (
                client_id,
                details,
                complaint_date,
                resolver_id,
                resolve_date
            )
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmt_insert =
            $conn->prepare($sql_insert);


        $resolver_id =
            $_SESSION['user_id'];


        $resolve_date =
            date('Y-m-d H:i:s');


        $stmt_insert->bind_param(
            "issis",
            $complaint['client_id'],
            $complaint['details'],
            $complaint['complaint_date'],
            $resolver_id,
            $resolve_date
        );


        if ($stmt_insert->execute()) {

            // Delete complaint
            $sql_delete = "
                DELETE FROM tbl_complaints
                WHERE Id = ?
            ";

            $stmt_delete =
                $conn->prepare($sql_delete);

            $stmt_delete->bind_param(
                "i",
                $resolve_id
            );


            if ($stmt_delete->execute()) {

                echo "
                <script>
                    alert(
                        'Complaint moved to resolved successfully'
                    );
                    window.location.href =
                        'view-complaints.php';
                </script>
                ";

                exit();

            } else {

                echo
                    "Error deleting complaint: "
                    . $stmt_delete->error;

            }

        } else {

            echo
                "Error inserting into resolved complaints: "
                . $stmt_insert->error;

        }

    } else {

        echo "Complaint not found.";

    }
}


// =========================================================
// BRANCH RESTRICTION
// =========================================================

$branch_filter = "";

if (
    $_SESSION['role'] !== 'super_admin'
    && !empty($_SESSION['branch'])
) {

    $branch =
        mysqli_real_escape_string(
            $conn,
            $_SESSION['branch']
        );

    $branch_filter =
        " AND cl.branch = '$branch' ";
}


// =========================================================
// DATE FILTER
// =========================================================

$fromDate =
    $_POST['fromDate'] ?? '';

$endDate =
    $_POST['endDate'] ?? '';


// =========================================================
// MAIN QUERY
// =========================================================

$sql = "
    SELECT
        c.Id,
        c.details,
        c.complaint_date,
        cl.branch,
        cl.cust_name,
        cl.services,
        cl.cust_phone,
        cl.cust_address,
        cl.due_date,
        cl.operator_name

    FROM tbl_complaints c

    JOIN tbl_client cl
        ON c.client_id = cl.Id

    WHERE 1

    $branch_filter
";


// =========================================================
// DATE SEARCH
// =========================================================

if (
    isset($_POST['but_search'])
    && !empty($fromDate)
    && !empty($endDate)
) {

    $safeFromDate =
        mysqli_real_escape_string(
            $conn,
            $fromDate
        );

    $safeEndDate =
        mysqli_real_escape_string(
            $conn,
            $endDate
        );


    $sql .= "
        AND c.complaint_date
        BETWEEN '$safeFromDate'
        AND '$safeEndDate'
    ";
}


// =========================================================
// ORDER
// =========================================================

$sql .= "
    ORDER BY c.complaint_date DESC
";


$result =
    mysqli_query(
        $conn,
        $sql
    );


if (!$result) {

    die(
        "Database Error: "
        . mysqli_error($conn)
    );

}


$total_records =
    mysqli_num_rows($result);


// =========================================================
// HEADER
// =========================================================

include 'header.php';

?>

<body>

<section id="main-content">

    <section class="wrapper">


        <!-- =================================================
             VIEW CLIENT STYLE PAGE
        ================================================== -->

        <div class="view-clients-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="view-clients-header">

                <div class="view-clients-title-wrap">

                    <h1 class="view-clients-title">

                        <span
                            class="view-clients-title-icon"
                        >
                            ⚠️
                        </span>

                        Complaints List

                    </h1>


                    <p class="view-clients-subtitle">

                        View and manage all complaints

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
                     FILTER
                ================================================== -->

                <form
                    method="post"
                    action=""
                    class="view-clients-filter"
                >


                    <!-- SEARCH -->

                    <div
                        class="view-client-search-wrap"
                    >

                        <span
                            class="view-client-search-icon"
                        >
                            🔍
                        </span>


                        <input
                            type="text"
                            id="txt_searchall"
                            class="view-client-search"
                            placeholder="Search complaints..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- FROM DATE -->

                    <input
                        type="date"
                        name="fromDate"
                        id="start_date"
                        class="view-client-date"
                        value="<?= htmlspecialchars(
                            $fromDate
                        ); ?>"
                    >


                    <!-- END DATE -->

                    <input
                        type="date"
                        name="endDate"
                        id="end_date"
                        class="view-client-date"
                        value="<?= htmlspecialchars(
                            $endDate
                        ); ?>"
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
                                    Branch
                                </th>

                                <th>
                                    Client Name
                                </th>

                                <th>
                                    Services
                                </th>

                                <th>
                                    Service Date
                                </th>

                                <th>
                                    Details
                                </th>

                                <th>
                                    Complaint Date
                                </th>

                                <th>
                                    Operator Name
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
                                $row =
                                mysqli_fetch_assoc($result)
                            ) {


                                // =================================================
                                // ESCAPE VALUES
                                // =================================================

                                $branchName =
                                    htmlspecialchars(
                                        $row['branch'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $clientName =
                                    htmlspecialchars(
                                        $row['cust_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $services =
                                    htmlspecialchars(
                                        $row['services'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $serviceDate =
                                    htmlspecialchars(
                                        $row['due_date'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $details =
                                    htmlspecialchars(
                                        $row['details'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $complaintDate =
                                    htmlspecialchars(
                                        $row['complaint_date'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $operatorName =
                                    htmlspecialchars(
                                        $row['operator_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                // =================================================
                                // BRANCH COLOR
                                // =================================================

                                $branchClass =
                                    'blue';


                                $branchLower =
                                    strtolower(
                                        trim(
                                            $row['branch'] ?? ''
                                        )
                                    );


                                if (
                                    $branchLower ===
                                    'nagpur'
                                ) {

                                    $branchClass =
                                        'blue';

                                }

                                elseif (
                                    $branchLower ===
                                    'bramhapuri'
                                ) {

                                    $branchClass =
                                        'green';

                                }

                                elseif (
                                    $branchLower ===
                                    'chandrapur'
                                    ||
                                    $branchLower ===
                                    'chandreapur'
                                ) {

                                    $branchClass =
                                        'orange';

                                }


                                // =================================================
                                // TODAY CHECK
                                // =================================================

                                $todayClass = '';


                                if (
                                    !empty(
                                        $row['complaint_date']
                                    )
                                    &&
                                    date(
                                        'Y-m-d',
                                        strtotime(
                                            $row['complaint_date']
                                        )
                                    )
                                    ==
                                    date('Y-m-d')
                                ) {

                                    $todayClass =
                                        'today';

                                }

                                ?>

                                <tr>


                                    <!-- SR NO -->

                                    <td
                                        class="view-client-sr"
                                    >

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

                                    <td
                                        class="view-client-name"
                                    >

                                        <?= $clientName; ?>

                                    </td>


                                    <!-- SERVICES -->

                                    <td
                                        class="view-client-service"
                                    >

                                        <?= $services; ?>

                                    </td>


                                    <!-- SERVICE DATE -->

                                    <td
                                        class="
                                            view-client-date-cell
                                        "
                                    >

                                        <?= $serviceDate; ?>

                                    </td>


                                    <!-- DETAILS -->

                                    <td
                                        class="view-client-address"
                                    >

                                        <?= $details; ?>

                                    </td>


                                    <!-- COMPLAINT DATE -->

                                    <td
                                        class="
                                            view-client-date-cell
                                            <?= $todayClass; ?>
                                        "
                                    >

                                        <?= $complaintDate; ?>

                                    </td>


                                    <!-- OPERATOR -->

                                    <td
                                        class="view-client-name"
                                    >

                                        <?= $operatorName; ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td
                                        class="view-client-action"
                                    >

                                        <a
                                            href="view-complaint-details.php?id=<?= intval(
                                                $row['Id']
                                            ); ?>"
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

                                    No complaints found.

                                </td>

                            </tr>

                            <?php

                        }

                        ?>

                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     BOTTOM SCROLL
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

                            row.style.display =
                                "";


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

function filterByDate() {

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
        function (row) {

            const dateCell =
                row.querySelector(
                    ".view-client-date-cell"
                );


            if (!dateCell) {
                return;
            }


            const dateText =
                dateCell.textContent.trim();


            if (!dateText) {
                return;
            }


            const rowDate =
                new Date(dateText);


            const start =
                startDate
                    ? new Date(startDate)
                    : null;


            const end =
                endDate
                    ? new Date(endDate)
                    : null;


            if (
                (start && rowDate < start)
                ||
                (end && rowDate > end)
            ) {

                row.style.display =
                    "none";

            }

            else {

                row.style.display =
                    "";

            }

        }
    );


    updateSerialNumbers();

}


/* =========================================================
   SERIAL NUMBER
========================================================= */

function updateSerialNumbers() {

    const rows =
        document.querySelectorAll(
            "#payments-table tbody tr"
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


/* =========================================================
   BOTTOM SCROLLBAR
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