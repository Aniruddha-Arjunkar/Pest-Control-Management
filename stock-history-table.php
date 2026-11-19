<?php


/* =========================================================
   SESSION
========================================================= */

session_start();


/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['email'])) {

    $_SESSION['msg'] = "You must log in first";

    header('Location: login.php');

    exit();
}


/* =========================================================
   LOGOUT
========================================================= */

if (isset($_GET['logout'])) {

    session_unset();
    session_destroy();

    header('Location: login.php');

    exit();
}


/* =========================================================
   DATABASE
========================================================= */

require_once 'db_connect.php';


/* =========================================================
   HELPER FUNCTION
   Convert quantity from used unit to rate unit
========================================================= */

function convert_to_rate_unit(
    $quantity,
    $from_unit,
    $to_unit
) {

    $conversion = [

        'ml' => [
            'ltr' => 0.001
        ],

        'ltr' => [
            'ml' => 1000
        ],

        'gm' => [
            'kg' => 0.001
        ],

        'kg' => [
            'gm' => 1000
        ]

    ];


    $from = strtolower(
        trim($from_unit)
    );

    $to = strtolower(
        trim($to_unit)
    );


    /* Same unit */

    if ($from === $to) {

        return $quantity;

    }


    /* Direct conversion */

    if (
        isset(
            $conversion[$from][$to]
        )
    ) {

        return $quantity *
            $conversion[$from][$to];

    }


    /* Reverse conversion */

    if (
        isset(
            $conversion[$to][$from]
        )
    ) {

        return $quantity /
            $conversion[$to][$from];

    }


    /* Unknown conversion */

    return $quantity;
}


/* =========================================================
   FETCH ONLY PURCHASED MATERIALS
========================================================= */

$materials = [];
$rates = [];
$rate_units = [];


$material_q = mysqli_query(
    $conn,
    "
    SELECT
        m.id,
        m.name,
        m.rate_per_unit,
        m.rate_unit

    FROM materials m

    JOIN stock_purchases sp
        ON sp.material_id = m.id

    GROUP BY
        m.id,
        m.name,
        m.rate_per_unit,
        m.rate_unit

    ORDER BY
        m.id
    "
);


if (!$material_q) {

    die(
        "Material query failed: " .
        mysqli_error($conn)
    );

}


while (
    $row =
    mysqli_fetch_assoc($material_q)
) {

    $id = $row['id'];


    $materials[$id] =
        $row['name'];


    $rates[$id] =
        floatval(
            $row['rate_per_unit']
        );


    $rate_units[$id] =
        $row['rate_unit'];
}


/* =========================================================
   BRANCH FILTER
========================================================= */

$branch_filter = '';


if (
    isset($_SESSION['role']) &&
    $_SESSION['role'] !== 'super_admin' &&
    !empty($_SESSION['branch'])
) {

    $branches = explode(',', $_SESSION['branch']);
$branchCondition = [];

foreach ($branches as $b) {

    $b = mysqli_real_escape_string(
        $conn,
        trim($b)
    );

    if ($b !== '') {
        $branchCondition[] = "c.branch = '$b'";
    }
}

if (!empty($branchCondition)) {

    $branch_filter = "
        AND (
            " . implode(" OR ", $branchCondition) . "
        )
    ";

}
}


/* =========================================================
   EXISTING MATERIAL ENTRIES
========================================================= */

$used_entries_with_materials = [];


$added_q = mysqli_query(
    $conn,
    "
    SELECT DISTINCT
        client_id,
        used_date

    FROM service_materials

    WHERE quantity_used > 0
    "
);


if (!$added_q) {

    die(
        "Existing material query failed: " .
        mysqli_error($conn)
    );

}


while (
    $row =
    mysqli_fetch_assoc($added_q)
) {

    $cid =
        $row['client_id'];

    $udate =
        $row['used_date'];


    $used_entries_with_materials[
        "$cid|$udate"
    ] = true;
}


/* =========================================================
   FETCH USAGE DATA
========================================================= */

$usage_data = [];


$usage_q = mysqli_query(
    $conn,
    "
    SELECT

        sm.client_id,

        sm.material_id,

        sm.quantity_used,

        sm.quantity_unit,

        sm.used_date,
        
        c.branch,

        c.cust_name,

        c.services,

        c.due_date

    FROM service_materials sm

    JOIN tbl_client c
        ON sm.client_id = c.id

    WHERE 1

    $branch_filter

    AND NOT EXISTS (

        SELECT 1

        FROM service_materials x

        WHERE
            x.client_id = sm.client_id

        AND
            x.used_date = sm.used_date

        AND
            x.material_id IS NULL

        GROUP BY
            x.client_id,
            x.used_date

        HAVING

            SUM(
                CASE
                    WHEN x.branch_id = 0
                    THEN 1
                    ELSE 0
                END
            ) > 0

        AND

            SUM(
                CASE
                    WHEN x.branch_id = 1
                    THEN 1
                    ELSE 0
                END
            ) > 0

    )

    ORDER BY

        sm.used_date DESC,

        sm.client_id,

        sm.material_id
    "
);


if (!$usage_q) {

    die(
        "Usage query failed: " .
        mysqli_error($conn)
    );

}


while (
    $row =
    mysqli_fetch_assoc($usage_q)
) {

    $cid =
        $row['client_id'];


    $mid =
        $row['material_id'];


    $qty =
        floatval(
            $row['quantity_used']
        );


    $used_date_raw =
        $row['used_date'];


    $uunit =
        $row['quantity_unit'];


    $client =
        $row['cust_name'];


    $services =
        $row['services'];
        
    $branch =
       $row['branch'];    


    $runit =
        $rate_units[$mid]
        ?? $uunit;


    $display_date =
        date(
            "d/m/Y",
            strtotime($used_date_raw)
        );


    $key =
        "$cid|$used_date_raw";


    /*
     * Skip if material was already added
     */

    if (
        isset(
            $used_entries_with_materials[$key]
        )
    ) {

        continue;

    }


    /*
     * Create date/client structure
     */

    if (
        !isset(
            $usage_data[$display_date][$cid]
        )
    ) {

        $usage_data[$display_date][$cid] = [

            'client' =>
                $client,

            'services' =>
                $services,
                
            'branch' =>
                  $branch,

            'used_date_raw' =>
                $used_date_raw,

            'materials' =>
                []

        ];

    }


    /*
     * Add material
     */

    $usage_data[$display_date][$cid]
        ['materials'][$mid][] = [

            'qty' =>
                $qty,

            'uunit' =>
                $uunit,

            'runit' =>
                $runit

        ];
}


/* =========================================================
   TOTAL RECORDS
========================================================= */

$total_records = 0;


foreach (
    $usage_data as $clients
) {

    foreach (
        $clients as $cdata
    ) {

        if (
            empty($cdata['hide']) &&
            !empty($cdata['materials'])
        ) {

            $total_records++;

        }

    }

}


/* =========================================================
   HEADER
========================================================= */

include 'header.php';

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
                            📋
                        </span>

                        Update Stock Usage History

                    </h1>


                    <p class="view-clients-subtitle">

                        View and update material usage
                        for clients

                    </p>

                </div>


                <!-- RECORD COUNT -->

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


            <!-- =================================================
                 CARD
            ================================================== -->

            <div class="view-clients-card">


                <!-- =================================================
                     SEARCH + ACTION
                ================================================== -->

                <div class="view-clients-filter">


                    <!-- SEARCH -->

                    <div class="view-client-search-wrap">

                        <span
                            class="view-client-search-icon"
                        >
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


                    <!-- MISCELLANEOUS STOCK -->

                    <a
                        class="view-client-excel-btn"
                        href="stock_adjustment.php"
                        style="
                            text-decoration:none;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        "
                    >

                        ⚠️ Miscellaneous Stock

                    </a>

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
                                
                                <th>Branch</th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Services
                                </th>

                                <th>
                                    Client
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        $serial = 1;

                        $hasClients = false;


                        foreach (
                            $usage_data
                            as $date => $clients
                        ):

                            foreach (
                                $clients
                                as $cid => $cdata
                            ):


                                if (
                                    !empty(
                                        $cdata['hide']
                                    ) ||
                                    empty(
                                        $cdata['materials']
                                    )
                                ) {

                                    continue;

                                }


                                $hasClients = true;


                                $client =
                                    $cdata['client'];


                                $services =
                                    $cdata['services'];


                                $used_date_raw =
                                    $cdata['used_date_raw'];

                        ?>

                            <tr>


                                <!-- SR NO -->

                                <td class="view-client-sr">

                                    <?= $serial++; ?>

                                </td>
                                
                                
                                <!-- BRANCH -->

<td>

    <?php
    $branchClass = 'blue';

    $branchLower = strtolower(
        trim($cdata['branch'] ?? '')
    );

    if ($branchLower === 'nagpur') {

        $branchClass = 'blue';

    } elseif ($branchLower === 'bramhapuri') {

        $branchClass = 'green';

    } elseif (
        $branchLower === 'chandrapur' ||
        $branchLower === 'chandreapur'
    ) {

        $branchClass = 'orange';

    }
    ?>

    <span class="view-client-branch <?= $branchClass; ?>">

        <?= htmlspecialchars(
            $cdata['branch'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        ); ?>

    </span>

</td>


                                <!-- DATE -->

                                <td
                                    class="view-client-date-cell"
                                >

                                    <?= htmlspecialchars(
                                        $date,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- SERVICES -->

                                <td
                                    class="view-client-service"
                                >

                                    <?= htmlspecialchars(
                                        $services ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- CLIENT -->

                                <td
                                    class="view-client-name"
                                >

                                    <?= htmlspecialchars(
                                        $client ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>

                                </td>


                                <!-- ACTION -->

                                <td
                                    class="view-client-action"
                                >

                                    <a
                                        href="add-service-materials.php?client_id=<?= intval($cid); ?>&used_date=<?= urlencode($used_date_raw); ?>"
                                        class="view-client-excel-btn"
                                    >

                                        ➕ Add Material

                                    </a>

                                </td>

                            </tr>

                        <?php

                            endforeach;

                        endforeach;


                        if (!$hasClients):

                        ?>

                            <tr>

                                <td
                                    colspan="6"
                                    class="view-client-no-data"
                                >

                                    🎉 All clients have
                                    updated materials.

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


<!-- =========================================================
     EXISTING JAVASCRIPT
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
