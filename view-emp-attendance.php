<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

// Ensure employee_id is set in the URL
if (!isset($_GET['employee_id'])) {
    echo "Employee ID is required.";
    exit();
}

$employee_id = $_GET['employee_id'];

// Set the current month
$selected_month = date("Y-m"); // Format: YYYY-MM
$start_date = $selected_month . "-01";  // Start date for the current month

// Fetch employee name
$emp_sql = "SELECT cust_name FROM tbl_employee WHERE id = '$employee_id'";
$emp_result = mysqli_query($conn, $emp_sql);
$employee = mysqli_fetch_assoc($emp_result);

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<style>
    .container { max-width: 900px; margin: 0 auto; padding: 30px; text-align: center;}
    #mini-calendar { width: 100%; max-width: 500px; margin: 0 auto; overflow: hidden;  }
    .back-btn { margin-top: 20px; text-align: center; }
    .fc-event { font-size: 11px; padding: 2px; text-align: center; }
    .summary-box { background: #f8f8f8; padding: 12px 20px; margin: 20px auto; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;  max-width: 500px; text-align: center; margin-bottom: 50px;}
    .summary-box span { margin-right: 15px; font-weight: bold; }
    .green { color: green; } .red { color: red; } .orange { color: orange; } .skyblue { color: skyblue; } .purple { color: purple; }
    .fc-unthemed td.fc-today {
        background: #dcd6b6a6;
}
 .fc-icon{
    color: black;
}
</style>
</head>
<body>
    <section id="main-content">
        <section class="wrapper">
            <div class="panel panel-default">
                <div class="panel-heading">
                    View Attendance
                </div>
                <div class="container">
                <h2>Attendance for <u><?= htmlspecialchars($employee['cust_name']) ?></u> <!-- - 
                    <span style="color: red"><?= strtoupper(date("F Y", strtotime($start_date))) ?></span> -->
                </h2>

                <!-- Summary -->
                <div class="summary-box">
                    <span class="green">Present: <span id="present">--</span></span>
                    <span class="red">Absent: <span id="absent">--</span></span>
                    <span class="orange">Leave: <span id="leave">--</span></span>
                    <span class="skyblue">Half Day: <span id="halfday">--</span></span>
                    <span class="purple">Holiday: <span id="holiday">--</span></span>
                </div>

                <!-- Calendar -->
                <div id="mini-calendar"></div>

                <!-- Back Button -->
                <div class="back-btn">
                    <a href="view-all-emp-attendance.php"><button class="btn btn-default">← Back</button></a>
                </div>
            </div>
        </div>
    </section>
</section>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.js"></script>
<script>
    $(document).ready(function () {
        var employeeId = '<?= $employee_id ?>';

        function updateSummary(data) {
            let p = 0, a = 0, l = 0, hd = 0, h = 0, wo = 0;

            var currentDate = $('#mini-calendar').fullCalendar('getDate');
            var monthStart = moment(currentDate).startOf('month');
            var monthEnd = moment(currentDate).endOf('month');

            data.forEach(e => {
        let eventDate = moment(e.start, 'YYYY-MM-DD');  // Strict format

        // Debug log
        // console.log("Checking date:", eventDate.format('YYYY-MM-DD'));

        if (eventDate.month() !== monthStart.month() || eventDate.year() !== monthStart.year()) {
            return;
        }

        switch (e.title) {
        case 'Present': p++; break;
        case 'Absent': a++; break;
        case 'Leave': l++; break;
        case 'Half Day': hd++; break;
        case 'Holiday': h++; break;
        case 'Week Off': wo++; break;
        }
    });

            $('#present').text(p);
            $('#absent').text(a);
            $('#leave').text(l);
            $('#halfday').text(hd);
            $('#holiday').text(h);
            $('#weekoff').text(wo);
        }


        $('#mini-calendar').fullCalendar({
            header: { left: 'prev,next', center: 'title', right: '' },
            defaultDate: '<?= $start_date ?>',
            editable: false,
            eventLimit: true,
    fixedWeekCount: false,  // <-- ADD THIS LINE
    events: function(start, end, timezone, callback) {
        $.ajax({
            url: 'fetch_attendance.php',
            dataType: 'json',
            data: {
                employee_id: employeeId,
                start: start.format('YYYY-MM-DD'),
                end: end.format('YYYY-MM-DD')
            },
            success: function(response) {
                callback(response);
                updateSummary(response);
            }
        });
    },
    eventRender: function (event, element, view) {
        element.attr('title', event.description);

        const eventMonth = moment(event.start).month();
        const currentMonth = $('#mini-calendar').fullCalendar('getDate').month();

    // If event is not from the current visible month, hide it
        if (eventMonth !== currentMonth) {
        element.hide(); // Hide attendance mark for non-current month dates
    }
}

});
    });
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
