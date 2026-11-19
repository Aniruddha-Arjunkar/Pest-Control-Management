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
ob_start(); 
?>

<style>
  .view-client-action-buttons {
    display: none;
    flex-direction: column;
    gap: 10px;
    min-width: 160px;
}

.view-client-action-buttons a,
button.view-client-action-toggle {
    width: 160px;
    padding: 9px 12px;
    box-sizing: border-box;
    border: 0;
    border-radius: 6px;
    color: #fff !important;
    text-align: center;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}

button.view-client-action-toggle {
    width: auto;
    background: #3f65e7 !important;
}

.view-client-edit-btn {
    background: #2563eb !important;
}

.view-client-print-amc-btn {
    background: #16a34a !important;
}

.view-client-print-no-amc-btn {
    background: #46b8da !important;
}

.view-client-view-btn {
    background: #f59e0b !important;
}

.view-client-print-btn{

background: #31c745 !important;
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

                        View GST Invoices

                    </h1>

                    <p class="view-clients-subtitle">
                        View and manage all GST invoices
                    </p>

                </div>

                <span class="view-clients-count">
    <span id="gst-invoice-count-placeholder">{{INVOICE_COUNT_PLACEHOLDER}} {{INVOICE_COUNT_LABEL_PLACEHOLDER}}</span>
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
                            placeholder="Search GST invoices..."
                            autocomplete="off"
                        >

                    </div>


                    <input
                        type="date"
                        name="fromDate"
                        id="start_date"
                        class="view-client-date"
                        value="<?= htmlspecialchars($_POST['fromDate'] ?? ''); ?>"
                    >


                    <input
                        type="date"
                        name="endDate"
                        id="end_date"
                        class="view-client-date"
                        value="<?= htmlspecialchars($_POST['endDate'] ?? ''); ?>"
                    >


                    <button
                        type="submit"
                        name="but_search"
                        class="view-client-search-btn"
                    >
                        🔍 Search
                    </button>

                </form>


                <?php if (!empty($_POST['fromDate']) && !empty($_POST['endDate'])): ?>

                    <form
                        method="post"
                        action="bulk-print-pdf.php"
                        target="_blank"
                        class="view-client-bulk-actions"
                    >

                        <input
                            type="hidden"
                            name="fromDate"
                            value="<?= htmlspecialchars($_POST['fromDate']); ?>"
                        >

                        <input
                            type="hidden"
                            name="endDate"
                            value="<?= htmlspecialchars($_POST['endDate']); ?>"
                        >

                        <button style="background:#399529; color:white; border-radius:10px; font-size:13px;"
                            type="submit"
                            name="action"
                            value="print"
                            class="view-client-print-btn"
                        >
                            🖨️ Print All
                        </button>

                        <button  style="background:#295d9f; color:white; border-radius:10px; font-size:13px;"
                            type="submit"
                            name="action"
                            value="pdf"
                            class="view-client-pdf-btn"
                        >
                            ⬇️ Download PDF
                        </button>

                    </form>

                <?php endif; ?>


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

                                <th style="min-width: 100px">
                                    Source
                                </th>

                                <th>
                                    Branch
                                </th>

                                <th style="min-width: 120px">
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
                                    Invoice No
                                </th>

                                <th style="min-width: 120px">
                                    Invoice Date
                                </th>

                                <th>
                                    Comments
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Total Amount
                                </th>

                                <th class="view-client-action">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        $from = mysqli_real_escape_string(
                            $conn,
                            $_POST['fromDate'] ?? ''
                        );

                        $to = mysqli_real_escape_string(
                            $conn,
                            $_POST['endDate'] ?? ''
                        );


                        $pestControlDateFilter =
                            (!empty($from) && !empty($to))
                            ?
                            "AND (
                                (gi.invoice_date BETWEEN '$from' AND '$to')
                                OR
                                (gi.id IS NULL AND c.due_date BETWEEN '$from' AND '$to')
                            )"
                            :
                            "";


                        $placementDateFilter =
                            (!empty($from) && !empty($to))
                            ?
                            "AND gi.invoice_date BETWEEN '$from' AND '$to'"
                            :
                            "";


                        $consultantDateFilter =
                            (!empty($from) && !empty($to))
                            ?
                            "AND gi.invoice_date BETWEEN '$from' AND '$to'"
                            :
                            "";


                        $branchFilter = "";

                        if (
                            $_SESSION['role'] !== 'super_admin'
                            &&
                            !empty($_SESSION['branch'])
                        ) {

                            $branch = mysqli_real_escape_string(
                                $conn,
                                $_SESSION['branch']
                            );

                            $branchFilter =
                                "AND c.branch = '$branch'";
                        }


                        $sql = "

                        SELECT * FROM (

                            SELECT

                                'Pest Control' AS source,

                                c.Id AS entity_id,

                                c.branch,

                                c.services,

                                c.cust_name AS name,

                                c.cust_phone AS phone,

                                c.cust_address AS address,

                                COALESCE(
                                    gi.invoice_number,
                                    c.gst_invoice_no
                                ) AS invoice_number,

                                COALESCE(
                                    gi.invoice_date,
                                    c.due_date
                                ) AS invoice_date,

                                gi.comments AS invoice_comments,

                                c.other_information AS comments,

                                gi.gst_type,

                                gi.amount,

                                gi.total_amount,

                                c.final_amount AS fallback_amount,

                                gi.id AS invoice_id

                            FROM tbl_client c

                            LEFT JOIN tbl_gst_invoice gi

                                ON gi.entity_id = c.Id

                                AND gi.source_type = 'Pest Control'

                            WHERE c.cgst > 0

                            AND c.sgst > 0

                            AND (

                                gi.id IS NOT NULL

                                OR (

                                    gi.id IS NULL

                                    AND c.gst_invoice_no IS NOT NULL

                                    AND c.due_date < '2025-03-01'

                                )

                            )

                            $branchFilter

                            $pestControlDateFilter


                            UNION ALL


                            SELECT

                                'Placement' AS source,

                                a.Id AS entity_id,

                                'Nagpur' AS branch,

                                'Consultancy' AS services,

                                a.name,

                                a.phone,

                                a.shipping_address AS address,

                                gi.invoice_number,

                                gi.invoice_date,

                                gi.comments AS invoice_comments,

                                '' AS comments,

                                gi.gst_type,

                                gi.amount,

                                gi.total_amount,

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
                                ) AS fallback_amount,

                                gi.id AS invoice_id

                            FROM tbl_agreement a

                            LEFT JOIN tbl_placement p

                                ON p.candidate_name = a.name

                            LEFT JOIN tbl_gst_invoice gi

                                ON gi.entity_id = a.Id

                                AND gi.source_type = 'Placement'

                            WHERE a.shipping_address IS NOT NULL

                            AND a.shipping_address != ''

                            AND IFNULL(
                                p.candidate_gst_amount,
                                0
                            ) > 0

                            AND gi.id IS NOT NULL

                            $placementDateFilter


                            UNION ALL


                            SELECT

                                'Consultant' AS source,

                                asoc.Id AS entity_id,

                                'Nagpur' AS branch,

                                'Consultancy' AS services,

                                asoc.company_name AS name,

                                asoc.phone,

                                asoc.shipping_address AS address,

                                gi.invoice_number,

                                gi.invoice_date,

                                gi.comments AS invoice_comments,

                                '' AS comments,

                                gi.gst_type,

                                gi.amount,

                                gi.total_amount,

                                IFNULL(
                                    CAST(
                                        p.company_pay_amount
                                        AS DECIMAL(10,2)
                                    )
                                    +
                                    IFNULL(
                                        p.company_gst_amount,
                                        0
                                    ),
                                    0
                                ) AS fallback_amount,

                                gi.id AS invoice_id

                            FROM tbl_associate asoc

                            LEFT JOIN (

                                SELECT

                                    company_name,

                                    MAX(company_pay_amount)
                                    AS company_pay_amount,

                                    MAX(company_gst_amount)
                                    AS company_gst_amount

                                FROM tbl_placement

                                GROUP BY company_name

                            ) p

                            ON p.company_name =
                            asoc.company_name

                            LEFT JOIN tbl_gst_invoice gi

                            ON gi.entity_id = asoc.Id

                            AND gi.source_type = 'Consultant'

                            WHERE asoc.gst_number IS NOT NULL

                            AND asoc.gst_number != ''

                            AND IFNULL(
                                p.company_gst_amount,
                                0
                            ) > 0

                            AND gi.id IS NOT NULL

                            $consultantDateFilter

                        ) AS final_data

                        ";


                        if ($_SESSION['role'] == 'HR') {

                            $sql .= "
                                WHERE source != 'Pest Control'
                            ";

                        }


                        $sql .= "
                            ORDER BY invoice_date DESC
                        ";


                        $result = mysqli_query(
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
$header_html = ob_get_clean();
$header_html = str_replace('{{INVOICE_COUNT_PLACEHOLDER}}', $total_records, $header_html);
$header_html = str_replace('{{INVOICE_COUNT_LABEL_PLACEHOLDER}}', ($total_records == 1 ? 'record' : 'records'), $header_html);
echo $header_html;
ob_start(); // resume buffering for the rest of the table markup that follows


                        $total_records =
                            mysqli_num_rows($result);


                        if ($total_records > 0):

                            $i = 1;

                            while (
                                $row =
                                mysqli_fetch_assoc($result)
                            ):

                                $source =
                                    htmlspecialchars(
                                        $row['source'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $branchName =
                                    htmlspecialchars(
                                        $row['branch'] ?: '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $service =
                                    htmlspecialchars(
                                        $row['services'] ?: '—',
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


                                $invoiceNumber =
                                    htmlspecialchars(
                                        $row['invoice_number'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $invoiceDate =
                                    htmlspecialchars(
                                        $row['invoice_date'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $invoiceComments =
                                    htmlspecialchars(
                                        $row['invoice_comments']
                                        ?: ($row['comments'] ?? '—'),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $amount =
                                    (float) (
                                        $row['amount'] ?? 0
                                    );


                                $totalAmount =
                                    !empty($row['total_amount'])
                                    ?
                                    (float)$row['total_amount']
                                    :
                                    (float)(
                                        $row['fallback_amount'] ?? 0
                                    );

                                ?>

                                <tr>

                                    <td class="view-client-sr">
                                        <?= $i++; ?>
                                    </td>


                                    <td>
                                        <span class="view-client-source">
                                            <?= $source; ?>
                                        </span>
                                    </td>


                                    <td>

                                        <span class="view-client-branch blue">

                                            <?= $branchName; ?>

                                        </span>

                                    </td>


                                    <td class="view-client-service">

                                        <?= $service; ?>

                                    </td>


                                    <td  style="min-width:120px" class="view-client-name">

                                        <?= $name; ?>

                                    </td>


                                    <td style="max-width:50px" class="view-client-phone">

                                        <?= $phone; ?>

                                    </td>


                                    <td class="view-client-address">

                                        <?= $address; ?>

                                    </td>


                                    <td class="view-client-invoice">

                                        <?= $invoiceNumber; ?>

                                    </td>


                                    <td class="view-client-date-cell">

                                        <?= $invoiceDate; ?>

                                    </td>


                                    <td class="view-client-address">

                                        <?= $invoiceComments; ?>

                                    </td>


                                    <td class="view-client-amount">

                                        ₹<?= number_format(
                                            $amount,
                                            2
                                        ); ?>

                                    </td>


                                    <td class="view-client-amount">

                                        ₹<?= number_format(
                                            $totalAmount,
                                            2
                                        ); ?>

                                    </td>


                                    <td class="view-client-action">

                                        <button
                                            type="button"
                                            class="view-client-action-toggle action-toggle"
                                        >
                                            Action ▾
                                        </button>


                                        <div class="view-client-action-buttons action-buttons">

                                            <?php if (!empty($row['invoice_id'])): ?>

                                                <a
                                                    href="edit-gst-invoice.php?id=<?= intval($row['invoice_id']); ?>"
                                                    class="view-client-edit-btn"
                                                >
                                                    Edit
                                                </a><br>


                                                <?php if ($row['source'] === 'Pest Control'): ?>

                                                     <a
                                                        href="print-tax-invoice-edit.php?invoice_number=<?= urlencode($row['invoice_number']); ?>&show_amc=1"
                                                        class="view-client-print-amc-btn"
                                                        target="_blank"
                                                    >
                                                        Print With AMC
                                                    </a><br>


                                                    <a
                                                        href="print-tax-invoice-edit.php?invoice_number=<?= urlencode($row['invoice_number']); ?>&show_amc=0"
                                                       class="view-client-print-no-amc-btn"
                                                        target="_blank"
                                                    >
                                                        Print Without AMC
                                                    </a>

                                                <?php else: ?>

                                                    <a
                                                        href="print-tax-invoice-edit.php?invoice_number=<?= urlencode($row['invoice_number']); ?>"
                                                        class="view-client-print-btn"
                                                        target="_blank"
                                                    >
                                                        Print
                                                    </a>

                                                <?php endif; ?>


                                            <?php else: ?>

                                                 <a
                                                    href="print-tax-invoice.php?client_id=<?= intval($row['entity_id']); ?>&source_type=<?= urlencode($row['source']); ?>"
                                                    class="view-client-view-btn"
                                                >
                                                    Print GST Invoice
                                                </a>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>


                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="13"
                                    class="view-client-no-data"
                                >

                                    No GST invoices found.

                                </td>

                            </tr>

                        <?php endif; ?>

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

    </section>

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


        if (!searchInput || !table) {
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


document.addEventListener(
    "DOMContentLoaded",
    function () {

        document
            .querySelectorAll(
                ".action-toggle"
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        "click",
                        function () {

                            const actions =
                                this.nextElementSibling;


                            if (
                                actions.style.display ===
                                "block"
                            ) {

                                actions.style.display =
                                    "none";

                            } else {

                                document
                                    .querySelectorAll(
                                        ".action-buttons"
                                    )
                                    .forEach(
                                        function (item) {

                                            item.style.display =
                                                "none";

                                        }
                                    );


                                actions.style.display =
                                    "block";

                            }

                        }
                    );

                }
            );

    }
);


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
echo ob_get_clean();

?>
