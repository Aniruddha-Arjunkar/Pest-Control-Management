<?php
session_start();
ob_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

$service_id = $_GET['id'] ?? null;

if (!$service_id) {
    $_SESSION['error'] = "Invalid service ID.";
    header('Location: view-pending-service.php');
    exit();
}

// Fetch service details
$sql_service = "
SELECT ps.*, c.cust_name AS client_name, c.services, c.cust_phone, c.cust_address
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
    header('Location: view-pending-service.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operator_name = $_POST['operator_name'];
    $monitored_by = $_POST['monitored_by'];
    $service_date = $_POST['service_date'];
    $description = $_POST['description'];
    $client_id = $service['client_Id'];

    // Check if a completed service already exists on this date
    $sql_check = "
    SELECT COUNT(*) FROM tbl_pending_services
    WHERE client_Id = ? AND service_date = ? AND status = 'Completed' AND Id != ?
    ";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("isi", $client_id, $service_date, $service_id);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        $_SESSION['error'] = "A completed service for this client already exists on this date.";
    } else {
        if (isset($_POST['mark_completed'])) {
            $status = 'Completed';
            $sql_update = "
            UPDATE tbl_pending_services
            SET operator_name = ?, monitored_by = ?, service_date = ?, description = ?, status = ?
            WHERE Id = ?
            ";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sssssi", $operator_name, $monitored_by, $service_date, $description, $status, $service_id);

            if ($stmt_update->execute()) {
                 // Insert into service_materials
        $remarks = $description;
        $used_date = $service_date;
        $quantity_unit = 'N/A';
        $branch_id = 0;

        $sql_material = "INSERT INTO service_materials (client_id, used_date, remarks, quantity_unit, branch_id)
                         VALUES (?, ?, ?, ?, ?)";
        $stmt_material = $conn->prepare($sql_material);
        $stmt_material->bind_param("isssi", $client_id, $used_date, $remarks, $quantity_unit, $branch_id);
        $stmt_material->execute();
                
                $_SESSION['msg'] = "Service marked as completed successfully!";
                header('Location: view-pending-service.php');
                exit();
            } else {
                $_SESSION['error'] = "Error updating service.";
            }
        } elseif (isset($_POST['mark_pending'])) {
            // Store data in session to complete later
            $_SESSION['complete_later'] = [
                'id' => $service_id,
                'client_id' => $client_id,
                'service_date' => $service_date,
                'operator_name' => $operator_name,
                'monitored_by' => $monitored_by,
                'description' => $description
            ];

            // Redirect to add new pending service
            header("Location: add-new-service.php?client_id=" . $client_id . "&back=update");
            exit();
        }
    }
}
?>


    <section id="main-content">
        <section class="wrapper">
           <div class="reg-w3">
    <div class="refollowup-card">

        <div class="refollowup-header">
            <div class="refollowup-title">
                <span class="phone-icon">📞</span>
                <div>
                    <h2>Update Today's Work</h2>
                    <p>Update today's service details</p>
                </div>
            </div>
        </div>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class='alert alert-danger'><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['msg'])): ?>
                        <div class='alert alert-success'><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
                    <?php endif; ?>

                <form method="POST" action="update-pending-service.php?id=<?= $service['Id'] ?>" class="refollowup-form">
                        <div class="form-group">
                            <label>Operator Name</label>
                           <input type="text" name="operator_name" value="<?= htmlspecialchars($service['operator_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Monitored By</label>
<input type="text" name="monitored_by" value="<?= htmlspecialchars($service['monitored_by'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Service Date</label>
                          <input type="date" name="service_date" value="<?= htmlspecialchars($service['service_date']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                           <textarea name="description" rows="4" required><?= htmlspecialchars($service['description']) ?></textarea>
                        </div>
                       <div style="display: flex; gap: 12px; align-items: center; flex-wrap: nowrap;">

<button type="submit"
        name="mark_completed"
        class="submit-btn"
        style="
            background: #28a745 !important;
            background-color: #28a745 !important;
            color: white !important;
            border: 1px solid #28a745 !important;
            box-shadow: none !important;
        ">
    <span style="color: white !important;">✓</span>
    Service Completed
</button>

    <button type="submit" class="submit-btn"name="mark_pending">
        <span>⏳</span>
        Service Pending
    </button>

</div>
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

<?php ob_end_flush(); ?>