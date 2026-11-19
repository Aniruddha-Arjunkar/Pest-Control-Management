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

$total_tds_due = 0;
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

                    TDS Due Payments

                </h1>

                <p class="view-clients-subtitle">
                    View and manage pending TDS payments
                </p>

            </div>

            <span class="view-clients-count">

                TDS Due

            </span>

        </div>


        <!-- =================================================
             CARD
        ================================================== -->

        <div class="view-clients-card">


            <!-- =================================================
                 SEARCH
            ================================================== -->

            <form
                method="get"
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
                        placeholder="Search by Name or Branch"
                        autocomplete="off"
                    >

                </div>

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
                                TDS Amount
                            </th>

                            <th>
                                Payment Date
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

<?php

$sql = "
SELECT
    p.Id,
    p.client_Id,

    CASE
        WHEN p1.company_name IS NOT NULL
             AND p1.candidate_name IS NOT NULL
        THEN CONCAT(
            p1.company_name,
            ' (',
            p1.candidate_name,
            ')'
        )

        WHEN p1.company_name IS NOT NULL
        THEN p1.company_name

        WHEN p1.candidate_name IS NOT NULL
        THEN p1.candidate_name

        ELSE c.cust_name
    END AS cust_name,

    COALESCE(c.branch,'Nagpur') AS branch,

    p.total_amount,
    p.tds_amount,
    p.payment_date

FROM tbl_payment p

LEFT JOIN tbl_client c
    ON c.Id = p.client_Id

LEFT JOIN tbl_placement p1
    ON p1.interview_id = p.interview_id

WHERE
    p.tds_amount > 0
    AND (p.tds_paid IS NULL OR p.tds_paid = 0)

GROUP BY
    p.client_Id,
    p.payment_date

ORDER BY
    p.payment_date DESC
";

$result = mysqli_query($conn, $sql);

$serial = 1;

if ($result && mysqli_num_rows($result) > 0) {

    while ($row = mysqli_fetch_assoc($result)) {

        $total_tds_due += (float)$row['tds_amount'];

        $branchName = htmlspecialchars(
            $row['branch'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

        $clientName = htmlspecialchars(
            $row['cust_name'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

        $tdsAmount = number_format(
            (float)$row['tds_amount'],
            2
        );

        $paymentDate = htmlspecialchars(
            $row['payment_date'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );


        /* =================================================
           BRANCH COLOR
        ================================================= */

        $branchClass = 'blue';

        $branchLower = strtolower(
            trim($row['branch'] ?? '')
        );

        if ($branchLower === 'nagpur') {

            $branchClass = 'blue';

        }

        elseif ($branchLower === 'bramhapuri') {

            $branchClass = 'green';

        }

        elseif (
            $branchLower === 'chandrapur'
            ||
            $branchLower === 'chandreapur'
        ) {

            $branchClass = 'orange';

        }

?>

<tr>

<!-- SR NO -->

<td class="view-client-sr">

    <?= $serial++; ?>

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

<td class="view-client-name">

    <?= $clientName; ?>

</td>


<!-- TDS -->

<td class="view-client-tds-amount">

    ₹ <?= $tdsAmount; ?>

</td>


<!-- PAYMENT DATE -->

<td class="view-client-date-cell">

    <?= $paymentDate; ?>

</td>


<!-- ACTION -->

<td class="view-client-action">

    <a
        href="add-payment.php?payment_id=<?= intval($row['Id']); ?>"
        class="view-client-view-btn"
    >

        💰 Pay TDS

    </a>

</td>


</tr>

<?php

    }

} else {

?>

<tr>


<td
    colspan="6"
    class="view-client-no-data"
>

    No TDS Due Found

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
                 TOTAL TDS
            ================================================== -->

            <div class="view-client-total">

                Total TDS Due (INR):

                <span id="total-tds">

                    ₹ <?= number_format($total_tds_due, 2); ?>

                </span>

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
            !searchInput
            ||
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

                let total = 0;

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


                            /* Calculate visible TDS */

                            const tdsCell =
                                row.querySelector(
                                    ".view-client-tds-amount"
                                );

                            if (tdsCell) {

                                let value =
                                    tdsCell.textContent
                                        .replace(
                                            /[^0-9.-]/g,
                                            ''
                                        );

                                total +=
                                    parseFloat(value)
                                    || 0;

                            }

                        }

                        else {

                            row.style.display =
                                "none";

                        }

                    }
                );


                document.getElementById(
                    "total-tds"
                ).textContent =
                    "₹ " + total.toFixed(2);

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


        function updateScrollWidth() {

            bottomInner.style.width =
                tableWrapper.scrollWidth
                + "px";

        }


        updateScrollWidth();


        window.addEventListener(
            "resize",
            updateScrollWidth
        );


        /* Bottom scrollbar -> table */

        bottomScroll.addEventListener(
            "scroll",
            function () {

                tableWrapper.scrollLeft =
                    bottomScroll.scrollLeft;

            }
        );


        /* Table -> bottom scrollbar */

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

</body>
</html>
