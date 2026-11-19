```php
<?php

session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
include 'header.php';


// =========================================================
// UNIT CONVERSION
// =========================================================

function convert_to_rate_unit($quantity, $from_unit, $to_unit)
{
    $conversion = [
        'ml'  => ['ltr' => 0.001],
        'ltr' => ['ml' => 1000],
        'gm'  => ['kg' => 0.001],
        'kg'  => ['gm' => 1000],
    ];

    $from = strtolower(trim($from_unit));
    $to   = strtolower(trim($to_unit));

    if ($from === $to) {
        return $quantity;
    }

    if (isset($conversion[$from][$to])) {
        return $quantity * $conversion[$from][$to];
    }

    if (isset($conversion[$to][$from])) {
        return $quantity / $conversion[$to][$from];
    }

    return $quantity;
}


// =========================================================
// CLIENT FILTER
// =========================================================

$clients = mysqli_query(
    $conn,
    "SELECT Id, cust_name, due_date
     FROM tbl_client
     ORDER BY cust_name"
);

$filter_client_id = isset($_GET['client_id'])
    ? intval($_GET['client_id'])
    : 0;


// =========================================================
// MAIN QUERY
// =========================================================

$sql = "
SELECT

    sm.client_id,

    c.cust_name,

    b.name AS branch_name,

    m.name AS material_name,

    sm.quantity_used,

    sm.quantity_unit,

    sm.rate_at_time,

    m.rate_unit,

    c.due_date,

    sm.used_date

FROM service_materials sm

INNER JOIN tbl_client c
    ON sm.client_id = c.Id

LEFT JOIN branches b
    ON sm.branch_id = b.id

INNER JOIN materials m
    ON sm.material_id = m.id
";


if ($filter_client_id > 0) {

    $sql .= "
        WHERE sm.client_id = $filter_client_id
    ";
}


$sql .= "
    ORDER BY sm.used_date DESC
";


$result = mysqli_query($conn, $sql);


// =========================================================
// TOTAL RECORDS
// =========================================================

$total_records = 0;

if ($result) {
    $total_records = mysqli_num_rows($result);
}

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

                        Materials Used Per Client

                    </h1>

                    <p class="view-clients-subtitle">
                        View material usage and cost details per client
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
                            placeholder="Search client, branch, material..."
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
                        id="payments-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Sr. No.
                                </th>

                                <th>
                                    Client Name
                                </th>

                                <th>
                                    Branch
                                </th>

                                <th>
                                    Material
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Rate (₹)
                                </th>

                                <th>
                                    Total (₹)
                                </th>

                                <th>
                                    Used On
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        if (
                            $result &&
                            mysqli_num_rows($result) > 0
                        ):

                            $i = 1;

                            while (
                                $row =
                                mysqli_fetch_assoc($result)
                            ):


                                // =================================================
                                // CONVERT QUANTITY
                                // =================================================

                                $converted_qty =
                                    convert_to_rate_unit(
                                        $row['quantity_used'],
                                        $row['quantity_unit'],
                                        $row['rate_unit']
                                    );


                                // =================================================
                                // TOTAL COST
                                // =================================================

                                $total_cost =
                                    $converted_qty *
                                    $row['rate_at_time'];

                        ?>

                            <tr>


                                <!-- SR NO -->

                                <td class="view-client-sr">

                                    <?= $i++; ?>

                                </td>


                                <!-- CLIENT -->

                                <td class="view-client-name">

                                    <?= htmlspecialchars(
                                        $row['cust_name']
                                    ); ?>

                                </td>


                                <!-- BRANCH -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row['branch_name'] ?? ''
                                    ); ?>

                                </td>


                                <!-- MATERIAL -->

                                <td class="view-client-material">

                                    <?= htmlspecialchars(
                                        $row['material_name']
                                    ); ?>

                                </td>


                                <!-- QUANTITY -->

                                <td class="view-client-quantity">

                                    <?= number_format(
                                        $row['quantity_used'],
                                        2
                                    ); ?>

                                    <?= htmlspecialchars(
                                        $row['quantity_unit']
                                    ); ?>

                                </td>


                                <!-- RATE -->

                                <td class="view-client-rate">

                                    ₹<?= number_format(
                                        $row['rate_at_time'],
                                        2
                                    ); ?>

                                    /

                                    <?= htmlspecialchars(
                                        $row['rate_unit']
                                    ); ?>

                                </td>


                                <!-- TOTAL -->

                                <td class="view-client-total">

                                    ₹<?= number_format(
                                        $total_cost,
                                        2
                                    ); ?>

                                </td>


                                <!-- DATE -->

                                <td class="view-client-date-cell">

                                    <?= htmlspecialchars(
                                        $row['used_date']
                                    ); ?>

                                </td>


                            </tr>

                        <?php

                            endwhile;

                        else:

                        ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="view-client-no-data"
                                >

                                    No material usage found.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     BOTTOM HORIZONTAL SCROLL
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
                            searchValue === "" ||
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

                        } else {

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


<!-- EXISTING JS -->

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
