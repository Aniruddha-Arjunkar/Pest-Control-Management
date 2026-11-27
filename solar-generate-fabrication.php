<?php
/* =========================================================
   SOLAR FABRICATION PDF GENERATOR
========================================================= */

session_start();

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

mysqli_set_charset($conn, "utf8mb4");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================
   HELPER FUNCTIONS
========================= */

function h($value)
{
    return (string)($value ?? '');
}

function money($value)
{
    return number_format((float)$value, 2);
}

/* =========================
   GET QUOTATION ID
========================= */

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    exit('Invalid Solar fabrication ID');
}

/* =========================
   FETCH SOLAR QUOTATION
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM tbl_solar_quotation
     WHERE id = ?
       AND quotation_type = 'fabrication'"
);

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$q = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$q) {
    exit('Solar fabrication record not found');
}

/* =========================
   FETCH SOLAR FABRICATION ITEMS
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT item_name, quantity, unit, rate, amount
     FROM tbl_solar_fabrication_items
     WHERE quotation_id = ?
     ORDER BY id"
);

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$items = [];

while ($item = mysqli_fetch_assoc($result)) {
    $items[] = $item;
}

mysqli_stmt_close($stmt);

if (empty($items)) {
    exit('No Solar fabrication items found');
}

/* =========================
   PDF TEMPLATE
========================= */

$template = __DIR__ . '/templates/Fabrication Quotation.pdf';

if (!file_exists($template)) {
    exit('PDF template not found');
}

/* =========================
   CREATE PDF
========================= */

$pdf = new Fpdi('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(false);

$pageCount = $pdf->setSourceFile($template);

if ($pageCount < 1) {
    exit('PDF template has no pages');
}

$tpl = $pdf->importPage(1);
$size = $pdf->getTemplateSize($tpl);

$pdf->AddPage(
    $size['orientation'],
    [$size['width'], $size['height']]
);

$pdf->useTemplate($tpl);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(210, 210, 210);
$pdf->SetLineWidth(0.2);

/* =========================
   QUOTATION HEADING
========================= */

$pdf->SetFont('Helvetica', 'BU', 16);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetXY(13, 20);

$pdf->Cell(
    $size['width'] - 26,
    8,
    'QUOTATION',
    0,
    1,
    'C'
);

/* =========================
   REF NO. AND DATE
========================= */

$pdf->SetFont('Helvetica', '', 11);

$pdf->SetXY(13, 27);

$pdf->Cell(
    90,
    5,
    'Ref. No: ' . h($q['ref_no']),
    0,
    0,
    'L'
);

$pdf->SetXY(145, 27);

$pdf->Cell(
    45,
    5,
    'Date: ' . h($q['quotation_date']),
    0,
    0,
    'R'
);

/* =========================
   CUSTOMER DETAILS
========================= */

$pdf->SetFont('Helvetica', '', 10);

$pdf->SetXY(13, 39);
$pdf->Cell(90, 4, 'To,', 0, 1, 'L');

$pdf->SetX(13);
$pdf->SetFont('Helvetica', 'B', 10);

$pdf->MultiCell(
    100,
    4,
    h($q['client_name']),
    0,
    'L'
);

$pdf->SetFont('Helvetica', '', 10);
$pdf->SetX(13);

$pdf->MultiCell(
    100,
    4,
    h($q['client_address']),
    0,
    'L'
);

if (!empty($q['client_phone'])) {
    $pdf->SetX(13);

    $pdf->Cell(
        100,
        4,
        'Phone: ' . h($q['client_phone']),
        0,
        1,
        'L'
    );
}

if (!empty($q['customer_gstin'])) {
    $pdf->SetX(13);

    $pdf->Cell(
        100,
        4,
        'GSTIN: ' . h($q['customer_gstin']),
        0,
        1,
        'L'
    );
}

/* =========================
   TABLE SETTINGS
========================= */

$pageWidth = $size['width'];

$width = 175;
$left = ($pageWidth - $width) / 2;

$srW   = 12;
$itemW = 75;
$qtyW  = 18;
$unitW = 20;
$rateW = 25;
$amtW  = 25;

$y = 75;

$headerH = 7;

$blue = [51, 51, 153];
$grey = [244, 244, 244];

/* =========================
   TABLE HEADER FUNCTION
========================= */

$drawTableHeader = function () use (
    $pdf,
    $left,
    $srW,
    $itemW,
    $qtyW,
    $unitW,
    $rateW,
    $amtW,
    $headerH,
    $blue
) {
    $pdf->SetFillColor(
        $blue[0],
        $blue[1],
        $blue[2]
    );

    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetDrawColor(150, 150, 150);
    $pdf->SetLineWidth(0.2);
    $pdf->SetFont('Helvetica', 'B', 9);

    $pdf->SetXY($left, $pdf->GetY());

    $pdf->Cell($srW, $headerH, 'Sr. No.', 1, 0, 'C', true);
    $pdf->Cell($itemW, $headerH, 'Item', 1, 0, 'C', true);
    $pdf->Cell($qtyW, $headerH, 'Qty', 1, 0, 'C', true);
    $pdf->Cell($unitW, $headerH, 'Unit', 1, 0, 'C', true);
    $pdf->Cell($rateW, $headerH, 'Rate', 1, 0, 'C', true);
    $pdf->Cell($amtW, $headerH, 'Amount', 1, 1, 'C', true);

    $pdf->SetTextColor(0, 0, 0);

    $currentY = $pdf->GetY();

    $pdf->SetDrawColor(170, 211, 232);
    $pdf->SetLineWidth(0.8);

    $pdf->Line(
        $left,
        $currentY,
        $left + $srW + $itemW + $qtyW + $unitW + $rateW + $amtW,
        $currentY
    );

    $pdf->SetLineWidth(0.2);

    $pdf->SetY($currentY + 1);
};

/* =========================
   DRAW FIRST TABLE HEADER
========================= */

$pdf->SetY($y);
$drawTableHeader();
$y = $pdf->GetY();

/* =========================
   ITEM ROWS WITH AUTO WRAPPING
========================= */

$lineHeight = 5;
$minRowH = 10;

foreach ($items as $index => $item) {

    $itemName = trim(h($item['item_name']));
    $textWidth = $itemW - 4;

    $words = preg_split('/\s+/', $itemName);
    $lines = [];
    $currentLine = '';

    foreach ($words as $word) {

        $testLine = ($currentLine === '')
            ? $word
            : $currentLine . ' ' . $word;

        if ($pdf->GetStringWidth($testLine) <= $textWidth) {
            $currentLine = $testLine;
            continue;
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        $currentLine = '';
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $char) {

            if (
                $currentLine !== '' &&
                $pdf->GetStringWidth($currentLine . $char) > $textWidth
            ) {
                $lines[] = $currentLine;
                $currentLine = '';
            }

            $currentLine .= $char;
        }
    }

    if ($currentLine !== '') {
        $lines[] = $currentLine;
    }

    if (empty($lines)) {
        $lines[] = '';
    }

    $currentRowH = max(
        $minRowH,
        count($lines) * $lineHeight + 2
    );

    /* PAGE BREAK */

    if ($y + $currentRowH > 225) {

        $nextPage = $pdf->PageNo() + 1;

        if ($nextPage > $pageCount) {
            exit(
                'Items exceed template pages. ' .
                'Please add more pages to the PDF template.'
            );
        }

        $pdf->AddPage(
            $size['orientation'],
            [$size['width'], $size['height']]
        );

        $pdf->useTemplate($pdf->importPage($nextPage));

        $pdf->SetY(75);
        $drawTableHeader();
        $y = $pdf->GetY();
    }

    /* ALTERNATE ROW BACKGROUND */

    $fill = ($index % 2 === 1);

    if ($fill) {
        $pdf->SetFillColor(
            $grey[0],
            $grey[1],
            $grey[2]
        );
    }

    $pdf->SetDrawColor(150, 150, 150);
    $pdf->SetLineWidth(0.2);
    $pdf->SetFont('Helvetica', '', 9);

    $x = $left;

    /* SERIAL NUMBER */

    $pdf->SetXY($x, $y);

    $pdf->Cell(
        $srW,
        $currentRowH,
        (string)($index + 1),
        1,
        0,
        'C',
        $fill
    );

    $x += $srW;

    /* ITEM COLUMN */

    $pdf->Rect(
        $x,
        $y,
        $itemW,
        $currentRowH,
        $fill ? 'DF' : 'D'
    );

    $textY = $y + 1;

    foreach ($lines as $line) {

        $pdf->SetXY($x + 1.5, $textY);

        $pdf->Cell(
            $itemW - 3,
            $lineHeight,
            $line,
            0,
            0,
            'L'
        );

        $textY += $lineHeight;
    }

    $x += $itemW;

    /* QUANTITY */

    $pdf->SetXY($x, $y);

    $pdf->Cell(
        $qtyW,
        $currentRowH,
        h($item['quantity']),
        1,
        0,
        'C',
        $fill
    );

    $x += $qtyW;

    /* UNIT */

    $pdf->SetXY($x, $y);

    $pdf->Cell(
        $unitW,
        $currentRowH,
        h($item['unit']),
        1,
        0,
        'C',
        $fill
    );

    $x += $unitW;

    /* RATE */

    $pdf->SetXY($x, $y);

    $pdf->Cell(
        $rateW,
        $currentRowH,
        money($item['rate']),
        1,
        0,
        'C',
        $fill
    );

    $x += $rateW;

    /* AMOUNT */

    $pdf->SetXY($x, $y);

    $pdf->Cell(
        $amtW,
        $currentRowH,
        money($item['amount']),
        1,
        0,
        'R',
        $fill
    );

    $y += $currentRowH;
}

/* =========================
   TOTALS
========================= */

$estimatedExtraHeight = 8 * 4 + 5 + 8 + 2 + (4 * 6) + 25 + 10;

if ($y + 2 + $estimatedExtraHeight > 280) {

    $nextPage = $pdf->PageNo() + 1;

    if ($nextPage <= $pageCount) {

        $pdf->AddPage(
            $size['orientation'],
            [$size['width'], $size['height']]
        );

        $pdf->useTemplate(
            $pdf->importPage($nextPage)
        );

        $y = 30;

    } else {
        exit(
            'Not enough template pages for totals and terms. ' .
            'Please add another page to the PDF template.'
        );
    }
}

$y += 2;

/* =========================
   DRAW TOTAL FUNCTION
========================= */

$drawTotal = function (
    $label,
    $value,
    $bold = false
) use (
    $pdf,
    &$y,
    $left,
    $srW,
    $itemW,
    $qtyW,
    $unitW,
    $rateW,
    $amtW
) {

    $labelX = $left + $srW;

    $amountX = $left + $srW + $itemW
        + $qtyW + $unitW + $rateW;

    $totalWidth = $srW + $itemW + $qtyW
        + $unitW + $rateW + $amtW;

    $pdf->SetDrawColor(210, 210, 210);
    $pdf->SetLineWidth(0.5);

    $pdf->Line(
        $left,
        $y,
        $left + $totalWidth,
        $y
    );

    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetFont(
        'Helvetica',
        $bold ? 'B' : '',
        10
    );

    $pdf->SetXY($labelX, $y + 1);

    $pdf->Cell(
        $itemW + $qtyW + $unitW + $rateW,
        7,
        $label,
        0,
        0,
        'L'
    );

    $pdf->SetXY($amountX, $y + 1);

    $pdf->Cell(
        $amtW,
        7,
        $value,
        0,
        0,
        'R'
    );

    $y += 8;
};

/* =========================
   GST TOTALS
========================= */

$drawTotal(
    'Subtotal',
    money($q['subtotal'])
);

$drawTotal(
    'CGST',
    money($q['cgst_amount'])
);

$drawTotal(
    'SGST',
    money($q['sgst_amount'])
);

$drawTotal(
    'Grand Total',
    'Rs. ' . money($q['total_amount']),
    true
);

/* =========================
   AMOUNT IN WORDS
========================= */

$y += 5;

$words = 'Amount in Words: ' .
    h($q['amount_in_words']);

$pdf->SetFillColor(51, 51, 153);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 9);

$pdf->SetXY($left, $y);

$pdf->MultiCell(
    $width,
    5,
    $words,
    0,
    'C',
    true
);

$pdf->SetTextColor(0, 0, 0);

$y = $pdf->GetY() + 7;

/* =========================
   TERMS & CONDITIONS
========================= */

$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetXY($left, $y);

$pdf->Cell(
    $width,
    6,
    'Terms & Conditions',
    0,
    1,
    'L'
);

$y = $pdf->GetY() + 2;

$terms = [
    '1. Extra Work will be charged.',
    '2. NO CREDIT PLEASE',
    '3. Warranty: As mentioned on Product.',
    '4. Validity: This quotation is valid for 7 days.'
];

$pdf->SetFont('Helvetica', '', 9);

foreach ($terms as $term) {

    if ($y + 6 > 280) {

        $nextPage = $pdf->PageNo() + 1;

        if ($nextPage > $pageCount) {
            exit(
                'Not enough template pages for terms and conditions.'
            );
        }

        $pdf->AddPage(
            $size['orientation'],
            [$size['width'], $size['height']]
        );

        $pdf->useTemplate(
            $pdf->importPage($nextPage)
        );

        $y = 25;
    }

    $pdf->SetXY($left + 3, $y);

    $pdf->MultiCell(
        $width - 6,
        5,
        $term,
        0,
        'L'
    );

    $y = $pdf->GetY() + 1;
}

/* =========================
   COMPUTER GENERATED NOTE
========================= */

$y += 15;

if ($y + 10 > 285) {

    $nextPage = $pdf->PageNo() + 1;

    if ($nextPage <= $pageCount) {

        $pdf->AddPage(
            $size['orientation'],
            [$size['width'], $size['height']]
        );

        $pdf->useTemplate(
            $pdf->importPage($nextPage)
        );

        $y = 260;

    } else {
        $y = 280;
    }
}

$pdf->SetFont('Helvetica', 'I', 9);
$pdf->SetXY($left, $y);

$pdf->MultiCell(
    $width,
    5,
    '(This is Computer generated PDF, hence signature or stamp not required)',
    0,
    'C'
);

/* =========================
   SAVE PDF TO SOLAR FOLDER
========================= */

$outDir = __DIR__ . '/upload-solar-fabrication/';

if (!is_dir($outDir)) {

    if (
        !mkdir($outDir, 0755, true) &&
        !is_dir($outDir)
    ) {
        exit('Unable to create Solar fabrication upload folder');
    }
}

/* SAFE FILENAME */

$clientNameSafe = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    $q['client_name'] ?? 'Client'
);

$fileName = 'Solar_Fabrication_' .
    $clientNameSafe . '_' . $id . '.pdf';

$outPath = $outDir . $fileName;

/* SAVE PDF */

$pdf->Output('F', $outPath);

if (!is_file($outPath)) {
    exit('Solar fabrication PDF could not be saved');
}

/* =========================
   UPDATE DATABASE PDF PATH
========================= */

$fileUrl = 'upload-solar-fabrication/' . $fileName;

$stmt = mysqli_prepare(
    $conn,
    "UPDATE tbl_solar_quotation
     SET pdf_file = ?
     WHERE id = ?
       AND quotation_type = 'fabrication'"
);

mysqli_stmt_bind_param(
    $stmt,
    'si',
    $fileUrl,
    $id
);

mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

/* =========================
   REDIRECT TO SOLAR VIEW PAGE
========================= */

header('Location: solar-view-quotations.php');
exit;
?>
