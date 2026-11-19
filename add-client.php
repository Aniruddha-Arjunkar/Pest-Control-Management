<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('db_connect.php');

if (isset($_POST['submit'])) {

    // Safe POST handling (no more undefined array key warnings)
    $client_Id = $_POST['client_Id'] ?? null;

    $services = $_POST['services'] ?? '';
    $warranty = $_POST['warranty'] ?? '';
    $cust_name = $_POST['cust_name'] ?? '';
    $cust_phone = $_POST['cust_phone'] ?? '';
    $cust_email = $_POST['cust_email'] ?? '';
    $cust_address = $_POST['cust_address'] ?? '';
    $total_amount = $_POST['total_amount'] ?? 0;

    // Fix decimal issues (empty string → 0)
    $cgst = (isset($_POST['cgst']) && $_POST['cgst'] !== '') ? $_POST['cgst'] : 0;
    $sgst = (isset($_POST['sgst']) && $_POST['sgst'] !== '') ? $_POST['sgst'] : 0;
    $gst_total = (isset($_POST['gst_total']) && $_POST['gst_total'] !== '') ? $_POST['gst_total'] : 0;

    $final_amount = (isset($_POST['final_amount']) && $_POST['final_amount'] !== '') 
                    ? $_POST['final_amount'] 
                    : $total_amount;

    $gst_number = $_POST['gst_number'] ?? '';
    $gst_invoice_no = !empty($_POST['gst_invoice_no']) ? $_POST['gst_invoice_no'] : null;

    $shipping_address = $_POST['shipping_address'] ?? '';
    $source = $_POST['source'] ?? '';
    $due_date = $_POST['due_date'] ?? date('Y-m-d');
    $operator_name = $_POST['operator_name'] ?? '';
    $pest_covered = $_POST['pest_covered'] ?? '';
    $other_information = $_POST['other_information'] ?? '';
    $invoice_no = $_POST['invoice_no'] ?? 0;
    $branch = $_POST['branch'] ?? '';

    // tbl_payment fields
    $paid_amount = $_POST['paid_amount'] ?? 0;
    $due_amount = $_POST['due_amount'] ?? 0;
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');

    // service_materials placeholders
    $material_id = null;
    $quantity_used = 0;
    $rate_at_time = 0;
    $used_date = $due_date;
    $remarks = "Auto-added from client creation";

    // Check duplicate client
    $check_sql = "SELECT * FROM tbl_client WHERE cust_name='$cust_name' AND due_date='$due_date'";
    $result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>alert('Client already exists.'); window.location.href='future-clients.php';</script>";
        exit;
    }

    // INSERT into tbl_client
    $sql = "INSERT INTO tbl_client 
    (services, warranty, cust_name, cust_phone, cust_email, cust_address, 
    total_amount, cgst, sgst, gst_total, final_amount, gst_number, gst_invoice_no, 
    shipping_address, source, due_date, operator_name, pest_covered, other_information, 
    invoice_no, branch) 
    VALUES 
    ('$services', '$warranty', '$cust_name', '$cust_phone', '$cust_email', '$cust_address', 
    '$total_amount', '$cgst', '$sgst', '$gst_total', '$final_amount', '$gst_number', 
    '$gst_invoice_no', '$shipping_address', '$source', '$due_date', '$operator_name', 
    '$pest_covered', '$other_information', '$invoice_no', '$branch')";

    if (mysqli_query($conn, $sql)) {
        $client_last_id = mysqli_insert_id($conn);
    } else {
        die("Error inserting client: " . mysqli_error($conn));
    }

    // INSERT into tbl_payment
    $sql1 = "INSERT INTO tbl_payment (client_Id, paid_amount, due_amount, total_amount, payment_date, due_date)
             VALUES ('$client_last_id', '$paid_amount', '$due_amount', '$final_amount', '$payment_date', '$due_date')";

    if (!mysqli_query($conn, $sql1)) {
        die("Error inserting payment: " . mysqli_error($conn));
    }

    // INSERT into service_materials
    $sql2 = "INSERT INTO service_materials (client_id, material_id, quantity_used, rate_at_time, used_date, remarks)
             VALUES ('$client_last_id', NULL, '$quantity_used', '$rate_at_time', '$used_date', '$remarks')";

    if (!mysqli_query($conn, $sql2)) {
        die("Error inserting materials: " . mysqli_error($conn));
    }

    echo "<script>alert('Client details added successfully'); 
    window.location.href='view-client.php';</script>";
}
?>
