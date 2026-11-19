<?php
session_start();

if (!isset($_SESSION['email'])) {
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

if ($client_id <= 0) {
    die("Invalid Client ID");
}


/* ===============================
   1️⃣ Fetch client details
================================ */

$sql_client = "SELECT cust_name FROM tbl_client WHERE Id = ?";

$stmt_client = $conn->prepare($sql_client);
$stmt_client->bind_param("i", $client_id);
$stmt_client->execute();

$client = $stmt_client->get_result()->fetch_assoc();


/* ===============================
   2️⃣ Check DEFAULT AMC exists
================================ */

$sql_default = "
    SELECT *
    FROM tbl_amc
    WHERE client_Id = ?
    AND status = 'DEFAULTER'
    LIMIT 1
";

$stmt_default = $conn->prepare($sql_default);
$stmt_default->bind_param("i", $client_id);
$stmt_default->execute();

$result_default = $stmt_default->get_result();

$has_default = $result_default->num_rows > 0;

$default_row = $has_default
    ? $result_default->fetch_assoc()
    : null;


/* ===============================
   3️⃣ Fetch services
      Completed + DEFAULT
      Pending hide if DEFAULT exist
================================ */

if ($has_default) {

    $sql_service = "
        SELECT *
        FROM tbl_amc
        WHERE client_Id = ?
        AND (
            status = 'completed'
            OR status = 'DEFAULTER'
        )
        ORDER BY service_date ASC
    ";

} else {

    $sql_service = "
        SELECT *
        FROM tbl_amc
        WHERE client_Id = ?
        ORDER BY service_date ASC
    ";

}

$stmt_service = $conn->prepare($sql_service);
$stmt_service->bind_param("i", $client_id);
$stmt_service->execute();

$result_service = $stmt_service->get_result();

$total_records = $result_service->num_rows;

?>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

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
                            📅
                        </span>

                        Service Dates

                    </h1>

                    <p class="view-clients-subtitle">

                        Client:
                        <?= htmlspecialchars(
                            $client['cust_name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

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
                            placeholder="Search service date, operator, status..."
                            autocomplete="off"
                        >

                    </div>

                </div>


                <!-- =================================================
                     TABLE WRAPPER
                ================================================== -->

                <div
                    class="view-clients-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="view-clients-table"
                        id="serviceTable"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Sr. No.
                                </th>

                                <th>
                                    Service Date
                                </th>

                                <th>
                                    Operator
                                </th>

                                <th>
                                    Monitored By
                                </th>

                                <th>
                                    Reason / Description
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        if ($result_service->num_rows > 0):

                            $sr = 1;

                            while (
                                $row = $result_service->fetch_assoc()
                            ):

                        ?>

                            <tr
                                class="<?= $row['status'] === 'DEFAULTER'
                                    ? 'view-client-defaulter-row'
                                    : '';
                                ?>"
                            >


                                <!-- SR NO -->

                                <td class="view-client-sr">

                                    <?= $sr++; ?>

                                </td>


                                <!-- SERVICE DATE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['service_date'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- OPERATOR -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['operator_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- MONITORED BY -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['monitored_by'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- DESCRIPTION -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['description']
                                        ?? $row['reason']
                                        ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if (
                                        $row['status'] === 'DEFAULTER'
                                    ): ?>

                                        <span
                                            class="view-client-status-defaulter"
                                        >
                                            DEFAULTER
                                        </span>

                                    <?php else: ?>

                                        <?= htmlspecialchars(
                                            $row['status'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <?php
                                    if (
                                        $row['status'] !== 'DEFAULTER'
                                    ):
                                    ?>

                                        <a
                                            href="edit-service-date.php?service_id=<?= $row['Id']; ?>&client_id=<?= $client_id; ?>"
                                            class="view-client-view-btn"
                                        >
                                            Update
                                        </a>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                            </tr>

                        <?php

                            endwhile;

                        else:

                        ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="view-client-no-data"
                                >

                                    No service records found.

                                </td>

                            </tr>

                        <?php endif; ?>

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
     PAGE-SPECIFIC SMALL CSS
========================================================= -->

<style>

.view-client-defaulter-row td {
    background: #f2dede !important;
    font-weight: bold;
}

.view-client-status-defaulter {
    color: #dc3545;
    font-weight: bold;
}

</style>


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
                "serviceTable"
            );

        if (
            !searchInput ||
            !table
        ) {
            return;
        }


        const tbody =
            table.querySelector("tbody");


        function filterRows() {

            const filter =
                searchInput.value
                    .toLowerCase()
                    .trim();


            const rows =
                tbody.querySelectorAll("tr");


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
                        row.innerText
                            .toLowerCase();


                    if (
                        filter === "" ||
                        rowText.includes(filter)
                    ) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                }
            );

        }


        searchInput.addEventListener(
            "input",
            filterRows
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

$stmt_client->close();
$stmt_default->close();
$stmt_service->close();
$conn->close();

?>