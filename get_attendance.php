<?php
require_once 'db_connect.php';

if (isset($_GET['batch_id']) && isset($_GET['student_id'])) {
    $batch_id = $_GET['batch_id'];
    $student_id = $_GET['student_id'];

    // Query to fetch attendance data for the student
    $attendance_sql = "
        SELECT a.status, a.date
        FROM tbl_attendance a
        JOIN tbl_student_admissions sa ON a.student_id = sa.Id
        WHERE a.batch_id = '$batch_id' AND sa.Id = '$student_id'
        ORDER BY a.date ASC
    ";

    $attendance_result = mysqli_query($conn, $attendance_sql);

    $events = [];
    $presentCount = 0;
    $absentCount = 0;

    // Fetch the attendance data
    if (mysqli_num_rows($attendance_result) > 0) {
        while ($attendance = mysqli_fetch_assoc($attendance_result)) {
            $event_date = date('Y-m-d', strtotime($attendance['date']));
            $status = $attendance['status'] == 'Present' ? 'Present' : 'Absent';
            $events[] = [
                'title' => $status,
                'start' => $event_date,
                'status' => $status
            ];

            if ($attendance['status'] == 'Present') {
                $presentCount++;
            } else {
                $absentCount++;
            }
        }
    }

    // Calculate the attendance percentage
    $totalDaysInPeriod = 180; // For example, a 6-month training period
    $attendancePercentage = round(($presentCount / $totalDaysInPeriod) * 100, 2);

    // Return the data as JSON
    echo json_encode([
        'events' => $events,
        'presentCount' => $presentCount,
        'absentCount' => $absentCount,
        'attendancePercentage' => $attendancePercentage
    ]);

    exit();
}
?>
