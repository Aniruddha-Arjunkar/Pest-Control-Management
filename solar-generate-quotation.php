<?php
use setasign\Fpdi\Fpdi;

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
   VALIDATION
========================================= */
if (!isset($_GET['id']) || (int)$_GET['id'] <= 0) {
    die('Quotation ID missing or invalid');
}

$id = (int)$_GET['id'];

/* =========================================
   FETCH SOLAR QUOTATION
========================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM tbl_solar_quotation WHERE id = ?"
);

if (!$stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    die('Solar Quotation not found');
}

$q = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

/* =========================================
   LOAD FPDI + FPDF
========================================= */
require __DIR__ . '/vendor/autoload.php';

/* =========================================
   INIT PDF
========================================= */
$pdf = new Fpdi();

$template = __DIR__ . '/templates/Shul Ventures Quotation Sample 6.pdf';

if (!file_exists($template)) {
    die("Quotation PDF template not found");
}

$pageCount = $pdf->setSourceFile($template);

if ($pageCount < 5) {
    die("PDF template must contain at least 5 pages");
}

/* =========================================
   PAGE 1 - CLIENT DETAILS
========================================= */
$pdf->AddPage();

$tpl = $pdf->importPage(1);
$pdf->useTemplate($tpl, 0, 0, 210);

$pdf->SetFont('Times', '', 11);

// Ref No
$pdf->SetXY(28, 23);
$pdf->Write(6, $q['ref_no'] ?? '');

// Date
$pdf->SetXY(180, 23);

$quotationDate = !empty($q['quotation_date'])
    ? date('d/m/Y', strtotime($q['quotation_date']))
    : '';

$pdf->Write(6, $quotationDate);

// Client details
$toX = 20;
$toY = 40;
$currentY = $toY;

// To
$pdf->SetFont('Times', '', 11);
$pdf->SetXY($toX, $currentY);
$pdf->Write(6, "");
$currentY += 5;

// Client Name
$pdf->SetFont('Times', 'B', 11);
$pdf->SetXY($toX, $currentY);
$pdf->Write(6, strtoupper($q['client_name'] ?? ''));
$currentY += 5;

// Address
$pdf->SetFont('Times', '', 11);
$pdf->SetXY($toX, $currentY);

$pdf->MultiCell(
    70,
    4.5,
    strtoupper($q['client_address'] ?? '')
);

$currentY = $pdf->GetY();

// Mobile
$pdf->SetXY($toX, $currentY);

$pdf->MultiCell(
    70,
    5,
    "Mo. No. " . ($q['client_phone'] ?? '')
);

$currentY = $pdf->GetY();

// Connection No (optional)
if (!empty($q['connection_no'])) {

    $pdf->SetXY($toX, $currentY);

    $pdf->MultiCell(
        70,
        5,
        "CON. NO. " . $q['connection_no']
    );

    $currentY = $pdf->GetY() + 2;
}

// Property Type
$pdf->SetFont('Times', 'B', 11);
$pdf->SetXY($toX, $currentY);

$pdf->Write(
    6,
    strtoupper($q['property_type'] ?? '')
);

// Subject
$pdf->SetFont('Times', 'BU', 11);
$pdf->SetXY(30, 77.8);

$pdf->MultiCell(
    170,
    6,
    $q['subject'] ?? ''
);

// Plant Capacity
$pdf->SetFont('Times', 'BU', 10);
$pdf->SetXY(81, 107);

$bodyText = ($q['plant_capacity_kw'] ?? '') . "   ";

$pdf->MultiCell(170, 6, $bodyText);


/* =========================================
   PAGE 2 - SUMMARY OF PROPOSAL
========================================= */

$tableStartX = 30;
$tableStartY = 35;

$colWidths = [
    95,
    65
];

$rowY = [
    1 => 0,
    2 => 5,
    3 => 11,
    4 => 17,
    5 => 22,
    6 => 28,
    7 => 34,
    8 => 40
];

function writeCell($pdf, $row, $col, $text, $sx, $sy, $cw, $rowY)
{
    $x = $sx;

    for ($i = 1; $i < $col; $i++) {
        $x += $cw[$i - 1];
    }

    $y = $sy + $rowY[$row];

    $pdf->SetXY($x + 2, $y + 3);

    $pdf->Cell(
        $cw[$col - 1] - 4,
        6,
        (string)$text,
        0,
        0,
        'C'
    );
}

$pdf->AddPage();

$tpl2 = $pdf->importPage(2);
$pdf->useTemplate($tpl2, 0, 0, 210);

$pdf->SetFont('Times', '', 11);

writeCell(
    $pdf, 1, 2,
    ($q['plant_capacity_kw'] ?? '') . " KWp",
    $tableStartX, $tableStartY, $colWidths, $rowY
);

writeCell(
    $pdf, 2, 2,
    $q['system_type'] ?? '',
    $tableStartX, $tableStartY, $colWidths, $rowY
);

writeCell(
    $pdf, 3, 2,
    ($q['monthly_generation'] ?? 0) . " Units",
    $tableStartX, $tableStartY, $colWidths, $rowY
);

writeCell(
    $pdf, 4, 2,
    ($q['yearly_generation'] ?? 0) . " Units",
    $tableStartX, $tableStartY, $colWidths, $rowY
);

writeCell(
    $pdf, 5, 2,
    "Included",
    $tableStartX, $tableStartY, $colWidths, $rowY
);

// Total System Cost
$pdf->SetFont('Times', 'BU', 11);

writeCell(
    $pdf,
    6,
    2,
    "INR " . number_format((float)$q['total_amount'], 2),
    $tableStartX,
    $tableStartY,
    $colWidths,
    $rowY
);

// Annual Saving
$pdf->SetFont('Times', 'BU', 11);

writeCell(
    $pdf,
    7,
    2,
    "INR " . number_format((float)$q['annual_saving'], 2),
    $tableStartX,
    $tableStartY,
    $colWidths,
    $rowY
);

// Payback Years
$pdf->SetFont('Times', '', 11);

writeCell(
    $pdf,
    8,
    2,
    ($q['payback_years'] ?? '0') . " Years",
    $tableStartX,
    $tableStartY,
    $colWidths,
    $rowY
);


/* =========================================
   HELPER FUNCTIONS - COMPONENT TABLE
========================================= */

function put($pdf, $row, $col, $text, $sx, $sy, $cw, $ry)
{
    $x = $sx;

    for ($i = 1; $i < $col; $i++) {
        $x += $cw[$i - 1];
    }

    $y = $sy + $ry[$row];

    $pdf->SetXY($x + 2, $y + 3);

    $pdf->Cell(
        $cw[$col - 1] - 4,
        6,
        (string)$text,
        0,
        0,
        'C'
    );
}

function getWrappedTextHeight($pdf, $text, $maxWidth, $lineHeight)
{
    $words = explode(' ', (string)$text);
    $line = '';
    $lines = 1;

    foreach ($words as $word) {

        $testLine = $line . $word . ' ';

        if ($pdf->GetStringWidth($testLine) > $maxWidth) {

            $lines++;
            $line = $word . ' ';

        } else {

            $line = $testLine;
        }
    }

    return $lines * $lineHeight;
}

function putSpecWrap(
    $pdf,
    $row,
    $text,
    $sx,
    $sy,
    $cw,
    $ry,
    $rowHeight = 14
) {
    $x = $sx + $cw[0];
    $y = $sy + $ry[$row];

    $lineHeight = 5;
    $usableWidth = $cw[1] - 18;

    $textHeight = getWrappedTextHeight(
        $pdf,
        $text,
        $usableWidth,
        $lineHeight
    );

    $vCenterY = $y + (($rowHeight - $textHeight) / 2);

    $pdf->SetXY($x + 18, $vCenterY);

    $pdf->MultiCell(
        $usableWidth,
        $lineHeight,
        (string)$text,
        0,
        'C'
    );
}


/* =========================================
   COMPONENT TABLE SETTINGS
========================================= */

$startX = -5;
$startY = 115;

$colWidth = [
    30,
    90,
    45,
    35
];

$rowY = [
    1 => -3,
    2 => 10,
    3 => 27,
    4 => 45,
    5 => 61,
    6 => 80,
    7 => 96,
    8 => 112,
    9 => 125
];

/* =========================================
   COMPONENT TABLE DATA
========================================= */

// Panels
putSpecWrap(
    $pdf, 1,
    $q['panel_spec'] ?? '',
    $startX, $startY, $colWidth, $rowY, 14
);

// Inverter
putSpecWrap(
    $pdf, 2,
    $q['inverter_spec'] ?? '',
    $startX, $startY, $colWidth, $rowY, 14
);

$pdf->SetFont('Times', 'BU', 10);

// Panel
put($pdf, 1, 3, $q['panel_make'] ?? '', $startX, $startY, $colWidth, $rowY);
put($pdf, 1, 4, $q['panel_qty'] ?? '', $startX, $startY, $colWidth, $rowY);

// Inverter
put($pdf, 2, 3, $q['inverter_make'] ?? '', $startX, $startY, $colWidth, $rowY);
put($pdf, 2, 4, $q['inverter_qty'] ?? '', $startX, $startY, $colWidth, $rowY);

// Cables / Wires
put($pdf, 3, 3, $q['cable_make'] ?? '', $startX, $startY, $colWidth, $rowY);
put($pdf, 4, 3, $q['cable_make'] ?? '', $startX, $startY, $colWidth, $rowY);

// Mounting
put($pdf, 5, 3, $q['mounting_make'] ?? '', $startX, $startY, $colWidth, $rowY);

// DCJB
put($pdf, 6, 3, $q['dc_jb_make'] ?? '', $startX, $startY, $colWidth, $rowY);

// ACDB
put($pdf, 7, 3, $q['acdb_make'] ?? '', $startX, $startY, $colWidth, $rowY);

// Earthing
put($pdf, 8, 3, $q['earthing_make'] ?? '', $startX, $startY, $colWidth, $rowY);
put($pdf, 8, 4, $q['earthing_qty'] ?? '', $startX, $startY, $colWidth, $rowY);

// Net Meter
put($pdf, 9, 3, $q['net_meter_make'] ?? '', $startX, $startY, $colWidth, $rowY);
put($pdf, 9, 4, $q['net_meter_qty'] ?? '', $startX, $startY, $colWidth, $rowY);


/* =========================================
   PAGE 3 - COMMERCIAL
========================================= */

$pageNo = 3;

$tpl = $pdf->importPage($pageNo);

$pdf->AddPage();
$pdf->useTemplate($tpl, 0, 0, 210);

$pdf->SetFont('Helvetica', '', 10);

$pdf->SetXY(91, 40);
$pdf->Cell(10, 5, $q['plant_capacity_kw'] ?? '');

$amountX = 148;
$baseY = 37;

$rowY = [
    1 => 2.5,
    2 => 8.5,
    3 => 14,
    4 => 22,
    5 => 29,
    6 => 36,
    7 => 41,
    8 => 47
];

function commercialRow(
    $pdf,
    $row,
    $text,
    $x,
    $baseY,
    $rowY,
    $align = 'C'
) {
    $amountColWidth = 65;

    $pdf->SetXY(
        $x,
        $baseY + $rowY[$row]
    );

    $pdf->Cell(
        $amountColWidth,
        8,
        $text,
        0,
        0,
        $align
    );
}

$supply = number_format(
    (float)$q['supply_installation_amount'],
    2
);

$gst5 = number_format(
    (float)$q['gst_5_amount'],
    2
);

$total = number_format(
    (float)$q['commercial_total'],
    2
);

$subsidy = number_format(
    (float)$q['govt_subsidy_amount'],
    2
);

$actual = number_format(
    (float)$q['actual_project_cost'],
    2
);

$pdf->SetFont('Helvetica', 'B', 11);

// Supply & Installation
commercialRow(
    $pdf, 1, "INR " . $supply,
    $amountX, $baseY, $rowY
);

// GST @ 5%
commercialRow(
    $pdf, 2, "INR " . $gst5,
    $amountX, $baseY, $rowY
);

// Total
commercialRow(
    $pdf, 4, "INR " . $total,
    $amountX, $baseY, $rowY
);

// Government Subsidy
if (
    stripos($q['property_type'] ?? '', 'residential') !== false
    && (float)$q['govt_subsidy_amount'] > 0
) {

    commercialRow(
        $pdf,
        7,
        "INR " . $subsidy,
        $amountX,
        $baseY,
        $rowY
    );

} else {

    commercialRow(
        $pdf,
        7,
        "NA",
        $amountX,
        $baseY,
        $rowY,
        'C'
    );
}

// Actual Project Cost
commercialRow(
    $pdf, 8, "INR " . $actual,
    $amountX, $baseY, $rowY
);


/* =========================================
   PAGE 4
========================================= */
$pdf->AddPage();

$tpl2 = $pdf->importPage(4);
$pdf->useTemplate($tpl2, 0, 0, 210);


/* =========================================
   PAGE 5
========================================= */
$pdf->AddPage();

$tpl2 = $pdf->importPage(5);
$pdf->useTemplate($tpl2, 0, 0, 210);


/* =========================================
   SAVE PDF
========================================= */
$outDir = __DIR__ . '/upload-solar-quotations/';

if (!is_dir($outDir)) {

    if (!mkdir($outDir, 0777, true)) {
        die("Unable to create quotation upload folder");
    }
}

$clientName = $q['client_name'] ?? 'Client';

$clientNameSafe = preg_replace(
    '/[^A-Za-z0-9\-]/',
    '_',
    $clientName
);

// Include ID to prevent overwriting another quotation
$fileName = 'Solar_Quotation_' . $clientNameSafe . '_' . $id . '.pdf';

$outPath = $outDir . $fileName;

$pdf->Output('F', $outPath);

if (!file_exists($outPath)) {
    die("PDF generation failed");
}

$fileUrl = 'upload-solar-quotations/' . $fileName;


/* =========================================
   UPDATE PDF PATH IN DATABASE
========================================= */
$updateStmt = mysqli_prepare(
    $conn,
    "UPDATE tbl_solar_quotation SET pdf_file = ? WHERE id = ?"
);

if (!$updateStmt) {
    die("Update Prepare Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $updateStmt,
    "si",
    $fileUrl,
    $id
);

if (!mysqli_stmt_execute($updateStmt)) {
    die("PDF Path Update Error: " . mysqli_stmt_error($updateStmt));
}

mysqli_stmt_close($updateStmt);


/* =========================================
   REDIRECT
========================================= */
header("Location: solar-view-quotations.php");
exit();
