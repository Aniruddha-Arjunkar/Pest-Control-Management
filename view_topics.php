<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

// Get batch ID from URL and sanitize it
if (isset($_GET['batch_id'])) {
    $batch_id = mysqli_real_escape_string($conn, $_GET['batch_id']);
} else {
    echo "Batch ID not provided!";
    exit();
}

// Fetch batch and trainer details
$sql_batch = "SELECT b.batch_name, t.name AS trainer_name
              FROM tbl_batches b
              JOIN tbl_trainers t ON b.trainer_id = t.Id
              WHERE b.Id = '$batch_id'";

$result_batch = mysqli_query($conn, $sql_batch);
$batch = mysqli_fetch_assoc($result_batch);

if (!$batch) {
    echo "Batch not found!";
    exit();
}

// Fetch topics covered for this batch
$sql_topics = "SELECT t.topic_name, t.date, t.topic_descriptions
               FROM tbl_topics_covered t
               WHERE t.batch_id = '$batch_id'";

$result_topics = mysqli_query($conn, $sql_topics);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Topics Covered - <?= htmlspecialchars($batch['batch_name']) ?> | Trainer: <?= htmlspecialchars($batch['trainer_name']) ?></title>
</head>
<body>
    <section id="main-content">
        <section class="wrapper">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Topics Covered for Batch: <?= htmlspecialchars($batch['batch_name']) ?> | Trainer: <?= htmlspecialchars($batch['trainer_name']) ?>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Topic Name</th>
                                <th>Topic Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result_topics) > 0) {
                                while ($row = mysqli_fetch_assoc($result_topics)) {
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['date']) ?></td>
                                        <td><?= htmlspecialchars($row['topic_name']) ?></td>
                                        <td><?= htmlspecialchars($row['topic_descriptions']) ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='3'>No topics covered for this batch yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <a href="add_topic.php?batch_id=<?= $batch_id ?>" class="btn btn-success">Add New Topic</a>
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
