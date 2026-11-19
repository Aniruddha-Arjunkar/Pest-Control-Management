<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

$employees = mysqli_query(
    $conn,
    "SELECT Id, cust_name, package 
     FROM tbl_employee 
     WHERE resignation_type IS NULL 
     OR resignation_type = ''"
);

$message = "";

/* =========================
   DEFAULT PENDING ADVANCE
========================= */

$pending_advance = 0;


/* =========================
   CHECK PENDING ADVANCE
========================= */

if (isset($_POST['employee_id'])) {

    $employee_id = $_POST['employee_id'];

    $advQuery = mysqli_query(
        $conn,
        "SELECT SUM(advance) AS total_adv 
         FROM tbl_salary 
         WHERE employee_id='$employee_id'"
    );

    $advData = mysqli_fetch_assoc($advQuery);

    $pending_advance = $advData['total_adv'] ?? 0;
}


/* =========================
   FORM SUBMISSION
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $employee_id = $_POST['employee_id'];
    $month = $_POST['month'];
    $payment_type = $_POST['payment_type'];


    /* =========================
       STIPEND
    ========================= */

    if ($payment_type === 'Stipend') {

        $stipend_amount = $_POST['stipend_amount'];

        $insert = "INSERT INTO tbl_salary 
        (
            employee_id,
            month,
            amount,
            payment_type
        )
        VALUES 
        (
            '$employee_id',
            '$month',
            '$stipend_amount',
            'Stipend'
        )";

        if (mysqli_query($conn, $insert)) {

            $message = "
                <div class='alert alert-success'>
                    Stipend added successfully
                </div>
            ";

        } else {

            $message = "
                <div class='alert alert-danger'>
                    " . mysqli_error($conn) . "
                </div>
            ";
        }
    }


    /* =========================
       SALARY
    ========================= */

    if ($payment_type === 'Salary') {

        $total_amount = $_POST['net_salary'];

        $advance_input =
            $_POST['advance'] ?? 0;

        $profession_tax =
            $_POST['profession_tax'] ?? 0;

        $expense_reimbursement =
            $_POST['expense_reimbursement'] ?? 0;

        $performance_incentive =
            $_POST['performance_incentive'] ?? 0;


        /* =========================
           EARNINGS BREAKUP
        ========================= */

        $basic =
            $total_amount * 0.50;

        $hra =
            $total_amount * 0.20;

        $conveyance =
            $total_amount * 0.10;

        $food_allowance =
            $total_amount * 0.20;


        /* =========================
           ADVANCE CALCULATION
        ========================= */

        $advQuery = mysqli_query(
            $conn,
            "
            SELECT

                SUM(
                    CASE
                        WHEN payment_type='Advance'
                        THEN COALESCE(advance,0)
                        ELSE 0
                    END
                ) AS total_advance,

                SUM(
                    CASE
                        WHEN payment_type='Salary'
                        THEN COALESCE(advance,0)
                        ELSE 0
                    END
                ) AS total_deducted

            FROM tbl_salary

            WHERE employee_id='$employee_id'
            "
        );

        $advData =
            mysqli_fetch_assoc($advQuery);

        $total_advance =
            $advData['total_advance'] ?? 0;

        $total_deducted =
            $advData['total_deducted'] ?? 0;

        $pending_advance =
            $total_advance -
            $total_deducted;


        if ($pending_advance < 0) {
            $pending_advance = 0;
        }


        $advance_to_deduct =
            min(
                $pending_advance,
                $advance_input
            );


        /* =========================
           TOTALS
        ========================= */

        $total_earnings =
            $basic +
            $hra +
            $conveyance +
            $food_allowance +
            $performance_incentive +
            $expense_reimbursement;

        $total_deductions =
            $profession_tax +
            $advance_to_deduct;

        $net_salary_final =
            $total_earnings -
            $total_deductions;


        /* =========================
           INSERT SALARY
        ========================= */

        $insert = "INSERT INTO tbl_salary
        (
            employee_id,
            month,
            payment_type,
            basic,
            hra,
            conveyance,
            food_allowance,
            performance_incentive,
            profession_tax,
            expense_reimbursement,
            advance,
            amount
        )
        VALUES
        (
            '$employee_id',
            '$month',
            '$payment_type',
            '$basic',
            '$hra',
            '$conveyance',
            '$food_allowance',
            '$performance_incentive',
            '$profession_tax',
            '$expense_reimbursement',
            '$advance_to_deduct',
            '$net_salary_final'
        )";


        if (mysqli_query($conn, $insert)) {

            $message = "
                <div class='alert alert-success'>
                    $payment_type added successfully.<br>
                    Advance Deducted: â‚¹$advance_to_deduct
                </div>
            ";

        } else {

            $message = "
                <div class='alert alert-danger'>
                    " . mysqli_error($conn) . "
                </div>
            ";
        }
    }


    /* =========================
       PER DAY
    ========================= */

    if ($payment_type === 'PerDay') {

        /* =========================
           MONTH / YEAR
        ========================= */

        $year_full =
            date(
                'Y',
                strtotime($month)
            );

        $month_num =
            date(
                'm',
                strtotime($month)
            );

        $days_in_month =
            cal_days_in_month(
                CAL_GREGORIAN,
                $month_num,
                $year_full
            );


        $month_short =
            date(
                'M',
                mktime(
                    0,
                    0,
                    0,
                    $month_num,
                    1
                )
            );

        $year_short =
            substr(
                $year_full,
                2
            );


        /* =========================
           ATTENDANCE
        ========================= */

        $attQuery = mysqli_query(
            $conn,
            "
            SELECT SUM(
                CASE
                    WHEN attendance='P' THEN 1
                    WHEN attendance='HD' THEN 0.5
                    ELSE 0
                END
            ) AS attended_days

            FROM tbl_emp_attendance

            WHERE employee_id = $employee_id

            AND attendance_month = '$month_short'

            AND attendance_year = '$year_short'
            "
        );


        $attData =
            mysqli_fetch_assoc($attQuery);

        $num_days_attended =
            $attData['attended_days'] ?? 0;


        /* =========================
           FETCH PACKAGE
        ========================= */

        $empQuery = mysqli_query(
            $conn,
            "
            SELECT package, employee_type
            FROM tbl_employee
            WHERE Id='$employee_id'
            "
        );

        $empData =
            mysqli_fetch_assoc($empQuery);

        $package =
            $empData['package'] ?? 0;

        $employee_type =
            $empData['employee_type'] ??
            'Salary';


        /* =========================
           RATE PER DAY
        ========================= */

        if ($employee_type === 'Per Day') {

            $rate_per_day =
                $package;

        } else {

            $rate_per_day =
                $package / $days_in_month;
        }


        /* =========================
           TOTAL AMOUNT
        ========================= */

        $total_amount =
            $num_days_attended *
            $rate_per_day;


        $advance_input =
            $_POST['advance_perday'] ?? 0;

        $profession_tax =
            $_POST['profession_tax_perday'] ?? 0;

        $expense_reimbursement =
            $_POST['expense_reimbursement_perday'] ?? 0;

        $performance_incentive =
            $_POST['performance_incentive_perday'] ?? 0;


        /* =========================
           PER DAY BREAKUP
        ========================= */

        $basic =
            $hra =
            $conveyance =
            $food_allowance =
            0;


        /* =========================
           ADVANCE LOGIC
        ========================= */

        $advQuery = mysqli_query(
            $conn,
            "
            SELECT

                SUM(
                    CASE
                        WHEN payment_type='Advance'
                        THEN COALESCE(advance,0)
                        ELSE 0
                    END
                ) AS total_advance,

                SUM(
                    CASE
                        WHEN payment_type IN ('Salary','PerDay')
                        THEN COALESCE(advance,0)
                        ELSE 0
                    END
                ) AS total_deducted

            FROM tbl_salary

            WHERE employee_id='$employee_id'
            "
        );


        $advData =
            mysqli_fetch_assoc($advQuery);

        $total_advance =
            $advData['total_advance'] ?? 0;

        $total_deducted =
            $advData['total_deducted'] ?? 0;

        $pending_advance =
            $total_advance -
            $total_deducted;


        if ($pending_advance < 0) {
            $pending_advance = 0;
        }


        $advance_to_deduct =
            min(
                $pending_advance,
                $advance_input
            );


        /* =========================
           TOTALS
        ========================= */

        $total_earnings =
            $total_amount +
            $performance_incentive +
            $expense_reimbursement;

        $total_deductions =
            $profession_tax +
            $advance_to_deduct;

        $net_salary_final =
            $total_earnings -
            $total_deductions;


        /* =========================
           INSERT PER DAY
        ========================= */

        $insert = "INSERT INTO tbl_salary
        (
            employee_id,
            month,
            payment_type,
            basic,
            hra,
            conveyance,
            food_allowance,
            performance_incentive,
            profession_tax,
            expense_reimbursement,
            advance,
            amount
        )
        VALUES
        (
            '$employee_id',
            '$month',
            '$payment_type',
            '$basic',
            '$hra',
            '$conveyance',
            '$food_allowance',
            '$performance_incentive',
            '$profession_tax',
            '$expense_reimbursement',
            '$advance_to_deduct',
            '$net_salary_final'
        )";


        if (mysqli_query($conn, $insert)) {

            $message = "
                <div class='alert alert-success'>
                    Per Day Payment added successfully.<br>
                    Advance Deducted: â‚¹$advance_to_deduct
                </div>
            ";

        } else {

            $message = "
                <div class='alert alert-danger'>
                    " . mysqli_error($conn) . "
                </div>
            ";
        }
    }
}

?>


<!-- =========================================
     MAIN CONTENT
========================================= -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">

                <!-- HEADER -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">💰</span>

                        <div>

                            <h2>Add Payment</h2>

                            <p>
                                Add employee salary and payment details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- FORM -->

                <form
                    method="post"
                    class="refollowup-form"
                >

                    <?= $message ?>


                    <!-- EMPLOYEE -->

                    <div class="form-row">

                        <div class="form-group full-width">

                            <label for="employee_id">
                                Select Employee <span>*</span>
                            </label>

                            <select
                                name="employee_id"
                                id="employee_id"
                                required
                            >

                                <option value="">
                                    -- Select Employee --
                                </option>

                                <?php
                                while (
                                    $emp =
                                    mysqli_fetch_assoc(
                                        $employees
                                    )
                                ):
                                ?>

                                    <option
                                        value="<?= htmlspecialchars($emp['Id']); ?>"
                                        data-package="<?= htmlspecialchars($emp['package']); ?>"
                                    >

                                        <?= htmlspecialchars($emp['cust_name']); ?>

                                        (Salary:
                                        â‚¹<?= htmlspecialchars($emp['package']); ?>)

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>

                    </div>


                    <!-- MONTH -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="month">
                                Select Month <span>*</span>
                            </label>

                            <input
                                type="month"
                                name="month"
                                id="month"
                                required
                            >

                        </div>


                        <!-- PAYMENT TYPE -->

                        <div class="form-group">

                            <label for="payment_type">
                                Payment Type <span>*</span>
                            </label>

                            <select
                                name="payment_type"
                                id="payment_type"
                                required
                            >

                                <option value="">
                                    -- Select Type --
                                </option>

                                <option value="Salary">
                                    Salary
                                </option>

                                <option value="Stipend">
                                    Stipend
                                </option>

                                <option value="PerDay">
                                    Per Day
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- =================================
                         STIPEND
                    ================================== -->

                    <div
                        id="stipend_section"
                        hidden
                    >

                        <div class="form-row">

                            <div class="form-group full-width">

                                <label>
                                    Stipend Amount
                                </label>

                                <input
                                    type="number"
                                    name="stipend_amount"
                                    id="stipend_amount"
                                    step="0.01"
                                    placeholder="Enter stipend amount"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- =================================
                         SALARY
                    ================================== -->

                    <div
                        id="salary_section"
                        hidden
                    >

                        <!-- NET SALARY -->

                        <div class="form-row">

                            <div class="form-group full-width">

                                <label>
                                    Desired Net Salary
                                </label>

                                <input
                                    type="number"
                                    name="net_salary"
                                    id="net_salary"
                                    step="0.01"
                                    placeholder="e.g. 10000"
                                >

                            </div>

                        </div>


                        <!-- BASIC + HRA -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Basic (50%)
                                </label>

                                <input
                                    type="number"
                                    id="basic"
                                    readonly
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    HRA (20%)
                                </label>

                                <input
                                    type="number"
                                    id="hra"
                                    readonly
                                >

                            </div>

                        </div>


                        <!-- CONVEYANCE + FOOD -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Conveyance (10%)
                                </label>

                                <input
                                    type="number"
                                    id="conveyance"
                                    readonly
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Food Allowance (20%)
                                </label>

                                <input
                                    type="number"
                                    id="food_allowance"
                                    readonly
                                >

                            </div>

                        </div>


                        <!-- INCENTIVE + TAX -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Performance Incentive (Manual)
                                </label>

                                <input
                                    type="number"
                                    name="performance_incentive"
                                    id="performance_incentive"
                                    value="<?= htmlspecialchars($_POST['performance_incentive'] ?? 0); ?>"
                                    step="0.01"
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Profession Tax (Manual)
                                </label>

                                <input
                                    type="number"
                                    name="profession_tax"
                                    value="0"
                                    step="0.01"
                                >

                            </div>

                        </div>


                        <!-- REIMBURSEMENT + ADVANCE -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Expense Reimbursement
                                </label>

                                <input
                                    type="number"
                                    name="expense_reimbursement"
                                    value="0"
                                    step="0.01"
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Advance Deduction
                                </label>

                                <input
                                    type="number"
                                    name="advance"
                                    id="advance"
                                    value="<?= htmlspecialchars($_POST['advance'] ?? $pending_advance); ?>"
                                    step="0.01"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- =================================
                         PER DAY
                    ================================== -->

                    <div
                        id="perday_section"
                        hidden
                    >

                        <!-- DAYS + RATE -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Number of Days
                                </label>

                                <input
                                    type="number"
                                    name="num_days"
                                    id="num_days"
                                    step="0.01"
                                    readonly
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Rate Per Day
                                </label>

                                <input
                                    type="number"
                                    name="rate_per_day"
                                    id="rate_per_day"
                                    step="0.01"
                                >

                            </div>

                        </div>


                        <!-- INCENTIVE + TAX -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Performance Incentive (Manual)
                                </label>

                                <input
                                    type="number"
                                    name="performance_incentive_perday"
                                    id="performance_incentive_perday"
                                    value="<?= htmlspecialchars($_POST['performance_incentive_perday'] ?? 0); ?>"
                                    step="0.01"
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Profession Tax (Manual)
                                </label>

                                <input
                                    type="number"
                                    name="profession_tax_perday"
                                    value="0"
                                    step="0.01"
                                >

                            </div>

                        </div>


                        <!-- REIMBURSEMENT + ADVANCE -->

                        <div class="form-row">

                            <div class="form-group">

                                <label>
                                    Expense Reimbursement
                                </label>

                                <input
                                    type="number"
                                    name="expense_reimbursement_perday"
                                    value="0"
                                    step="0.01"
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Advance Deduction
                                </label>

                                <input
                                    type="number"
                                    name="advance_perday"
                                    id="advance_perday"
                                    value="<?= htmlspecialchars($_POST['advance_perday'] ?? $pending_advance); ?>"
                                    step="0.01"
                                >

                            </div>

                        </div>


                        <!-- NET PAYABLE -->

                        <div class="form-row">

                            <div class="form-group full-width">

                                <label>
                                    Net Payable
                                </label>

                                <input
                                    type="number"
                                    id="net_perday"
                                    readonly
                                >

                            </div>

                        </div>

                    </div>


                    <!-- SUBMIT -->

                    <div class="form-row">

                        <div class="form-group full-width">

                            <button
                                type="submit"
                                class="submit-btn"
                            >

                                <span>💰</span>

                                Save Payment

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </section>

</section>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const employee_id =
        document.getElementById('employee_id');

    const payment_type =
        document.getElementById('payment_type');

    const stipend_section =
        document.getElementById('stipend_section');

    const salary_section =
        document.getElementById('salary_section');

    const perday_section =
        document.getElementById('perday_section');

    const stipend_amount =
        document.getElementById('stipend_amount');

    const net_salary =
        document.getElementById('net_salary');

    const basic =
        document.getElementById('basic');

    const hra =
        document.getElementById('hra');

    const conveyance =
        document.getElementById('conveyance');

    const food_allowance =
        document.getElementById('food_allowance');

    const advance =
        document.getElementById('advance');

    const advance_perday =
        document.getElementById('advance_perday');

    const num_days =
        document.getElementById('num_days');

    const rate_per_day =
        document.getElementById('rate_per_day');

    const net_perday =
        document.getElementById('net_perday');


    /* =========================
       PAYMENT TYPE CHANGE
    ========================= */

    payment_type.addEventListener(
        'change',
        function () {

            let val = this.value;


            stipend_section.hidden =
                val !== 'Stipend';

            salary_section.hidden =
                val !== 'Salary';

            perday_section.hidden =
                val !== 'PerDay';


            let emp =
                employee_id.selectedOptions[0];

            if (!emp) {
                return;
            }


            let pkg =
                parseFloat(
                    emp.dataset.package
                ) || 0;


            if (val === 'Salary') {

                net_salary.value =
                    pkg;

                net_salary.dispatchEvent(
                    new Event('input')
                );
            }


            if (val === 'Stipend') {

                stipend_amount.value =
                    pkg;
            }


            if (val === 'PerDay') {

                fetchPerDayData();
            }

        }
    );


    /* =========================
       SALARY BREAKDOWN
    ========================= */

    net_salary.addEventListener(
        'input',
        function () {

            let n =
                parseFloat(this.value) || 0;


            basic.value =
                (n * 0.5).toFixed(2);

            hra.value =
                (n * 0.2).toFixed(2);

            conveyance.value =
                (n * 0.2).toFixed(2);

            food_allowance.value =
                (n * 0.1).toFixed(2);

        }
    );


    /* =========================
       PER DAY DATA
    ========================= */

    function fetchPerDayData() {

        let emp =
            employee_id.selectedOptions[0];

        if (!emp) {
            return;
        }


        let monthVal =
            document.querySelector(
                'input[name="month"]'
            ).value;


        if (!monthVal) {
            return;
        }


        let [year, month] =
            monthVal.split('-');


        fetch(
            `get_attendance_days.php?employee_id=${emp.value}&year=${year}&month=${month}`
        )

        .then(res => res.json())

        .then(data => {

            num_days.value =
                data.attended_days || 0;

            rate_per_day.value =
                data.rate_per_day || 0;

            net_perday.value =
                (
                    data.attended_days *
                    data.rate_per_day
                ).toFixed(2);

        })

        .catch(error => {

            console.error(
                'Attendance Error:',
                error
            );

        });

    }


    /* =========================
       EMPLOYEE CHANGE
    ========================= */

    employee_id.addEventListener(
        'change',
        function () {

            let emp =
                this.selectedOptions[0];

            if (!emp) {
                return;
            }


            let pkg =
                parseFloat(
                    emp.dataset.package
                ) || 0;


            let val =
                payment_type.value;


            stipend_section.hidden =
                val !== 'Stipend';

            salary_section.hidden =
                val !== 'Salary';

            perday_section.hidden =
                val !== 'PerDay';


            if (val === 'Salary') {

                net_salary.value =
                    pkg;

                net_salary.dispatchEvent(
                    new Event('input')
                );
            }


            if (val === 'Stipend') {

                stipend_amount.value =
                    pkg;
            }


            if (val === 'PerDay') {

                fetchPerDayData();
            }


            /* =========================
               PENDING ADVANCE
            ========================= */

            fetch(
                `get_pending_advance.php?employee_id=${emp.value}`
            )

            .then(res => res.json())

            .then(data => {

                advance.value =
                    data.pending_advance || 0;

                advance_perday.value =
                    data.pending_advance || 0;

            })

            .catch(error => {

                console.error(
                    'Advance Error:',
                    error
                );

            });

        }
    );


    /* =========================
       MONTH CHANGE
    ========================= */

    document.querySelector(
        'input[name="month"]'
    ).addEventListener(
        'change',
        function () {

            if (
                payment_type.value ===
                'PerDay'
            ) {

                fetchPerDayData();
            }

        }
    );

});

</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>

</body>
</html>