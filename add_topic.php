<?php
session_start();

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

// Get batch ID from URL
if (isset($_GET['batch_id'])) {
    $batch_id = $_GET['batch_id'];
} else {
    echo "Batch ID not provided!";
    exit();
}

// Fetch batch and trainer details
$sql_batch = "SELECT b.batch_name, t.name AS trainer_name, t.Id AS trainer_id
              FROM tbl_batches b
              JOIN tbl_trainers t ON b.trainer_id = t.Id
              WHERE b.Id = '$batch_id'";

$result_batch = mysqli_query($conn, $sql_batch);
$batch = mysqli_fetch_assoc($result_batch);

if (!$batch) {
    echo "Batch not found!";
    exit();
}

// Use the trainer_id from the $batch array
$trainer_id = $batch['trainer_id'];  // Corrected: Now using trainer_id directly from the batch data

// Fetch student IDs for the selected batch
$student_sql = "SELECT Id FROM tbl_student_admissions WHERE batch_id = '$batch_id'";
$student_result = mysqli_query($conn, $student_sql);

// Handle form submission for topics covered
if (isset($_POST['submit'])) {
    $date = $_POST['date'];
    $topic_name = $_POST['topic_name'];
    $topic_descriptions = $_POST['topic_descriptions']; // Topic for the batch on the given date

    // Loop through each student and insert the topic
    while ($student = mysqli_fetch_assoc($student_result)) {
        $student_id = $student['Id'];

        // Insert the topic into the database for each student
        $insert_topic_sql = "INSERT INTO tbl_topics_covered (trainer_id, batch_id, student_id, date, topic_name, topic_descriptions) 
                             VALUES ('$trainer_id', '$batch_id', '$student_id', '$date', '$topic_name', '$topic_descriptions')";

        if (!mysqli_query($conn, $insert_topic_sql)) {
            echo "Error recording topic for student ID $student_id.";
        }
    }
    echo "Topic recorded successfully for all students!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Topics - <?= $batch['batch_name'] ?> | Trainer: <?= $batch['trainer_name'] ?></title>
</head>
<body>
    <section id="main-content">
        <section class="wrapper">
            <div class="reg-w3">
                <div class="w3layouts-main" style="margin-top: 15px;width: 460px;padding-left: 40px;">
    <h2>Batch: <?= $batch['batch_name']; ?> | Trainer: <?= $batch['trainer_name']; ?></h2>

    <form method="POST" action="">
        <label for="date">Date:</label>
        <input type="date" class="ggg" name="date" required><br><br>

        <label for="topic_name">Topic Covered:</label>
        <input type="text" class="ggg" name="topic_name" required><br><br>

        <label for="topic_descriptions">Topic Descriptions:</label>
        <input type="text" class="ggg" name="topic_descriptions" required><br><br>

        <input type="submit" name="submit" value="Submit Topic">
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
    <script src="js/search.js"></script>
</body>
</html>

<?php
mysqli_close($conn);
?>
