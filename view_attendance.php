<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';

// Get the batch ID from the URL
if (isset($_GET['batch_id'])) {
    $batch_id = $_GET['batch_id'];

    // Fetch batch and trainer information
    $batch_sql = "SELECT b.batch_name, t.name AS trainer_name 
                  FROM tbl_batches b
                  JOIN tbl_trainers t ON b.trainer_id = t.Id
                  WHERE b.Id = '$batch_id'";
    $batch_result = mysqli_query($conn, $batch_sql);
    $batch = mysqli_fetch_assoc($batch_result);

    if (!$batch) {
        echo "Invalid batch ID.";
        exit();
    }

    // Fetch attendance data for the batch
    $attendance_sql = "SELECT sa.name AS student_name, a.status, a.date 
                       FROM tbl_attendance a
                       JOIN tbl_student_admissions sa ON a.student_id = sa.Id
                       WHERE a.batch_id = '$batch_id'
                       ORDER BY a.date ASC";
    $attendance_result = mysqli_query($conn, $attendance_sql);
} else {
    echo "Batch ID is required.";
    exit();
}

include 'header.php';
?>

<body>
    <section id="main-content">
        <section class="wrapper">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Attendance for Batch: <?= htmlspecialchars($batch['batch_name']) ?> (Trainer: <?= htmlspecialchars($batch['trainer_name']) ?>)
                </div>
                <div class="panel-body">
                    <h3>Attendance Records</h3>
                    <!-- Display attendance records -->
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Attendance Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($attendance_result) > 0) {
                                while ($attendance = mysqli_fetch_assoc($attendance_result)) {
                                    // Convert status to human-readable format
                                    $status = $attendance['status'] == 1 ? 'Present' : 'Absent';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($attendance['student_name']) ?></td>
                                        <td><?= htmlspecialchars($attendance['date']) ?></td>
                                        <td><?= htmlspecialchars($attendance['status']) ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='3'>No attendance records found for this batch.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </section>
    <!-- JS Scripts -->
<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
</body>

</html>
