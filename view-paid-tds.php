<?php
session_start();

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';


/* =========================================================
   MAIN QUERY
========================================================= */

$sql = "
SELECT 
    p.Id,
    c.branch,
    c.cust_name,
    p.total_amount,
    p.tds_amount,
    p.tds_paid,
    p.payment_date
FROM tbl_payment p
LEFT JOIN tbl_client c ON c.Id = p.client_Id
WHERE p.tds_paid = 1
ORDER BY p.payment_date DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$total_records = mysqli_num_rows($result);

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
                    💰
                </span>

                Paid TDS Payments

            </h1>

            <p class="view-clients-subtitle">

                View all paid TDS payment records

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
                    placeholder="Search by Name or Branch..."
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
                id="payments-table"
            >

                <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            Branch
                        </th>

                        <th>
                            Client Name
                        </th>

                        <th>
                            TDS Amount Paid
                        </th>

                        <th>
                            Payment Date
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                if ($total_records > 0) {

                    $i = 1;

                    while ($row = mysqli_fetch_assoc($result)) {

                        ?>

                        <tr>

                            <!-- SR NO -->

                            <td class="view-client-sr">

                                <?= $i++; ?>

                            </td>


                            <!-- BRANCH -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['branch'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </td>


                            <!-- CLIENT NAME -->

                            <td class="view-client-name">

                                <?= htmlspecialchars(
                                    $row['cust_name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>

                            </td>


                            <!-- TDS AMOUNT -->

                            <td class="view-client-amount">

                                ₹<?= number_format(
                                    (float)$row['tds_amount'],
                                    2
                                ); ?>

                            </td>


                            <!-- PAYMENT DATE -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['payment_date'] ?? '',
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
                            colspan="5"
                            class="view-client-no-data"
                        >

                            No Paid TDS Found.

                        </td>

                    </tr>

                    <?php

                }

                ?>

                </tbody>


                <!-- =================================================
                     TOTAL TDS
                ================================================== -->

                <tfoot>

                    <tr class="view-client-total-row">

                        <td
                            colspan="3"
                            class="view-client-total-label"
                        >

                            Total:

                        </td>

                        <td
                            id="totalTds"
                            class="view-client-total-value"
                        >

                            ₹0.00

                        </td>

                        <td></td>

                    </tr>

                </tfoot>

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

        if (!searchInput || !table) {
            return;
        }


        const tbody =
            table.querySelector("tbody");


        const totalTdsCell =
            document.getElementById(
                "totalTds"
            );


        /* =====================================================
           UPDATE TOTAL
        ===================================================== */

        function updateTotal() {

            let total = 0;

            const rows =
                tbody.querySelectorAll("tr");


            rows.forEach(
                function (row) {

                    if (
                        row.style.display !== "none" &&
                        row.cells.length >= 5
                    ) {

                        const amountText =
                            row.cells[3]
                                .innerText
                                .replace(/[₹,]/g, "")
                                .trim();


                        const amount =
                            parseFloat(amountText);


                        if (!isNaN(amount)) {

                            total += amount;

                        }

                    }

                }
            );


            totalTdsCell.innerText =
                "₹" +
                total.toLocaleString(
                    undefined,
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );

        }


        /* =====================================================
           FILTER ROWS
        ===================================================== */

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


            updateTotal();

        }


        /* INITIAL TOTAL */

        updateTotal();


        /* SEARCH */

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

