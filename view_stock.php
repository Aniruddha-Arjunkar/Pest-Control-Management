<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
include 'header.php';

/* =========================================================
   MAIN STOCK QUERY
========================================================= */

$query = "
SELECT 
    b.id AS branch_id,
    b.name AS branch_name,
    m.id AS material_id,
    m.name AS material_name,
    m.rate_unit,

    COALESCE(p.total_purchased, 0) AS total_purchased,
    COALESCE(ti.total_in, 0) AS total_transferred_in,
    COALESCE(u.total_used, 0) AS total_used,
    COALESCE(tof.total_out, 0) AS total_transferred_out,
    COALESCE(adj.total_adjustment, 0) AS total_adjustment,
    lu.last_used_date

FROM branches b
CROSS JOIN materials m

LEFT JOIN (
    SELECT 
        material_id, 
        branch_id, 
        SUM(quantity) AS total_purchased
    FROM stock_purchases
    GROUP BY material_id, branch_id
) p 
ON p.material_id = m.id 
AND p.branch_id = b.id

LEFT JOIN (
    SELECT 
        material_id, 
        to_branch AS branch_id, 
        SUM(quantity) AS total_in
    FROM stock_transfers
    GROUP BY material_id, to_branch
) ti 
ON ti.material_id = m.id 
AND ti.branch_id = b.id

LEFT JOIN (
    SELECT 
        material_id, 
        from_branch AS branch_id, 
        SUM(quantity) AS total_out
    FROM stock_transfers
    GROUP BY material_id, from_branch
) tof 
ON tof.material_id = m.id 
AND tof.branch_id = b.id

LEFT JOIN (
    SELECT 
        sm.material_id, 
        sm.branch_id, 
        SUM(
            CASE 
                WHEN m.rate_unit = 'Ltr' 
                     AND sm.quantity_unit = 'ml' 
                    THEN sm.quantity_used / 1000

                WHEN m.rate_unit = 'Ltr' 
                     AND sm.quantity_unit = 'Ltr' 
                    THEN sm.quantity_used

                WHEN m.rate_unit = 'Kg' 
                     AND sm.quantity_unit = 'gm' 
                    THEN sm.quantity_used / 1000

                WHEN m.rate_unit = 'Kg' 
                     AND sm.quantity_unit = 'Kg' 
                    THEN sm.quantity_used

                WHEN m.rate_unit = 'Pc' 
                     AND sm.quantity_unit = 'Pc' 
                    THEN sm.quantity_used

                WHEN m.rate_unit = 'Box' 
                     AND sm.quantity_unit = 'Box'
                    THEN sm.quantity_used

                ELSE 0
            END
        ) AS total_used

    FROM service_materials sm

    INNER JOIN materials m 
        ON m.id = sm.material_id

    GROUP BY sm.material_id, sm.branch_id

) u 
ON u.material_id = m.id 
AND u.branch_id = b.id

LEFT JOIN (
    SELECT 
        material_id, 
        branch_id,
        SUM(quantity) AS total_adjustment
    FROM stock_adjustments
    GROUP BY material_id, branch_id
) adj 
ON adj.material_id = m.id 
AND adj.branch_id = b.id

LEFT JOIN (
    SELECT 
        material_id, 
        branch_id, 
        MAX(used_date) AS last_used_date
    FROM service_materials
    GROUP BY material_id, branch_id
) lu 
ON lu.material_id = m.id 
AND lu.branch_id = b.id

WHERE
      COALESCE(p.total_purchased, 0)
    + COALESCE(ti.total_in, 0)
    + COALESCE(u.total_used, 0)
    + COALESCE(tof.total_out, 0)
    + COALESCE(adj.total_adjustment, 0) > 0

ORDER BY b.name, m.name
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}

$total_records = mysqli_num_rows($result);

?>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<section id="main-content">

<section class="wrapper">

<div class="view-clients-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="view-clients-header">

        <div class="view-clients-title-wrap">

            <h1 class="view-clients-title">

                <span class="view-clients-title-icon">
                    📦
                </span>

                Available Materials Stock by Branch

            </h1>

            <p class="view-clients-subtitle">

                View current material stock available in each branch

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


    <!-- =====================================================
         CARD
    ====================================================== -->

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
                    placeholder="Search branch, material, unit..."
                    autocomplete="off"
                >

            </div>

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
                id="stockTable"
            >

                <thead>

                <tr>

                    <th>Sr. No.</th>

                    <th>Branch</th>

                    <th>Material</th>

                    <th>Unit</th>

                    <th>Purchased Quantity</th>

                    <th>Transferred In</th>

                    <th>Used</th>

                    <th>Transferred Out</th>

                    <th>Miscellaneous Stock</th>

                    <th>Last Used</th>

                    <th>Available</th>

                </tr>

                </thead>


                <tbody>

                <?php

                if ($total_records > 0) {

                    $sr = 1;

                    while ($row = mysqli_fetch_assoc($result)) {

                        $available =
                              (float)$row['total_purchased']
                            + (float)$row['total_transferred_in']
                            - (float)$row['total_used']
                            - (float)$row['total_transferred_out']
                            - (float)$row['total_adjustment'];

                ?>

                <tr>

                    <!-- SR NO -->

                    <td class="view-client-sr">

                        <?= $sr++; ?>

                    </td>


                    <!-- BRANCH -->

                    <td>

                        <?= htmlspecialchars(
                            $row['branch_name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </td>


                    <!-- MATERIAL -->

                    <td class="view-client-name">

                        <?= htmlspecialchars(
                            $row['material_name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </td>


                    <!-- UNIT -->

                    <td>

                        <?= htmlspecialchars(
                            $row['rate_unit'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </td>


                    <!-- PURCHASED -->

                    <td>

                        <?= number_format(
                            (float)$row['total_purchased'],
                            3
                        ); ?>

                    </td>


                    <!-- TRANSFERRED IN -->

                    <td>

                        <?= number_format(
                            (float)$row['total_transferred_in'],
                            3
                        ); ?>

                    </td>


                    <!-- USED -->

                    <td>

                        <?= number_format(
                            (float)$row['total_used'],
                            3
                        ); ?>

                    </td>


                    <!-- TRANSFERRED OUT -->

                    <td>

                        <?= number_format(
                            (float)$row['total_transferred_out'],
                            3
                        ); ?>

                    </td>


                    <!-- MISCELLANEOUS -->

                    <td>

                        <?= number_format(
                            (float)$row['total_adjustment'],
                            3
                        ); ?>

                    </td>


                    <!-- LAST USED -->

                    <td>

                        <?php

                        if (!empty($row['last_used_date'])) {

                            echo date(
                                "d-m-Y",
                                strtotime($row['last_used_date'])
                            );

                        } else {

                            echo '-';

                        }

                        ?>

                    </td>


                    <!-- AVAILABLE -->

                    <td class="view-client-available">

                        <?= number_format(
                            $available,
                            3
                        ); ?>

                    </td>

                </tr>

                <?php

                    }

                } else {

                ?>

                <tr>

                    <td
                        colspan="11"
                        class="view-client-no-data"
                    >

                        No stock available.

                    </td>

                </tr>

                <?php

                }

                ?>

                </tbody>


                <!-- =================================================
                     TOTAL ROW
                ================================================== -->

                <tfoot>

                <tr>

                    <td
                        colspan="10"
                        class="view-client-total-label"
                    >

                        Total Available:

                    </td>

                    <td
                        id="totalAvailable"
                        class="view-client-total-value"
                    >

                        0.000

                    </td>

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


        <!-- =================================================
             ACTION BUTTONS
        ================================================== -->

        <div class="view-client-actions">

            <a
                class="btn btn-primary"
                href="stock_transfer.php"
            >

                Transfer Stock

            </a>


            <a
                class="btn btn-warning"
                href="transfer_history.php"
            >

                View Transfer History

            </a>

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
                "stockTable"
            );

        if (!searchInput || !table) {
            return;
        }


        const tbody =
            table.querySelector("tbody");

        const totalAvailableCell =
            document.getElementById(
                "totalAvailable"
            );


        /* =====================================================
           UPDATE AVAILABLE TOTAL
        ===================================================== */

        function updateTotal() {

            let total = 0;

            const rows =
                tbody.querySelectorAll("tr");

            rows.forEach(
                function (row) {

                    if (
                        row.style.display !== "none" &&
                        row.cells.length >= 11
                    ) {

                        const valueText =
                            row.cells[10]
                                .innerText
                                .replace(/,/g, "")
                                .trim();

                        const value =
                            parseFloat(valueText);

                        if (!isNaN(value)) {

                            total += value;

                        }

                    }

                }
            );


            totalAvailableCell.innerText =
                total.toLocaleString(
                    undefined,
                    {
                        minimumFractionDigits: 3,
                        maximumFractionDigits: 3
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


        /* =====================================================
           INITIAL TOTAL
        ===================================================== */

        updateTotal();


        /* =====================================================
           SEARCH
        ===================================================== */

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
<script src="js/search.js"></script>

</body>
</html>