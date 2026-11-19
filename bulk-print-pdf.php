<?php
session_start();
require_once 'db_connect.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Safe htmlspecialchars wrapper for PHP 8.1+
function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$from = $_POST['fromDate'] ?? $_GET['fromDate'] ?? null;
$to = $_POST['endDate'] ?? $_GET['endDate'] ?? null;
$action = $_POST['action'] ?? $_GET['action'] ?? 'print';


if (!$from || !$to) {
    die("Please provide start and end dates.");
}

// Fetch all invoices in date range
$sql = "SELECT * FROM tbl_gst_invoice WHERE invoice_date BETWEEN ? AND ? ORDER BY invoice_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();



// Fetch fallback clients who do NOT have GST invoice in date range
$sqlClients = "
SELECT *
FROM tbl_client c
WHERE c.due_date BETWEEN '$from' AND '$to'
AND c.cgst > 0 AND c.sgst > 0
AND NOT EXISTS (
    SELECT 1 FROM tbl_gst_invoice gi
    WHERE gi.entity_id = c.Id AND gi.source_type = 'Pest Control'
    )
ORDER BY c.due_date ASC
";

$resultClients = mysqli_query($conn, $sqlClients);


if ($result->num_rows === 0) {
    die("No invoices found between $from and $to.");
}

// Helper function to convert amount to words (simplified)
function convertToWords($number) {
    
    
    // Always convert only absolute value (no negative word)
    $number = abs(intval($number));

    
    // your convertToWords function here or use the one you have
    $words = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
    'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen',
    'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    if ($number == 0) return 'Zero';

    if ($number < 20) return $words[$number];

    if ($number < 100) {
        return $tens[(int)($number / 10)] . ' ' . $words[$number % 10];
    }

    if ($number < 1000) {
        return $words[(int)($number / 100)] . ' Hundred ' . convertToWords($number % 100);
    }

    if ($number < 100000) {
        return convertToWords((int)($number / 1000)) . ' Thousand ' . convertToWords($number % 1000);
    }

    if ($number < 10000000) {
        return convertToWords((int)($number / 100000)) . ' Lakh ' . convertToWords($number % 100000);
    }

    return convertToWords((int)($number / 10000000)) . ' Crore ' . convertToWords($number % 10000000);
}

// ---------------- Combine and Sort Records ----------------
$all_records = [];

// Collect GST Invoices
while ($invoice = $result->fetch_assoc()) {
    $all_records[] = [
        'type' => 'invoice',
        'date' => $invoice['invoice_date'],
        'data' => $invoice
    ];
}

// Collect Fallback Clients
while ($client = $resultClients->fetch_assoc()) {
    $all_records[] = [
        'type' => 'client',
        'date' => $client['due_date'],
        'data' => $client
    ];
}

// Sort combined array by date
usort($all_records, function($a, $b) {
    return strtotime($a['date']) <=> strtotime($b['date']);
});

ob_start();

foreach ($all_records as $record) {
    if ($record['type'] === 'invoice') {
        $invoice = $record['data'];    // Fetch entity info based on invoice source_type and entity_id (your logic)
        
        $amount = floatval($invoice['amount']);
$cgst   = floatval($invoice['cgst'] ?? 0);
$sgst   = floatval($invoice['sgst'] ?? 0);
$igst   = floatval($invoice['igst'] ?? 0);

$total_amount = ($igst > 0)
    ? $amount + $igst
    : $amount + $cgst + $sgst;
        
    $source = strtolower(trim($invoice['source_type']));
    $eid = $invoice['entity_id'];
    if ($source == 'pest control') {
        $q = "SELECT * FROM tbl_client WHERE Id = ?";
    } elseif ($source == 'placement') {
        $q = "SELECT * FROM tbl_agreement WHERE Id = ?";
    } elseif ($source == 'consultant') {
        $q = "SELECT * FROM tbl_associate WHERE Id = ?";
    } else {
        continue; // skip unknown source
    }
    $est = $conn->prepare($q);
    $est->bind_param("i", $eid);
    $est->execute();
    $entity = $est->get_result()->fetch_assoc();
   $payments = [];
$total_paid = 0;

if ($source == 'placement' || $source == 'consultant') {
    // For Placement & Consultant, match by interview_id
    $interview_id = $invoice['placement_interview_id'] ?? 0; // fallback to 0 if null
    $payment_sql = "SELECT * FROM tbl_payment WHERE interview_id = ? ORDER BY payment_date DESC";
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param("i", $interview_id); 
} else {
    // For Pest Control, match by client_id
    $payment_sql = "SELECT * FROM tbl_payment WHERE client_id = ? ORDER BY payment_date DESC";
    $payment_stmt = $conn->prepare($payment_sql);
    $payment_stmt->bind_param("i", $eid); // entity_id = client_id
}
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();

if ($payment = $payment_result->fetch_assoc()) {
    $payments[] = $payment;
   $total_paid = floatval($payment['paid_amount']) + floatval($payment['tds_amount']);
}


$balance_amount = $total_amount - $total_paid;


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
                width: 100%;
                max-width: 700px;
                margin: auto;
                /*padding: 30px;*/
                /*border: 1px solid #eee;*/
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
            .page-break { page-break-after: always; }
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
                padding: 0 20px;
                box-sizing: border-box;
            }
            .footer-table {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
}

.footer-table td {
    width: 33%;
    font-size: 14px;
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
                margin-top: 5px;
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
        <div class="invoice-box page-break">
            <div id="logo" style="text-align:left; margin-bottom:20px;">
                <?php
$logo = base64_encode(file_get_contents('img/shulventures.jpg'));
?>
<img src="data:image/jpeg;base64,<?= $logo ?>" width="240" height="70" alt="Company Logo" />

                
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

            <strong>Invoice Date:</strong> <?= h($invoice['invoice_date']) ?><br><br>
            <strong>Invoice No:</strong> <?= h($invoice['invoice_number']) ?><br><br>

            <strong>Invoice To:</strong>
            <table class="items">
                <tr>
                    <th>Billing Address</th>
                    <th>Shipping Address</th>
                </tr>
                <tr>
                    <td>
                        <?= h($entity['cust_name'] ?? $entity['name'] ?? $entity['company_name']) ?><br>
                        <?= nl2br(h($entity['cust_address'] ?? $entity['shipping_address'] ?? '')) ?><br>
                        GSTIN: <?= h($entity['gst_number'] ?? '') ?>
                    </td>
                    <td>
                        <?= h($entity['cust_name'] ?? $entity['name'] ?? $entity['company_name']) ?><br>
                        <?= nl2br(h($entity['cust_address'] ?? $entity['shipping_address'] ?? '')) ?><br>
                        GSTIN: <?= h($entity['gst_number'] ?? '') ?>
                    </td>
                </tr>
            </table>

            <table class="items">
                <tr>
                    <th>Service</th>
                    <th>Service Date</th>
                    <th>Description</th>
                    <th>Service Charges</th>
                </tr>
                <tr>
                    <td><?= h($entity['services'] ?? '') ?></td>
                    <td><?= h($invoice['invoice_date']) ?></td>
                    <td><?= h($invoice['comments']) ?></td>

                    <td class="text-right"><?= number_format($invoice['amount'], 2) ?></td>
                </tr>
                <?php if ($igst > 0): ?>
<tr>
    <td colspan="3" style="text-align:center;">IGST (18%)</td>
    <td class="text-right"><?= number_format($igst, 2) ?></td>
</tr>
<?php else: ?>
<tr>
    <td colspan="3" style="text-align:center;">CGST (9%)</td>
    <td class="text-right"><?= number_format($cgst, 2) ?></td>
</tr>
<tr>
    <td colspan="3" style="text-align:center;">SGST (9%)</td>
    <td class="text-right"><?= number_format($sgst, 2) ?></td>
</tr>
<?php endif; ?>

                <tr>
                    <td class="text-total" colspan="3"><strong>Total Amount</strong></td>
                    <td class="text-total">
    <strong><?= number_format($total_amount, 2) ?></strong>
</td>

                </tr>
                <tr>
                    <td colspan="4" style="text-align:center; color:#333;">
                        <strong>(In Words: <?= convertToWords($total_amount); ?> Only)</strong>

                    </td>
                </tr>
            </table><br>
          
         
<!-- Payments table showing only last payment -->
<table class="items">
<thead>
    <tr>
        <th>Payment Date</th>
        <th>Gateway</th>
        <th>Paid Amount</th>
    </tr>
</thead>
<tbody>
    <?php
    if (!empty($payments)) {
        $last_payment = end($payments); // Get last payment
        ?>
        <tr>
            <td><?= h($last_payment["payment_date"]); ?></td>
            <td><?= h($last_payment["payment_gateway"]); ?></td>
            <td><?= h($last_payment["paid_amount"]) + floatval($last_payment["tds_amount"]); ?></td>
        </tr>
    <?php
    } else {
        ?>
        <tr><td colspan="4">No payments found.</td></tr>
    <?php
    }
    ?>
    <tr>
        <td colspan="2" class="highlight" style="text-align:center;">Balance Amount</td>
        <td class="highlight"><?= number_format($balance_amount, 2); ?></td>
    </tr>
    <tr>
        <td colspan="3" style="text-align:center; color:#333;">
            <strong>(In Words: <?= convertToWords($balance_amount); ?> Only)</strong>
        </td>
    </tr>
</tbody>
</table><br>
            

            <p style="text-align:center;">
                <strong>(This is computer-generated; signature not required)</strong>
            </p>

            <footer>
                <hr>
                <div class="footer">
                   <table class="footer-table">
    <tr>
        <td>+91-9764385996</td>
        <td>www.shulventures.com</td>
        <td>support@shulventures.com</td>
    </tr>
</table>
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


<?php
} else {
        $client = $record['data'];
    $invoice = [
        'invoice_number' => $client['gst_invoice_no'],
        'invoice_date' => $client['due_date'],
        'comments' => $client['other_information'],
        'amount' => $client['final_amount'],
        'cgst' => $client['cgst'],
        'sgst' => $client['sgst'],
        'total_amount' => ($client['igst'] > 0)
    ? $client['final_amount'] + $client['igst']
    : $client['final_amount'] + $client['cgst'] + $client['sgst'],
        'source_type' => 'Pest Control'
    ];

    $entity = $client;

    // Fetch payments
    $payments = [];
    $total_paid = 0;

    $payment_sql = "SELECT * FROM tbl_payment WHERE client_id = ?";
    $payment_stmt = $conn->prepare($payment_sql);
$payment_stmt->bind_param("i", $client['Id']); // Use client Id
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();

while ($row = $payment_result->fetch_assoc()) {
    $payments[] = $row;
    $total_paid += floatval($row['paid_amount']);
}

$balance_amount = $client['final_amount'] - $total_paid;
$total_invoice_amount = $client['total_amount'] + $client['cgst'] + $client['sgst'];


?>

            <div class="invoice-box page-break">
        <div id="logo" style="text-align:left; margin-bottom:20px;">
                <?php
$logo = base64_encode(file_get_contents('img/shulventures.jpg'));
?>
<img src="data:image/jpeg;base64,<?= $logo ?>" width="240" height="70" alt="Company Logo" />

                
            </div>   

        <hr>
        <div class="company-details">
            <strong>Shul Ventures Pvt. Ltd.</strong><br>
            Bank A/C Number: 920020001745664<br>
            IFSC: UTIB0001242<br>
            Bank Name: AXIS Bank Ltd.<br>
            Branch: Swavalambi Nagar, Nagpur<br> 
            GSTIN: 12ABCDE3456F7Z8<br>
        </div>

        <div class="invoice-title">TAX INVOICE</div>

        <strong>Invoice date:</strong> <?= h($client['due_date']) ?><br><br>

        <strong>Invoice No:</strong> <?= h($client['gst_invoice_no']) ?><br><br>
        <strong>Date:</strong> <?= h($client['due_date']) ?><br><br>

        <strong>Invoice To:</strong> 

        <table class="items">
            <tr>
                <th>Billing Address</th>
                <th>Shipping Address</th>
            </tr>
            <tr>
                <td>
                    <?= h($client['cust_name']) ?><br>
                    <?= nl2br(h($client['cust_address'])) ?><br>
                    GSTIN: <?= h($client['gst_number']) ?>
                </td>
                <td>
                    <?= h($client['cust_name']) ?><br>
                    <?= nl2br(h($client['cust_address'])) ?><br>
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
                <td class="text-total"><strong><?= number_format($total_invoice_amount, 2) ?></strong></td>
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
                    <th>Payment Date</th>
                    <th>Gateway</th>
                    <th>Paid Amount</th> <!-- New TH for Total Amount -->
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= h($payment["payment_date"]); ?></td>
                            <td><?= h($payment["payment_gateway"]); ?></td>
                            <td><?= h($payment["paid_amount"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No payments found.</td></tr>
                <?php endif; ?>

                <tr>
                    <td colspan="2" style="text-align:center;" class="highlight">Balance Amount</td>
                    <td class="highlight"><?= number_format($balance_amount, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center; color: #333;">
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
                <table class="footer-table">
    <tr>
        <td>+91-9764385996</td>
        <td>www.shulventures.com</td>
        <td>support@shulventures.com</td>
    </tr>
</table>
              <div class="add"> 
                  <p>Plot No 21, 2nd Floor, Jiddhi App. 3rd Bus Stop, Gopal Nagar, IT Park Road, Nagpur - 440022</p>
              </div>
              <div class="cin">
                  <p>CIN : U74999MH2018PTC308404</p>
              </div>
          </div>
          </footer>
          <hr>
      </div>
 

</body>
</html>
<?php
}
}

$html = ob_get_clean();

if ($action === 'pdf') {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = "Invoices_{$from}_to_{$to}.pdf";
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
} else {
    echo '<html><head><title>Invoices from ' . h($from) . ' to ' . h($to) . '</title></head><body>';
    echo $html;
    echo '<script>window.onload = function() { window.print(); }</script>';
    echo '</body></html>';
}
?>
