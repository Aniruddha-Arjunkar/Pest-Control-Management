<?php
session_start();
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

include 'header.php';
require_once 'db_connect.php'; // Database connection

// Helper function to safely escape HTML
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Get the service ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid service ID.";
    header('Location: view-pending-service.php'); // Redirect if the ID is invalid
    exit();
}

$service_id = $_GET['id'];

// Fetch the service details for the given service ID
$sql_service = "
    SELECT ps.*, c.cust_name AS client_name, c.services, c.cust_phone, c.cust_address, c.due_date, c.operator_name
    FROM tbl_pending_services ps
    JOIN tbl_client c ON ps.client_Id = c.Id
    WHERE ps.Id = ?
";
$stmt_service = $conn->prepare($sql_service);
$stmt_service->bind_param("i", $service_id);
$stmt_service->execute();
$result_service = $stmt_service->get_result();
$service = $result_service->fetch_assoc();

if (!$service) {
    $_SESSION['error'] = "Service not found.";
    header('Location: view-pending-service.php'); // Redirect if the service is not found
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Detail</title>
    <link rel="stylesheet" href="styles.css">
</head>
<style>

/* ================================
   PAGE / MAIN CONTENT
================================ */

#main-content {
    width: 100%;
    padding: 20px;
    box-sizing: border-box;
    background: #f1f4f8;
}

#main-content .wrapper {
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
}


/* ================================
   MAIN PANEL
================================ */

.panel.panel-default {
    background: #e1e5f1;
    border: none;
    border-radius: 12px;
    padding: 35px;
    box-shadow: none;
    max-width: 1180px;
    margin: 0 auto;
}

.panel-heading {
    background: transparent;
    border: none;
    color: #17243a;
    font-size: 25px;
    font-weight: 700;
    padding: 4px 0 18px 0;
}

.panel-body {
    background: #ffffff;
    border-radius: 10px;
    padding: 18px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}


/* ================================
   SERVICE DETAILS TABLE
================================ */

.table-responsive {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-family: Arial, sans-serif;
    font-size: 14px;
    box-shadow: none;
    margin: 0;
}

.table-responsive th,
.table-responsive td {
    border: 1px solid #c9d3e4;
    padding: 11px 12px;
    height: 38px;
    box-sizing: border-box;
    vertical-align: middle;
}

.table-responsive th {
    width: 150px;
    background: linear-gradient(135deg, #4d86e8, #3979dc);
    color: #ffffff;
    text-align: left;
    text-transform: uppercase;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.2;
}

.table-responsive td {
    background: #ffffff;
    color: #27364d;
    font-size: 13px;
    font-weight: 400;
    word-wrap: break-word;
}


/* Alternate row background */

.table-responsive tr:nth-child(even) td {
    background: #f4f7fc;
}


/* Status text */

.table-responsive tr:last-child td {
    font-weight: 600;
    color: #26364c;
}


/* ================================
   PANEL FOOTER / BUTTONS
================================ */

.panel-footer {
    background: transparent;
    border: none;
    padding: 16px 0 0 0;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}


/* Common button style */

.panel-footer .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 6px 13px;
    border-radius: 6px;
    border: none;
    color: #ffffff !important;
    text-decoration: none;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.2;
    box-shadow: none;
}


/* Update Service */

.panel-footer .btn-success {
    background: #16a34a !important;
}


/* Back button */

.panel-footer .btn-primary {
    background: #1454d8 !important;
}


/* Update Service Date & Description */

.panel-footer .btn-info {
    background: #0795bd !important;
}


/* Hover */

.panel-footer .btn:hover {
    opacity: 0.9;
    text-decoration: none;
}


/* ================================
   MOBILE
================================ */

@media (max-width: 768px) {

    #main-content {
        padding: 10px;
    }

    .panel.panel-default {
        padding: 12px;
        border-radius: 10px;
    }

    .panel-heading {
        font-size: 21px;
        padding-bottom: 14px;
    }

    .panel-body {
        padding: 10px;
        overflow-x: auto;
    }

    .table-responsive {
        min-width: 600px;
    }

    .table-responsive th {
        width: 145px;
    }

    .panel-footer {
        padding-top: 12px;
    }

    .panel-footer .btn {
        font-size: 12px;
        padding: 7px 10px;
    }
}

</style>
<body>
    <!-- Main content start -->
    <section id="main-content">
        <section class="wrapper">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Service Details - Pending Service
                </div>
                <div class="panel-body">
                    <table class="table-responsive">
                        <tbody>
                            <tr>
                                <th>Client Name</th>
                                <td><?= h($service['client_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Service Type</th>
                                <td><?= h($service['services']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td><?= h($service['cust_phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td><?= h($service['cust_address']); ?></td>
                            </tr>
                            <tr>
                                <th>Previous Service Date</th>
                                <td><?= h($service['due_date']); ?></td>
                                </tr>
                                <tr>
                                <th>Previous Operator Name</th>
                                <td><?= h($service['operator_name']); ?></td>
                            </tr>
                            
                             
                            <tr>
                                <th>Upcoming Service Date</th>
                                <td><?= h($service['service_date']); ?></td>
                            </tr>
                            <tr>
                                <th>Operator Name</th>
                                <td><?= h($service['operator_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Monitored By</th>
                                <td><?= h($service['monitored_by']); ?></td>
                            </tr>
                            
                            <tr>
                                <th>Description</th>
                                <td><?= h($service['description']); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><?= h($service['status']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="panel-footer">
                    <a href="update-pending-service.php?id=<?= $service['Id']; ?>" class="btn btn-success">Update Service</a>
                    <a href="view-pending-service.php" class="btn btn-primary">Back to Pending Services</a>
                    <a href="update-service.php?id=<?= $service['Id']; ?>" class="btn btn-info">Update Service Date and Description</a>
                </div>
            </div>
        </section>
    </section>
    <!-- Main content end -->
    
    <script src="js/bootstrap.js"></script>
    <script src="js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/jquery.nicescroll.js"></script>
    <script src="js/jquery.scrollTo.js"></script>
</body>
</html>