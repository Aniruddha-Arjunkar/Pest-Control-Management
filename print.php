<?php
session_start();
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location: login.php');
    exit();
}

require_once 'db_connect.php'; // Database connection

// Get client_id from query string
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Fetch client details from the database
$sql_client = "SELECT * FROM tbl_client WHERE Id = ?";
$stmt_client = $conn->prepare($sql_client);
$stmt_client->bind_param("i", $client_id);
$stmt_client->execute();
$result_client = $stmt_client->get_result();

// Check if client exists
if ($result_client->num_rows > 0) {
    $client = $result_client->fetch_assoc();
} else {
    echo "Client not found.";
    exit();
}

// Fetch payment details for the client
$sql_payments = "SELECT paid_amount, payment_date, payment_gateway 
                  FROM tbl_payment 
                  WHERE client_Id = ?";
$stmt_payments = $conn->prepare($sql_payments);
$stmt_payments->bind_param("i", $client_id);
$stmt_payments->execute();
$result_payments = $stmt_payments->get_result();

$total_service_charge = floatval($client['total_amount']);
$total_paid_amount = 0;

// Store payment details
$payments = [];
while ($payment = $result_payments->fetch_assoc()) {
    if (!empty($payment['paid_amount']) && floatval($payment['paid_amount']) > 0) {
    $paid_amount = floatval($payment['paid_amount']);
    $total_paid_amount += $paid_amount;
    $payments[] = $payment;
}
}

// Calculate balance amount
$balance_amount = $total_service_charge - $total_paid_amount;




// Fetch AMC (Annual Maintenance Contract) details for the client
$sql_amc = "SELECT * FROM tbl_amc WHERE client_Id = ? ";
$stmt_amc = $conn->prepare($sql_amc);
$stmt_amc->bind_param("i", $client_id);
$stmt_amc->execute();
$result_amc = $stmt_amc->get_result();

// Fetch service track details for the client
$sql_service_track = "SELECT service_date, operator_name, description, monitored_by 
                      FROM tbl_pending_services  
                      WHERE client_id = ? ORDER BY service_date ASC";
$stmt_service_track = $conn->prepare($sql_service_track);
$stmt_service_track->bind_param("i", $client_id);
$stmt_service_track->execute();
$result_service_track = $stmt_service_track->get_result();

// Store service track details
$service_tracks = [];
while ($track = $result_service_track->fetch_assoc()) {
    $service_tracks[] = $track;
}


// Fetch resolved complaints for the client
$sql_resolved_complaints = "SELECT complaint_date, details, resolver_name, resolve_date 
                            FROM tbl_resolved_complaints 
                            WHERE client_id = ?";
$stmt_resolved_complaints = $conn->prepare($sql_resolved_complaints);
$stmt_resolved_complaints->bind_param("i", $client_id);
$stmt_resolved_complaints->execute();
$result_resolved_complaints = $stmt_resolved_complaints->get_result();



function formatTermsAndConditions($terms) {
    // Split terms by line breaks OR numbering patterns
    $points = preg_split('/\r\n|\r|\n|\d+\.\s*/', $terms, -1, PREG_SPLIT_NO_EMPTY);

    // Start ordered list
    $formatted = "<ol style='margin:0; padding-left:20px;'>";

    foreach ($points as $point) {
        $point = trim($point);
        if ($point != '') {
            $formatted .= "<li>" . htmlspecialchars($point) . "</li>";
        }
    }

    $formatted .= "</ol>";
    return $formatted;
}


// Function to format the address by splitting based on commas or newlines
function formatAddress($address) {
    // Split the address based on commas or newlines
    $addressArray = preg_split('/[\n;]+/', $address);
    $formattedAddress = '';

    // Loop through each part and add a line break
    foreach ($addressArray as $line) {
        $formattedAddress .= nl2br(trim($line)) . "<br>"; // Ensure proper line breaks
    }
    return $formattedAddress;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Bill</title>
    <link rel="stylesheet" href="path/to/your/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .invoice-box {
            width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
        }
        h1 {
            text-align: center;
            text-decoration: underline;
            font-size: 25px;
        }
        h2 {
            text-align: left;
            margin: 10px 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        h5 {
            text-align: center;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            font-size: 15px;
            background-color: #cefad0;
            color: black;
        }
        .table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .table tr:nth-child(odd) {
            background-color: #ffffff;
        }
        .total {
            font-weight: bold;
            margin-top: 20px;
        }
        .button {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 15px;
            background-color: #0275d8;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 13px;
            color: blue;
            background-color: #cefad0;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        @media print {
            .button {
                display: none;
            }
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 0;
                border: none;
            }
        }
        .table td {
            word-wrap: break-word;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
    <div id="logo" style="text-align:left; margin-bottom: 20px;">
        <a href="index.html"><img src="img/shul.jpg" width="250" height="70" alt="Company Logo" title="Company Logo" /></a>
    </div>
    <h1>Service Bill</h1>
    
    <!-- Client Details Section -->
    <h3>Client Details</h3>
    <table class="table">
        <tr>
            <th>Customer Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Address</th>
        </tr>
        <tr>
            <td><?= htmlspecialchars($client["cust_name"] ?? ''); ?></td>
            <td><?= htmlspecialchars($client["cust_phone"] ?? ''); ?></td>
            <td><?= htmlspecialchars($client["cust_email"] ?? ''); ?></td>
            <td><?= formatAddress($client["cust_address"] ?? ''); ?></td>
        </tr>
    </table>

    <!-- Service Details Section -->
    <h3>Service Details</h3>
    <table class="table">
        <tr>
            <th>Branch</th>
            <td><?= htmlspecialchars($client["branch"] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Invoice No</th>
            <td><?= htmlspecialchars($client["invoice_no"] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Service</th>
            <td><?= htmlspecialchars($client["services"] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Warranty</th>
            <td><?= htmlspecialchars($client["warranty"] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Pest Covered</th>
            <td><?= htmlspecialchars($client["pest_covered"] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Service Terms and Conditions</th>
            <td><?= formatTermsAndConditions($client["other_information"] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Service Date</th>
            <td><?= htmlspecialchars($client["due_date"] ?? ''); ?></td>
        </tr>
        <tr>
            <th>Service Charges</th>
            <td>INR <?= htmlspecialchars($client["total_amount"] ?? ''); ?></td>
        </tr>
    </table>

    <!-- Service Track Report Section -->
    <div id="service-track-report" class="no-print">
        <?php if (!empty($service_tracks)): ?>
            <h3>Service Track Report</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Service Date</th>
                        <th>Description</th>
                        <th>Monitored By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($service_tracks as $track): ?>
                        <tr>
                            <td><?= htmlspecialchars($track["service_date"] ?? ''); ?></td>
                            <td><?= htmlspecialchars($track["description"] ?? ''); ?></td>
                            <td><?= htmlspecialchars($track["monitored_by"] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    
    <div id="resolved-complaints-report" class="no-print">
    <?php if ($result_resolved_complaints->num_rows > 0): ?>
        <h3>Resolved Complaints</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Complaint Date</th>
                    <th>Details</th>
                    <th>Operator Name</th>
                    <th>Resolve Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($complaint = $result_resolved_complaints->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($complaint["complaint_date"] ?? ''); ?></td>
                        <td><?= htmlspecialchars($complaint["details"] ?? ''); ?></td>
                        <td><?= htmlspecialchars($complaint["resolver_name"] ?? ''); ?></td>
                        <td><?= htmlspecialchars($complaint["resolve_date"] ?? ''); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>


    <!-- Payment Details Section -->
    <h3>Payment Details</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Paid Amount</th>
                <th>Payment Date</th>
                <th>Gateway</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($payments)): ?>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td>INR <?= htmlspecialchars($payment["paid_amount"] ?? ''); ?></td>
                        <td><?= htmlspecialchars($payment["payment_date"] ?? ''); ?></td>
                        <td><?= htmlspecialchars($payment["payment_gateway"] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan='3'>No payments found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Balance Amount Section -->
    <?php if ($balance_amount > 0): ?>
        <p class="total">Balance Amount: INR <?= htmlspecialchars(number_format($balance_amount, 2)); ?></p>
    <?php endif; ?>

    <!-- Conditionally display the AMC details if available -->
    <div id="amc-report" class="no-print">
        <?php if ($result_amc->num_rows > 0): ?>
            <h3> Services Track Report</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Service Date</th>
                        <th>Description</th>
                        <th>Monitored By</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($amc = $result_amc->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($amc["service_date"] ?? ''); ?></td>
                            <td><?= htmlspecialchars($amc["description"] ?? ''); ?></td>
                            <td><?= htmlspecialchars($amc["monitored_by"] ?? ''); ?></td>
                            <td><?= htmlspecialchars($amc["status"] ?? ''); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <h5>(This is a computer-generated PDF, hence signature or stamp is not required)</h5>

    <div class="footer">
        <p>Plot No 21, 2nd Floor, Jiddhi App 3rd Bus Stop, Gopal Nagar, IT Park Road, Nagpur - 440022</p>
        <p>+91 - 9767713742 | CIN: U74999MH2018PTC308404</p>
        <p><a href="mailto:pestcontrol@shulventures.com" style="color: blue;">pestcontrol@shulventures.com</a> | <a href="http://www.shulventures.com" style="color: blue;">www.shulventures.com</a></p>
    </div>

    <!-- Print Button -->
    <a href="#" onclick="printBill()" class="button">Print Bill</a>
    <a href="#" onclick="printBillWithServiceReport()" class="button">Print Bill With Service Report</a>
    <!-- Back Button -->
    <a href="javascript:window.history.back()" class="button">Back</a>    
    <script>
        function printBill() {
    document.getElementById('service-track-report').style.display = 'none';
    document.getElementById('amc-report').style.display = 'none';
    document.getElementById('resolved-complaints-report').style.display = 'none';
    window.print();
    document.getElementById('service-track-report').style.display = 'block';
    document.getElementById('amc-report').style.display = 'block';
    document.getElementById('resolved-complaints-report').style.display = 'block';
}

function printBillWithServiceReport() {
    document.getElementById('service-track-report').style.display = 'block';
    document.getElementById('amc-report').style.display = 'block';
    document.getElementById('resolved-complaints-report').style.display = 'block';
    window.print();
}

    </script>
    </div>
</body>
</html>

