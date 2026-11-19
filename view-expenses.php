<?php
session_start();
require_once 'db_connect.php';
include 'header.php';

// Redirect to login if not logged in
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location:login.php');
    exit();
}
?>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">
    <link href="css/datatables.min.css" rel="stylesheet" type="text/css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
    
</head>
<body>

<section id="main-content">
    <div class="view-clients-page">
        <div class="view-clients-card">

            <!-- PAGE HEADER -->
            <div class="view-clients-header">
                <div class="view-clients-title-wrap">
                    <h2 class="view-clients-title">
                        <span class="view-clients-title-icon">💰</span>
                        View Expenses
                    </h2>
                </div>
            </div>

            <!-- Search filter -->
            <div class="view-clients-filter">

                <div class="view-client-search-wrap">
                    <span class="view-client-search-icon">🔍</span>

                    <input type="text"
                           id="txt_searchall"
                           class="view-client-search"
                           placeholder="Search">
                </div>

                <form method="post" action="" style="display: contents;">

                    <input type="date"
                           name="fromDate"
                           value="<?= $_POST['fromDate'] ?? ''; ?>"
                           class="view-client-date">

                    <input type="date"
                           name="endDate"
                           value="<?= $_POST['endDate'] ?? ''; ?>"
                           class="view-client-date">

                    <input type="submit"
                           name="but_search"
                           value="Search"
                           class="view-client-search-btn">

                </form>

                <input type="button"
                       value="Print"
                       onclick="printExpenses()"
                       class="view-client-search-btn">

            </div>

            <!-- TABLE -->
            <div class="view-clients-table-wrapper">
                <table class="view-clients-table" id="expenses">
                        <thead>
                            <tr>
                                <th>Sr No</th>
                                <th>Branch</th>
                                <th>Expenses</th>
                                <th>Services</th>
                                <th>Paid Date</th>
                                <th>Gateway</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Prepare SQL query
                           $sql = "SELECT * FROM tbl_expenses WHERE 1";
$params = [];
$types = "";

if (!empty($_POST['fromDate']) && !empty($_POST['endDate'])) {
    $sql .= " AND date BETWEEN ? AND ?";
    $params[] = $_POST['fromDate'];
    $params[] = $_POST['endDate'];
    $types .= "ss";
}

$sql .= " ORDER BY date DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    ?>
                                <tr>
    <td class="view-client-sr"></td>

    <td>
       <span class="view-client-branch branch-<?= strtolower(str_replace(' ', '-', $row["branch"])); ?>">
    <?= htmlspecialchars($row["branch"]); ?>
</span>
    </td>

    <td class="view-client-name">
        <?= htmlspecialchars($row["expenses"]); ?>
    </td>

    <td class="view-client-service">
        <?= htmlspecialchars($row["services"]); ?>
    </td>

    <td class="view-client-date-cell">
        <?= htmlspecialchars($row["date"]); ?>
    </td>

    <td>
        <?= htmlspecialchars($row["payment_gateway"]); ?>
    </td>

    <td>
        <?= number_format((float)$row["amount"], 2); ?>
    </td>
</tr>
                                    <?php
                                }
                            } else {
                            echo "<tr><td colspan='7' class='view-client-no-data'>No record found.</td></tr>";
                            }

                            $stmt->close();
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6">Total</th>
                                <th id="total-amount">INR 0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                    
                    </div>

       

                
            </div>
        </div>
    </section>

<style>
    .view-clients-table {
    width: 100% !important;
    table-layout: fixed !important;
}

.view-clients-table th:nth-child(1),
.view-clients-table td:nth-child(1) {
    width: 4% !important;
}

.view-clients-table th:nth-child(2),
.view-clients-table td:nth-child(2) {
    width: 5% !important;
}

.view-clients-table th:nth-child(3),
.view-clients-table td:nth-child(3) {
    width: 15% !important;
}

.view-clients-table th:nth-child(4),
.view-clients-table td:nth-child(4) {
    width: 5% !important;
}

.view-clients-table th:nth-child(5),
.view-clients-table td:nth-child(5) {
    width: 5% !important;
}

.view-clients-table th:nth-child(6),
.view-clients-table td:nth-child(6) {
    width: 5% !important;
}

.view-clients-table th:nth-child(7),
.view-clients-table td:nth-child(7) {
    width: 5% !important;
}

.view-clients-table th,
.view-clients-table td {
    text-align: center !important;
    vertical-align: middle !important;
    overflow-wrap: break-word;
    word-break: break-word;
}

.view-client-branch {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
}
    .view-client-branch {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
}

.branch-nagpur {
    color: #1149cc;
    background: #e8efff;
}

.branch-bramhapuri {
    color: #16a34a;
    background: #eaf8ef;
}

.branch-chandrapur {
    color: #dc2626;
    background: #feecec;
}</style>


<script>
$(document).ready(function () {

    var table = $('#expenses').DataTable({
        paging: false,
        info: false,
        ordering: false,
        searching: false,
        retrieve: true
    });

    // ✅ SR NO FIX (DOM based - most stable)
    function updateSerialNo() {
        $('#expenses tbody tr:visible').each(function (index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    // ✅ TOTAL FIX (DOM based - NO DataTables dependency)
    function calculateTotal() {
        let total = 0;

        $('#expenses tbody tr:visible').each(function () {
            let value = $(this).find('td').eq(6).text();

            value = value.replace(/[^0-9.-]/g, '');

            total += parseFloat(value) || 0;
        });

        $('#total-amount').html('INR ' + total.toFixed(2));
    }

    // ✅ RUN BOTH
    function updateAll() {
        updateSerialNo();
        calculateTotal();
    }

    // initial run
    updateAll();

    // search box
    $('#txt_searchall').on('keyup', function () {
        let value = this.value.toLowerCase();

        $('#expenses tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });

        updateAll();
    });

    // also run on DOM changes (safe)
    $(document).on('keyup change', updateAll);

});
</script>
<script>
function printExpenses() {
    const table = document.getElementById('expenses');

    if (!table) {
        alert('Expense table not found.');
        return;
    }

    const printWindow = window.open('', '_blank', 'width=1200,height=800');

    // Clone table
    const printTable = table.cloneNode(true);

    // Remove screen total footer
    const footer = printTable.querySelector('tfoot');
    if (footer) {
        footer.remove();
    }

    // Get total
    const totalAmount =
        document.getElementById('total-amount').innerText;

    // Add print class
    printTable.className = 'expense-print-table';

    printWindow.document.write(`
<!DOCTYPE html>
<html>
<head>

    <title>Expenses Report</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 10mm;
            color: #000;
        }

        h2 {
            text-align: center;
            margin: 0 0 15px 0;
            font-size: 22px;
        }

        .expense-print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
.expense-print-table th,
.expense-print-table td {
    border: 1px solid #000;
    padding: 8px 6px;
    text-align: center !important;
    vertical-align: middle !important;
    font-size: 16px;
    line-height: 1.3;
    overflow-wrap: break-word;
    word-break: normal;
}

        .expense-print-table th {
            background: #1149cc !important;
            color: #fff !important;
            font-weight: 700;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* =========================
           COLUMN WIDTHS
        ========================= */

        .expense-print-table th:nth-child(1),
        .expense-print-table td:nth-child(1) {
            width: 7%;
        }

        .expense-print-table th:nth-child(2),
        .expense-print-table td:nth-child(2) {
            width: 12%;
        }

        .expense-print-table th:nth-child(3),
        .expense-print-table td:nth-child(3) {
            width: 20%;
        }

        .expense-print-table th:nth-child(4),
        .expense-print-table td:nth-child(4) {
            width: 21%;
        }

        .expense-print-table th:nth-child(5),
        .expense-print-table td:nth-child(5) {
            width: 10%;
        }

        .expense-print-table th:nth-child(6),
        .expense-print-table td:nth-child(6) {
            width: 20%;
        }

        .expense-print-table th:nth-child(7),
        .expense-print-table td:nth-child(7) {
            width: 10%;
        }

        /* =========================
           TOTAL
        ========================= */

        .print-total {
            margin-top: 12px;
            display: flex;
            justify-content: flex-end;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .print-total-label,
        .print-total-amount {
            background: #1149cc !important;
            color: #fff !important;
            border: 1px solid #000;
            padding: 7px 15px;
            font-weight: 700;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-total-label {
            min-width: 80px;
            text-align: center;
        }

        .print-total-amount {
            min-width: 120px;
            text-align: center;
        }

        /* =========================
           PRINT
        ========================= */

        @media print {

            body {
                margin: 8mm;
            }

            .expense-print-table {
                width: 100%;
            }

            .expense-print-table thead {
                display: table-header-group;
            }

            .expense-print-table tr {
                page-break-inside: avoid;
            }

            .print-total {
                page-break-inside: avoid;
                break-inside: avoid;
            }

        }

    </style>

</head>

<body>

    <h2>Expenses Report</h2>

    ${printTable.outerHTML}

    <div class="print-total">

        <div class="print-total-label">
            Total
        </div>

        <div class="print-total-amount">
            ${totalAmount}
        </div>

    </div>

</body>
</html>
    `);

    printWindow.document.close();

    printWindow.onload = function () {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
}
</script>
<script src="js/bootstrap.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/jquery.nicescroll.js"></script>
    <script src="js/jquery.scrollTo.js"></script>
    <script src="js/search.js"></script>
</body>
</html>