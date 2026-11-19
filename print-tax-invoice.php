<?php
require_once 'db_connect.php';

// Safe htmlspecialchars wrapper for PHP 8.1+
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Fetch client
$sql = "SELECT * FROM tbl_client WHERE Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Client not found.";
    exit;
}

$client = $result->fetch_assoc();



// Fetch payments
$payments = [];
$payment_sql = "SELECT * FROM tbl_payment WHERE client_id = ? AND paid_amount > 0";
$payment_stmt = $conn->prepare($payment_sql);
$payment_stmt->bind_param("i", $client_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$total_paid = 0;

while ($row = $payment_result->fetch_assoc()) {
    $payments[] = $row;
    $total_paid += floatval($row['paid_amount']);
}

$balance_amount = $client['final_amount'] - $total_paid;
$total_invoice_amount = $client['total_amount'] + $client['cgst'] + $client['sgst'];



/* =========================
   BLOCK IF PAYMENT PENDING
========================= */
// if ($balance_amount > 0) {
//     die("GST Invoice cannot be generated. Payment is pending.");
// }

/* =========================
   CHECK GST INVOICE EXISTS
========================= */
$check_sql = "SELECT id FROM tbl_gst_invoice WHERE entity_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $client_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

$invoice_exists = ($check_result->num_rows > 0);

/* =========================
   INSERT GST INVOICE (ONCE)
========================= */
if (!$invoice_exists) {

     $invoice_number = $client['gst_invoice_no'];
    $insert_sql = "INSERT INTO tbl_gst_invoice
        (entity_id, source_type, invoice_number, gst_type, amount, cgst, sgst, igst, total_amount, invoice_date, comments)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)";

    $insert_stmt = $conn->prepare($insert_sql);

    $source_type = 'Pest Control';
    $gst_type = 'normal';
    $igst = 0.00;
    $comments = trim(strip_tags($client['other_information']));


    $insert_stmt->bind_param(
        "isissdddds",
        $client_id,
        $source_type,
        $invoice_number,
        $gst_type,
        $client['total_amount'],
        $client['cgst'],
        $client['sgst'],
        $igst,
        $total_invoice_amount,
        $comments
    );

    $insert_stmt->execute();

}




// Number to Words (Indian Style)
function convertToWords($number) {
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three',
        '4' => 'Four', '5' => 'Five', '6' => 'Six',
        '7' => 'Seven', '8' => 'Eight', '9' => 'Nine',
        '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve',
        '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen',
        '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen',
        '19' => 'Nineteen', '20' => 'Twenty', '30' => 'Thirty',
        '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty',
        '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety'
    );

    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');

    if ($number == 0) return 'Zero';

    $no = floor($number);
    $decimal = round($number - $no, 2) * 100;
    $i = 0;
    $str = array();

    while ($no > 0) {
        $divider = ($i == 2) ? 10 : 100;
        $number = $no % $divider;
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;

        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred
                : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
        } else {
            $str[] = null;
        }
    }

    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? "And " . convertToWords($decimal) . " Paise" : '';
    return trim($Rupees . "Rupees " . $paise);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tax Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .invoice-box {
            width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
        }
        .company-details {
            text-align: right;
            margin-top: 10px;
        }
        .invoice-title {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
            text-decoration: underline;
        }
        .details, .items, .total {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .details td, .items th, .items td, .total td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        .items th {
            background-color: #005580;
            color: #ffffff;
        }
        .items td {
            text-align: center;
        }
        .highlight {
            font-weight: bold;
            background-color: #ff8080;
            font-size: 16px;
        }
        .text-right {
            text-align: right;
        }
        .text-total{
            text-align: right;
            background-color: #c2f0f0;
        }
        hr{
height: 5px;
border-width:0;
color: #99dea5;
background-color: #99dea5;
}
        footer {
            background-color: white;
            color: #ffffff;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .footer {
            max-width: 100%;
            margin: 0;
            padding: 0 50px;
            box-sizing: border-box;
        }
        .footer .cont {
            display: flex;
            justify-content: space-between;
            align-items: center;
            line-height: 1.6;
        }
        .footer .cont p,
        .footer .cont a,
        .add, .cin {
            margin: 0;
            font-size: 16px;
            color: black;
            text-decoration: none;
        }
        .footer .cont a:hover {
            color: #1e90ff;
        }
        .add, .cin {
            text-align: center;
        }

        @media print {
  @page {
    size: A4 portrait;
    margin: 10mm; /* safe print margin */
  }

    </style>
</head>
<body onload="window.print()">

<div class="invoice-box">
    <div id="logo" style="text-align:left; margin-bottom: 20px;">
        <a href="index.html"><img src="img/shulventures.jpg" width="240" height="70" alt="Company Logo" /></a>
    </div>
    <hr>
    <div class="company-details">
        <strong>Shul Ventures Pvt. Ltd.</strong><br>
        Bank A/C Number: 920020001745664<br>
        IFSC: UTIB0001242<br>
        Bank Name: AXIS Bank Ltd.<br>
        Branch: Swavalambi Nagar, Nagpur<br> 
        GSTIN: 27ABACS2141Q1Z3<br>
    </div>

    <div class="invoice-title">TAX INVOICE</div>

    <strong>Invoice date:</strong> <?= h($client['due_date']) ?><br><br>

    <strong>Invoice No:</strong> <?= h($client['gst_invoice_no']) ?><br><br>
    <strong>Due Date:</strong> <?= h($client['due_date']) ?><br><br>

    <strong>Invoice To:</strong> 

    <table class="items">
        <tr>
            <th>Billing Address</th>
            <th>Service Address</th>
        </tr>
        <tr>
            <td>
                <?= h($client['cust_name']) ?><br>
                <?= nl2br(h($client['cust_address'])) ?><br>
                GSTIN: <?= h($client['gst_number']) ?>
            </td>
            <td>
                <?= h($client['cust_name']) ?><br>
                <?= nl2br(h(!empty($client['shipping_address']) ? $client['shipping_address'] : $client['cust_address'])) ?><br>
                GSTIN: <?= h($client['gst_number']) ?>
            </td>
        </tr>
    </table>

    <table class="items">
        <tr>
            <th>Service</th>
            <th>Service Date</th>
            <th>Pest Covered</th>
            <th>Description</th>
            <th>Service Charges</th>
        </tr>
        <tr>
            <td><?= h($client['services']) ?></td>
            <td><?= h($client["due_date"]); ?></td>
            <td><?= h($client["pest_covered"]); ?></td>
            <td><?= h($client["other_information"]); ?></td>
            <td class="text-right"><?= number_format($client['total_amount'], 2) ?></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center;">CGST (9%)</td>
            <td class="text-right"><?= number_format($client['cgst'], 2) ?></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:center;">SGST (9%)</td>
            <td class="text-right"><?= number_format($client['sgst'], 2) ?></td>
        </tr>
        <tr>
            <td class="text-total" colspan="4" style="text-align:center;"><strong>Total Amount</strong></td>
            <td class="text-total"><strong>₹<?= number_format($total_invoice_amount, 2) ?></strong></td>
        </tr>
        <tr>
            <td colspan="5" style="color: #444; text-align:center;">
                <strong>(In Words: <?= convertToWords($total_invoice_amount); ?> Only)</strong>
            </td>
        </tr>
    </table><br>

    <table class="items">
        <thead>
            <tr>
                <th>Paid Amount</th>
                <th>Payment Date</th>
                <th>Gateway</th>
                <th>Balance</th> <!-- New TH for Total Amount -->
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($payments)): ?>
                <?php foreach ($payments as $payment): ?>
                   <?php if (floatval($payment['paid_amount']) <= 0) continue; ?>
                    <tr>
                        <td>₹<?= h($payment["paid_amount"]); ?></td>
                        <td><?= h($payment["payment_date"]); ?></td>
                        <td><?= h($payment["payment_gateway"]); ?></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">No payments found.</td></tr>
            <?php endif; ?>

            <tr>
                <td colspan="3" style="text-align:center;" class="highlight">Balance Amount</td>
                <td class="highlight">₹<?= number_format($balance_amount, 2); ?></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:center; color: #333;">
                    <strong>(In Words: <?= convertToWords($balance_amount); ?> Only)</strong>
                </td>
            </tr>
        </tbody>
    </table><br>

    <!-- <?php if ($balance_amount > 0): ?>
        <p style="color: red;">Balance Amount: ₹<?= number_format($balance_amount, 2); ?></p>
        <p style="font-style: italic; color: #333;">(In Words: <?= convertToWords($balance_amount); ?> Only)</p>
    <?php endif; ?> -->

         <p style="text-align:center;"><strong> (This is computer generated PDF, hence signature or stamp not required)</strong></p><br>
<footer>
    <hr>
  <div class="footer">
    <div class="cont">
      <p>+91-9764385996</p>
      <a href="https://shulventures.com/">www.shulventures.com</a>
      <p>support@shulventures.com</p>
    </div>
    <div class="add"> 
      <p>Plot No 21, 2nd Floor, Jiddhi App. 3rd Bus Stop, Gopal Nagar, IT Park Road, Nagpur - 440022</p>
    </div>
    <div class="cin">
      <p>CIN : U74999MH2018PTC308404</p>
    </div>
  </div>
<hr>
</footer>
</body>
</html>
