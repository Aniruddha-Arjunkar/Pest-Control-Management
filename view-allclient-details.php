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

include 'header.php';
require_once 'db_connect.php';


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function h($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}


function formatTermsAndConditions($terms)
{
    if (empty($terms)) {
        return '<span class="empty-value">No terms available</span>';
    }

    $points = preg_split(
        '/\r\n|\r|\n|\d+\.\s*/',
        $terms,
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    $formatted = '<ol>';

    foreach ($points as $point) {

        $point = trim($point);

        if ($point !== '') {
            $formatted .= '<li>' . h($point) . '</li>';
        }
    }

    $formatted .= '</ol>';

    return $formatted;
}


function formatAddress($address)
{
    if (empty($address)) {
        return '<span class="empty-value">No address available</span>';
    }

    $addressArray = preg_split('/[\n;]+/', $address);

    $formattedAddress = '';

    foreach ($addressArray as $line) {

        $line = trim($line);

        if ($line !== '') {
            $formattedAddress .= h($line) . '<br>';
        }
    }

    return preg_replace('/<br>$/', '', $formattedAddress);
}


function displayValue($value, $default = '-')
{
    $value = trim((string)($value ?? ''));

    if ($value === '') {
        return '<span class="empty-value">' . h($default) . '</span>';
    }

    return h($value);
}


function getStatusClass($status)
{
    $status = strtolower(trim((string)$status));

    if ($status === 'completed') {
        return 'completed';
    }

    if ($status === 'pending') {
        return 'pending';
    }

    if ($status === 'defaulter') {
        return 'defaulter';
    }

    return 'other';
}


/* =========================================================
   CLIENT ID
========================================================= */

$client_id = isset($_GET['client_id'])
    ? intval($_GET['client_id'])
    : 0;


/* =========================================================
   FETCH CLIENT
========================================================= */

$sql_client = "
    SELECT *
    FROM tbl_client
    WHERE Id = ?
";

$stmt_client = $conn->prepare($sql_client);

if (!$stmt_client) {
    die("Database error: " . $conn->error);
}

$stmt_client->bind_param("i", $client_id);
$stmt_client->execute();

$result_client = $stmt_client->get_result();


if ($result_client->num_rows <= 0) {

    echo '
        <div style="padding:30px;">
            Client not found.
        </div>
    ';

    exit();
}


$client = $result_client->fetch_assoc();


/* =========================================================
   GST
========================================================= */

$show_gst_bill =
    (
        (float)($client["cgst"] ?? 0) > 0 &&
        (float)($client["sgst"] ?? 0) > 0
    );


/* =========================================================
   PAYMENTS
========================================================= */

$sql_payments = "
    SELECT
        paid_amount,
        payment_date,
        tds_amount,
        payment_gateway
    FROM tbl_payment
    WHERE client_Id = ?
    ORDER BY payment_date ASC
";

$stmt_payments = $conn->prepare($sql_payments);

if (!$stmt_payments) {
    die("Database error: " . $conn->error);
}

$stmt_payments->bind_param("i", $client_id);
$stmt_payments->execute();

$result_payments = $stmt_payments->get_result();


/* =========================================================
   PAYMENT TOTAL
========================================================= */

$total_paid = 0;
$total_tds = 0;


while ($payment = $result_payments->fetch_assoc()) {

    $paid_amount =
        (float)($payment["paid_amount"] ?? 0);

    $tds_amount =
        (float)($payment["tds_amount"] ?? 0);

    if ($paid_amount != 0) {

        $total_paid += $paid_amount;
        $total_tds += $tds_amount;
    }
}


/* =========================================================
   TOTAL SERVICE CHARGES
========================================================= */

$total_service_charges = $show_gst_bill

    ? (float)$client["total_amount"]
        + (float)$client["cgst"]
        + (float)$client["sgst"]

    : (float)$client["total_amount"];


$balance =
    $total_service_charges - $total_paid;


$is_payment_completed =
    ($balance <= 0);


/* =========================================================
   SERVICES STATUS
   PENDING + COMPLETED
========================================================= */

$sql_service_status = "
    SELECT
        SUM(
            LOWER(TRIM(status)) = 'completed'
        ) AS completed_count,

        SUM(
            LOWER(TRIM(status)) = 'pending'
        ) AS pending_count
    FROM tbl_pending_services
    WHERE client_Id = ?
";

$stmt_service_status =
    $conn->prepare($sql_service_status);

$stmt_service_status->bind_param(
    "i",
    $client_id
);

$stmt_service_status->execute();

$result_service_status =
    $stmt_service_status->get_result();

$row_service_status =
    $result_service_status->fetch_assoc();


$has_completed_service =
    (
        (int)(
            $row_service_status['completed_count']
            ?? 0
        ) > 0
    );


$has_pending_service =
    (
        (int)(
            $row_service_status['pending_count']
            ?? 0
        ) > 0
    );


$has_service_track =
    (
        $has_completed_service ||
        $has_pending_service
    );


/* =========================================================
   FETCH PENDING + COMPLETED SERVICES
========================================================= */

$result_services = null;

if ($has_service_track) {

    $sql_services = "
        SELECT
            operator_name,
            service_date,
            description,
            monitored_by,
            status
        FROM tbl_pending_services
        WHERE client_Id = ?

        AND LOWER(TRIM(status))
            IN ('pending', 'completed')

        ORDER BY service_date ASC
    ";

    $stmt_services =
        $conn->prepare($sql_services);

    if (!$stmt_services) {
        die("Database error: " . $conn->error);
    }

    $stmt_services->bind_param(
        "i",
        $client_id
    );

    $stmt_services->execute();

    $result_services =
        $stmt_services->get_result();
}


/* =========================================================
   AMC STATUS
   PENDING + COMPLETED
========================================================= */

$sql_amc_status = "
    SELECT
        SUM(
            LOWER(TRIM(status)) = 'completed'
        ) AS completed_count,

        SUM(
            LOWER(TRIM(status)) = 'pending'
        ) AS pending_count
    FROM tbl_amc
    WHERE client_Id = ?
";

$stmt_amc_status =
    $conn->prepare($sql_amc_status);

$stmt_amc_status->bind_param(
    "i",
    $client_id
);

$stmt_amc_status->execute();

$result_amc_status =
    $stmt_amc_status->get_result();

$row_amc_status =
    $result_amc_status->fetch_assoc();


$has_completed_amc =
    (
        (int)(
            $row_amc_status['completed_count']
            ?? 0
        ) > 0
    );


$has_pending_amc =
    (
        (int)(
            $row_amc_status['pending_count']
            ?? 0
        ) > 0
    );


$has_amc_track =
    (
        $has_completed_amc ||
        $has_pending_amc
    );


/* =========================================================
   FETCH PENDING + COMPLETED AMC
========================================================= */

$result_amc = null;

if ($has_amc_track) {

    $sql_amc = "
        SELECT *
        FROM tbl_amc
        WHERE client_Id = ?

        AND LOWER(TRIM(status))
            IN ('pending', 'completed')

        ORDER BY service_date ASC
    ";

    $stmt_amc =
        $conn->prepare($sql_amc);

    if (!$stmt_amc) {
        die("Database error: " . $conn->error);
    }

    $stmt_amc->bind_param(
        "i",
        $client_id
    );

    $stmt_amc->execute();

    $result_amc =
        $stmt_amc->get_result();
}


/* =========================================================
   RESOLVED COMPLAINTS
========================================================= */

$sql_resolved_complaints = "
    SELECT *
    FROM tbl_resolved_complaints
    WHERE client_Id = ?
    ORDER BY resolve_date ASC
";

$stmt_resolved_complaints =
    $conn->prepare($sql_resolved_complaints);

$stmt_resolved_complaints->bind_param(
    "i",
    $client_id
);

$stmt_resolved_complaints->execute();

$result_resolved_complaints =
    $stmt_resolved_complaints->get_result();


$has_resolved_complaints =
    ($result_resolved_complaints->num_rows > 0);


/* =========================================================
   LATEST SERVICE / PENDING BUTTON
========================================================= */

$sql_last_service = "
    SELECT status
    FROM tbl_pending_services
    WHERE client_Id = ?
    ORDER BY service_date DESC
    LIMIT 1
";

$stmt_last_service =
    $conn->prepare($sql_last_service);

$stmt_last_service->bind_param(
    "i",
    $client_id
);

$stmt_last_service->execute();

$result_last_service =
    $stmt_last_service->get_result();


$can_add_pending = false;
$pending_message = '';


if ($result_last_service->num_rows > 0) {

    $latest_service =
        $result_last_service->fetch_assoc();


    if (
        strtolower(
            trim($latest_service['status'])
        ) === 'pending'
    ) {

        $pending_message =
            "A pending service already exists for this client.";

    } else {

        $sql_check_all = "
            SELECT
                COUNT(*) AS total,

                SUM(
                    LOWER(TRIM(status))
                    = 'completed'
                ) AS completed

            FROM tbl_pending_services

            WHERE client_Id = ?
        ";

        $stmt_check_all =
            $conn->prepare($sql_check_all);

        $stmt_check_all->bind_param(
            "i",
            $client_id
        );

        $stmt_check_all->execute();

        $result_check_all =
            $stmt_check_all->get_result();

        $row =
            $result_check_all->fetch_assoc();


        $total_services =
            (int)($row['total'] ?? 0);

        $completed_services =
            (int)($row['completed'] ?? 0);


        if (
            $total_services > 0 &&
            $total_services == $completed_services
        ) {

            $pending_message =
                "All services are completed. Cannot add more.";

        } else {

            $can_add_pending = true;
        }
    }

} else {

    $can_add_pending = true;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Client Details</title>

<style>
/* =========================================================
   CERTIFICATE MODAL
========================================================= */

#certificateModal {
    display: none;
    position: fixed;
    z-index: 99999;
    inset: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.55);
    overflow-y: auto;
    padding: 30px 15px;
    box-sizing: border-box;
}

#certificateModal.active {
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

#certificateModal .modal-dialog {
    width: 100%;
    max-width: 500px;
    margin: 50px auto 0;
}

#certificateModal .modal-content {
    position: relative;
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #dce1ed;
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.20);
    overflow: hidden;
}

#certificateModal .modal-header {
    padding: 12px 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(90deg, #536fe5, #6989f3);
    color: #ffffff;
    border-bottom: none;
}

#certificateModal .modal-title {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    font-size: 18px;
    font-weight: 600;
}

#certificateModal .close {
    margin: 0;
    padding: 0;
    border: none;
    background: transparent;
    color: #ffffff;
    font-size: 28px;
    font-weight: 400;
    line-height: 1;
    cursor: pointer;
    opacity: 0.9;
}

#certificateModal .close:hover {
    opacity: 1;
    transform: none;
}

#certificateModal .modal-body {
    padding: 18px 15px;
    background: #ffffff;
}

#certificateModal .form-group {
    margin-bottom: 12px;
}

#certificateModal label {
    display: block;
    margin-bottom: 5px;
    color: #39465d;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 600;
}

#certificateModal .form-control {
    width: 100%;
    min-height: 38px;
    padding: 7px 10px;
    box-sizing: border-box;
    border: 1px solid #cfd6e4;
    border-radius: 5px;
    background: #ffffff;
    color: #263650;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    outline: none;
}

#certificateModal .form-control:focus {
    border-color: #536fe5;
    box-shadow: 0 0 0 2px rgba(83, 111, 229, 0.12);
}

#certificateModal .text-muted {
    display: block;
    margin-top: 5px;
    color: #8993a5;
    font-size: 11px;
    line-height: 1.4;
}

#certificateModal .modal-footer {
    padding: 10px 15px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 5px;
    background: #f8f9fd;
    border-top: 1px solid #edf0f6;
}

#certificateModal .modal-footer .btn {
    min-height: 30px;
    padding: 6px 12px !important;
    background: #43b982 !important;
    font-size: 12px !important;
}


/* =========================================================
   MOBILE MODAL
========================================================= */

@media (max-width: 700px) {

    #certificateModal {
        padding: 15px 8px;
    }

    #certificateModal .modal-dialog {
        max-width: 100%;
        margin: 25px auto 0;
    }

    #certificateModal .modal-header {
        padding: 9px 10px;
    }

    #certificateModal .modal-title {
        font-size: 13px;
    }

    #certificateModal .close {
        font-size: 23px;
    }

    #certificateModal .modal-body {
        padding: 12px 10px;
    }

    #certificateModal label {
        font-size: 9px;
    }

    #certificateModal .form-control {
        min-height: 32px;
        padding: 5px 7px;
        font-size: 9px;
    }

    #certificateModal .text-muted {
        font-size: 7px;
    }

    #certificateModal .modal-footer {
        padding: 8px 10px;
    }

    #certificateModal .modal-footer .btn {
        min-height: 25px;
        padding: 5px 8px !important;
        font-size: 8px !important;
    }
}
/* =========================================================
   FONT
========================================================= */

@import url(
'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap'
);


/* =========================================================
   MAIN
========================================================= */

#main-content {
    min-height: 100vh;
    background: #edf0f8;
}


.wrapper {
    width: 95%;
  
    margin: 0 auto;
    padding: 12px;
}


.table-agile-info {
    overflow: visible !important;
}


/* =========================================================
   PANEL
========================================================= */

.panel {
    width: 100%;
    background: #ffffff;
    border: 1px solid #dce1ed;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 10px;
    box-shadow:
        0 3px 10px rgba(
            50,
            65,
            100,
            0.10
        );
}


/* =========================================================
   PANEL HEADER
========================================================= */

.panel-heading {
    position: relative;
    min-height: 32px;
    padding: 7px 10px;
    display: flex;
    align-items: center;
    color: #ffffff;
    font-size: 25px;
    font-weight: 600;
    line-height: 1.2;
    overflow: hidden;
}


.panel-heading::after {
    content: "";
    position: absolute;
    width: 65px;
    height: 65px;
    right: -20px;
    top: -30px;
    border-radius: 50%;
    background: rgba(
        255,
        255,
        255,
        0.08
    );
}


.customer-heading,
.payment-heading,
.report-heading,
.complaint-heading,
.action-heading {
    background:
        linear-gradient(
            90deg,
            #536fe5,
            #6989f3
        );
}


.service-heading,
.gst-heading {
    background:
        linear-gradient(
            90deg,
            #9750d5,
            #a863df
        );
}


/* =========================================================
   PANEL BODY
========================================================= */

.panel-body {
    padding: 7px;
    background: #ffffff;
}


/* =========================================================
   DETAIL GRID
========================================================= */

.detail-grid {
    display: grid;
    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );
    gap: 5px;
    width: 100%;
}


.detail-box {
    min-width: 0;
    min-height: 42px;
    padding: 6px 7px;
    background: #f8f9fd;
    border: 1px solid #d8deeb;
    border-radius: 5px;
    transition: .15s ease;
}


.detail-box:hover {
    border-color: #bcc7dc;
    background: #f5f7fc;
}


.detail-box.full {
    grid-column: 1 / -1;
}


/* =========================================================
   LABEL
========================================================= */

.detail-label {
    display: block;
    margin-bottom: 3px;
   
    font-size: 14px;
    font-weight: 600;
    line-height: 1.1;
    text-transform: uppercase;
    letter-spacing: .2px;
    color: black;
}


/* =========================================================
   VALUE
========================================================= */

.detail-value {
    display: block;
    color: #263650;
    font-size: 12.5px;
    font-weight: 500;
    line-height: 1.35;
    word-break: break-word;
    overflow-wrap: anywhere;
}


.empty-value {
    color: #9ca6b8;
    font-style: italic;
}


/* =========================================================
   ADDRESS
========================================================= */

.address-box {
    min-height: 42px;
}


/* =========================================================
   TERMS
========================================================= */

.terms-box {
    min-height: 65px;
    background: #fafbfe;
}


.terms-box ol {
    margin: 0;
    padding-left: 18px;
}


.terms-box li {
    color: #46536a;
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: 2px;
}


/* =========================================================
   PAYMENT SUMMARY
========================================================= */

.payment-summary {
    display: grid;
    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );
    gap: 5px;
    margin-bottom: 6px;
}


.payment-box {
    min-height: 45px;
    padding: 7px 8px;
    border-radius: 5px;
    border: 1px solid #cfd8e8;
    background: #f7f9fc;
}


.payment-box.total {
    background: #eaf8ef;
    border-color: #b9dfc5;
}


.payment-box.balance {
    background: #eef9f2;
    border-color: #c4e4cd;
}


.payment-label {
    display: block;
    margin-bottom: 3px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #56705e;
}


.payment-value {
    display: block;
    color: #244e35;
    font-size: 14px;
    font-weight: 600;
}


/* =========================================================
   TABLE WRAPPER
========================================================= */

.data-table-wrapper,
.service-table-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid #d5dce8;
    border-radius: 6px;
    background: #ffffff;
}


/* =========================================================
   TABLE
========================================================= */

.data-table,
.service-track-table {
    width: 100%;
    min-width: 600px;
    border-collapse: collapse;
    table-layout: auto;
}


/* =========================================================
   TABLE HEADER
========================================================= */

.data-table thead th,
.service-track-table thead th {
    padding: 8px 8px;
    background: #303d58;
    color: #ffffff;
    border-right: 1px solid #56627a;
    border-bottom: 2px solid #253149;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.2;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}


.data-table thead th:last-child,
.service-track-table thead th:last-child {
    border-right: none;
}


/* =========================================================
   TABLE BODY
========================================================= */

.data-table tbody td,
.service-track-table tbody td {
    padding: 8px 8px;
    background: #ffffff;
    color: #39465d;
    border-right: 1px solid #e1e5ed;
    border-bottom: 1px solid #e1e5ed;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
    vertical-align: middle;
}


.data-table tbody td:last-child,
.service-track-table tbody td:last-child {
    border-right: none;
}


.data-table tbody tr:nth-child(even) td,
.service-track-table tbody tr:nth-child(even) td {
    background: #f7f9fc;
}


.data-table tbody tr:hover td,
.service-track-table tbody tr:hover td {
    background: #eef3ff;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.description-cell {
    min-width: 180px;
    max-width: 320px;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
}


/* =========================================================
   STATUS
========================================================= */

.service-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 65px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
}


.service-status.completed {
    color: #147a45;
    background: #dff6e8;
    border: 1px solid #b9e7ca;
}


.service-status.pending {
    color: #9a6100;
    background: #fff1d6;
    border: 1px solid #f2d49c;
}


.service-status.defaulter {
    color: #b42318;
    background: #ffe4e1;
    border: 1px solid #f2b8b2;
}


.service-status.other {
    color: #526078;
    background: #edf0f5;
    border: 1px solid #d7dce5;
}


/* =========================================================
   NO DATA
========================================================= */

.no-data {
    padding: 14px !important;
    text-align: center !important;
    color: #8993a5 !important;
    font-size: 13px !important;
    background: #ffffff !important;
}


/* =========================================================
   GST
========================================================= */

.gst-grid {
    display: grid;
    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );
    gap: 5px;
}


.gst-box {
    min-height: 42px;
    padding: 6px 7px;
    background: #faf8ff;
    border: 1px solid #ddd4ed;
    border-radius: 5px;
}


.gst-label {
    display: block;
    margin-bottom: 3px;
    color: #746287;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}


.gst-value {
    display: block;
    color: #382c49;
    font-size: 12px;
    font-weight: 500;
    word-break: break-word;
}


/* =========================================================
   ACTION
========================================================= */

.panel-footer {
    padding: 7px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    gap: 5px;
    background: #ffffff;
    border-top: 1px solid #edf0f6;
}


#actionButtons {
    display: none;
    width: 100%;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 5px;
    margin: 0 !important;
}


.btn {
    min-height: 26px;
    padding: 5px 10px !important;
    border: none;
    border-radius: 5px;
    color: #ffffff !important;
    font-family: 'Poppins', sans-serif;
    font-size: 13px !important;
    font-weight: 600;
    line-height: 1.2;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
    white-space: nowrap;
    cursor: pointer;
    transition: .15s ease;
}


.btn:hover {
    opacity: .9;
    transform: translateY(-1px);
}


#actionButtons a[href*="print.php"] {
    background: #2864d7 !important;
}


#actionButtons a[href*="update-client"] {
    background: #9435d9 !important;
}


#actionButtons a[href*="add-complaint"] {
    background: #df2525 !important;
}


#actionButtons a[href*="add-amc-service"] {
    background: #f59b00 !important;
}


#actionButtons #addPendingService {
    background: #43b982 !important;
}


#actionButtons a[href*="send-certificate"] {
    background: #129a8d !important;
}


#actionButtons a[href*="send-service-track-report"] {
    background: #079b46 !important;
}


.no-focus-outline {
    margin: 0 !important;
    background: #1557d6 !important;
    min-height: 26px;
    padding: 5px 10px !important;
    border-radius: 5px;
    outline: none !important;
}


/* =========================================================
   SCROLLBAR
========================================================= */

::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}


::-webkit-scrollbar-track {
    background: #edf0f8;
}


::-webkit-scrollbar-thumb {
    background: #b8c1d3;
    border-radius: 10px;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .wrapper {
        padding: 6px;
    }

    .panel {
        margin-bottom: 7px;
        border-radius: 7px;
    }

    .panel-heading {
        min-height: 29px;
        padding: 6px 8px;
        font-size: 12px;
    }

    .panel-body {
        padding: 5px;
    }

    .detail-grid {
        grid-template-columns:
            repeat(
                3,
                minmax(0, 1fr)
            );
        gap: 3px;
    }

    .detail-box {
        min-height: 36px;
        padding: 5px;
    }

    .detail-label {
        font-size: 6px;
        margin-bottom: 2px;
    }

    .detail-value {
        font-size: 8px;
        line-height: 1.3;
    }

    .terms-box {
        min-height: 55px;
    }

    .terms-box li {
        font-size: 7px;
    }

    .gst-grid {
        grid-template-columns:
            repeat(
                3,
                minmax(0, 1fr)
            );
        gap: 3px;
    }

    .gst-box {
        padding: 5px;
    }

    .gst-label {
        font-size: 6px;
    }

    .gst-value {
        font-size: 7px;
    }

    .payment-summary {
        gap: 3px;
    }

    .payment-box {
        padding: 6px;
        min-height: 39px;
    }

    .payment-label {
        font-size: 6px;
    }

    .payment-value {
        font-size: 8px;
    }

    .data-table,
    .service-track-table {
        min-width: 650px;
    }

    .data-table thead th,
    .service-track-table thead th {
        padding: 7px 6px;
        font-size: 8px;
    }

    .data-table tbody td,
    .service-track-table tbody td {
        padding: 7px 6px;
        font-size: 8px;
    }

    .service-status {
        min-width: 55px;
        padding: 4px 6px;
        font-size: 7px;
    }

    .panel-footer {
        padding: 5px;
        gap: 4px;
    }

    #actionButtons {
        gap: 3px;
    }

    .btn {
        min-height: 23px;
        padding: 4px 7px !important;
        font-size: 8px !important;
    }
}


/* =========================================================
   EXTRA SMALL
========================================================= */

@media (max-width: 400px) {

    .wrapper {
        padding: 4px;
    }

    .panel-heading {
        min-height: 27px;
        padding: 5px 7px;
        font-size: 9px;
    }

    .panel-body {
        padding: 4px;
    }

    .detail-grid {
        gap: 2px;
    }

    .detail-box {
        min-height: 34px;
        padding: 4px;
    }

    .detail-label {
        font-size: 5.5px;
    }

    .detail-value {
        font-size: 7px;
    }

    .terms-box li {
        font-size: 6.5px;
    }

    .btn {
        font-size: 7px !important;
        padding: 4px 6px !important;
    }
}

</style>

</head>


<body>

<section id="main-content">

<section class="wrapper">

<div class="table-agile-info">

<div style="margin-bottom:10px; text-align:right;">
    <button
        type="button"
        class="btn btn-primary"
        onclick="history.back();"
    >
        ← Back
    </button>
</div>



<!-- =====================================================
     CUSTOMER DETAILS
===================================================== -->

<div class="panel">

    <div class="panel-heading customer-heading">
        👤 Customer Details
    </div>

    <div class="panel-body">

        <div class="detail-grid">

            <div class="detail-box">

                <span class="detail-label">
                    Name
                </span>

                <span class="detail-value">
                    <?= displayValue($client["cust_name"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Phone
                </span>

                <span class="detail-value">
                    <?= displayValue($client["cust_phone"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Email
                </span>

                <span class="detail-value">
                    <?= displayValue($client["cust_email"]); ?>
                </span>

            </div>


            <div class="detail-box full address-box">

                <span class="detail-label">
                    Address
                </span>

                <span class="detail-value">
                    <?= formatAddress($client["cust_address"]); ?>
                </span>

            </div>

        </div>

    </div>

</div>
<br>

<!-- =====================================================
     SERVICE DETAILS
===================================================== -->

<div class="panel">

    <div class="panel-heading service-heading">
        🛠 Service Details
    </div>

    <div class="panel-body">

        <div class="detail-grid">

            <div class="detail-box">

                <span class="detail-label">
                    Invoice No
                </span>

                <span class="detail-value">
                    <?= displayValue($client["invoice_no"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Branch
                </span>

                <span class="detail-value">
                    <?= displayValue($client["branch"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Service
                </span>

                <span class="detail-value">
                    <?= displayValue($client["services"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Warranty
                </span>

                <span class="detail-value">
                    <?= displayValue($client["warranty"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Pest Covered
                </span>

                <span class="detail-value">
                    <?= displayValue($client["pest_covered"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Service Date
                </span>

                <span class="detail-value">
                    <?= displayValue($client["due_date"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Source
                </span>

                <span class="detail-value">
                    <?= displayValue($client["source"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Operator
                </span>

                <span class="detail-value">
                    <?= displayValue($client["operator_name"]); ?>
                </span>

            </div>


            <div class="detail-box">

                <span class="detail-label">
                    Service Charges
                </span>

                <span class="detail-value">

                    ₹ <?= h(
                        number_format(
                            $total_service_charges,
                            2
                        )
                    ); ?>

                    <?php if ($show_gst_bill): ?>

                        <small style="color:#68748a;">
                            (Incl. GST)
                        </small>

                    <?php endif; ?>

                </span>

            </div>


            <div class="detail-box full terms-box">

                <span class="detail-label">
                    Service Terms and Conditions
                </span>

                <?= formatTermsAndConditions(
                    $client["other_information"]
                ); ?>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     SERVICES TRACK REPORT
     PENDING + COMPLETED
===================================================== -->

<?php if ($has_service_track): ?>

<div class="panel">

    <div class="panel-heading report-heading">
        📋 Services Track Report
    </div>

    <div class="panel-body">

        <div class="service-table-wrapper">

            <table class="service-track-table">

                <thead>

                    <tr>

                        <th>
                            Service Date
                        </th>

                        <th>
                            Operator
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Monitored By
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php while (
                    $service =
                    $result_services->fetch_assoc()
                ): ?>

                    <?php
                    $service_status =
                        strtolower(
                            trim(
                                $service["status"] ?? ''
                            )
                        );

                    $service_status_class =
                        getStatusClass(
                            $service_status
                        );
                    ?>

                    <tr>

                        <td>
                            <?= h(
                                $service["service_date"]
                            ); ?>
                        </td>


                        <td>
                            <?= h(
                                $service["operator_name"]
                            ); ?>
                        </td>


                        <td class="description-cell">

                            <?= h(
                                $service["description"]
                            ); ?>

                        </td>


                        <td>
                            <?= h(
                                $service["monitored_by"]
                            ); ?>
                        </td>


                        <td>

                            <span class="service-status
                                <?= h(
                                    $service_status_class
                                ); ?>">

                                <?= h(
                                    ucfirst(
                                        $service_status
                                    )
                                ); ?>

                            </span>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =====================================================
     AMC TRACK REPORT
     PENDING + COMPLETED
===================================================== -->

<?php if ($has_amc_track): ?>

<div class="panel">

    <div class="panel-heading report-heading">
        📋 AMC Services Track Report
    </div>


    <div class="panel-body">

        <div class="service-table-wrapper">

            <table class="service-track-table">

                <thead>

                    <tr>

                        <th>
                            Service Date
                        </th>

                        <th>
                            Operator
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Monitored By
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php while (
                    $amc =
                    $result_amc->fetch_assoc()
                ): ?>

                    <?php
                    $amc_status =
                        strtolower(
                            trim(
                                $amc["status"] ?? ''
                            )
                        );

                    $amc_status_class =
                        getStatusClass(
                            $amc_status
                        );

                    $amc_description =
                        $amc["description"]
                        ?? $amc["reason"]
                        ?? '';
                    ?>

                    <tr>

                        <td>
                            <?= h(
                                $amc["service_date"]
                            ); ?>
                        </td>


                        <td>
                            <?= h(
                                $amc["operator_name"]
                            ); ?>
                        </td>


                        <td class="description-cell">

                            <?= h(
                                $amc_description
                            ); ?>

                        </td>


                        <td>
                            <?= h(
                                $amc["monitored_by"]
                            ); ?>
                        </td>


                        <td>

                            <span class="service-status
                                <?= h(
                                    $amc_status_class
                                ); ?>">

                                <?= h(
                                    ucfirst(
                                        $amc_status
                                    )
                                ); ?>

                            </span>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- =====================================================
     RESOLVED COMPLAINTS
===================================================== -->

<?php if ($has_resolved_complaints): ?>

<div class="panel">

    <div class="panel-heading complaint-heading">
        🚨 Resolved Complaints
    </div>


    <div class="panel-body">

        <div class="service-table-wrapper">

            <table class="service-track-table">

                <thead>

                    <tr>

                        <th>
                            Service
                        </th>

                        <th>
                            Complaint Date
                        </th>

                        <th>
                            Details
                        </th>

                        <th>
                            Operator
                        </th>

                        <th>
                            Resolve Date
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php while (
                    $complaint =
                    $result_resolved_complaints->fetch_assoc()
                ): ?>

                    <tr>

                        <td>
                            <?= h(
                                $client["services"]
                            ); ?>
                        </td>


                        <td>
                            <?= h(
                                $complaint["complaint_date"]
                            ); ?>
                        </td>


                        <td class="description-cell">

                            <?= h(
                                $complaint["details"]
                            ); ?>

                        </td>


                        <td>
                            <?= h(
                                $complaint["resolver_name"]
                            ); ?>
                        </td>


                        <td>
                            <?= h(
                                $complaint["resolve_date"]
                            ); ?>
                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php endif; ?>
<br>

<!-- =====================================================
     PAYMENT DETAILS
===================================================== -->

<div class="panel">

    <div class="panel-heading payment-heading">
        💳 Payment Details
    </div>


    <div class="panel-body">


        <div class="payment-summary">

            <div class="payment-box total">

                <span class="payment-label">
                    Total Paid
                </span>

                <span class="payment-value">

                    ₹ <?= h(
                        number_format(
                            $total_paid,
                            2
                        )
                    ); ?>

                </span>

            </div>


            <div class="payment-box balance">

                <span class="payment-label">
                    Balance
                </span>

                <span class="payment-value">

                    ₹ <?= h(
                        number_format(
                            max(0, $balance),
                            2
                        )
                    ); ?>

                </span>

            </div>

        </div>


        <?php
        $result_payments->data_seek(0);
        ?>


        <div class="data-table-wrapper">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>
                            Amount
                        </th>

                        <th>
                            Payment Date
                        </th>

                        <th>
                            Gateway
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                $has_real_payment = false;

                while (
                    $payment =
                    $result_payments->fetch_assoc()
                ):

                    $paid_amount =
                        (float)(
                            $payment["paid_amount"]
                            ?? 0
                        );

                    $tds_amount =
                        (float)(
                            $payment["tds_amount"]
                            ?? 0
                        );


                    if ($paid_amount == 0) {
                        continue;
                    }


                    $has_real_payment = true;

                ?>

                    <tr>

                        <td>

                            ₹ <?= h(
                                number_format(
                                    $paid_amount,
                                    2
                                )
                            ); ?>


                            <?php if ($tds_amount > 0): ?>

                                <span style="
                                    color:#6b7486;
                                    font-size:8px;
                                    display:block;
                                ">

                                    TDS:
                                    ₹ <?= h(
                                        number_format(
                                            $tds_amount,
                                            2
                                        )
                                    ); ?>

                                </span>

                            <?php endif; ?>

                        </td>


                        <td>
                            <?= h(
                                $payment["payment_date"]
                            ); ?>
                        </td>


                        <td>
                            <?= h(
                                $payment["payment_gateway"]
                            ); ?>
                        </td>

                    </tr>

                <?php endwhile; ?>


                <?php if (!$has_real_payment): ?>

                    <tr>

                        <td
                            colspan="3"
                            class="no-data"
                        >
                            No payments found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>


        <?php if ($has_real_payment): ?>

            <div
                class="detail-grid"
                style="margin-top:5px;"
            >

                <div class="detail-box">

                    <span class="detail-label">
                        Total Paid
                    </span>

                    <span class="detail-value">

                        ₹ <?= h(
                            number_format(
                                $total_paid,
                                2
                            )
                        ); ?>

                    </span>

                </div>


                <div class="detail-box">

                    <span class="detail-label">
                        TDS Amount
                    </span>

                    <span class="detail-value">

                        ₹ <?= h(
                            number_format(
                                $total_tds,
                                2
                            )
                        ); ?>

                    </span>

                </div>


                <div class="detail-box">

                    <span class="detail-label">
                        Balance
                    </span>

                    <span class="detail-value">

                        ₹ <?= h(
                            number_format(
                                max(
                                    0,
                                    $balance
                                ),
                                2
                            )
                        ); ?>

                    </span>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>


<!-- =====================================================
     GST DETAILS
===================================================== -->

<?php if ($show_gst_bill): ?>

<div class="panel">

    <div class="panel-heading gst-heading">
        🧾 GST Bill Details
    </div>


    <div class="panel-body">

        <div class="gst-grid">

            <div class="gst-box">

                <span class="gst-label">
                    GST Number
                </span>

                <span class="gst-value">
                    <?= displayValue(
                        $client["gst_number"]
                    ); ?>
                </span>

            </div>


            <div class="gst-box">

                <span class="gst-label">
                    Basic Amount
                </span>

                <span class="gst-value">

                    ₹ <?= h(
                        number_format(
                            (float)$client["total_amount"],
                            2
                        )
                    ); ?>

                </span>

            </div>


            <div class="gst-box">

                <span class="gst-label">
                    CGST
                </span>

                <span class="gst-value">

                    ₹ <?= h(
                        number_format(
                            (float)$client["cgst"],
                            2
                        )
                    ); ?>

                </span>

            </div>


            <div class="gst-box">

                <span class="gst-label">
                    SGST
                </span>

                <span class="gst-value">

                    ₹ <?= h(
                        number_format(
                            (float)$client["sgst"],
                            2
                        )
                    ); ?>

                </span>

            </div>


            <div class="gst-box">

                <span class="gst-label">
                    Total GST
                </span>

                <span class="gst-value">

                    ₹ <?= h(
                        number_format(
                            (float)$client["cgst"]
                            +
                            (float)$client["sgst"],
                            2
                        )
                    ); ?>

                </span>

            </div>


            <div class="gst-box">

                <span class="gst-label">
                    Total Amount
                </span>

                <span class="gst-value">

                    ₹ <?= h(
                        number_format(
                            $total_service_charges,
                            2
                        )
                    ); ?>

                </span>

            </div>

        </div>

    </div>

</div>

<?php endif; ?>

<br>
<!-- =====================================================
     ACTIONS
===================================================== -->

<div class="panel">

    <div class="panel-footer">

        <!-- ACTION BUTTON -->
        <button
            type="button"
            class="btn btn-primary no-focus-outline"
            onclick="toggleClientActions()"
        >
            ⚡ Actions
        </button>

        <!-- ACTION BUTTONS -->
        <div id="actionButtons">

            <?php if ($show_gst_bill): ?>

                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="handleGstAction()"
                >
                    🖨 Print GST Invoice
                </button>

            <?php else: ?>

                <a
                    href="print.php?client_id=<?= (int)$client['Id']; ?>"
                    class="btn btn-primary"
                    target="_blank"
                >
                    🖨 Print Bill
                </a>

            <?php endif; ?>


            <a
                href="update-client.php?id=<?= (int)$client['Id']; ?>"
                class="btn btn-primary"
            >
                ✏ Update
            </a>


            <a
                href="add-complaint.php?client_id=<?= (int)$client['Id']; ?>"
                class="btn btn-primary"
            >
                ➕ Complaint
            </a>


            <a
                href="add-amc-service.php?client_id=<?= (int)$client['Id']; ?>"
                class="btn btn-primary"
            >
                ➕ AMC
            </a>


           
                <a
                href="add-pending-service.php?client_id=<?= (int)$client['Id']; ?>"class="btn btn-success"
            >
                ➕ Pending Service
                </a>

            <button
                type="button"
                class="btn btn-primary"
                onclick="openCertificateModal(
                    <?= (int)$client['Id']; ?>,
                    '<?= date('Y-m-d', strtotime($client['due_date'])); ?>'
                )"
            >
                📜 Certificate
            </button>


            <a
                href="send-service-track-report.php?client_id=<?= (int)$client['Id']; ?>"
                class="btn btn-success"
                target="_blank"
            >
                📋 Track Report
            </a>

        </div>

    </div>

</div>

</div>

</section>

</section>
<!-- =====================================================
     CERTIFICATE MODAL
===================================================== -->

<div id="certificateModal">
    <div class="modal-dialog">
        <form
            action="send-certificate.php"
            method="GET"
            target="_blank"
        >
            <input
                type="hidden"
                name="client_id"
                id="certificate_client_id"
            >

            <div class="modal-content">

                <div class="modal-header bg-primary text-white">
                    <h4 class="modal-title">
                        Generate Certificate
                    </h4>

                    <button
    type="button"
    class="close"
    onclick="closeCertificateModal()"
>
    &times;
</button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label>Date of Service</label>

                        <input
                            type="date"
                            id="certificate_service_date"
                            name="service_date"
                            class="form-control"
                        >
                    </div>

                    <small class="text-muted">
                        The service date is pre-filled. You may change it before generating the certificate.
                    </small>

                    <div
                        class="form-group"
                        style="margin-top:15px;"
                    >
                        <label>Validity</label>

                        <select
                            name="validity"
                            id="certificate_validity"
                            class="form-control"
                            required
                        >
                            <option value="1 month">1 Month</option>
                            <option value="3 months">3 Months</option>
                            <option value="6 months">6 Months</option>
                            <option value="9 months">9 Months</option>
                            <option value="1 year">1 Year</option>
                            <option value="3 year">3 Years</option>
                            <option value="4 year">4 Years</option>
                            <option value="5 year">5 Years</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        Generate Certificate
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- =====================================================
   JAVASCRIPT
===================================================== -->
<!-- =====================================================
   EXISTING JS
===================================================== -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>

<script src="js/search.js"></script>

<script>

function toggleClientActions()
{
    const buttons = document.getElementById("actionButtons");

    if (!buttons) {
        return;
    }

    const currentDisplay = window.getComputedStyle(buttons).display;

    if (currentDisplay === "none")
    {
        buttons.style.display = "flex";
    }
    else
    {
        buttons.style.display = "none";
    }
}


function openCertificateModal(clientId, serviceDate)
{
    document.getElementById("certificate_client_id").value = clientId;

    document.getElementById("certificate_service_date").value = serviceDate;

    document.getElementById("certificateModal").classList.add("active");
}


function closeCertificateModal()
{
    document.getElementById("certificateModal").classList.remove("active");
}

</script>
<!-- =====================================================
   CERTIFICATE MODAL JS
===================================================== -->




</body>
</html>