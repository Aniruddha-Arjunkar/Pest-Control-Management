<?php 
session_start();
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

// Get the service_id and client_id from the query string
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Fetch the current service date details
$sql_service = "SELECT * FROM tbl_amc WHERE Id = ?";
$stmt_service = $conn->prepare($sql_service);
$stmt_service->bind_param("i", $service_id);
$stmt_service->execute();
$result_service = $stmt_service->get_result();

if ($result_service->num_rows > 0) {
    $service_data = $result_service->fetch_assoc();
} else {
    echo "Service date not found.";
    exit;
}

// Handle the form submission to update the service date
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_date = $_POST['service_date'];
    $operator_name = $_POST['operator_name'];
    $monitored_by = $_POST['monitored_by'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    
    
    
        // Handle Defaulter AMC
if (isset($_POST['mark_defaulter'])) {

    $defaulter_date   = $_POST['service_date'];   // reuse service_date
    $defaulter_reason = $_POST['description'];    // reuse description

    if (empty($defaulter_date) || empty($defaulter_reason)) {
        echo "<script>alert('Defaulter date and reason are required');</script>";
    } else {

        $sql_defaulter = "UPDATE tbl_amc 
                          SET status = 'DEFAULTER',
                              service_date = ?,
                              description = ?
                          WHERE Id = ?";

        $stmt_defaulter = $conn->prepare($sql_defaulter);
        $stmt_defaulter->bind_param("ssi", $defaulter_date, $defaulter_reason, $service_id);

        if ($stmt_defaulter->execute()) {
            echo "<script>
                alert('AMC marked as Defaulter successfully');
                window.location.href='view-service-dates.php?client_id=$client_id';
            </script>";
        } else {
            echo "Error: " . $stmt_defaulter->error;
        }

        $stmt_defaulter->close();
    }
}


    // Update the service date in the database
    else{
    $sql_update = "UPDATE tbl_amc SET service_date = ?, operator_name = ?, monitored_by = ?, description = ?, status = ? WHERE Id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssssi", $service_date, $operator_name, $monitored_by, $description, $status, $service_id);

    if ($stmt_update->execute()) {
        
              // ✅ If status is "Completed", insert into service_materials
        if ($status === 'Completed') {
            $remarks = $description;       // Use description as remarks
            $used_date = $service_date;    // Used date is service date
            $quantity_unit = 'N/A';        // Placeholder
            $branch_id = 0;                // Placeholder (replace with actual if needed)

            // Check if already inserted
            $sql_check = "SELECT id FROM service_materials WHERE client_id = ? AND used_date = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("is", $client_id, $used_date);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows === 0) {
                // Insert new record
                $sql_material = "INSERT INTO service_materials 
                    (client_id, used_date, remarks, quantity_unit, branch_id)
                    VALUES (?, ?, ?, ?, ?)";
                $stmt_material = $conn->prepare($sql_material);
                $stmt_material->bind_param("isssi", $client_id, $used_date, $remarks, $quantity_unit, $branch_id);

                if (!$stmt_material->execute()) {
                    echo "Error inserting into service_materials: " . $stmt_material->error;
                }

                $stmt_material->close();
            }

            $stmt_check->close();
        }
        
        echo "<script>alert('Service Date updated successfully'); window.location.href='view-service-dates.php?client_id=$client_id';</script>";
    } else {
        echo "Error updating service date: " . $stmt_update->error;
    }
    $stmt_update->close();
}
}

?>

<body>
<section id="main-content"> 

    <section class="wrapper"> 

        <div class="reg-w3">

            <div class="refollowup-card">

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">📞</span>

                        <div>

                            <h2>Update Service Date</h2>

                            <p>
                                Update service date details
                            </p>

                        </div>

                    </div>

                </div>

                <form action="" method="POST" class="refollowup-form">
                    <!-- Form to edit service date -->
                   <form action="" method="POST" class="refollowup-form">
                        <div class="form-group">
                            <label for="service_date">Service Date</label>
                           <input type="date" name="service_date" id="service_date" value="<?= htmlspecialchars($service_data['service_date']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="operator_name">Operator Name</label>
                          <input type="text"
       name="operator_name"
       id="operator_name"
       placeholder="Operator Name"
       value="<?= htmlspecialchars($service_data['operator_name']); ?>"
       required>
                        </div>

                        <div class="form-group">
                            <label for="monitored_by">Monitored By</label>
                           <input type="text"
       name="monitored_by"
       id="monitored_by"
       placeholder="Monitored By"
       value="<?= htmlspecialchars($service_data['monitored_by']); ?>"
       required>
                        </div>

                        <!-- Description field -->
                        <div class="form-group">
                            <label for="description">Description</label>
                        <textarea name="description"
          id="description"
          class="form-control"
          rows="4"
          required><?= htmlspecialchars($service_data['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- Status field -->
                        <div class="form-group">
                            <label for="status">Status</label>
                           <select name="status" id="status" required>
                                <option value="Completed" <?= ($service_data['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="Pending" <?= ($service_data['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="DEFAULTER" <?= ($service_data['status'] == 'DEFAULTER') ? 'selected' : ''; ?>>
                                    Defaulter
                                </option>
                            </select>
                        </div>

                       <div style="display: flex; gap: 12px; align-items: center; flex-wrap: nowrap;">

    <button type="submit" class="submit-btn">
        <span>✓</span>
        Update Service Status
    </button>

    <button type="submit"
            name="mark_defaulter"
            class="refollowup-clear-btn"
            style="background-color: #dc3545; color: #fff; border-color: #dc3545;
            onclick="return confirm('Mark this AMC as Defaulter?');">
        <span>⚠</span>
        Mark as Defaulter AMC
    </button>

    <a href="view-service-dates.php?client_id=<?= $client_id; ?>"
       class="refollowup-back-btn">
        <span>←</span>
        Cancel
    </a>

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

<?php
$stmt_service->close();
$conn->close();
?>