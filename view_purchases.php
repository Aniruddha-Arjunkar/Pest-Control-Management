```php
<?php
session_start();

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

require 'db_connect.php';
include 'header.php';

$fromDate = $_POST['fromDate'] ?? '';
$endDate  = $_POST['endDate'] ?? '';


/* =========================================================
   MAIN QUERY
========================================================= */

$sql = "
SELECT sp.*, 
       m.name AS material_name, 
       m.rate_unit, 
       b.name AS branch_name
FROM stock_purchases sp
JOIN materials m ON sp.material_id = m.id
JOIN branches b ON sp.branch_id = b.id
";

$conditions = [];

if (!empty($fromDate)) {

    $conditions[] =
        "sp.purchase_date >= '" .
        $conn->real_escape_string($fromDate) .
        "'";
}

if (!empty($endDate)) {

    $conditions[] =
        "sp.purchase_date <= '" .
        $conn->real_escape_string($endDate) .
        "'";
}

if ($conditions) {

    $sql .= " WHERE " . implode(" AND ", $conditions);

}

$sql .= " ORDER BY sp.purchase_date DESC";


$purchases = $conn->query($sql);

if (!$purchases) {
    die("Database Error: " . $conn->error);
}

$total_records = $purchases->num_rows;

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
                            📦
                        </span>

                        All Stock Purchase Records

                    </h1>

                    <p class="view-clients-subtitle">
                        View and manage all stock purchase records
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
                            placeholder="Search branch, material, vendor..."
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
                        id="purchaseTable"
                    >

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Material
                                </th>

                                <th>
                                    Branch
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Rate
                                </th>

                                <th>
                                    Total Cost
                                </th>

                                <th>
                                    Purchase Date
                                </th>

                                <th>
                                    Vendor Name
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        if ($total_records > 0) {

                            $i = 1;

                            while ($row = $purchases->fetch_assoc()) {

                                $total_cost =
                                    (float)$row['quantity'] *
                                    (float)$row['rate'];

                                ?>

                                <tr>


                                    <!-- SR NO -->

                                    <td class="view-client-sr">

                                        <?= $i++; ?>

                                    </td>


                                    <!-- MATERIAL -->

                                    <td class="view-client-name">

                                        <?= htmlspecialchars(
                                            $row['material_name'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- BRANCH -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['branch_name'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- QUANTITY -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['quantity'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        <?= htmlspecialchars(
                                            $row['rate_unit'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- RATE -->

                                    <td>

                                        <?= number_format(
                                            $row['rate'],
                                            2
                                        ); ?>

                                    </td>


                                    <!-- TOTAL COST -->

                                    <td>

                                        <?= number_format(
                                            $total_cost,
                                            2
                                        ); ?>

                                    </td>


                                    <!-- PURCHASE DATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['purchase_date'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- VENDOR -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['vendor_name'] ?? '',
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
                                    colspan="8"
                                    class="view-client-no-data"
                                >

                                    No stock purchase records found.

                                </td>

                            </tr>

                            <?php

                        }

                        ?>

                        </tbody>


                        <!-- =================================================
                             TOTAL
                        ================================================== -->

                        <tfoot>

                            <tr class="view-client-total-row">

                                <td
                                    colspan="5"
                                    class="view-client-total-label"
                                >

                                    Total:

                                </td>

                                <td
                                    id="totalCost"
                                    class="view-client-total-value"
                                >

                                    0.00

                                </td>

                                <td colspan="2"></td>

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
                "purchaseTable"
            );

        if (
            !searchInput ||
            !table
        ) {
            return;
        }


        const tbody =
            table.querySelector("tbody");

        const totalCostCell =
            document.getElementById(
                "totalCost"
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
                        row.style.display !== "none"
                    ) {

                        if (
                            row.cells.length >= 6
                        ) {

                            const totalCostText =
                                row.cells[5]
                                    .innerText
                                    .replace(/,/g, "");

                            const cost =
                                parseFloat(
                                    totalCostText
                                );

                            if (!isNaN(cost)) {

                                total += cost;

                            }

                        }

                    }

                }
            );


            totalCostCell.innerText =
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
```
