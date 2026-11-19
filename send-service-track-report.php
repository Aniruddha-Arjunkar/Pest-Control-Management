<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';


// =====================================================
// GET CLIENT ID
// =====================================================

$client_id = isset($_GET['client_id'])
    ? intval($_GET['client_id'])
    : 0;

if ($client_id <= 0) {
    echo "Invalid Client ID.";
    exit;
}


// =====================================================
// FETCH CLIENT
// =====================================================

$sql_client = "
    SELECT
        Id,
        cust_name,
        cust_address
    FROM tbl_client
    WHERE Id = ?
";

$stmt_client = $conn->prepare($sql_client);

if (!$stmt_client) {
    die("Database Error: " . $conn->error);
}

$stmt_client->bind_param("i", $client_id);
$stmt_client->execute();

$result_client = $stmt_client->get_result();

if ($result_client->num_rows === 0) {
    echo "Client not found.";
    exit;
}

$client = $result_client->fetch_assoc();

$client_name = $client['cust_name'] ?? '';
$client_address = $client['cust_address'] ?? '';


// =====================================================
// 1. FETCH COMPLETED SERVICE TRACK
// =====================================================

$sql_services = "
    SELECT
        service_date,
        operator_name,
        description,
        monitored_by,
        status
    FROM tbl_pending_services
    WHERE client_Id = ?
      AND LOWER(status) = 'completed'
    ORDER BY service_date ASC
";

$stmt_services = $conn->prepare($sql_services);

if (!$stmt_services) {
    die("Database Error: " . $conn->error);
}

$stmt_services->bind_param("i", $client_id);
$stmt_services->execute();

$result_services = $stmt_services->get_result();

$service_tracks = [];

while ($service = $result_services->fetch_assoc()) {
    $service_tracks[] = $service;
}


// =====================================================
// 2. FETCH AMC SERVICE TRACK
// =====================================================

$sql_amc = "
    SELECT
        service_date,
        operator_name,
        description,
        reason,
        monitored_by,
        status
    FROM tbl_amc
    WHERE client_Id = ?
    ORDER BY service_date ASC
";

$stmt_amc = $conn->prepare($sql_amc);

if (!$stmt_amc) {
    die("Database Error: " . $conn->error);
}

$stmt_amc->bind_param("i", $client_id);
$stmt_amc->execute();

$result_amc = $stmt_amc->get_result();

$amc_tracks = [];

while ($amc = $result_amc->fetch_assoc()) {
    $amc_tracks[] = $amc;
}


// =====================================================
// 3. FETCH ONLY PENDING SERVICES
// =====================================================

$sql_pending = "
    SELECT
        service_date,
        operator_name,
        description,
        monitored_by,
        status
    FROM tbl_pending_services
    WHERE client_Id = ?
      AND LOWER(status) = 'pending'
    ORDER BY service_date ASC
";

$stmt_pending = $conn->prepare($sql_pending);

if (!$stmt_pending) {
    die("Database Error: " . $conn->error);
}

$stmt_pending->bind_param("i", $client_id);
$stmt_pending->execute();

$result_pending = $stmt_pending->get_result();

$pending_tracks = [];

while ($pending = $result_pending->fetch_assoc()) {
    $pending_tracks[] = $pending;
}


// =====================================================
// CURRENT DATE
// =====================================================

$report_date = date("d/m/Y");


// =====================================================
// DATE FORMAT FUNCTION
// =====================================================

function formatServiceDate($date)
{
    if (empty($date)) {
        return '';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return htmlspecialchars($date);
    }

    return date("d/m/Y", $timestamp);
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Service Track Report</title>


<style>

/* =====================================================
   GENERAL
===================================================== */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
    color: #000;
    background: #fff;
}


/* =====================================================
   LOGO
===================================================== */

.logo {
    margin-left: 50px;
    max-width: 280px;
    height: 70px;
    display: block;
    object-fit: contain;
}


/* =====================================================
   GREEN LINE
===================================================== */

.green-line {
    height: 5px;
    border: 0;
    background-color: #99dea5;
    margin: 10px 0;
}


/* =====================================================
   LICENSE
===================================================== */

.license {
    text-align: center;
    text-decoration: underline;
    margin-top: 20px;
    margin-bottom: 30px;
    font-size: 22px;
}


/* =====================================================
   REF DATE
===================================================== */

.ref-date {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0 50px 30px 50px;
    font-size: 18px;
}

.ref-date p {
    margin: 0;
}


/* =====================================================
   CLIENT
===================================================== */

.client-info {
    margin: 0 50px;
    font-size: 18px;
    line-height: 1.6;
}


/* =====================================================
   REPORT CONTAINER
===================================================== */

.report-container {
    margin: 0 50px;
}


/* =====================================================
   REPORT HEADING
===================================================== */

.report-heading {
    text-align: center;
    font-size: 25px;
    text-decoration: underline;
    margin-top: 35px;
    margin-bottom: 25px;
}


/* =====================================================
   AMC HEADING
===================================================== */

.amc-heading {
    text-align: center;
    font-size: 25px;
    text-decoration: underline;
    margin-top: 35px;
    margin-bottom: 25px;
}


/* =====================================================
   PENDING HEADING
===================================================== */

.pending-heading {
    text-align: center;
    font-size: 25px;
    text-decoration: underline;
    margin-top: 35px;
    margin-bottom: 25px;
}


/* =====================================================
   TABLE
===================================================== */

.service-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-bottom: 30px;
}

.service-table th,
.service-table td {
    border: 1px solid #000;
    padding: 8px;
    text-align: center;
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
    font-size: 14px;
}

.service-table th {
    background-color: #99dea5;
    font-weight: bold;
}


/* =====================================================
   COLUMN WIDTH
===================================================== */

.service-table th:nth-child(1),
.service-table td:nth-child(1) {
    width: 7%;
}

.service-table th:nth-child(2),
.service-table td:nth-child(2) {
    width: 15%;
}

.service-table th:nth-child(3),
.service-table td:nth-child(3) {
    width: 35%;
}

.service-table th:nth-child(4),
.service-table td:nth-child(4) {
    width: 18%;
}

.service-table th:nth-child(5),
.service-table td:nth-child(5) {
    width: 17%;
}

.service-table th:nth-child(6),
.service-table td:nth-child(6) {
    width: 8%;
}


/* =====================================================
   NO RECORD
===================================================== */

.no-record {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    padding: 20px;
    border: 1px solid #000;
}


/* =====================================================
   SIGNATURE
===================================================== */

.signature-align {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    justify-content: flex-start;
    margin: 55px 50px 40px 50px;
    font-size: 18px;
}

.sig-column {
    width: 300px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}


/* COMPANY NAME */

.company-name {
    font-weight: bold;
    margin: 0 0 -8px 0;
    padding: 0;
    white-space: nowrap;
    line-height: 1.2;
}


/* STAMP */

.company-stamp {
    height: 129px;
    width: auto;
    display: block;
    object-fit: contain;
    margin: 0 0 -25px 10px;
    padding: 0;
}


/* AUTHORIZED */

.authorized {
    font-weight: bold;
    margin: 0;
    padding: 0;
    white-space: nowrap;
    line-height: 1.2;
}


/* =====================================================
   FOOTER
===================================================== */

footer {
    padding: 15px 0;
    margin-top: 20px;
}

.footer {
    padding: 0 50px;
}

.footer-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 15px;
}

.footer-top p {
    margin: 0;
}

.footer-top a {
    color: #000;
    text-decoration: none;
}

.footer-address {
    text-align: center;
    font-size: 15px;
}

.footer-address p {
    margin: 8px 0;
}

.footer-cin {
    text-align: center;
    font-size: 15px;
}

.footer-cin p {
    margin: 8px 0;
}


/* =====================================================
   PRINT
===================================================== */

@media print {

    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    body {
        margin: 0;
        padding: 0;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        zoom: 75%;
    }

    .service-table {
        page-break-inside: auto;
    }

    .service-table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    .report-section {
        page-break-inside: auto;
    }

    .signature-align {
        display: flex !important;
        flex-direction: row !important;
        align-items: flex-start !important;
        justify-content: flex-start !important;
        margin: 45px 50px 30px 50px !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    .sig-column {
        width: 300px !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
    }

    .company-name {
        display: block !important;
        margin: 0 0 -8px 0 !important;
        padding: 0 !important;
        white-space: nowrap !important;
    }

    .company-stamp {
        height: 129px !important;
        width: auto !important;
        display: block !important;
        margin: 0 0 -25px 10px !important;
    }

    .authorized {
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        white-space: nowrap !important;
    }

    footer {
        page-break-inside: avoid;
        break-inside: avoid;
    }

}

</style>

</head>


<body onload="window.print()">


<!-- =====================================================
     LOGO
===================================================== -->

<img
    src="img/logo_new.jpg"
    alt="Company Logo"
    class="logo"
>


<hr class="green-line">


<!-- =====================================================
     LICENSE
===================================================== -->

<h2 class="license">
    License No.: LAID2701060
</h2>


<!-- =====================================================
     REF / DATE
===================================================== -->

<div class="ref-date">

    <p>
        Ref: Shul/Pest/Service/25-26
    </p>

    <p>
        Date: <?= htmlspecialchars($report_date); ?>
    </p>

</div>


<!-- =====================================================
     CLIENT
===================================================== -->

<div class="client-info">

    <strong>To,</strong>

    <br>

    <strong>
        <?= htmlspecialchars($client_name); ?>
    </strong>

    <br>

    <?= nl2br(
        htmlspecialchars($client_address)
    ); ?>

</div>


<!-- =====================================================
     1. COMPLETED SERVICE TRACK
     YE SECTION TABHI DIKHEGA JAB COMPLETED RECORD HOGA
===================================================== -->

<?php if (!empty($service_tracks)): ?>

<div class="report-container report-section">

    <h1 class="report-heading">
        Service Track Report
    </h1>

    <table class="service-table">

        <thead>

            <tr>

                <th>Sr. No.</th>

                <th>Service Date</th>

                <th>Description</th>

                <th>Operator Name</th>

                <th>Monitored By</th>

                <th>Status</th>

            </tr>

        </thead>

        <tbody>

        <?php $sr_no = 1; ?>

        <?php foreach ($service_tracks as $service): ?>

            <tr>

                <td>
                    <?= $sr_no++; ?>
                </td>

                <td>
                    <?= formatServiceDate(
                        $service['service_date'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $service['description'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $service['operator_name'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $service['monitored_by'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $service['status'] ?? ''
                    ); ?>
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php endif; ?>


<!-- =====================================================
     2. AMC SERVICE TRACK
     YE SECTION TABHI DIKHEGA JAB AMC RECORD HOGA
===================================================== -->

<?php if (!empty($amc_tracks)): ?>

<div class="report-container report-section">

    <h1 class="amc-heading">
        AMC Service Track Report
    </h1>

    <table class="service-table">

        <thead>

            <tr>

                <th>Sr. No.</th>

                <th>Service Date</th>

                <th>Description</th>

                <th>Operator Name</th>

                <th>Monitored By</th>

                <th>Status</th>

            </tr>

        </thead>

        <tbody>

        <?php $amc_sr_no = 1; ?>

        <?php foreach ($amc_tracks as $amc): ?>

            <?php

            // AMC me description nahi hai to reason use karega
            $amc_description = '';

            if (!empty($amc['description'])) {

                $amc_description = $amc['description'];

            } elseif (!empty($amc['reason'])) {

                $amc_description = $amc['reason'];

            }

            ?>

            <tr>

                <td>
                    <?= $amc_sr_no++; ?>
                </td>

                <td>
                    <?= formatServiceDate(
                        $amc['service_date'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $amc_description
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $amc['operator_name'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $amc['monitored_by'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $amc['status'] ?? ''
                    ); ?>
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php endif; ?>


<!-- =====================================================
     3. PENDING SERVICE TRACK
     IMPORTANT:
     AGAR PENDING RECORD NAHI HAI TO KUCH BHI NA DIKHE
===================================================== -->

<?php if (!empty($pending_tracks)): ?>

<div class="report-container report-section">

    <h1 class="pending-heading">
        Pending Service Track Report
    </h1>

    <table class="service-table">

        <thead>

            <tr>

                <th>Sr. No.</th>

                <th>Service Date</th>

                <th>Description</th>

                <th>Operator Name</th>

                <th>Monitored By</th>

                <th>Status</th>

            </tr>

        </thead>

        <tbody>

        <?php $pending_sr_no = 1; ?>

        <?php foreach ($pending_tracks as $pending): ?>

            <tr>

                <td>
                    <?= $pending_sr_no++; ?>
                </td>

                <td>
                    <?= formatServiceDate(
                        $pending['service_date'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $pending['description'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $pending['operator_name'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $pending['monitored_by'] ?? ''
                    ); ?>
                </td>

                <td>
                    <?= htmlspecialchars(
                        $pending['status'] ?? ''
                    ); ?>
                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php endif; ?>


<!-- =====================================================
     SIGNATURE
===================================================== -->

<div class="signature-align">

    <div class="sig-column">

        <div class="company-name">
            For Shul Ventures Pvt. Ltd.
        </div>

        <img
            src="img/stamp1.png"
            alt="Company Stamp"
            class="company-stamp"
        >

        <div class="authorized">
            Authorized Signatory
        </div>

    </div>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <hr class="green-line">

    <div class="footer">

        <div class="footer-top">

            <p>
                +91-9764385996
            </p>

            <a href="https://shulventures.com/">
                www.shulventures.com
            </a>

            <p>
                pestcontrol@shulventures.com
            </p>

        </div>

        <div class="footer-address">

            <p>
                Shree Ganesh Apartment,
                Ring Road, Plot No. 32,
                above Rajat Wines,
                Khamla, Nagpur - 440015
            </p>

        </div>

        <div class="footer-cin">

            <p>
                CIN : U74999MH2018PTC308404
            </p>

        </div>

    </div>

    <hr class="green-line">

</footer>


</body>

</html>
