<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
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

include 'header.php';
require_once 'db_connect.php'; // DB connection
?>
<style>
/* =========================================================
   EMPLOYEE ATTENDANCE PAGE
========================================================= */

#main-content {
    width: 100%;
    box-sizing: border-box;
}

#main-content .wrapper {
    width: 100%;
    max-width: 1250px;
    margin: 0 auto;
    padding: 25px 20px;
    box-sizing: border-box;
}

/* =========================================================
   PANEL
========================================================= */

#main-content .panel {
    background: #ffffff;
    border: none;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
}

/* =========================================================
   PANEL HEADING
========================================================= */

#main-content .panel-heading {
    padding: 18px 22px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #ffffff;
    font-size: 20px;
    font-weight: 600;
    border: none;
    border-radius: 14px 14px 0 0;
}

/* =========================================================
   PANEL BODY
========================================================= */

#main-content .panel-body {
    padding: 22px;
    background: #f8fafc;
}

/* =========================================================
   ATTENDANCE TABLE
========================================================= */

#main-content .table-responsive {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-family: Arial, sans-serif;
    font-size: 14px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

/* =========================================================
   TABLE HEADER
========================================================= */

#main-content .table-responsive tr:first-child td {
    background: #f8fafc;
    color: #374151;
    font-weight: 600;
    text-align: left;
    padding: 15px 16px;
}

#main-content .table-responsive tr:first-child td:first-child {
    width: 180px;
    font-weight:700px;
}

/* Date input */
#main-content input[type="date"] {
    width: 220px;
    max-width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    background: #ffffff;
    color: #374151;
    font-size: 14px;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

#main-content input[type="date"]:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
}

/* =========================================================
   COLUMN HEADERS
========================================================= */

#main-content .table-responsive tr:nth-child(2) th {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #ffffff;
    font-weight: 750;
    padding: 14px 10px;
    font-size:15px;
    text-align: center;
    border-top: 1px solid #e0e7ff;
    border-bottom: 1px solid #e0e7ff;
}

#main-content .table-responsive tr:nth-child(2) th:first-child {
    text-align: left;
    padding-left: 16px;
}

/* =========================================================
   TABLE CELLS
========================================================= */

#main-content .table-responsive td,
#main-content .table-responsive th {
    border-right: 1px solid #e5e7eb;
    border-bottom: 1px solid #e5e7eb;
}

#main-content .table-responsive tr td:last-child,
#main-content .table-responsive tr th:last-child {
    border-right: none;
}

#main-content .table-responsive tbody td,
#main-content .table-responsive tr td {
    padding: 13px 10px;
    background: #ffffff;
    color: #374151;
    text-align: center;
    vertical-align: middle;
}

/* Employee name */
#main-content .table-responsive tr td:first-child {
    text-align: left;
    padding-left: 16px;
    font-weight: 500;
    color: #1f2937;
}

/* =========================================================
   HOVER EFFECT
========================================================= */

#main-content .table-responsive tr:hover td {
    background: #f8faff;
}

/* =========================================================
   CHECKBOXES
========================================================= */

#main-content .table-responsive input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;

    width: 14px;
    height: 14px;

    border: 2px solid #8ca4c0;
    border-radius: 5px;

    background: #ffffff;
    cursor: pointer;

    position: relative;
    vertical-align: middle;

    transition: all 0.2s ease;
}

#main-content .table-responsive input[type="checkbox"]:hover {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.08);
}

#main-content .table-responsive input[type="checkbox"]:checked {
    background: #6366f1;
    border-color: #6366f1;
}

#main-content .table-responsive input[type="checkbox"]:checked::after {
    content: "";
    position: absolute;

    width: 2px;
    height: 6px;

    border: solid #ffffff;
    border-width: 0 2px 2px 0;

    transform: rotate(45deg);

    left: 4px;
    top: 1px;
}

/* =========================================================
   SUBMIT ROW
========================================================= */

#main-content .table-responsive tr:last-child th {
    padding: 18px;
    background: #f8fafc;
    border-bottom: none;
}

/* =========================================================
   SUBMIT BUTTON
========================================================= */

#main-content input[name="addAttendanceBTN"] {
    border: none;
    outline: none;

    padding: 11px 24px;

    background: #6366f1;
    color: #ffffff;

    font-size: 14px;
    font-weight: 600;

    border-radius: 7px;

    cursor: pointer;

    box-shadow: 0 3px 8px rgba(99, 102, 241, 0.25);

    transition: all 0.2s ease;
}

#main-content input[name="addAttendanceBTN"]:hover {
    background: #4f46e5;
    transform: translateY(-1px);
    box-shadow: 0 5px 12px rgba(99, 102, 241, 0.30);
}

#main-content input[name="addAttendanceBTN"]:active {
    transform: translateY(0);
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    #main-content .wrapper {
        padding: 15px 10px;
    }

    #main-content .panel-heading {
        font-size: 18px;
        padding: 16px;
    }

    #main-content .panel-body {
        padding: 12px;
        overflow-x: auto;
    }

    #main-content .table-responsive {
        min-width: 750px;
    }

    #main-content input[type="date"] {
        width: 100%;
        max-width: 250px;
    }
}

/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    #main-content .wrapper {
        padding: 10px 5px;
    }

    #main-content .panel-heading {
        font-size: 16px;
        padding: 14px;
    }

    #main-content .panel-body {
        padding: 8px;
    }
}
</style>

<body>
<section id="main-content">
<section class="wrapper">
    <div class="panel panel-default">
        <div class="panel-heading">Employee Attendance</div>
        <div class="panel-body">
            <form method="POST">
                <table class="table-responsive" border="1" cellspacing="0">
                    <tr>
                        <td>Select Date (Optional)</td>
                        <td colspan="6">
                            <input type="date" name="selected_date" />
                        </td>
                    </tr>
                    <tr>
                        <th>Employee Name</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Leave</th>
                        <th>Holiday</th>
                        <th>Half Day</th>
                        <th>Week Off</th>
                    </tr>
                    <?php
                    
                    $branchFilter = "";

                    if ($role !== 'super_admin' && !empty($_SESSION['branch'])) {
                        $branch = mysqli_real_escape_string($conn, $_SESSION['branch']);
                        $branchFilter = " AND branch = '$branch'";
                    }
                    $employees_sql = "SELECT id, cust_name FROM tbl_employee WHERE (resignation_type IS NULL 
        OR resignation_type = '') $branchFilter ";
                    $employees_result = mysqli_query($conn, $employees_sql);

                    while ($data = mysqli_fetch_assoc($employees_result)) {
                        $employee_id = $data['id'];
                        $cust_name = $data['cust_name'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($cust_name); ?></td>
                        <td><input type="checkbox" name="employeePresent[]" value="<?= $employee_id; ?>" /></td>
                        <td><input type="checkbox" name="employeeAbsent[]" value="<?= $employee_id; ?>" /></td>
                        <td><input type="checkbox" name="employeeLeave[]" value="<?= $employee_id; ?>" /></td>
                        <td><input type="checkbox" name="employeeHoliday[]" value="<?= $employee_id; ?>" /></td>
                        <td><input type="checkbox" name="employeeHalfDay[]" value="<?= $employee_id; ?>" /></td>
                        <td><input type="checkbox" name="employeeWeekOff[]" value="<?= $employee_id; ?>" /></td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <th colspan="7">
                            <input type="submit" name="addAttendanceBTN" value="Submit Attendance" />
                        </th>
                    </tr>
                </table>
            </form>
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

<?php
function upsertAttendance($conn, $employee_id, $date, $month, $year, $status) {
    $check = "SELECT * FROM tbl_emp_attendance WHERE employee_id = '$employee_id' AND curr_date = '$date'";
    $res = mysqli_query($conn, $check);

    if (mysqli_num_rows($res) > 0) {
        $update = "UPDATE tbl_emp_attendance 
                   SET attendance = '$status', attendance_month = '$month', attendance_year = '$year' 
                   WHERE employee_id = '$employee_id' AND curr_date = '$date'";
        mysqli_query($conn, $update);
    } else {
        $insert = "INSERT INTO tbl_emp_attendance (employee_id, curr_date, attendance_month, attendance_year, attendance)
                   VALUES ('$employee_id', '$date', '$month', '$year', '$status')";
        mysqli_query($conn, $insert);
    }
}

if (isset($_POST['addAttendanceBTN'])) {
    date_default_timezone_set("Asia/Karachi");
    $selected_date = !empty($_POST['selected_date']) ? $_POST['selected_date'] : date("Y-m-d");
    $attendance_month = date("M", strtotime($selected_date));
    $attendance_year = date("y", strtotime($selected_date));

    $statuses = [
        'employeePresent' => 'P',
        'employeeAbsent' => 'A',
        'employeeLeave' => 'L',
        'employeeHoliday' => 'H',
        'employeeHalfDay' => 'HD',
        'employeeWeekOff' => 'WO'
    ];

    foreach ($statuses as $field => $code) {
        if (isset($_POST[$field])) {
            foreach ($_POST[$field] as $employee_id) {
                upsertAttendance($conn, $employee_id, $selected_date, $attendance_month, $attendance_year, $code);
            }
        }
    }

    echo "<script>alert('Attendance recorded successfully.'); window.location.href='';</script>";
}
?>