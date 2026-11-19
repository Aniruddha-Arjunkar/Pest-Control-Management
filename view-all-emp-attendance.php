
<?php
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

/* =========================
   LOGOUT
========================= */
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    unset($_SESSION['role']);
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

/* =========================
   USER ROLE
========================= */
$role = $_SESSION['role'];

/* =========================
   SELECTED MONTH
========================= */
if (isset($_GET['selected_month']) && !empty($_GET['selected_month'])) {
    $selectedMonth = $_GET['selected_month'];
} else {
    $selectedMonth = date("Y-m");
}

/* Validate month format */
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date("Y-m");
}

$firstDayOfMonth = $selectedMonth . "-01";
$totalDaysInMonth = date("t", strtotime($firstDayOfMonth));

/* =========================
   GET EMPLOYEES
========================= */

$employees_sql = "
    SELECT id, cust_name, branch
    FROM tbl_employee
    WHERE (resignation_type IS NULL OR resignation_type = '')
";

/* Branch-wise access */
if ($role !== 'super_admin' && !empty($_SESSION['branch'])) {

    $branch = mysqli_real_escape_string(
        $conn,
        $_SESSION['branch']
    );

    $employees_sql .= " AND branch = '$branch'";
}

$employees_sql .= " ORDER BY cust_name ASC";

$employees_result = mysqli_query($conn, $employees_sql);

$employeesNameArray = array();
$employeesIDsArray = array();
$employeesBranchArray = array();

if ($employees_result) {

    while ($employees = mysqli_fetch_assoc($employees_result)) {

        $employeesNameArray[] = $employees['cust_name'];
        $employeesIDsArray[] = $employees['id'];
        $employeesBranchArray[] = $employees['branch'];
    }
}

$totalNumberOfEmployees = count($employeesIDsArray);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Employee Attendance</title>

    <!-- Attendance Page CSS -->
    <link rel="stylesheet"
          href="css/employee-attendance.css">

</head>

<body>

<!-- =========================
     MAIN CONTENT START
========================= -->

<section id="main-content">

    <section class="wrapper">

        <div class="attendance-page">

            <div class="attendance-card">


                <!-- =========================
                     PAGE HEADER
                ========================= -->

                <div class="attendance-header">

                    <div class="attendance-header-left">

                        <h1>
                            View Employee Attendance
                        </h1>

                        <div class="attendance-subtitle">
                            Monthly Attendance Report
                        </div>

                    </div>


                    <div class="attendance-record-count">

                        <?= $totalNumberOfEmployees; ?> Records

                    </div>

                </div>


                <!-- =========================
                     WHITE CONTENT
                ========================= -->

                <div class="attendance-content">


                    <!-- =========================
                         MONTH SELECTOR
                    ========================= -->

                    <div class="attendance-month-row">

                        <div class="attendance-month-label">

                            Select Month :

                        </div>


                        <form method="GET"
                              class="attendance-month-form">

                            <input
                                type="month"
                                name="selected_month"
                                value="<?= htmlspecialchars($selectedMonth); ?>"
                                class="attendance-month-input"
                                required
                            >

                            <button type="submit">
                                View
                            </button>

                        </form>

                    </div>


                    <!-- =========================
                         MONTH TITLE
                    ========================= -->

                    <div class="attendance-month-title">

                        Attendance Of :

                        <span>

                            <?= strtoupper(
                                date(
                                    "F Y",
                                    strtotime($firstDayOfMonth)
                                )
                            ); ?>

                        </span>

                    </div>


                    <!-- =========================
                         LEGEND
                    ========================= -->

                    <div class="attendance-legend">

                        <span class="attendance-legend-title">
                            Legend :
                        </span>


                        <span class="legend-box legend-present">
                            P
                        </span>

                        <span>
                            Present
                        </span>


                        <span class="legend-box legend-absent">
                            A
                        </span>

                        <span>
                            Absent
                        </span>


                        <span class="legend-box legend-leave">
                            L
                        </span>

                        <span>
                            Leave
                        </span>


                        <span class="legend-box legend-halfday">
                            HD
                        </span>

                        <span>
                            Half Day
                        </span>


                        <span class="legend-box legend-holiday">
                            H
                        </span>

                        <span>
                            Holiday
                        </span>


                        <span class="legend-box legend-weekoff">
                            WO
                        </span>

                        <span>
                            Week Off
                        </span>

                    </div>


                    <!-- =========================
                         ATTENDANCE TABLE
                    ========================= -->

                    <div class="attendance-table-wrapper">

                        <table class="attendance-table">


                            <!-- =========================
                                 TABLE HEADER
                            ========================= -->

                            <thead>

                                <tr>

                                    <th rowspan="2">
                                        NO
                                    </th>

                                    <th rowspan="2">
                                        NAME
                                    </th>


                                    <?php

                                    for (
                                        $j = 1;
                                        $j <= $totalDaysInMonth;
                                        $j++
                                    ) {

                                        echo "<th>";

                                        echo $j;

                                        echo "</th>";
                                    }

                                    ?>

                                </tr>


                                <!-- DAY NAME -->

                                <tr>

                                    <?php

                                    for (
                                        $j = 0;
                                        $j < $totalDaysInMonth;
                                        $j++
                                    ) {

                                        $dayName = date(
                                            "D",
                                            strtotime(
                                                "+$j days",
                                                strtotime(
                                                    $firstDayOfMonth
                                                )
                                            )
                                        );

                                        echo "<th>";

                                        echo strtoupper($dayName);

                                        echo "</th>";
                                    }

                                    ?>

                                </tr>

                            </thead>


                            <!-- =========================
                                 TABLE BODY
                            ========================= -->

                            <tbody>

                            <?php

                            if ($totalNumberOfEmployees > 0) {

                                for (
                                    $i = 0;
                                    $i < $totalNumberOfEmployees;
                                    $i++
                                ) {

                                    $empId =
                                        $employeesIDsArray[$i];

                                    $empName =
                                        $employeesNameArray[$i];

                                    $empBranch =
                                        $employeesBranchArray[$i];

                                    ?>

                                    <tr>


                                        <!-- NO -->

                                        <td>

                                            <?= $i + 1; ?>

                                        </td>


                                        <!-- EMPLOYEE NAME -->

                                        <td>

                                            <span class="employee-name">

                                                <?= htmlspecialchars(
                                                    $empName
                                                ); ?>

                                            </span>


                                            <?php if (!empty($empBranch)) { ?>

                                                <span class="employee-branch">

                                                    <?= htmlspecialchars(
                                                        $empBranch
                                                    ); ?>

                                                </span>

                                            <?php } ?>


                                            <a
                                                href="view-emp-attendance.php?employee_id=<?= urlencode($empId); ?>"
                                                class="employee-view-btn"
                                            >
                                                View
                                            </a>

                                        </td>


                                        <!-- =========================
                                             ATTENDANCE DAYS
                                        ========================= -->

                                        <?php

                                        for (
                                            $j = 1;
                                            $j <= $totalDaysInMonth;
                                            $j++
                                        ) {

                                            $dayFormatted =
                                                str_pad(
                                                    $j,
                                                    2,
                                                    "0",
                                                    STR_PAD_LEFT
                                                );

                                            $dateOfAttendance =
                                                $selectedMonth .
                                                "-" .
                                                $dayFormatted;


                                            /*
                                             * Fetch attendance
                                             */

                                            $employeeIdSafe =
                                                mysqli_real_escape_string(
                                                    $conn,
                                                    $empId
                                                );

                                            $dateSafe =
                                                mysqli_real_escape_string(
                                                    $conn,
                                                    $dateOfAttendance
                                                );


                                            $query = "
                                                SELECT attendance
                                                FROM tbl_emp_attendance
                                                WHERE employee_id = '$employeeIdSafe'
                                                AND curr_date = '$dateSafe'
                                                LIMIT 1
                                            ";


                                            $result =
                                                mysqli_query(
                                                    $conn,
                                                    $query
                                                );


                                            $attendanceCode = "";

                                            $attendanceClass =
                                                "attendance-blank";


                                            if (
                                                $result &&
                                                mysqli_num_rows(
                                                    $result
                                                ) > 0
                                            ) {

                                                $attendanceRow =
                                                    mysqli_fetch_assoc(
                                                        $result
                                                    );


                                                $attendanceCode =
                                                    $attendanceRow[
                                                        'attendance'
                                                    ];


                                                /*
                                                 * Color coding
                                                 */

                                                switch (
                                                    $attendanceCode
                                                ) {

                                                    case 'P':

                                                        $attendanceClass =
                                                            'attendance-present';

                                                        break;


                                                    case 'A':

                                                        $attendanceClass =
                                                            'attendance-absent';

                                                        break;


                                                    case 'L':

                                                        $attendanceClass =
                                                            'attendance-leave';

                                                        break;


                                                    case 'HD':

                                                        $attendanceClass =
                                                            'attendance-halfday';

                                                        break;


                                                    case 'H':

                                                        $attendanceClass =
                                                            'attendance-holiday';

                                                        break;


                                                    case 'WO':

                                                        $attendanceClass =
                                                            'attendance-weekoff';

                                                        break;


                                                    default:

                                                        $attendanceClass =
                                                            'attendance-blank';

                                                        break;
                                                }
                                            }


                                            ?>

                                            <td
                                                class="<?= $attendanceClass; ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $attendanceCode
                                                ); ?>

                                            </td>

                                            <?php
                                        }

                                        ?>

                                    </tr>

                                    <?php
                                }

                            } else {

                                ?>

                                <tr>

                                    <td
                                        colspan="<?= $totalDaysInMonth + 2; ?>"
                                        class="no-attendance"
                                    >

                                        No employees found.

                                    </td>

                                </tr>

                                <?php
                            }

                            ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </section>

</section>


<!-- =========================
     JAVASCRIPT
========================= -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>


</body>

</html>