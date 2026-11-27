<?php
session_start();

/* =========================================
   LOGIN CHECK
========================================= */
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

/* =========================================
   DATABASE CONNECTION
========================================= */
require_once 'db_connect.php';

mysqli_set_charset($conn, "utf8mb4");

/* =========================================
   REQUEST CHECK
========================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid Request');
}

/* =========================================
   GET INQUIRY ID
========================================= */
$inquiry_id = isset($_POST['inquiry_id'])
    ? (int) $_POST['inquiry_id']
    : 0;

if ($inquiry_id <= 0) {
    die("Invalid Inquiry ID");
}

/* =========================================
   FETCH SOLAR INQUIRY
========================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM tbl_solar_inquiry WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $inquiry_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Solar Inquiry not found");
}

$inq = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

/* =========================================
   BASIC DETAILS
========================================= */
$ref_no         = trim($_POST['ref_no'] ?? '');
$quotation_date = $_POST['quotation_date'] ?? '';

$property_type  = trim($_POST['property_type'] ?? '');
$subject        = trim($_POST['subject'] ?? '');

$plant_capacity_kw = (float)($_POST['plant_capacity_kw'] ?? 0);
$system_type       = trim($_POST['system_type'] ?? '');
$total_amount      = (float)($_POST['total_amount'] ?? 0);

/* =========================================
   AUTO CALCULATIONS
========================================= */
$monthly_generation = (int)($plant_capacity_kw * 120);
$yearly_generation  = $monthly_generation * 12;

$annual_saving = $yearly_generation * 13;

$payback_years = ($annual_saving > 0)
    ? (string)round($total_amount / $annual_saving, 1)
    : '0';

/* =========================================
   COMPONENT DETAILS
========================================= */
$panel_spec    = trim($_POST['panel_spec'] ?? '');
$panel_make    = trim($_POST['panel_make'] ?? '');
$panel_qty     = trim($_POST['panel_qty'] ?? '');

$inverter_spec = trim($_POST['inverter_spec'] ?? '');
$inverter_make = trim($_POST['inverter_make'] ?? '');
$inverter_qty  = trim($_POST['inverter_qty'] ?? '');

$cable_make    = trim($_POST['cable_make'] ?? '');
$mounting_make = trim($_POST['mounting_make'] ?? '');

$dc_jb_make    = trim($_POST['dc_jb_make'] ?? '');
$acdb_make     = trim($_POST['acdb_make'] ?? '');

$earthing_make = trim($_POST['earthing_make'] ?? '');
$earthing_qty  = trim($_POST['earthing_qty'] ?? '');

$net_meter_make = trim($_POST['net_meter_make'] ?? '');
$net_meter_qty  = trim($_POST['net_meter_qty'] ?? '');

/* =========================================
   COMMERCIAL CALCULATIONS
========================================= */
$supply_installation_amount = (float)(
    $_POST['supply_installation_amount'] ?? 0
);

/* =========================================
   GOVERNMENT SUBSIDY
========================================= */
$govt_subsidy_amount = 0;

// Subsidy only for Residential
if (strpos($property_type, 'Residential') !== false) {

    if ($plant_capacity_kw > 0 && $plant_capacity_kw <= 2) {
        $govt_subsidy_amount = 68000;

    } elseif ($plant_capacity_kw > 2) {
        $govt_subsidy_amount = 78000;
    }
}

// Commercial gets no subsidy

/* =========================================
   GST & TOTAL CALCULATIONS
========================================= */
$gst_5_amount = round(
    $supply_installation_amount * 0.05,
    2
);

$commercial_total = round(
    $supply_installation_amount + $gst_5_amount,
    2
);

$actual_project_cost = round(
    $commercial_total - $govt_subsidy_amount,
    2
);

/* =========================================
   CLIENT DETAILS FROM SOLAR INQUIRY
========================================= */
$client_name    = $inq['name'] ?? '';
$client_address = $inq['address'] ?? '';
$client_phone   = $inq['phone'] ?? '';
$client_email   = $inq['email'] ?? '';

/* =========================================
   INSERT INTO TBL_SOLAR_QUOTATION
========================================= */
$sql = "INSERT INTO tbl_solar_quotation (
    inquiry_id,
    ref_no,
    quotation_date,
    client_name,
    client_address,
    client_phone,
    client_email,
    property_type,
    subject,
    plant_capacity_kw,
    system_type,
    monthly_generation,
    yearly_generation,
    total_amount,
    annual_saving,
    payback_years,
    panel_spec,
    panel_make,
    panel_qty,
    inverter_spec,
    inverter_make,
    inverter_qty,
    cable_make,
    mounting_make,
    dc_jb_make,
    acdb_make,
    earthing_make,
    earthing_qty,
    net_meter_make,
    net_meter_qty,
    supply_installation_amount,
    gst_5_amount,
    commercial_total,
    govt_subsidy_amount,
    actual_project_cost
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?
)";

/* =========================================
   PREPARE STATEMENT
========================================= */
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Prepare Error: " . mysqli_error($conn));
}

/*
   Types:
   i  = inquiry_id
   s  = 10 string fields
   ii = monthly_generation, yearly_generation
   dd = total_amount, annual_saving
   s  = payback_years
   s  = 14 component fields
   d  = 5 commercial amount fields
*/

$types = 'i'
       . str_repeat('s', 10)
       . 'ii'
       . 'dd'
       . 's'
       . str_repeat('s', 14)
       . str_repeat('d', 5);

/* =========================================
   BIND PARAMETERS
========================================= */
mysqli_stmt_bind_param(
    $stmt,
    $types,

    $inquiry_id,
    $ref_no,
    $quotation_date,
    $client_name,
    $client_address,
    $client_phone,
    $client_email,
    $property_type,
    $subject,
    $plant_capacity_kw,
    $system_type,
    $monthly_generation,
    $yearly_generation,
    $total_amount,
    $annual_saving,
    $payback_years,

    $panel_spec,
    $panel_make,
    $panel_qty,

    $inverter_spec,
    $inverter_make,
    $inverter_qty,

    $cable_make,
    $mounting_make,
    $dc_jb_make,
    $acdb_make,

    $earthing_make,
    $earthing_qty,

    $net_meter_make,
    $net_meter_qty,

    $supply_installation_amount,
    $gst_5_amount,
    $commercial_total,
    $govt_subsidy_amount,
    $actual_project_cost
);

/* =========================================
   EXECUTE QUERY
========================================= */
if (!mysqli_stmt_execute($stmt)) {
    die("DB Error: " . mysqli_stmt_error($stmt));
}

/* =========================================
   GET INSERTED QUOTATION ID
========================================= */
$quotation_id = mysqli_insert_id($conn);

mysqli_stmt_close($stmt);

/* =========================================
   REDIRECT TO SOLAR PDF
========================================= */
header(
    "Location: solar-generate-quotation.php?id=" . $quotation_id
);
exit();
