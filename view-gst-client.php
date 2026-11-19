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

include 'header.php';

?>
<style>
  .view-client-view-btn {
    font-size:12.5px;
    background:green;
font-weight:500;
width:150px;
}
  .view-client-view-btn:hover {

    background:green;
  }
</style>
<body>

<section id="main-content">
    
<section class="wrapper">

    <div class="view-clients-page">


        <div class="view-clients-header">

            <div class="view-clients-title-wrap">

                <h1 class="view-clients-title">

                    <span class="view-clients-title-icon">
                        🧾
                    </span>

                    View GST Clients 

                </h1>

                <p class="view-clients-subtitle">
                    View GST applied clients and pending invoice amounts
                </p>

            </div>


            <?php

            $clientWhere = "WHERE cgst > 0 AND sgst > 0";

            if (
                $_SESSION['role'] !== 'super_admin'
                &&
                !empty($_SESSION['branch'])
            ) {

                $branch = mysqli_real_escape_string(
                    $conn,
                    $_SESSION['branch']
                );

                $clientWhere .= "
                    AND branch = '$branch'
                ";

            }


            if (
                isset($_POST['but_search'])
            ) {

                $fromDate =
                    $_POST['fromDate'] ?? '';

                $endDate =
                    $_POST['endDate'] ?? '';


                if (
                    !empty($fromDate)
                    &&
                    !empty($endDate)
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

                    $clientWhere .= "
                        AND due_date
                        BETWEEN '$safeFromDate'
                        AND '$safeEndDate'
                    ";

                }

            }


            $sql = "

                SELECT *

                FROM (

                    SELECT

                        'Pest Control' AS source,

                        c.Id AS Id,

                        c.branch,

                        c.services,

                        c.cust_name AS name,

                        c.cust_phone AS phone,

                        c.cust_address AS address,

                        c.due_date AS date,

                        c.final_amount AS amount,

                        IFNULL(
                            SUM(
                                CAST(
                                    p.paid_amount
                                    AS DECIMAL(10,2)
                                )
                            ),
                            0
                        ) AS paid_amount,

                        IFNULL(
                            SUM(p.tds_amount),
                            0
                        ) AS tds_amount

                    FROM tbl_client c

                    LEFT JOIN tbl_payment p

                        ON p.client_Id = c.Id

                        AND p.status = 'active'

                    $clientWhere

                    GROUP BY c.Id

                    HAVING
                        amount >
                        (
                            paid_amount
                            +
                            IFNULL(
                                tds_amount,
                                0
                            )
                        )


                    UNION ALL


                    SELECT

                        'Placement' AS source,

                        a.Id,

                        'Nagpur' AS branch,

                        'Consultancy' AS services,

                        a.name,

                        a.phone,

                        a.shipping_address AS address,

                        p.candidate_pay_date AS date,

                        IFNULL(

                            CAST(
                                p.candidate_pay_amount
                                AS DECIMAL(10,2)
                            )

                            +

                            IFNULL(
                                p.candidate_gst_amount,
                                0
                            ),

                            0

                        ) AS amount,


                        IFNULL(

                            (

                                SELECT
                                    SUM(
                                        CAST(
                                            pmt.paid_amount
                                            AS DECIMAL(10,2)
                                        )
                                    )

                                FROM tbl_payment pmt

                                WHERE pmt.client_Id IN (

                                    SELECT candidate_id

                                    FROM tbl_placement

                                    WHERE candidate_name = a.name

                                )

                                AND pmt.payment_type =
                                    'Candidate Pay'

                            ),

                            0

                        ) AS paid_amount,


                        IFNULL(

                            (

                                SELECT
                                    SUM(
                                        pmt.tds_amount
                                    )

                                FROM tbl_payment pmt

                                WHERE pmt.client_Id IN (

                                    SELECT candidate_id

                                    FROM tbl_placement

                                    WHERE candidate_name = a.name

                                )

                                AND pmt.payment_type =
                                    'Candidate Pay'

                            ),

                            0

                        ) AS tds_amount


                    FROM tbl_agreement a

                    LEFT JOIN tbl_placement p

                        ON p.candidate_name = a.name

                    WHERE

                        a.shipping_address IS NOT NULL

                        AND a.shipping_address != ''

                        AND IFNULL(
                            p.candidate_gst_amount,
                            0
                        ) > 0

                    GROUP BY p.candidate_id

                    HAVING
                        amount >
                        (
                            paid_amount
                            +
                            IFNULL(
                                tds_amount,
                                0
                            )
                        )


                    UNION ALL


                    SELECT

                        'Consultant' AS source,

                        asoc.Id,

                        'Nagpur' AS branch,

                        'Consultancy' AS services,

                        CONCAT(
                            asoc.company_name,
                            ' (',
                            p.candidate_name,
                            ')'
                        ) AS name,

                        asoc.phone,

                        asoc.shipping_address AS address,

                        p.company_pay_date AS date,

                        CAST(
                            p.company_pay_amount
                            AS DECIMAL(10,2)
                        )
                        +
                        IFNULL(
                            p.company_gst_amount,
                            0
                        ) AS amount,

                        SUM(
                            CAST(
                                pay.paid_amount
                                AS DECIMAL(10,2)
                            )
                        ) AS paid_amount,

                        IFNULL(
                            SUM(
                                pay.tds_amount
                            ),
                            0
                        ) AS tds_amount


                    FROM tbl_associate asoc

                    INNER JOIN tbl_placement p

                        ON p.company_name =
                           asoc.company_name

                    INNER JOIN tbl_payment pay

                        ON (
                            pay.client_Id =
                            p.candidate_id

                            OR

                            pay.interview_id =
                            p.interview_id
                        )

                        AND pay.payment_type =
                            'Company Pay'

                        AND pay.status =
                            'active'


                    WHERE

                        asoc.shipping_address IS NOT NULL

                        AND asoc.shipping_address != ''

                        AND IFNULL(
                            p.company_gst_amount,
                            0
                        ) > 0

                    GROUP BY p.candidate_id

                    HAVING
                        amount >
                        (
                            paid_amount
                            +
                            IFNULL(
                                tds_amount,
                                0
                            )
                        )

                ) AS final_data

            ";


            if (
                $_SESSION['role'] == 'HR'
            ) {

                $sql .= "
                    WHERE source != 'Pest Control'
                ";

            }


            $sql .= "
                ORDER BY
                date DESC,
                source ASC
            ";


            $result =
                mysqli_query(
                    $conn,
                    $sql
                );


            if (!$result) {

                die(
                    "Database Error: "
                    .
                    mysqli_error($conn)
                );

            }


            $total_records =
                mysqli_num_rows(
                    $result
                );

            ?>


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


        <div class="view-clients-card">


            <form
                method="post"
                action=""
                class="view-clients-filter"
            >


                <div class="view-client-search-wrap">

                    <span class="view-client-search-icon">
                        🔍
                    </span>

                    <input
                        type="text"
                        id="txt_searchall"
                        class="view-client-search"
                        placeholder="Search GST clients..."
                        autocomplete="off"
                    >

                </div>


                <input
                    type="date"
                    name="fromDate"
                    id="start_date"
                    class="view-client-date"
                    value="<?= htmlspecialchars(
                        $_POST['fromDate'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >


                <input
                    type="date"
                    name="endDate"
                    id="end_date"
                    class="view-client-date"
                    value="<?= htmlspecialchars(
                        $_POST['endDate'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >


                <button
                    type="submit"
                    name="but_search"
                    class="view-client-search-btn"
                >
                    🔍 Search
                </button>


            </form>


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
                                Source
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
                                Final Amount
                            </th>

                            <th>
                                Paid Amount
                            </th>

                            <th class="view-client-action">
                                Invoice / Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    if (
                        $total_records > 0
                    ) {

                        $i = 1;


                        while (
                            $row =
                            mysqli_fetch_assoc(
                                $result
                            )
                        ) {


                            $source =
                                htmlspecialchars(
                                    $row['source'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                            $branchName =
                                htmlspecialchars(
                                    $row['branch'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                            $services =
                                htmlspecialchars(
                                    $row['services'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                            $name =
                                htmlspecialchars(
                                    $row['name'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                            $phone =
                                htmlspecialchars(
                                    $row['phone'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                            $address =
                                htmlspecialchars(
                                    $row['address'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                            $date =
                                htmlspecialchars(
                                    $row['date'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                            $amount =
                                htmlspecialchars(
                                    $row['amount'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


                            $paidAmount =
                                htmlspecialchars(
                                    $row['paid_amount'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );


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


                            $todayClass = '';


                            if (
                                !empty(
                                    $row['date']
                                )
                                &&
                                date(
                                    'Y-m-d',
                                    strtotime(
                                        $row['date']
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


                                <td class="view-client-sr">

                                    <?= $i++; ?>

                                </td>

<td style="max-width:30px">

    <span
        class="
            view-client-branch
            <?= $branchClass; ?>
        "
    >

        <?= $branchName; ?>

    </span>

</td>

                               <td style="min-width:100px">
                                <?= $source; ?>
                            </td>

                                <td style="min-width:120px";class="view-client-service">

                                    <?= $services; ?>

                                </td>


                                <td style="min-width:140px"; class="view-client-name">

                                    <?= $name; ?>

                                </td>


                                <td style="min-width:115px";class="view-client-phone">

                                    <?= $phone; ?>

                                </td>


                                <td class="view-client-address">

                                    <?= $address; ?>

                                </td>


                                <td style="min-width:120px";
                                    class="
                                        view-client-date-cell
                                        <?= $todayClass; ?>
                                    "
                                >

                                    <?= $date; ?>

                                </td>


                                <td>

                                    ₹<?= $amount; ?>

                                </td>


                                <td>

                                    ₹<?= $paidAmount; ?>

                                </td>


                                <td class="view-client-action">


                                    <a
                                        href="generate_invoice.php?client_id=<?= intval($row['Id']); ?>&source_type=<?= urlencode($row['source']); ?>&amount=<?= urlencode($row['amount']); ?>"
                                        class="view-client-view-btn"
                                    >

                                        🧾 Generate Invoice

                                    </a>


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

                                No GST-applied records found.

                            </td>

                        </tr>


                        <?php

                    }

                    ?>


                    </tbody>

                </table>

            </div>


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

</section


</section>

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


/* DATE FILTER */

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
                            from
                            &&
                            rowDate <
                            new Date(from)
                        ) {

                            hide = true;

                        }


                        if (
                            to
                            &&
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


/* BOTTOM SCROLL */

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

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>

<script src="js/search.js"></script>

</body>
</html>

<?php

mysqli_close($conn);

?>