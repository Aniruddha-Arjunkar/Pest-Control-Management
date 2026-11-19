<?php
require_once 'db_connect.php';

if (!isset($_GET['employee_id']) || !isset($_GET['start']) || !isset($_GET['end'])) {
    echo json_encode([]);
    exit();
}

$employee_id = $_GET['employee_id'];
$start = $_GET['start'];
$end = $_GET['end'];

$query = "SELECT curr_date, attendance FROM tbl_emp_attendance 
          WHERE employee_id = '$employee_id' 
          AND curr_date BETWEEN '$start' AND '$end'";
$result = mysqli_query($conn, $query);

$events = [];

while ($row = mysqli_fetch_assoc($result)) {
    switch ($row['attendance']) {
        case 'P': $title = 'Present'; $color = 'green'; break;
        case 'A': $title = 'Absent'; $color = 'red'; break;
        case 'L': $title = 'Leave'; $color = 'orange'; break;
        case 'HD': $title = 'Half Day'; $color = 'skyblue'; break;
        case 'H': $title = 'Holiday'; $color = 'purple'; break;
        case 'WO': $title = 'Week Off'; $color = 'BlueViolet'; break;
        default: $title = 'Unknown'; $color = 'gray'; break;
    }

    $events[] = [
        'title' => $title,
        'start' => $row['curr_date'],
        'description' => "Status: $title",
        'color' => $color
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
?>
