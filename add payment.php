<?php
session_start();
if (!isset($_SESSION['email'])) {
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
include 'header.php';
?>

<section id="main-content">
    <section class="wrapper">
        <div class="reg-w3">
            <div class="w3layouts-main">
                <h2>Add Payment</h2>
                <div class="ggg" id="payment">
                    <?php
                    $payment = [];
                    if (isset($_GET['Id'])) {
                        $Id = intval($_GET['Id']);
                        $sql = "SELECT * FROM tbl_payment WHERE client_Id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('i', $Id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $payment = $result->fetch_assoc();
                    }
                    ?>

                    <form action="add-payment.php" method="post">
                        <input type="hidden" name="client_Id" value="<?= htmlspecialchars($payment['client_Id'] ?? ''); ?>" required>
                        
                        <h4>Total Amount -</h4>
                        <input type="text" class="ggg" name="total_amount" placeholder="Total Amount" value="<?= htmlspecialchars($payment['total_amount'] ?? ''); ?>" required>
                        
                        <h4>Paid Amount -</h4>
                        <input type="text" class="ggg" name="paid_amount" placeholder="Paid Amount" value="<?= htmlspecialchars($payment['paid_amount'] ?? ''); ?>" required>
                        
                        <h4 style="color:#fff">Payment Date -</h4>
                        <input type="date" class="ggg" name="payment_date" value="<?= htmlspecialchars($payment['payment_date'] ?? ''); ?>" required>
                        
                        <h4>Due Date -</h4>
                        <input type="date" class="ggg" name="due_date" value="<?= htmlspecialchars($payment['due_date'] ?? ''); ?>" >

                        <h4>Payment Gateway -</h4>
                        <input type="text" class="ggg" name="payment_gateway" placeholder="Payment Gateway" value="<?= htmlspecialchars($payment['payment_gateway'] ?? ''); ?>" required>

                        <div class="clearfix"></div>
                        
                        <input name="submit" type="submit" class="primary-btn" value="Submit">
                    </form>

                  <?php
// Processing form submission
if (isset($_POST['submit'])) {
    $client_Id = $_POST['client_Id'];
    $total_amount = $_POST['total_amount'];
    $paid_amount = $_POST['paid_amount'];
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null; // Handle optional field
    $payment_date = $_POST['payment_date'];
    $payment_gateway = $_POST['payment_gateway'];
    $due_amount = $total_amount - $paid_amount;

    if (!empty($payment)) {
        // Update existing payment record
        $sql = "UPDATE tbl_payment SET total_amount = ?, paid_amount = ?, due_amount = ?, due_date = ?, payment_date = ?, payment_gateway = ? WHERE client_Id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ddssisi', $total_amount, $paid_amount, $due_amount, $due_date, $payment_date, $payment_gateway, $client_Id);
    } else {
        // Insert new payment record
        $sql = "INSERT INTO tbl_payment (client_Id, total_amount, paid_amount, due_amount, due_date, payment_date, payment_gateway) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ddsssss', $client_Id, $total_amount, $paid_amount, $due_amount, $due_date, $payment_date, $payment_gateway);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Payment details updated successfully'); window.location.href='due-payments.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

                </div>
            </div>
        </div>
    </section>
   <!-- footer -->
    <!-- <section class="footer">
        <div class="wthree-copyright">
            <p>© 2017 Visitors. All rights reserved | Design by <a href="http://w3layouts.com">W3layouts</a></p>
        </div> -->
    </section> -->
</section>

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
</body>
</html>
