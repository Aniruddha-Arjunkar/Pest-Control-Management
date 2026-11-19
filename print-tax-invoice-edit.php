<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connect.php';

$invoice_number = isset($_GET['invoice_number']) ? intval($_GET['invoice_number']) : 0;

if (!$invoice_number) {
    die("Invoice number missing.");
}

// Fetch GST invoice info by invoice_number
$invSql = "SELECT * FROM tbl_gst_invoice WHERE invoice_number = ?";
$invStmt = $conn->prepare($invSql);
$invStmt->bind_param("i", $invoice_number);
$invStmt->execute();
$invResult = $invStmt->get_result();
if ($invResult->num_rows === 0) {
    die("GST Invoice not found.");
}
$invoice = $invResult->fetch_assoc();


// Get source_type safely
$source_type = isset($invoice['source_type']) ? strtolower(trim($invoice['source_type'])) : null;

if (!$source_type) {
    die("Source type not specified in invoice.");
}

// Identify source_type and entity_id from invoice, so you can fetch accordingly
$entity_id = $invoice['entity_id'];
$joining_date = null;
$entity = null;
$client = null;


// Fetch entity info based on source_type
if ($source_type === 'pest control') {
    $sql = "SELECT * FROM tbl_client WHERE Id = ?";
} elseif ($source_type === 'placement') {
    $sql = "SELECT * FROM tbl_agreement WHERE Id = ?";
} elseif ($source_type === 'consultant') {
    $sql = "SELECT * FROM tbl_associate WHERE Id = ?";
} else {
    die("Unknown source type.");
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $entity_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die(ucfirst($source_type) . " not found.");
}
$entity = $result->fetch_assoc();

// ✅ Get Joining Date
$joining_date = '';

if (!empty($invoice['placement_interview_id'])) {
    $interview_id = intval($invoice['placement_interview_id']);
    $sql2 = "SELECT joining_date FROM tbl_placement WHERE interview_id = $interview_id";
    $res2 = mysqli_query($conn, $sql2);
    if ($row2 = mysqli_fetch_assoc($res2)) {
        $joining_date = $row2['joining_date'];
    }
}



// Fetch pending services for this entity (only for clients, you can adapt this for others if needed)
if ($source_type === 'pest control') {
    $pendingSql = "SELECT * FROM tbl_pending_services WHERE client_Id = ?";
    $pendingStmt = $conn->prepare($pendingSql);
    $pendingStmt->bind_param("i", $entity_id);
    $pendingStmt->execute();
    $pendingResult = $pendingStmt->get_result();
} else {
    $pendingResult = false;
}

// Function to convert numbers to Indian words
function convertToWords($number) {
    // (Keep your convertToWords function here as in your original code)
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
        $number_part = $no % $divider;
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;

        if ($number_part) {
            $plural = (($counter = count($str)) && $number_part > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number_part < 21) ? $words[$number_part] . " " . $digits[$counter] . $plural . " " . $hundred
            : $words[floor($number_part / 10) * 10] . " " . $words[$number_part % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
        } else {
            $str[] = null;
        }
    }

    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? "And " . convertToWords($decimal) . " Paise" : '';
    return trim("Rupees " . $Rupees . $paise);
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
            color: #000;
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
                margin: 10mm;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="invoice-box">
        <div id="logo" style="text-align:left; margin-bottom:20px;">
            <img src="img/shulventures.jpg" width="240" height="70" alt="Company Logo" />
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

        <strong>Invoice Date:</strong> <?= htmlspecialchars($invoice['invoice_date']) ?><br><br>
        <strong>Invoice No:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?><br><br>

        <strong>Invoice To:</strong>
        <table class="items">
            <tr>
                <th>Billing Address</th>
                <th>Shipping Address</th>
            </tr>
            <tr>
                <td>
                    <?= htmlspecialchars($entity['cust_name'] ?? $entity['name'] ?? $entity['company_name']) ?><br>
                    <?= nl2br(htmlspecialchars($entity['cust_address'] ?? $entity['shipping_address'] ?? '')) ?><br>
                    GSTIN: <?= htmlspecialchars($entity['gst_number'] ?? '') ?>
                </td>
                <td>
                    <?= htmlspecialchars($entity['cust_name'] ?? $entity['name'] ?? $entity['company_name']) ?><br>
                    <?= nl2br(htmlspecialchars($entity['cust_address'] ?? $entity['shipping_address'] ?? '')) ?><br>
                    GSTIN: <?= htmlspecialchars($entity['gst_number'] ?? '') ?>
                </td>
            </tr>
        </table>

        <table class="items">
            <tr>
                <th>Service</th>
               <th>
        <?= ($source_type === 'consultant' || $source_type === 'placement') ? 'Joining Date' : 'Service Date'; ?>
    </th>
                <th>Description</th>
                <th>Service Charges</th>
            </tr>
            <tr>
                <td>
    <?php
        if ($source_type === 'consultant') {
            echo 'Consultant';
        } elseif ($source_type === 'placement') {
            echo 'Placement';
        } else {
            echo htmlspecialchars($entity['services'] ?? 'Service');
        }
    ?>
</td>

                <td>
    <?php 
                    if ($source_type === 'pest control' && !empty($entity['due_date'])) {
                        echo date('d-m-Y', strtotime($entity['due_date']));
                    } elseif (($source_type === 'consultant' || $source_type === 'placement') && !empty($joining_date)) {
                        echo date('d-m-Y', strtotime($joining_date));
                    } else {
                        echo date('d-m-Y', strtotime($invoice['invoice_date']));
                    }
                    ?>
</td>
                <td>
                    <?php if ($source_type === 'Pest Control'): ?>
                        <?php if (!empty($entity['other_information'])): ?>
                            <?= nl2br(htmlspecialchars($entity['other_information'])) ?><br>
                        <?php endif; ?>
                        <?= nl2br(htmlspecialchars($invoice['comments'])) ?>
                    <?php else: ?>
                        <?= nl2br(htmlspecialchars($invoice['comments'])) ?>
                    <?php endif; ?>
                </td>

                <td class="text-right">₹<?= number_format($invoice['amount'], 2) ?></td>
            </tr>
           <?php if ($invoice['gst_type'] === 'igst'): ?>
                <tr>
                    <td colspan="3" style="text-align:center;">IGST (18%)</td>
                    <td class="text-right">₹<?= number_format($invoice['igst'], 2) ?></td>
                </tr>
            <?php elseif ($invoice['gst_type'] === 'exclusive' || $invoice['gst_type'] === 'inclusive'): ?>
                <tr>
                    <td colspan="3" style="text-align:center;">CGST (9%)</td>
                    <td class="text-right">₹<?= number_format($invoice['cgst'], 2) ?></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;">SGST (9%)</td>
                    <td class="text-right">₹<?= number_format($invoice['sgst'], 2) ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="text-total" colspan="3"><strong>Total Amount</strong></td>
                <td class="text-total"><strong>₹<?= number_format($invoice['total_amount'], 2) ?></strong></td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:center; color:#333;">
                    <strong>(In Words: <?= convertToWords($invoice['total_amount']); ?> Only)</strong>
                </td>
            </tr>
        </table><br>

        <?php if ($pendingResult && $pendingResult->num_rows > 0): ?>
            <h3 style="margin-top:40px; text-align:center;">Pending Services for <?= htmlspecialchars($entity['cust_name'] ?? $entity['name'] ?? $entity['company_name']); ?></h3>
            <table class="items" style="width: 100%; margin-top: 10px;">
                <tr style="background-color:#005580; color:#fff;">
                    <th>Service Date</th>
                    <th>Operator Name</th>
                    <th>Monitored By</th>
                    <th>Description</th>
                </tr>
                <?php while ($service = $pendingResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($service['service_date']) ?></td>
                        <td><?= htmlspecialchars($service['operator_name']) ?></td>
                        <td><?= htmlspecialchars($service['monitored_by']) ?></td>
                        <td><?= htmlspecialchars($service['description']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php endif; ?>

        <p style="text-align:center;">
            <strong>(This is system generated PDF; signature & stamp not required)</strong>
        </p>

        <footer>
            <hr>
            <div class="footer">
                <div class="cont">
                    <p>+91-9764385996</p>
                    <a href="https://shulventures.com/">www.shulventures.com</a>
                    <p>support@shulventures.com</p>
                </div>
                <div class="add">
                    <p>Plot No 21, 2nd Floor, Jiddhi App. 3rd Bus Stop, Gopal Nagar, IT Park Road, Nagpur – 440022</p>
                </div>
                <div class="cin">
                    <p>CIN: U74999MH2018PTC308404</p>
                </div>
            </div>
            <hr>
        </footer>
    </div>
</body>
</html>
