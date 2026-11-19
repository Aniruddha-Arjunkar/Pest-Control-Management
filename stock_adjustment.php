<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $branch_id   = intval($_POST['branch_id']);
    $material_id = intval($_POST['material_id']);
    $qty         = floatval($_POST['quantity']);
    $reason      = mysqli_real_escape_string($conn, $_POST['reason']);
    $user        = $_SESSION['email'];

    if ($qty <= 0) {
        die("Quantity must be greater than 0");
    }

    $insert = "INSERT INTO stock_adjustments 
               (branch_id, material_id, quantity, reason, adjusted_by)
               VALUES 
               ($branch_id, $material_id, $qty, '$reason', '$user')";

    if (mysqli_query($conn, $insert)) {
        echo "<script>alert('Stock Reduced Successfully'); window.location='view_stock.php';</script>";
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

include 'header.php';
?>


<body>
<section id="main-content">
    <section class="wrapper">
<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h3>⚠️ Miscellaneous Stock (Physical Less)</h3>
            <!--<a href="stock-history-table.php" class="btn btn-light btn-sm">Back</a>-->
        </div><br>

        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Branch</label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Select Branch</option>
                            <?php
                            $bq = mysqli_query($conn, "SELECT id,name FROM branches");
                            while($b = mysqli_fetch_assoc($bq)){
                                echo "<option value='{$b['id']}'>{$b['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Material</label>
                        <select name="material_id" class="form-select" required>
                            <option value="">Select Material</option>
                            <?php
                            $mq = mysqli_query($conn, "SELECT id,name FROM materials");
                            while($m = mysqli_fetch_assoc($mq)){
                                echo "<option value='{$m['id']}'>{$m['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Quantity (Reduce)</label>
                        <input type="number" step="0.001" name="quantity" class="form-control" placeholder="Enter quantity to reduce" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="Damage / Missing / Expired / Physical mismatch">
                    </div>
                </div><br>
                <br>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-danger">Reduce Stock</button>
                    <a href="stock-history-table.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
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
