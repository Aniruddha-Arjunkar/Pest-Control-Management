```php
<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';

/* =========================
   EMPLOYEE ID
========================= */
if (!isset($_GET['id'])) {
    echo "<h4 style='color:red;text-align:center;'>Invalid Employee ID</h4>";
    exit();
}

$emp_id = intval($_GET['id']);

/* =========================
   EMPLOYEE DETAILS
========================= */
$emp_query = mysqli_query(
    $conn,
    "SELECT cust_name FROM tbl_employee WHERE Id='$emp_id'"
);

$emp = mysqli_fetch_assoc($emp_query);

if (!$emp) {
    echo "<h4 style='color:red;text-align:center;'>Employee not found</h4>";
    exit();
}

$emp_name = $emp['cust_name'];

/* =========================
   SELECTED MONTH
========================= */
$selected_month = isset($_GET['month'])
    ? $_GET['month']
    : date('Y-m');

$month_start = $selected_month . "-01";
$month_end = date("Y-m-t", strtotime($month_start));

/* =========================
   PREVIOUS ADVANCES
========================= */
$prev_advances = mysqli_query($conn, "
    SELECT SUM(advance) AS total_adv
    FROM tbl_salary
    WHERE employee_id='$emp_id'
      AND payment_type='Advance'
      AND advance_date < '$month_start'
");

/* =========================
   PREVIOUS DEDUCTIONS
========================= */
$prev_deductions = mysqli_query($conn, "
    SELECT SUM(advance) AS total_ded
    FROM tbl_salary
    WHERE employee_id='$emp_id'
      AND payment_type IN ('Salary','PerDay')
      AND advance > 0
      AND (
          YEAR(CONCAT(month, '-01')) < YEAR('$month_start')
          OR (
              YEAR(CONCAT(month, '-01')) = YEAR('$month_start')
              AND MONTH(CONCAT(month, '-01')) < MONTH('$month_start')
          )
      )
");

$prev_adv = mysqli_fetch_assoc($prev_advances)['total_adv'] ?? 0;
$prev_ded = mysqli_fetch_assoc($prev_deductions)['total_ded'] ?? 0;

$carry_forward = (float)$prev_adv - (float)$prev_ded;

/* =========================
   CURRENT MONTH ADVANCES
========================= */
$advances = mysqli_query($conn, "
    SELECT advance, advance_date, remark
    FROM tbl_salary
    WHERE employee_id='$emp_id'
      AND payment_type='Advance'
      AND advance_date BETWEEN '$month_start' AND '$month_end'
    ORDER BY advance_date ASC
");

/* =========================
   CURRENT MONTH DEDUCTIONS
========================= */
$deductions = mysqli_query($conn, "
    SELECT advance, month
    FROM tbl_salary
    WHERE employee_id='$emp_id'
      AND payment_type IN ('Salary','PerDay')
      AND advance > 0
      AND YEAR(CONCAT(month, '-01')) = YEAR('$month_start')
      AND MONTH(CONCAT(month, '-01')) = MONTH('$month_start')
    ORDER BY month ASC
");

/* =========================
   TOTALS
========================= */
$total_adv_current = 0;

while ($a = mysqli_fetch_assoc($advances)) {
    $total_adv_current += (float)$a['advance'];
}

mysqli_data_seek($advances, 0);

$total_ded_current = 0;

while ($d = mysqli_fetch_assoc($deductions)) {
    $total_ded_current += (float)$d['advance'];
}

mysqli_data_seek($deductions, 0);

$total_advance_including_carry =
    $carry_forward + $total_adv_current;

$pending =
    $total_advance_including_carry - $total_ded_current;


/* =========================
   HEADER
========================= */

include 'header.php';
?>

<style>

/* =========================================================
   VIEW ADVANCE DETAILS
   SAME DESIGN AS VIEW CLIENTS
========================================================= */

.view-clients-page {
    width: 100%;
}

.view-clients-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.view-clients-title-wrap {
    display: flex;
    flex-direction: column;
}

.view-clients-title {
    margin: 0;
    font-size: 26px;
    font-weight: 600;
    color: #2c3e50;
}

.view-clients-title-icon {
    margin-right: 8px;
}

.view-clients-subtitle {
    margin: 6px 0 0;
    color: #777;
    font-size: 14px;
}

.view-clients-count {
    background: #2c3e50;
    color: #fff;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 13px;
}

/* =========================
   MAIN CARD
========================= */

.view-clients-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

/* =========================
   FILTER
========================= */

.view-clients-filter {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.view-client-date {
    height: 42px;
    padding: 0 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    color: #333;
    background: #fff;
}

.view-client-search-btn {
    height: 42px;
    padding: 0 18px;
    border: none;
    border-radius: 6px;
    background: #2c3e50;
    color: #fff;
    cursor: pointer;
}

.view-client-back-btn {
    height: 42px;
    padding: 11px 18px;
    border-radius: 6px;
    background: #6c757d;
    color: #fff !important;
    text-decoration: none;
}

/* =========================
   INFO BOX
========================= */

.view-client-info-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 18px;
    margin-bottom: 20px;
    border-radius: 8px;
    background: #f4f8fb;
    border: 1px solid #dce6ed;
}

.view-client-info-title {
    font-weight: 600;
    color: #2c3e50;
}

.view-client-info-value {
    font-size: 18px;
    font-weight: bold;
    color: #2c3e50;
}

/* =========================
   SECTION TITLE
========================= */

.view-client-section-title {
    font-size: 17px;
    font-weight: 600;
    color: #2c3e50;
    margin: 22px 0 12px;
}

/* =========================
   TABLE WRAPPER
========================= */

.view-clients-table-wrapper {
    width: 100%;
    overflow-x: auto;
    margin-bottom: 20px;
}

/* =========================
   TABLE
========================= */

.view-clients-table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
    font-size: 14px;
    background: #fff;
}

.view-clients-table thead tr {
    background: linear-gradient(to right, #2c3e50, #4ca1af);
    color: #fff;
}

.view-clients-table th,
.view-clients-table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
}

.view-clients-table tbody tr:nth-child(even) {
    background: #f3f6f9;
}

.view-clients-table tbody td {
    color: #000 !important;
    background: #fff;
}

.view-clients-table tbody tr:nth-child(even) td {
    background: #f3f6f9;
}

.view-client-sr {
    width: 70px;
}

.view-client-date-cell {
    white-space: nowrap;
}

.view-client-amount {
    font-weight: 600;
}

.view-client-no-data {
    text-align: center !important;
    color: #777 !important;
    padding: 20px !important;
}

/* =========================
   TOTAL ROW
========================= */

.view-client-total-row td {
    font-weight: bold;
    background: #e8eef2 !important;
    color: #2c3e50 !important;
}

/* =========================
   PENDING
========================= */

.view-client-pending-box {
    margin-top: 20px;
    padding: 16px 20px;
    text-align: right;
    border-radius: 8px;
    background: #f8f9fa;
    border: 1px solid #ddd;
}

.view-client-pending-label {
    font-size: 17px;
    font-weight: 600;
}

.view-client-pending-value {
    font-size: 21px;
    font-weight: bold;
    margin-left: 10px;
}

.pending-red {
    color: red;
}

.pending-green {
    color: green;
}

/* =========================
   MOBILE
========================= */

@media (max-width: 768px) {

    .view-clients-header {
        align-items: flex-start;
        gap: 10px;
        flex-direction: column;
    }

    .view-clients-title {
        font-size: 22px;
    }

    .view-clients-card {
        padding: 12px;
    }

    .view-clients-filter {
        flex-direction: column;
        align-items: stretch;
    }

    .view-client-date,
    .view-client-search-btn,
    .view-client-back-btn {
        width: 100%;
        box-sizing: border-box;
        text-align: center;
    }

    .view-client-info-box {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }

    .view-client-pending-box {
        text-align: left;
    }
}

</style>


<body>

<section id="main-content">

<section class="wrapper">

<div class="view-clients-page">

    <!-- =========================
         PAGE HEADER
    ========================== -->

    <div class="view-clients-header">

        <div class="view-clients-title-wrap">

            <h1 class="view-clients-title">

                <span class="view-clients-title-icon">
                    💰
                </span>

                Advance Details

            </h1>

            <p class="view-clients-subtitle">

                Employee:
                <strong>
                    <?= htmlspecialchars($emp_name) ?>
                </strong>

            </p>

        </div>


        <span class="view-clients-count">

            <?= date(
                "F Y",
                strtotime($selected_month)
            ) ?>

        </span>

    </div>


    <!-- =========================
         MAIN CARD
    ========================== -->

    <div class="view-clients-card">


        <!-- =========================
             FILTER
        ========================== -->

        <form method="get"
              class="view-clients-filter">

            <input
                type="hidden"
                name="id"
                value="<?= $emp_id ?>"
            >

            <input
                type="month"
                name="month"
                class="view-client-date"
                value="<?= htmlspecialchars($selected_month) ?>"
            >

            <button
                type="submit"
                class="view-client-search-btn"
            >
                🔍 Filter
            </button>

            <a
                href="view_advance.php"
                class="view-client-back-btn"
            >
                ← Back
            </a>

        </form>


        <!-- =========================
             CARRY FORWARD
        ========================== -->

        <div class="view-client-info-box">

            <span class="view-client-info-title">
                Carry Forward from Previous Months
            </span>

            <span class="view-client-info-value">
                ₹ <?= number_format($carry_forward, 2) ?>
            </span>

        </div>


        <!-- =========================
             CURRENT MONTH ADVANCE
        ========================== -->

        <div class="view-client-section-title">

            💵 Advance Taken -
            <?= date(
                "F Y",
                strtotime($selected_month)
            ) ?>

        </div>


        <div class="view-clients-table-wrapper">

            <table class="view-clients-table">

                <thead>

                    <tr>

                        <th>
                            Sr. No.
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Amount (₹)
                        </th>

                        <th>
                            Remark
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                mysqli_data_seek(
                    $advances,
                    0
                );

                if (
                    mysqli_num_rows($advances) > 0
                ):

                    $i = 1;

                    while (
                        $a =
                        mysqli_fetch_assoc($advances)
                    ):

                ?>

                    <tr>

                        <td class="view-client-sr">
                            <?= $i++ ?>
                        </td>

                        <td class="view-client-date-cell">
                            <?= htmlspecialchars(
                                $a['advance_date']
                            ) ?>
                        </td>

                        <td class="view-client-amount">
                            ₹ <?= number_format(
                                $a['advance'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $a['remark'] ?? '-'
                            ) ?>
                        </td>

                    </tr>

                <?php

                    endwhile;

                else:

                ?>

                    <tr>

                        <td
                            colspan="4"
                            class="view-client-no-data"
                        >
                            No advance records for this month.
                        </td>

                    </tr>

                <?php endif; ?>


                    <!-- TOTAL THIS MONTH -->

                    <tr class="view-client-total-row">

                        <td colspan="2">
                            Total (This Month)
                        </td>

                        <td>
                            ₹ <?= number_format(
                                $total_adv_current,
                                2
                            ) ?>
                        </td>

                        <td>
                            -
                        </td>

                    </tr>


                    <!-- TOTAL INCLUDING CARRY -->

                    <tr class="view-client-total-row">

                        <td colspan="2">
                            Total Including Carry Forward
                        </td>

                        <td>
                            ₹ <?= number_format(
                                $total_advance_including_carry,
                                2
                            ) ?>
                        </td>

                        <td>
                            -
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>


        <!-- =========================
             DEDUCTIONS
        ========================== -->

        <div class="view-client-section-title">

            📉 Deductions (Recovered Advances)

        </div>


        <div class="view-clients-table-wrapper">

            <table class="view-clients-table">

                <thead>

                    <tr>

                        <th>
                            Sr. No.
                        </th>

                        <th>
                            Month
                        </th>

                        <th>
                            Deducted Amount (₹)
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                mysqli_data_seek(
                    $deductions,
                    0
                );

                if (
                    mysqli_num_rows($deductions) > 0
                ):

                    $i = 1;

                    while (
                        $d =
                        mysqli_fetch_assoc($deductions)
                    ):

                ?>

                    <tr>

                        <td class="view-client-sr">
                            <?= $i++ ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $d['month']
                            ) ?>
                        </td>

                        <td class="view-client-amount">
                            ₹ <?= number_format(
                                $d['advance'],
                                2
                            ) ?>
                        </td>

                    </tr>

                <?php

                    endwhile;

                else:

                ?>

                    <tr>

                        <td
                            colspan="3"
                            class="view-client-no-data"
                        >
                            No deductions for this month.
                        </td>

                    </tr>

                <?php endif; ?>


                    <!-- TOTAL DEDUCTION -->

                    <tr class="view-client-total-row">

                        <td colspan="2">
                            Total Deduction (This Month)
                        </td>

                        <td>
                            ₹ <?= number_format(
                                $total_ded_current,
                                2
                            ) ?>
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>


        <!-- =========================
             PENDING
        ========================== -->

        <div class="view-client-pending-box">

            <span class="view-client-pending-label">
                Pending Advance:
            </span>

            <span
                class="view-client-pending-value
                <?= ($pending > 0)
                    ? 'pending-red'
                    : 'pending-green'
                ?>"
            >
                ₹ <?= number_format(
                    $pending,
                    2
                ) ?>
            </span>

        </div>


    </div>

</div>

</section>

</section>


<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>

</body>
</html>
```
