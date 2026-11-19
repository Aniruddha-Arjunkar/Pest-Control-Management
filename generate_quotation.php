<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
require 'vendor/autoload.php';
use setasign\Fpdi\Fpdi;

// Get inquiry/customer
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM tbl_inquiry WHERE Id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if (!$customer) die("Customer not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $services  = $_POST['service'] ?? [];
    $pests     = $_POST['pest'] ?? [];
    $chemicals = $_POST['chemical'] ?? [];
    $warranties= $_POST['warranty'] ?? [];
    $areas     = $_POST['area'] ?? [];
    $charges   = $_POST['charges'] ?? [];
    $discounts = $_POST['discount'] ?? [];
    $totals    = $_POST['total'] ?? [];
    $paymentTerms  = $_POST['payment_terms'] ?? '';
    $mergeCharges = isset($_POST['merge_charges']);
    $createdBy = $_SESSION['email'];


     /* =================================================
       QUOTATION DATE

       Manual date selected:
       Use selected date

       Blank:
       Use today's date
    ================================================= */

    $quotationDate =
        trim($_POST['quotation_date'] ?? '');

    if (!empty($quotationDate)) {

        // Validate date
        $dateObj =
            DateTime::createFromFormat(
                'Y-m-d',
                $quotationDate
            );

        if (
            !$dateObj ||
            $dateObj->format('Y-m-d') !== $quotationDate
        ) {
            die("Invalid quotation date.");
        }

        /*
         * Manual date + current time
         */
        $createdAt =
            $quotationDate . ' ' . date('H:i:s');

    } else {

        /*
         * No manual date:
         * Current date + current time
         */
        $createdAt =
            date('Y-m-d H:i:s');
    }


//second logic 

    $quotationDate = $_POST['quotation_date'] ?? date('Y-m-d');

if (!empty($quotationDate)) {
    $dateObj = DateTime::createFromFormat('Y-m-d', $quotationDate);

    if (
        !$dateObj ||
        $dateObj->format('Y-m-d') !== $quotationDate
    ) {
        die("Invalid quotation date.");
    }

    $createdAt = $quotationDate . ' ' . date('H:i:s');
} else {
    $createdAt = date('Y-m-d H:i:s');
}

//end 

    foreach ($totals as $k => $v) { $totals[$k] = (float) $v; }
    foreach ($charges as $k => $v) { $charges[$k] = (float) $v; }
    foreach ($discounts as $k => $v) { $discounts[$k] = (float) $v; }

    $grandTotal = array_sum($totals);
    $sumDiscount = array_sum($discounts);

    function numberToWords($num) {
    if ($num == 0) return 'Zero';

    $ones = ['', 'One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten',
             'Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['', '', 'Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];

    $result = '';

    if ($num >= 10000000) { // Crores
        $crores = intval($num / 10000000);
        $result .= numberToWords($crores) . ' Crore ';
        $num %= 10000000;
    }
    if ($num >= 100000) { // Lakhs
        $lakhs = intval($num / 100000);
        $result .= numberToWords($lakhs) . ' Lakh ';
        $num %= 100000;
    }
    if ($num >= 1000) { // Thousands
        $thousands = intval($num / 1000);
        $result .= numberToWords($thousands) . ' Thousand ';
        $num %= 1000;
    }
    if ($num >= 100) { // Hundreds
        $hundreds = intval($num / 100);
        $result .= numberToWords($hundreds) . ' Hundred ';
        $num %= 100;
    }
    if ($num >= 20) { // Tens
        $result .= $tens[intval($num / 10)] . ' ';
        $num %= 10;
    }
    if ($num > 0) { // Ones
        $result .= $ones[$num] . ' ';
    }

    return trim($result);
}

    $inWords = "Rupees " . numberToWords((int)$grandTotal) . " Only";

    // PDF start
    $templateMain = __DIR__ . '/templates/Quotation_template (2).pdf';
    if (!file_exists($templateMain)) die("Template not found.");

    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($templateMain);
    $tplIdx = $pdf->importPage(1);
    $pdf->AddPage();
    $pdf->useTemplate($tplIdx, 0, 0, 210);

    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetTextColor(0,0,0);

    // Date & Ref
    $pdf->SetXY(15, 28);
    $pdf->Write(6, "Ref: Shul/Pest/25-26");

     /*
     * IMPORTANT:
     * PDF date comes from manual date
     * OR current date if blank
     */

    $displayDate =
        date(
            'd/m/Y',
            strtotime($createdAt)
        );


    $pdf->SetXY(160, 28);

    $pdf->Write(
        6,
        "Date: " . $displayDate
    );


    //second logic 
    $displayDate = date('d/m/Y', strtotime($quotationDate));

$pdf->SetXY(160, 28);
$pdf->Write(6, "Date: " . $displayDate);
//end
     

    // Customer
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY(15, 38);
    $pdf->Write(6, strtoupper($customer['cust_name']));
    $pdf->SetXY(15, 45);
    $pdf->MultiCell(120, 6, ucwords($customer['cust_address']), 0, 'L');
    $currentY = $pdf->GetY() + 1;
    $pdf->SetXY(15, $currentY);
    $pdf->Write(6, "Mobile: " . $customer['cust_phone']);

    // Subject
    $pdf->SetXY(15, 64);
    $pdf->Write(6, "Sub: Quotation for " . implode(", ", $services));
    $pdf->SetXY(15, 71);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->MultiCell(180, 6, "Dear Sir,\n\nWe reference our visit and discussion with you and we propose our offer as below for ". implode(", ", $services)."  the suggested premises.");

    // Table background
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect(12, 101, 190, 45, 'F');

    $lineHeight = 6;
    $startX = 12;
    $y = 101;

    // Utility function
    function drawTableRow($pdf, $x, $y, $rowData, $colWidths, $lineHeight = 6) {
        $pdf->SetFont('Helvetica', '', 10);
        $cellHeights = [];
        foreach ($rowData as $j => $txt) {
            $nb = max(1, ceil($pdf->GetStringWidth($txt) / ($colWidths[$j] - 2)));
            $cellHeights[$j] = $nb * $lineHeight;
        }
        $maxHeight = max($cellHeights);
        $currentX = $x;
        foreach ($rowData as $j => $txt) {
            $pdf->Rect($currentX, $y, $colWidths[$j], $maxHeight);
            $pdf->SetXY($currentX, $y);
            $pdf->MultiCell($colWidths[$j], $lineHeight, $txt, 0, 'C');
            $currentX += $colWidths[$j];
            $pdf->SetXY($currentX, $y);
        }
        return $maxHeight;
    }

    // Split services
    // $plyServices = [];
    $otherServices = [];
    foreach ($services as $i => $srv) {
        // if (stripos($srv, 'ply sheet') !== false) {
        //     $plyServices[] = $i;
        // } else {
            $otherServices[] = $i;
        }
    // }

    // ==============================
    //   PLY SHEET TABLE
    // ==============================
    // foreach ($plyServices as $i) {
    //      // Header row
    // $pdf->SetFillColor(255, 204, 153);
    // $pdf->SetTextColor(0);
    // $pdf->SetFont('Helvetica', 'B', 11);
    //     $headers = ["Service Type", "Pest Covered", "Chemical", "Warranty", "Amount"];
    //     $colWidths = [43, 30, 42, 20, 53];
    //     $rowData = [
    //         $services[$i],
    //         $pests[$i],
    //         $chemicals[$i],
    //         $warranties[$i],
    //         number_format($charges[$i], 2)
    //     ];
    //     $x = $startX;
    //     foreach ($headers as $j => $header) {
    //         $pdf->SetXY($x, $y);
    //         $pdf->Cell($colWidths[$j], $lineHeight, $header, 1, 0, 'C', true);
    //         $x += $colWidths[$j];
    //     }
    //     $y += $lineHeight;
    //     $rowHeight = drawTableRow($pdf, $startX, $y, $rowData, $colWidths, $lineHeight);
    //     $y += $rowHeight + 6;
    // }

    // // Totals logic
    // $totalPlySheet = 0;
    $totalOther = 0;
    $sumDiscount = 0;

    // foreach ($plyServices as $i) {
    //     $totalPlySheet += (float)$charges[$i];
    // }
    foreach ($otherServices as $i) {
        $totalOther += (float)$totals[$i];
        $sumDiscount += (float)$discounts[$i];
    }

    if (count($otherServices) > 0) {
        $grandTotal = $totalOther;
    // } elseif (count($plyServices) > 0) {
    //     $grandTotal = $totalPlySheet;
    } else {
        $grandTotal = 0;
    }

    // ========================================
    //   OTHER SERVICES TABLE (FIXED)
    // ========================================
    if (count($otherServices) > 0) {

        // Header print only once
         // Header row
    $pdf->SetFillColor(255, 204, 153);
    $pdf->SetTextColor(0);
    $pdf->SetFont('Helvetica', 'B', 10);
        $headers = ["Service Type", "Pest Covered", "Chemical", "Warranty", "Area", "Service Charges"];
        $colWidths = [34, 32, 32, 25, 31, 34];

        $x = $startX;
        $pdf->SetFont('Helvetica', 'B', 11);
        foreach ($headers as $j => $header) {
            $pdf->SetXY($x, $y);
            $pdf->Cell($colWidths[$j], $lineHeight, $header, 1, 0, 'C', true);
            $x += $colWidths[$j];
        }
        $y += $lineHeight;
        
        
        // ================= MERGE LOGIC START =================
// ================= MERGE LOGIC START =================
if ($mergeCharges) {

    $yStart = $y;
    $totalMerged = 0;
    $sumDiscount = 0;

    // PRINT ROWS USING SAME AUTO HEIGHT FUNCTION
    foreach ($otherServices as $i) {

        $rowData = [
            $services[$i],
            $pests[$i],
            $chemicals[$i],
            $warranties[$i],
            $areas[$i]
        ];

        // draw only first 5 columns (NO service charge column)
        $rowHeight = drawTableRow(
            $pdf,
            $startX,
            $y,
            $rowData,
            array_slice($colWidths, 0, 5),
            $lineHeight
        );

        $totalMerged += (float)$charges[$i];   // original charges
        $sumDiscount += (float)$discounts[$i];
        $y += $rowHeight;
    }

    // ===== DRAW ONE MERGED SERVICE CHARGES BOX =====
    $totalHeight = $y - $yStart;

    $xCharges = $startX + array_sum(array_slice($colWidths, 0, 5));

    // Border box
    $pdf->Rect($xCharges, $yStart, $colWidths[5], $totalHeight);

    // Center total amount vertically + horizontally
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetXY($xCharges, $yStart);

    $pdf->MultiCell(
        $colWidths[5],
        $totalHeight,
        number_format($totalMerged, 2),
        0,
        'C'
    );

    $grandTotal = $totalMerged - $sumDiscount;


} else {


        // Draw rows
        foreach ($otherServices as $i) {
            $rowData = [
                $services[$i],
                $pests[$i],
                $chemicals[$i],
                $warranties[$i],
                $areas[$i],
                number_format($charges[$i], 2)
            ];
            $rowHeight = drawTableRow($pdf, $startX, $y, $rowData, $colWidths, $lineHeight);
            $y += $rowHeight;
        }
        
}

        // Discount Row
        if ($sumDiscount > 0) {
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->SetXY($startX, $y);
            $pdf->Cell(array_sum(array_slice($colWidths, 0, 5)), $lineHeight, "Special Discount", 1, 0, 'C');
            $pdf->Cell($colWidths[5], $lineHeight, number_format($sumDiscount, 2), 1, 1, 'C');
            $y += $lineHeight;
        }

        // Total Row
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetXY($startX, $y);
        $pdf->Cell(array_sum(array_slice($colWidths, 0, 5)), $lineHeight, "Total Service Charges", 1, 0, 'C');
        $pdf->Cell($colWidths[5], $lineHeight, number_format($grandTotal, 2), 1, 1, 'C');
        $y += $lineHeight;

        // In Words
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetXY($startX, $y);
        $pdf->Cell(array_sum($colWidths), $lineHeight, "In Words: Rupees " . numberToWords((int)$grandTotal) . " Only", 1, 1, 'C', true);
        $y += $lineHeight;
    }
    
    // ------------------------------
    // Payment Terms
    if (!empty($paymentTerms)) {
        $y += 10;
        $pdf->SetXY($startX, $y);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, "Payment Terms:", 0, 1);
        $y += 6;
        $pdf->SetXY($startX, $y);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(0, 6, $paymentTerms, 0, 'L');
        $y += 6;
    }



    // Append service-specific PDFs
    foreach ($services as $srv) {
        $srvFile = '';
        $srvLower = strtolower($srv);

        if (strpos($srvLower, 'column') !== false && strpos($srvLower, 'plinth') !== false) {
            $srvFile = __DIR__ . '/templates/anti_termite_pre_column_plinth.pdf';
        } elseif (strpos($srvLower, 'plinth') !== false && strpos($srvLower, 'drilling') !== false) {
            $srvFile = __DIR__ . '/templates/anti_termite_plinth_drillng.pdf';
        } elseif (stripos($srv, 'bed') !== false) {
            $srvFile = __DIR__ . '/templates/bedbug_control_template.pdf';
        } elseif (stripos($srv, 'mosquito') !== false) {
            $srvFile = __DIR__ . '/templates/mosquit_control.pdf';
        } elseif (stripos($srv, 'plinth') !== false) {
            $srvFile = __DIR__ . '/templates/anti_termite_pre_plinth.pdf';
        } elseif (stripos($srv, 'column') !== false) {
            $srvFile = __DIR__ . '/templates/anti_termite_template_pre_coloumn.pdf';
        } elseif (stripos($srv, '3 step') !== false) {
            $srvFile = __DIR__ . '/templates/anti_termite_3_step.pdf';
        } elseif (stripos($srv, 'general') !== false) {
            $srvFile = __DIR__ . '/templates/general_pest_control_template.pdf';
        } elseif (stripos($srv, 'drilling') !== false) {
            $srvFile = __DIR__ . '/templates/anti_termite_driling.pdf';
        } elseif (stripos($srv, 'anti') !== false) {
            $srvFile = __DIR__ . '/templates/anti_termite_template.pdf';
        } elseif (stripos($srv, 'rodent') !== false) {
            $srvFile = __DIR__ . '/templates/rodent_template.pdf';
        }
         // === NEW ADDITIONS ===
    elseif (stripos($srv, 'prophylactic') !== false) {
        $srvFile = __DIR__ . '/templates/prophylactic.pdf';
    } elseif (stripos($srv, 'cockroach') !== false) {
        $srvFile = __DIR__ . '/templates/cockroach_control.pdf';
    }

        if ($srvFile && file_exists($srvFile)) {
            $count = $pdf->setSourceFile($srvFile);
            for ($p = 1; $p <= $count; $p++) {
                $tpl = $pdf->importPage($p);
                $pdf->AddPage();
                $pdf->useTemplate($tpl, 0, 0, 210);
            }
        }
    }

    // Save PDF
    $outDir = __DIR__ . '/quotations/';
    if (!is_dir($outDir)) mkdir($outDir, 0777, true);
    $cleanName = preg_replace('/[^A-Za-z0-9._-]/', '_', $customer['cust_name']);
    $fileName = 'Quotation_' . $cleanName . '_' . date('Ymd_His') . '.pdf';

    $outPath = $outDir . $fileName;
    $pdf->Output($outPath, 'F');

    // Save record
    $fileUrl = 'quotations/' . $fileName;
    $serviceLabel = (count($services) > 1) ? 'Multiple' : $services[0];
   $stmt = $conn->prepare(
    "INSERT INTO tbl_generated_quotations
    (
        inquiry_id,
        service,
        file_path,
        total,
        created_at,
        created_by
    )
    VALUES (?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "issdss",
    $id,
    $serviceLabel,
    $fileUrl,
    $grandTotal,
    $createdAt,
    $createdBy
);

$stmt->execute();


    header('Location: view_quotations.php');
    exit;
}
?>
