<li class="sub-menu">
    <a href="javascript:;">
        <span>Batches Management</span>
    </a>
    <ul class="sub">
        <!-- Existing submenu items -->
        <li class="sub-menu">
            <a href="javascript:;">
                <i class="fa fa-users"></i>
                <span>View My Profile</span>
            </a>
            <ul class="sub">
                <li><a href="view-trainers.php">View Trainers</a></li>
            
        

        <!-- New 'Select Batches' with Dropdown -->
        <li>
            <a href="javascript:void(0);" onclick="toggleBatchDropdown()">View Batches</a>
            <ul class="sub" id="batchDropdown" style="display:none;">
                <?php
                // Assuming session contains trainer's email or ID to fetch batches
                $logged_in_email = $_SESSION['email'];
                $trainer_query = "SELECT Id FROM tbl_trainers WHERE email = '$logged_in_email'";
                $trainer_result = mysqli_query($conn, $trainer_query);

                if ($trainer_row = mysqli_fetch_assoc($trainer_result)) {
                    $trainer_id = $trainer_row['Id'];

                    // Fetch batches for the trainer
                    $batch_query = "SELECT Id, batch_name FROM tbl_batches WHERE trainer_id = '$trainer_id'";
                    $batch_result = mysqli_query($conn, $batch_query);

                    // Check if batches are available for the trainer
                    if (mysqli_num_rows($batch_result) > 0) {
                        while ($batch_row = mysqli_fetch_assoc($batch_result)) {
                            echo "<li><a href='view_batch_details.php?batch_id={$batch_row['Id']}'>{$batch_row['batch_name']}</a></li>";
                        }
                    } else {
                        echo "<li>No batches available</li>";
                    }
                }
                ?>
            </ul>
        </li>
        </ul>
    </ul>
</li>


