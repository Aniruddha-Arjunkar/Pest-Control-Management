
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

mysqli_set_charset($conn, 'utf8mb4');

/* ================= VALIDATION ================= */

$client_id  = (int)($_GET['client_id'] ?? 0);
$created_at = trim($_GET['created_at'] ?? '');

if ($client_id <= 0 || $created_at === '') {
    die('Client ID or timestamp missing');
}

/* ================= FETCH SOLAR INVOICE DATA ================= */

$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM tbl_solar_invoice
    WHERE client_id = ?
      AND created_at = ?
    ORDER BY id ASC
");

if (!$stmt) {
    die('Invoice query error: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'is', $client_id, $created_at);
mysqli_stmt_execute($stmt);

$qry = mysqli_stmt_get_result($stmt);

if (!$qry || mysqli_num_rows($qry) == 0) {
    die('Solar Invoice not found');
}

$invoiceRows = [];

while ($row = mysqli_fetch_assoc($qry)) {
    $invoiceRows[] = $row;
}

mysqli_stmt_close($stmt);

$first = $invoiceRows[0];

$client_name = $first['cust_name'] ?? 'Solar Client';

/* ================= FETCH SOLAR CLIENT GST NUMBER ================= */

$stmt = mysqli_prepare($conn, "
    SELECT gst_number
    FROM tbl_solar_client
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Client GST query error: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'i', $client_id);
mysqli_stmt_execute($stmt);

$gstQry = mysqli_stmt_get_result($stmt);
$gstData = mysqli_fetch_assoc($gstQry);

mysqli_stmt_close($stmt);

$gst_number = $gstData['gst_number'] ?? '';

/* ================= INVOICE NUMBER ================= */

$gst_invoice_no = trim($first['gst_invoice_no'] ?? '');

if ($gst_invoice_no !== '') {

    // Existing invoice number reuse

} else {

    mysqli_begin_transaction($conn);

    try {

        // Lock and generate next invoice number
        $r = mysqli_query($conn, "
            SELECT MAX(CAST(gst_invoice_no AS UNSIGNED)) AS last_inv
            FROM tbl_solar_invoice
            WHERE gst_invoice_no IS NOT NULL
              AND gst_invoice_no != ''
            FOR UPDATE
        ");

        if (!$r) {
            throw new Exception(mysqli_error($conn));
        }

        $row = mysqli_fetch_assoc($r);

        $next_invoice = ((int)($row['last_inv'] ?? 0)) + 1;

        $gst_invoice_no = (string)$next_invoice;

        $updateStmt = mysqli_prepare($conn, "
            UPDATE tbl_solar_invoice
            SET gst_invoice_no = ?
            WHERE client_id = ?
              AND created_at = ?
        ");

        if (!$updateStmt) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $updateStmt,
            'sis',
            $gst_invoice_no,
            $client_id,
            $created_at
        );

        if (!mysqli_stmt_execute($updateStmt)) {
            throw new Exception(mysqli_stmt_error($updateStmt));
        }

        mysqli_stmt_close($updateStmt);

        mysqli_commit($conn);

    } catch (Throwable $e) {

        mysqli_rollback($conn);

        die('Invoice number generation failed: ' . $e->getMessage());
    }
}

/* ================= TOTAL ================= */

$grandTotal = 0;

foreach ($invoiceRows as $row) {
    $grandTotal += (float)($row['item_total'] ?? 0);
}

$grandTotal = round($grandTotal, 2);

/* ================= PDF INIT ================= */

$pdf = new Fpdi();

$pdf->AddPage();

$template = __DIR__ . '/templates/Shul-Tax-Invoice (4).pdf';

if (!file_exists($template)) {
    die('Invoice PDF template not found: ' . $template);
}

$pdf->setSourceFile($template);

$tpl = $pdf->importPage(1);

$pdf->useTemplate($tpl, 0, 0, 210);

/* ================= HEADER ================= */

$pdf->SetFont('Times', 'B', 11);

$pdf->SetXY(32, 44);
$pdf->Write(6, $gst_invoice_no);

$pdf->SetXY(35, 35);
$pdf->Write(6, date('d-m-Y', strtotime($first['invoice_date'])));

$pdf->SetXY(158, 35);
$pdf->Write(6, date('d-m-Y', strtotime($first['due_date'])));

/* ================= ADDRESSES ================= */

$pdf->SetFont('Times', 'B', 10);

$billing =
    strtoupper($client_name) . "\n" .
    ($first['billing_address'] ?? '') . "\n" .
    "Ph: " . ($first['cust_phone'] ?? '') . "\n" .
    ($first['cust_email'] ?? '') . "\n" .
    "GSTIN: " . ($gst_number ?: 'N/A');

$shipping =
    strtoupper($client_name) . "\n" .
    ($first['shipping_address'] ?? '') . "\n" .
    "Ph: " . ($first['cust_phone'] ?? '') . "\n" .
    ($first['cust_email'] ?? '') . "\n" .
    "GSTIN: " . ($gst_number ?: 'N/A');

$pdf->SetXY(13, 70);
$pdf->MultiCell(90, 3.5, $billing);

$pdf->SetXY(107, 70);
$pdf->MultiCell(90, 3.5, $shipping);

/* ================= ROW HEIGHT ================= */

function rowHeight($pdf, $w, $lh, $txt) {

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    $pdf->MultiCell($w, $lh, $txt, 0);

    $h = $pdf->GetY() - $y;

    $pdf->SetXY($x, $y);

    return $h;
}

/* ================= ITEM TABLE ================= */

$pdf->SetFont('Times', '', 9);

$y = 108.5;
$lh = 4;
$sr = 1;

$PAGE_BOTTOM = 230;

$skippedItems = 0;

foreach ($invoiceRows as $row) {

    $itemName = $row['item_name'] ?? '';
    $hsn = $row['hsn'] ?? '';
    $qty = $row['qty'] ?? 1;

    $pdf->SetXY(20.5, $y);

    $h = rowHeight($pdf, 49, $lh, $itemName);

    $rowH = max($h, 3.5);

    // Same single-page layout as reference code
    if ($y + $rowH > $PAGE_BOTTOM) {
        $skippedItems++;
        continue;
    }

    // Serial number
    $pdf->SetXY(13.5, $y);
    $pdf->Cell(7, $rowH, $sr, 1, 0, 'C');

    // Item name
    $pdf->SetXY(20.5, $y);
    $pdf->MultiCell(49.3, $lh, $itemName, 1);

    $x = 69.8;

    // HSN
    $pdf->SetXY($x, $y);
    $pdf->Cell(17.3, $rowH, $hsn, 1, 0, 'C');
    $x += 17.3;

    // Quantity
    $pdf->SetXY($x, $y);
    $pdf->Cell(9.5, $rowH, $qty, 1, 0, 'C');
    $x += 9.5;

    // Base amount
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        19,
        $rowH,
        number_format((float)$row['base_amount'], 2, '.', ','),
        1,
        0,
        'C'
    );
    $x += 19;

    // CGST percentage
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        12.6,
        $rowH,
        number_format((float)$row['cgst_per'], 2, '.', ',') . '%',
        1,
        0,
        'C'
    );
    $x += 12.6;

    // CGST amount
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        17.5,
        $rowH,
        number_format((float)$row['cgst_amt'], 2, '.', ','),
        1,
        0,
        'C'
    );
    $x += 17.5;

    // SGST percentage
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        11.2,
        $rowH,
        number_format((float)$row['sgst_per'], 2, '.', ',') . '%',
        1,
        0,
        'C'
    );
    $x += 11.2;

    // SGST amount
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        15.7,
        $rowH,
        number_format((float)$row['sgst_amt'], 2, '.', ','),
        1,
        0,
        'C'
    );
    $x += 15.7;

    // Final amount
    $pdf->SetXY($x, $y);
    $pdf->Cell(
        26,
        $rowH,
        number_format((float)$row['item_total'], 2, '.', ','),
        1,
        1,
        'C'
    );

    $y += $rowH;
    $sr++;
}

/* ================= AMOUNT IN WORDS ================= */

function amountInWords($number) {

    $number = (int)floor($number);

    $words = [
        0 => '',
        'One',
        'Two',
        'Three',
        'Four',
        'Five',
        'Six',
        'Seven',
        'Eight',
        'Nine',
        'Ten',
        'Eleven',
        'Twelve',
        'Thirteen',
        'Fourteen',
        'Fifteen',
        'Sixteen',
        'Seventeen',
        'Eighteen',
        'Nineteen',
        20 => 'Twenty',
        30 => 'Thirty',
        40 => 'Forty',
        50 => 'Fifty',
        60 => 'Sixty',
        70 => 'Seventy',
        80 => 'Eighty',
        90 => 'Ninety'
    ];

    if ($number == 0) {
        return 'Zero Rupees Only';
    }

    $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

    $str = [];
    $i = 0;

    while ($number > 0) {

        $divider = ($i == 1) ? 10 : 100;

        $num = $number % $divider;

        $number = floor($number / $divider);

        if ($num) {

            if ($num < 21) {

                $str[] = $words[$num] . ' ' . $digits[$i];

            } else {

                $str[] =
                    $words[floor($num / 10) * 10] . ' ' .
                    $words[$num % 10] . ' ' .
                    $digits[$i];
            }
        }

        $i++;
    }

    return trim(implode(' ', array_reverse($str))) . ' Rupees Only';
}

/* ================= GRAND TOTAL ================= */

$pdf->SetFont('Times', 'B', 9);

$pdf->SetXY(13.5, $y);

$pdf->Cell(159, 8, 'GRAND TOTAL', 1, 0, 'C');

$pdf->Cell(
    26,
    8,
    number_format($grandTotal, 2, '.', ','),
    1,
    1,
    'C'
);

/* ================= AMOUNT IN WORDS ================= */

$y += 8;

$pdf->SetFont('Times', 'B', 9.5);

$pdf->SetFillColor(198, 217, 241);

$pdf->SetXY(13.5, $y);

$pdf->Cell(
    185,
    7,
    'Total Amount (in words): ' . amountInWords($grandTotal),
    1,
    1,
    'C',
    true
);

$y = $pdf->GetY();

/* ================= BANK DETAILS ================= */

$y = $pdf->GetY() + 2;

$pdf->SetFont('Times', 'B', 8.5);

$pdf->SetFillColor(198, 217, 241);

$pdf->SetXY(13.6, $y);

$pdf->Cell(185, 7, 'Bank Details', 1, 1, 'C', true);

$pdf->SetFont('Times', '', 9);

$pdf->SetX(13.5);

$pdf->MultiCell(
    185,
    3.8,
    "Shul Ventures Pvt. Ltd
Bank A/C Number: 877020110000167        IFSC: BKID0008770
Bank Name: Bank of India                 Branch: Nagpur
GSTN: 27BEJPM5990K1ZR",
    1,
    'C'
);

/* ================= TERMS ================= */

$y = $pdf->GetY() + 1;

$pdf->SetFont('Times', 'BU', 8.5);

$pdf->SetXY(13.5, $y);

$pdf->Cell(185, 6, 'Terms and Conditions', 0, 1, 'L');

$pdf->SetFont('Times', '', 8.5);

$pdf->SetX(13.5);

$terms =
"1. Goods once sold will not be taken back.
2. Interest @18% P.A will be charged if payment is delayed.
3. If cheque is bounced, payment must be done via RTGS/NEFT with Rs.650 charges.
4. We are not responsible for loss or damage after goods are handed to carrier.";

$pdf->MultiCell(185, 3.5, $terms, 0);

/* ================= STAMP & SIGN ================= */

$y = $pdf->GetY() + 1;

$pdf->SetFont('Times', 'B', 10);

$pdf->SetXY(15, $y);

$pdf->Cell(80, 6, 'For Shul Ventures Pvt. Ltd.', 0, 1, 'L');

// Uncomment if stamp is required
/*
$stampPath = __DIR__ . '/img/stamp.png';

if (file_exists($stampPath)) {
    $pdf->Image($stampPath, 20, $y + 6, 28);
}
*/

$pdf->SetXY(15, $y + 22);

$pdf->Cell(80, 6, 'Authorized Signatory', 0, 1, 'L');

/* ================= SAVE PDF ================= */

$folder = __DIR__ . '/invoices';

if (!is_dir($folder)) {
    if (!mkdir($folder, 0775, true)) {
        die('Unable to create invoices folder');
    }
}

$safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $client_name);

$fileName = $safeName . '_' . $gst_invoice_no . '.pdf';

$fullPath = $folder . '/' . $fileName;

$relativePath = 'invoices/' . $fileName;

// Save PDF to server
$pdf->Output('F', $fullPath);

if (!file_exists($fullPath)) {
    die('PDF file could not be saved');
}

/* ================= UPDATE SOLAR INVOICE TABLE ================= */

$stmt = mysqli_prepare($conn, "
    UPDATE tbl_solar_invoice
    SET gst_invoice_no = ?,
        pdf_file = ?
    WHERE client_id = ?
      AND created_at = ?
");

if (!$stmt) {
    die('PDF database update error: ' . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    'ssis',
    $gst_invoice_no,
    $relativePath,
    $client_id,
    $created_at
);

if (!mysqli_stmt_execute($stmt)) {
    die('PDF path update failed: ' . mysqli_stmt_error($stmt));
}

mysqli_stmt_close($stmt);

/* ================= OUTPUT PDF TO BROWSER ================= */

$pdf->Output('I', $fileName);

exit;
