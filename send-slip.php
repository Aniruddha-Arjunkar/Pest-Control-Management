<?php
require_once 'db_connect.php';
require_once 'dompdf/autoload.inc.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Convert number to words
function numberToWords($num)
{
    $num = (int)$num;
    if ($num === 0) return 'Zero';

    $ones = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen',
        'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
    ];

    $tens = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty',
        'Sixty', 'Seventy', 'Eighty', 'Ninety'
    ];

    $levels = ['', 'Thousand', 'Lakh', 'Crore'];

    $words = '';
    $level = 0;

    while ($num > 0) {
        $divider = ($level === 0) ? 1000 : 100;
        $part = $num % $divider;
        $num = floor($num / $divider);

        if ($part > 0) {
            $segment = '';

            if ($part > 99) {
                $segment .= $ones[floor($part / 100)] . ' Hundred ';
                $part %= 100;
            }

            if ($part > 19) {
                $segment .= $tens[floor($part / 10)] . ' ';
                $segment .= $ones[$part % 10] . ' ';
            } else {
                $segment .= $ones[$part] . ' ';
            }

            if (!empty($levels[$level])) {
                $segment .= $levels[$level] . ' ';
            }

            $words = $segment . $words;
        }

        $level++;
    }

    return trim($words);
}


// Inputs
$employee_id = $_POST['employee_id'];
$month = $_POST['month']; // YYYY-MM

$month_name = date('M', strtotime($month . '-01')); // Jan, Feb, Mar
$year_short = date('y', strtotime($month . '-01')); // 25, 26



// Fetch employee & salary data
$emp_sql = "SELECT * FROM tbl_employee WHERE Id = '$employee_id'";
$emp = mysqli_fetch_assoc(mysqli_query($conn, $emp_sql));

$sal_sql = "SELECT * FROM tbl_salary WHERE employee_id = '$employee_id' AND month = '$month'";
$sal = mysqli_fetch_assoc(mysqli_query($conn, $sal_sql));

if (!$emp || !$sal) {
    echo "❌ Employee or salary data not found.";
    exit;
}

// Common Variables
$payment_type = strtolower(trim($sal['payment_type']));
$logo_path = 'https://shulventures.com/img/shulventures.jpg';
$uan_line = '';

/* ---------------- ATTENDANCE ---------------- */
$attStmt = $conn->prepare("
    SELECT attendance, COUNT(*) as total
    FROM tbl_emp_attendance
    WHERE employee_id = ?
    AND attendance_month = ?
    AND attendance_year = ?
    GROUP BY attendance
");

$attStmt->bind_param("iss", $employee_id, $month_name, $year_short);
$attStmt->execute();
$attRes = $attStmt->get_result();

$full_days = 0;
$half_days = 0;
$total_leaves = 0;

while ($row = $attRes->fetch_assoc()) {
    if ($row['attendance'] === 'P') {
        $full_days = $row['total'];
    }
    if ($row['attendance'] === 'HD') {
        $half_days = $row['total'];
    }
    if ($row['attendance'] === 'A') {
        $total_leaves = $row['total'];
    }
}

// Final working days (HD = 0.5)
$working_days = $full_days + ($half_days * 0.5);



$total_days_in_month = cal_days_in_month(
    CAL_GREGORIAN,
    date('m', strtotime($month . '-01')), // numeric month
    date('Y', strtotime($month . '-01'))  // 4-digit year
);



// 🧾 Generate PDF HTML based on payment type
if ($payment_type === 'salary') {

    // Salary Calculation
    $total_earnings =
    $sal['basic'] +
    $sal['hra'] +
    $sal['conveyance'] +
    $sal['food_allowance'] +
    $sal['performance_incentive'] +
    $sal['expense_reimbursement'];

// Initialize deductions
    $total_deductions =
        $sal['profession_tax'] +
        $sal['advance'];

    $pf_deduction = 0;
    if (!empty($emp['pf_no']) && !empty($emp['uan_no']) && !empty($sal['pf'])) {
        $pf_deduction = $sal['pf'];
        $total_deductions += $pf_deduction;

        $uan_line = "
        <tr>
            <td><strong>PF No.:</strong> {$emp['pf_no']}</td>
            <td><strong>UAN No.:</strong> {$emp['uan_no']}</td>
        </tr>";
    }

    $net_salary = $total_earnings - $total_deductions;

    $performanceRow = '';
if ($sal['performance_incentive'] > 0) {
    $performanceRow = '
    <tr>
        <td>Performance Incentives</td>
        <td>' . number_format($sal['performance_incentive'], 2) . '</td>
        <td></td><td></td>
    </tr>';
}

$expenseRow = '';
if ($sal['expense_reimbursement'] > 0) {
    $expenseRow = '
    <tr>
        <td>Expense Reimbursement</td>
        <td>' . number_format($sal['expense_reimbursement'], 2) . '</td>
        <td></td><td></td>
    </tr>';
}


    $html = '
    <div style="font-family: Arial, sans-serif; font-size: 13px;">
    <table width="100%" style="border: 2px solid #000; border-collapse: collapse;">
    <tr>
    <td colspan="2" style="padding: 10px; text-align: center; border-bottom: 1px solid #000;">
    <img src="' . $logo_path . '" style="max-height: 60px;"><br>
    <span>Plot No. 21/A, Jiddi App., 2nd floor, 3rd Bus Stop, Gopal Nagar, Nagpur - 22</span><br>
    <h3 style="margin: 5px 0;">Salary Slip for the Month of ' . date('F Y', strtotime($month)) . '</h3>
    </td>
    </tr>

    <tr>
    <td colspan="2" style="padding: 10px; border-bottom: 1px solid #000;">
    <table width="100%" style="font-size: 13px;">
    <tr><td><strong>Name of the Employee:</strong> Mr. ' . $emp['cust_name'] . '</td><td><strong>Employee ID:</strong> ' . $emp['emp_id'] . '</td></tr>
    <tr><td><strong>PAN No.:</strong> ' . $emp['pan_card_no'] . '</td><td><strong>Designation:</strong> ' . $emp['designation'] . '</td></tr>
    <tr><td><strong>Bank Name:</strong> ' . $emp['bank_name'] . '</td><td><strong>Account No.:</strong> ' . $emp['account_no'] . '</td></tr>
    ' . $uan_line . '
    <tr><td><strong>Total Days in Month:</strong> ' . $total_days_in_month . '</td><td><strong>Working Days:</strong> ' . number_format($working_days, 1) . '
</td></tr>

    
    </table>
    </td>
    </tr>

    <tr>
    <td colspan="2">
    <table width="100%" border="1" cellpadding="8" style="border-collapse: collapse; font-size: 13px;">
    <thead style="background-color:#f2f2f2;">
    <tr><th>Earnings</th><th>Amount (₹)</th><th>Deductions</th><th>Amount (₹)</th></tr>
    </thead>
    <tbody>
    <tr>
    <td>Basic</td>
    <td>' . number_format($sal['basic'], 2) . '</td>
    <td>' . ($sal['advance'] > 0 ? 'Advance' : '') . '</td>
    <td>' . ($sal['advance'] > 0 ? number_format($sal['advance'], 2) : '') . '</td>
    
</tr>

<tr>
    <td>HRA</td>
    <td>' . number_format($sal['hra'], 2) . '</td>
    <td>' . ($sal['profession_tax'] > 0 ? 'Professional Tax' : '') . '</td>
    <td>' . ($sal['profession_tax'] > 0 ? number_format($sal['profession_tax'], 2) : '') . '</td>
</tr>

    <tr><td>Conveyance</td><td>' . number_format($sal['conveyance'], 2) . '</td><td></td><td></td></tr>
    <tr><td>Food Allowance</td><td>' . number_format($sal['food_allowance'], 2) . '</td><td></td><td></td></tr>
    '.$performanceRow.'
'.$expenseRow.'
    <tr><th>Total Earnings</th><td>' . number_format($total_earnings, 2) . '</td><th>Total Deductions</th><td>' . number_format($total_deductions, 2) . '</td></tr>
    <tr><th colspan="3" style="text-align:right;">Net Salary</th><td><strong>' . number_format($net_salary, 2) . '</strong></td></tr>
    <tr><td colspan="4"><strong>In Words:</strong> Rupees ' . ucwords(numberToWords($net_salary)) . ' Only</td></tr>
    </tbody>
    </table>
    </td>
    </tr>

    <tr><td colspan="2" style="padding: 10px;text-align: center; font-size: 12px;">
    <em>This is a system-generated payslip and does not require signature.</em>
    </td></tr>
    </table>
    </div>';
    
    
}
/* ---------------- PER DAY SLIP LOGIC ---------------- */
elseif ($payment_type === 'perday') {

    $empPack = mysqli_fetch_assoc(mysqli_query($conn,"SELECT package, employee_type FROM tbl_employee WHERE Id='$employee_id'"));

    $package = floatval($empPack['package']);
    $employee_type = $empPack['employee_type'];

    if ($employee_type === 'Per Day') {
        $rate_per_day = $package;
    } else {
        $rate_per_day = $package / $total_days_in_month;
    }

    $amount   = $working_days * $rate_per_day;
    $incentive      = $sal['performance_incentive'] ?? 0;
    $expense        = $sal['expense_reimbursement'] ?? 0;
    $advance        = $sal['advance'] ?? 0;
    $tax            = $sal['profession_tax'] ?? 0;

    $total_earnings = $amount + $incentive + $expense;
    $total_deductions = $advance + $tax;
    $net_salary = $total_earnings - $total_deductions;

    $html = '
       <div style="font-family: Arial, sans-serif; font-size: 13px;">
    <table width="100%" style="border: 2px solid #000; border-collapse: collapse;">
    <tr>
    <td colspan="2" style="padding: 10px; text-align: center; border-bottom: 1px solid #000;">
    <img src="' . $logo_path . '" style="max-height: 60px;"><br>
    <span>Plot No. 21/A, Jiddi App., 2nd floor, 3rd Bus Stop, Gopal Nagar, Nagpur - 22</span><br>
    <h3 style="margin: 5px 0;">Salary Slip for the Month of ' . date('F Y', strtotime($month)) . '</h3>
    </td>
    </tr>

    <tr>
    <td colspan="2" style="padding: 10px; border-bottom: 1px solid #000;">
    <table width="100%" style="font-size: 13px;">
    <tr><td><strong>Name of the Employee:</strong>' . $emp['cust_name'] . '</td><td><strong>Employee ID:</strong> ' . $emp['emp_id'] . '</td></tr>
    <tr><td><strong>PAN No.:</strong> ' . $emp['pan_card_no'] . '</td><td><strong>Designation:</strong> ' . $emp['designation'] . '</td></tr>
    <tr><td><strong>Bank Name:</strong> ' . $emp['bank_name'] . '</td><td><strong>Account No.:</strong> ' . $emp['account_no'] . '</td></tr>
    ' . $uan_line . '
    <tr><td><strong>Total Days in Month:</strong> ' . $total_days_in_month . '</td><td><strong>Working Days:</strong> ' . number_format($working_days, 1) . '
</td></tr>

    
    <tr><td colspan="2">
    <table width="100%" border="1" cellpadding="8" style="border-collapse:collapse;">
    <tr><th>Earnings</th><th>Amount (₹)</th><th>Deductions</th><th>Amount (₹)</th></tr>';

   if ($advance > 0) {
    $html .= '<tr>
        <td style="text-align:center;">Rate Per Day</td>
        <td style="text-align:center;">'.number_format($rate_per_day, 2).'</td>
        <td style="text-align:center;">Advance</td>
        <td style="text-align:center;">'.number_format($advance, 2).'</td>
    </tr>';
} else {
    $html .= '<tr>
        <td style="text-align:center;">Rate Per Day</td>
        <td style="text-align:center;">'.number_format($rate_per_day, 2).'</td>
        <td style="text-align:center;"></td>
        <td style="text-align:center;"></td>
    </tr>';
}


$html .= '
<tr>
    <th>Total (Days × Rate)</th>
    <th>' . number_format($amount, 2) . '</th>
    <th></th>
    <th></th>
</tr>';


    if ($tax > 0) {
        $html .= '<tr><td></td><td></td><td>Professional Tax</td><td>'.number_format($tax,2).'</td></tr>';
    }
    if ($incentive > 0) {
        $html .= '<tr><td>Performance Incentive</td><td>'.number_format($incentive,2).'</td><td></td><td></td></tr>';
    }
    if ($expense > 0) {
        $html .= '<tr><td>Expense Reimbursement</td><td>'.number_format($expense,2).'</td><td></td><td></td></tr>';
    }

    $html .= '
    <tr><th>Total Earnings</th><td>'.number_format($total_earnings,2).'</td><th>Total Deductions</th><td>'.number_format($total_deductions,2).'</td></tr>
    <tr><th colspan="3">Net Salary</th><td><strong>'.number_format($net_salary,2).'</strong></td></tr>
    <tr><td colspan="4"><strong>In Words:</strong> Rupees '.ucwords(numberToWords($net_salary)).' Only</td></tr>
    </table></td></tr>
    <tr><td colspan="2" style="padding: 10px;text-align: center; font-size: 12px;">
    <em>This is a system-generated payslip and does not require signature.</em>
    </td></tr>
    </table></div>';



} elseif ($payment_type === 'stipend') {

    $stipend_amount = !empty($sal['amount']) ? $sal['amount'] : $sal['basic'];

    $html = '
    <div style="font-family: Arial, sans-serif; font-size: 13px;">
    <table width="100%" style="border: 2px solid #000; border-collapse: collapse;">
    <tr>
    <td colspan="2" style="padding: 10px; text-align: center; border-bottom: 1px solid #000;">
    <img src="' . $logo_path . '" style="max-height: 60px;"><br>
    <span>Plot No. 21/A, Jiddi App., 2nd floor, 3rd Bus Stop, Gopal Nagar, Nagpur - 22</span><br>
    <h3 style="margin: 5px 0;">Stipend Slip for the Month of ' . date('F Y', strtotime($month)) . '</h3>
    </td>
    </tr>

    <tr>
    <td colspan="2" style="padding: 10px; border-bottom: 1px solid #000;">
    <table width="100%" style="font-size: 13px;">
    <tr><td><strong>Name of the Employee:</strong> Mr. ' . $emp['cust_name'] . '</td><td><strong>Employee ID:</strong> ' . $emp['emp_id'] . '</td></tr>
    <tr><td><strong>Designation:</strong> ' . $emp['designation'] . '</td><td><strong>Account No.:</strong> ' . $emp['account_no'] . '</td></tr>
    <tr><td><strong>Total Days in Month:</strong> ' . $total_days_in_month . '</td><td><strong>Working Days:</strong> ' . $working_days . '</td></tr>
    </table>
    </td>
    </tr>

    <tr>
    <td colspan="2">
    <table width="100%" border="1" cellpadding="8" style="border-collapse: collapse;">
    <tr><th>Stipend Amount</th><td>' . number_format($stipend_amount, 2) . '</td></tr>
    <tr><th>Date</th><td>' . date('d M Y', strtotime($sal['month'].'-01')) . '</td></tr>
    <tr><td colspan="2"><strong>In Words:</strong> Rupees ' . ucwords(numberToWords($stipend_amount)) . ' Only</td></tr>
    </table>

    </td>
    </tr>
    <tr><td colspan="2" style="padding: 10px;text-align: center; font-size: 12px;">
    <em>This is a system-generated payslip and does not require signature.</em>
    </td></tr>
    </table>
    </div>';

} else {
    echo "⚠️ Slip not generated for this payment type.";
    exit;
}

// Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfOutput = $dompdf->output();
$filename = 'SalarySlip_' . $emp['cust_name'] . '_' . date('F_Y', strtotime($month)) . '.pdf';

// ===== DOWNLOAD ONLY (NO EMAIL) =====
if (isset($_POST['download']) && $_POST['download'] == 1) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfOutput));
    echo $pdfOutput;
    exit;
}

// Send Email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'mail.shulventures.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'hr@shulventures.com';
    $mail->Password = 'Shul@2018';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    $mail->setFrom('hr@shulventures.com', 'HR Department');
    $mail->addAddress($emp['cust_email'], $emp['cust_name']);
    
    // Add CC
    $mail->addCC('atul@shulventures.com', 'Atul');
    
    $mail->isHTML(true);
    $mail->Subject = 'Salary Slip - ' . date('F Y', strtotime($month));
    $mail->Body = 'Dear ' . $emp['cust_name'] . ',<br><br>Please find enclosed your payslip for ' . date('F Y', strtotime($month)) . ' in PDF format.<br><br>Thank you.<br><br>Best Regards,<br>HR Team<br>Shul Ventures Pvt. Ltd.';

    $mail->addStringAttachment($pdfOutput, $filename);
    $mail->send();
    
    
    header("Location: view-salary.php?employee_id=" . $employee_id . "&status=success");
    exit;

} catch (Exception $e) {

    header("Location: view-salary.php?employee_id=" . $employee_id . "&status=error");
    exit;
}

//     echo "✅ Salary slip sent successfully to " . $emp['cust_email'];
// } catch (Exception $e) {
//     echo "❌ Email Error: " . $mail->ErrorInfo;
// }
?>
