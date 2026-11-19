<?php
session_start();
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location: login.php');
    exit();
}

require_once 'db_connect.php'; // Database connection
include 'header.php';
?>

<body>
    <!-- Main content start -->
    <section id="main-content">
        <section class="wrapper">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Archived Complaints
                </div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client ID</th>
                                <th>Details</th>
                                <th>Date</th>
                                <th>Resolver Name</th>
                                <th>Resolve Date</th>
                                <th>Deleted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM tbl_complaints_archive ORDER BY deleted_at DESC";
                            $result = mysqli_query($conn, $sql);

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row["Id"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["client_id"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["details"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["date"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["resolver_name"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["resolve_date"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["deleted_at"]) . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No archived complaints found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </section>
    <!-- Main content end -->

    <?php include 'footer.php'; ?>

    <script src="js/bootstrap.js"></script>
    <script src="js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/jquery.nicescroll.js"></script>
    <!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot-chart/excanvas.min.js"></script><![endif]-->
    <script src="js/jquery.scrollTo.js"></script>
</body>
</html>
