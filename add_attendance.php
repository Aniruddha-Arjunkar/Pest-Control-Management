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

    // Fetch students for this batch
    $students_sql = "SELECT sa.Id, sa.name 
                     FROM tbl_student_admissions sa 
                     WHERE sa.batch_id = '$batch_id'";
    $students_result = mysqli_query($conn, $students_sql);
} else {
    echo "Batch ID is required.";
    exit();
}

if (isset($_POST['submit'])) {
    $date = $_POST['date'];  // Get the selected date from the calendar

    // Loop through the posted attendance data and update it
    foreach ($_POST['attendance'] as $student_id => $status) {
        // Convert status to 1 (present) or 0 (absent)
        $attendance_status = ($status == 'present') ? 'Present' : 'Absent';

        // Insert or update attendance for the selected date
        $attendance_sql = "INSERT INTO tbl_attendance (student_id, batch_id, status, date)
                           VALUES ('$student_id', '$batch_id', '$attendance_status', '$date')
                           ON DUPLICATE KEY UPDATE status = '$attendance_status', date = '$date'";

        mysqli_query($conn, $attendance_sql);
    }

    $_SESSION['msg'] = "Attendance added successfully!";
    header("Location: add_attendance.php?batch_id=$batch_id");
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
                    <form method="POST" action="add_attendance.php?batch_id=<?= $batch_id ?>">
                        <!-- Select Month Year -->
                        <div class="form-group">
                            <label for="date">Select Attendance Date:</label>
                            <input type="text" name="date" id="date" class="form-control" required>
                        </div>

                        <!-- Display students and attendance -->
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (mysqli_num_rows($students_result) > 0) {
                                    while ($student = mysqli_fetch_assoc($students_result)) {
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['name']) ?></td>
                                            <td>
                                                <!-- Attendance selection for each student -->
                                                <input type="radio" name="attendance[<?= $student['Id'] ?>]" value="present" required> Present
                                                <input type="radio" name="attendance[<?= $student['Id'] ?>]" value="absent" required> Absent
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='2'>No students in this batch.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>

                        <button type="submit" name="submit" class="btn btn-success">Submit Attendance</button>
                    </form>

                    <!-- Show success message -->
                    <?php if (isset($_SESSION['msg'])) { ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['msg'] ?>
                        </div>
                        <?php unset($_SESSION['msg']); ?>
                    <?php } ?>
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

<!-- Include jQuery and jQuery UI Datepicker -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

<script>
    // Initialize the datepicker for selecting a date
    $(document).ready(function() {
        $("#date").datepicker({
            dateFormat: "yy-mm-dd", // Format the date as yyyy-mm-dd
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            onSelect: function(dateText) {
                // When a date is selected, automatically update the date field
                $("#date").val(dateText);
            }
        });
    });
</script>

</html>
