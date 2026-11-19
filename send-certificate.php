<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

// Get client_id from URL
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

// Fetch client details (now includes other_information)
$sql = "SELECT cust_name, cust_address, due_date, services, warranty, pest_covered, other_information 
        FROM tbl_client 
        WHERE Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Client not found.";
    exit;
}

$client = $result->fetch_assoc();
$client_name = $client['cust_name'];
$client_address = nl2br($client['cust_address']);
$service = $client['services'];
$pest_covered = $client['pest_covered'];
$warranty = $client['warranty'];
$due_date = date("d/m/Y", strtotime($client['due_date']));
$other_information = trim($client['other_information'] ?? '');

// Define matching keywords for each service type
$match_keywords = [
    'GPC' => ['GENERAL PEST CONTROL', 'GPC'],
    'TM'  => ['ANTI-TERMITE', 'TERMITE'],
];

// Initialize empty array
$service_dates_map = [];

// Fetch pending services
$sql2 = "SELECT description, service_date 
         FROM tbl_pending_services 
         WHERE client_id = ? 
         ORDER BY service_date ASC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $client_id);
$stmt2->execute();
$res2 = $stmt2->get_result();

// Get actual services
$services = preg_split('/\d+\)\s*/', $client['services'], -1, PREG_SPLIT_NO_EMPTY);

// Loop through each description and map to service if matched by keyword
while ($row = $res2->fetch_assoc()) {
    $desc = strtoupper($row['description']);
    foreach ($services as $srv) {
        $srv_clean = strtoupper(trim($srv));
        if (!isset($service_dates_map[$srv_clean])) {
            foreach ($match_keywords as $service_code => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($desc, $keyword) !== false) {
                        $service_dates_map[$srv_clean] = date("d/m/Y", strtotime($row['service_date']));
                        break 3;
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Service Certificate</title>
    <style>
        img {
            margin-left: 50px;
            max-width: 280px;
            height: 70px;
            display: block;
        }
        h1, h2 {
            text-align: center;
            text-decoration: underline 2px solid;
        }
        h1 { margin-top: 50px; margin-bottom: 40px; }
        .ref-date {
            display: flex;
            justify-content: space-between;
            margin: 0 50px;
            font-size: 20px;
        }
        .ref-date p { margin: 0; }
        .signature-align {
            display: flex;
            justify-content: space-between;
            margin: 0 50px;
            font-size: 20px;
        }
        .sig-column {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 25%;
            margin-bottom: 100px;
        }
        .spacer { height: 30px; }
        .info {
            margin-left: 50px;
            margin-right: 50px;
            font-size: 20px;
        }
        .address {
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-left: 50px;
            margin-right: 50px;
            font-size: 18px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            width: 40%;
        }
        .table1, th, td {
            border: 1px solid black;
            border-collapse: collapse;
            text-align: center;
            margin-left: 50px;
            margin-right: 50px;
        }
        td, th { padding: 5px; font-weight: bold; }
        .tblr td { padding: 10px; }
        th {
            color: black;
            background-color: #99dea5;
        }
        .terms {
            margin-left: 50px;
            margin-right: 50px;
            font-size: 20px;
            margin-bottom: 100px;
        }
        .terms p { margin-bottom: 0; }
        .terms p + p { margin-top: 0; }
        hr {
            height: 5px;
            border-width: 0;
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
        .footer .cont a:hover { color: #1e90ff; }
        .add, .cin { text-align: center; }

        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                zoom: 75%;
            }
            footer {
                position: fixed;
                bottom: 0;
                width: 100%;
            }
            .table1-container,
            .terms,
            .info,
            .address { page-break-inside: avoid; }
            .signature-align {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: flex-start;
                flex-wrap: nowrap !important;
                margin: 0 50px;
                font-size: 20px;
            }
            .sig-column {
                width: 30% !important;
                margin-bottom: 0 !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

<img alt="logo" src="img/shulventures.jpg">
<hr>
<h2>License No.: LAID2701060</h2>

<div class="ref-date">
  <p>Ref: Shul/Pest/Cert/25-26</p>
  <p>Date: <?= htmlspecialchars($due_date); ?></p>
</div>

<pre class="address">
To,
<b><?= htmlspecialchars($client_name); ?></b>
<?= $client_address ?>
</pre>

<h1>Subject: Certificate For Pest Control Services</h1>

<div class="info">
  <p>This is to certify that <b><?= htmlspecialchars($client_name); ?></b> is in contract with <b>Shul Ventures Pvt. Ltd.</b> for following services at the above address.</p>
</div>

<div class="table1-container">
<table class="table1" width="92%">
<tr>
<th>Service</th>
<th>Pest Covered</th>
<th>Validity</th>
<th>Date of Service</th>
</tr>
<?php
$services   = preg_split('/\d+\)\s*/', $client['services'], -1, PREG_SPLIT_NO_EMPTY);
$pests      = preg_split('/\d+\)\s*/', $client['pest_covered'], -1, PREG_SPLIT_NO_EMPTY);
$warranties = preg_split('/\d+\)\s*/', $client['warranty'], -1, PREG_SPLIT_NO_EMPTY);

$count = count($services);
for ($i = 0; $i < $count; $i++) {
    $srv = trim($services[$i]);
    $pest = isset($pests[$i]) ? trim($pests[$i]) : '';
    $warr = isset($warranties[$i]) ? trim($warranties[$i]) : '';
    $serv_date = isset($service_dates_map[strtoupper($srv)]) 
        ? $service_dates_map[strtoupper($srv)] 
        : date("d/m/Y", strtotime($client['due_date']));
?>
<tr class="tblr">
  <td><?= htmlspecialchars($srv); ?></td>
  <td><?= htmlspecialchars($pest); ?></td>
  <td><?= htmlspecialchars($warr); ?></td>
  <td><?= htmlspecialchars($serv_date); ?></td>
</tr>
<?php } ?>
</table>
</div><br>

<div class="terms">
  <p>We further under cover that, this job is covered over service validity subject to following conditions.</p>

  <?php 
  $serial = 1;
  if (!empty($other_information)): 
      // Split other info into separate lines
      $other_lines = preg_split("/\r\n|\n|\r/", $other_information);
  ?>
      <div class="other-info">
       <?php 
foreach ($other_lines as $line): 
    $clean_line = trim(preg_replace('/^\s*\d+[\.\)]\s*/', '', $line)); // Remove any starting number like "1." or "2)"
    if ($clean_line === '') continue; 
?>
    <p><?= $serial++ . ". " . htmlspecialchars($clean_line); ?></p>
<?php endforeach; ?>
      </div>
  <?php endif; ?>
  <p><?= $serial++ ?>. The above services provided based on the precautionary/infestation available during the service. If any infestation reappears, it shall be suitably treated as per service schedule of the contract.</p>
  <p><?= $serial++ ?>. Entire work has been carried under the instructions and guidelines of client's purchase order.</p>
  <br>
  <p><b>Note:</b> The terms and conditions are same as mentioned in contract work order.</p>
</div>

<div class="signature-align">
  <div class="sig-column">
    <p>For <b>Shul Ventures Pvt. Ltd.</b></p>
    <div class="spacer"></div>
    <img src="img/stamp1.png" alt="Company Stamp" style="height: 129px; margin-bottom: -40px; margin-top: -60px; margin-left: 10px;">
    <p>Authorized Signatory</p>
  </div>
</div>

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
