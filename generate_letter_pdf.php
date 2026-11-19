<?php

ob_start();
session_start();

// ==========================================
// LOAD FPDF / FPDI
// ==========================================

require __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;


// ==========================================
// NUMBER TO WORDS
// ==========================================

function numberToWords($num)
{
    if (!class_exists('NumberFormatter')) {
        return $num;
    }

    if ($num === null || $num === '' || !is_numeric($num)) {
        return '';
    }

    $num = (float)$num;

    $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);

    return ucfirst($f->format($num));
}


// ==========================================
// FORMAT DATE
// ==========================================

function formatDate($dateStr)
{
    if (
        empty($dateStr) ||
        $dateStr == '0000-00-00' ||
        $dateStr == '0000-00-00 00:00:00'
    ) {
        return '';
    }

    $timestamp = strtotime($dateStr);

    if ($timestamp === false) {
        return '';
    }

    return date('jS F, Y', $timestamp);
}


// ==========================================
// CHECK VALID DATE
// ==========================================

function safeDate($dateStr)
{
    if (
        empty($dateStr) ||
        $dateStr == '0000-00-00' ||
        $dateStr == '0000-00-00 00:00:00'
    ) {
        return false;
    }

    return strtotime($dateStr) !== false;
}


// ==========================================
// PDF CLASS
// ==========================================

class PDF extends Fpdi
{
    function Header()
    {
        // Letterhead
        if (file_exists('assets/letterhead.png')) {
            $this->Image('assets/letterhead.png', 0, 0, 210);
        }

        // Space below letterhead
        $this->Ln(30);
    }

    function Footer()
    {
        $this->SetY(-18);

        $this->SetFont('Arial', 'I', 9);

        $this->Cell(
            0,
            8,
            'Page ' . $this->PageNo() . ' / {nb}',
            0,
            0,
            'C'
        );
    }
}


// ==========================================
// SESSION CHECK
// ==========================================

if (!isset($_SESSION['email'])) {

    $_SESSION['msg'] = "You must log in first";

    header('location:login.php');

    exit();
}


// ==========================================
// DATABASE CONNECTION
// ==========================================

require_once 'db_connect.php';


// ==========================================
// GET POST VALUES SAFELY
// ==========================================

$employee_id = isset($_POST['employee_id'])
    ? intval($_POST['employee_id'])
    : 0;

$letter_type = isset($_POST['letter_type'])
    ? trim($_POST['letter_type'])
    : '';


// ==========================================
// VALIDATE INPUT
// ==========================================

if ($employee_id <= 0) {
    die("Employee ID is missing.");
}

if (empty($letter_type)) {
    die("Letter type is missing.");
}


// ==========================================
// FETCH EMPLOYEE
// ==========================================

$sql = "SELECT * FROM tbl_employee WHERE Id = ?";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database prepare error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $employee_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$employee = mysqli_fetch_assoc($result);


// ==========================================
// EMPLOYEE CHECK
// ==========================================

if (!$employee) {
    die("Employee not found.");
}


// ==========================================
// TITLE
// ==========================================

$title = !empty($employee['title'])
    ? $employee['title']
    : 'Ms.';


// ==========================================
// SALARY WORDS
// ==========================================

if (
    isset($employee['package']) &&
    $employee['package'] !== ''
) {

    $employee['salary_words'] =
        numberToWords($employee['package']);

} else {

    $employee['salary_words'] = '';
}


// ==========================================
// CREATE PDF
// ==========================================

$pdf = new PDF();

$pdf->AliasNbPages();

$pdf->SetAutoPageBreak(
    true,
    30
);

$pdf->AddPage();

$pdf->SetFont(
    'Arial',
    '',
    12
);

$pdf->SetXY(
    20,
    25
);


// ==========================================
// LETTER DATE
// ==========================================

switch ($letter_type) {

    case 'relieving':

        if (safeDate($employee['release_date'])) {

            $date =
                formatDate($employee['release_date']);

        } else {

            $date =
                date('jS F, Y');
        }

        break;


    case 'experience':

        if (safeDate($employee['release_date'])) {

            $date =
                formatDate($employee['release_date']);

        } else {

            $date =
                date('jS F, Y');
        }

        break;


    case 'internship':

        if (safeDate($employee['release_date'])) {

            $date =
                formatDate($employee['release_date']);

        } else {

            $date =
                date('jS F, Y');
        }

        break;


    case 'offer':

        if (safeDate($employee['date'])) {

            $date =
                formatDate($employee['date']);

        } else {

            $date =
                date('jS F, Y');
        }

        break;


    case 'appointment':

        if (safeDate($employee['date'])) {

            $date = date(
                'jS F, Y',
                strtotime(
                    $employee['date'] . ' +7 days'
                )
            );

        } else {

            $date =
                date('jS F, Y');
        }

        break;


    default:

        $date =
            date('jS F, Y');

        break;
}


// ==========================================
// REFERENCE NUMBER
// ==========================================

$ref =
    "SHUL/EMP/" .
    strtoupper(substr($letter_type, 0, 3)) .
    "/" .
    str_pad(
        $employee['Id'],
        3,
        '0',
        STR_PAD_LEFT
    );


// ==========================================
// REF + DATE
// ==========================================

$pdf->SetXY(
    10,
    25
);

$pdf->Cell(
    85,
    6,
    "Ref - $ref",
    0,
    0,
    'L'
);

$pdf->Cell(
    85,
    6,
    "Date: $date",
    0,
    1,
    'R'
);

$pdf->Ln(5);


// ==========================================
// EMPLOYEE ADDRESS
// ==========================================

if (
    !in_array(
        $letter_type,
        ['internship', 'experience']
    )
) {

    $pdf->Ln(6);

    $pdf->SetX(10);

    $pdf->SetFont(
        'Arial',
        '',
        12
    );


    $rawAddress =
        trim(
            $employee['cust_address1'] ?? ''
        );


    // Address line formatting

    $rawAddress = str_replace(

        [
            ' Plot',
            ' 1st',
            ' Behind',
            'Near',
            ' Road',
            ' road',
            ' Nagpur',
            ','
        ],

        [
            "\nPlot",
            "\n1st",
            "\nBehind",
            "\nNear",
            "\nRoad",
            "\nroad",
            "\nNagpur",
            ",\n"
        ],

        $rawAddress
    );


    $pdf->Write(
        6,
        "To,\n"
    );


    $pdf->SetFont(
        'Arial',
        'B',
        12
    );


    $pdf->Write(
        6,
        $title . " " .
        $employee['cust_name'] .
        "\n"
    );


    $pdf->SetFont(
        'Arial',
        '',
        12
    );


    if (!empty($rawAddress)) {

        $pdf->MultiCell(
            0,
            6,
            utf8_decode($rawAddress)
        );

    }


    $pdf->Ln(6);
}
?>
<?php

// ==========================================
// LETTER HEADING
// ==========================================

$pdf->SetFont(
    'Arial',
    'BU',
    14
);


// ==========================================
// INTERNSHIP / EXPERIENCE HEADING
// ==========================================

if (
    in_array(
        $letter_type,
        ['internship', 'experience']
    )
) {

    if ($letter_type == 'internship') {

        $pdf->Cell(
            0,
            10,
            "INTERNSHIP COMPLETION CERTIFICATE",
            0,
            1,
            'C'
        );

    } else {

        $pdf->Cell(
            0,
            10,
            "TO WHOMSOEVER IT MAY CONCERN",
            0,
            1,
            'C'
        );
    }

    $pdf->Ln(5);
}


// ==========================================
// RELIEVING LETTER HEADING
// ==========================================

elseif ($letter_type == 'relieving') {

    $pdf->Cell(
        0,
        10,
        "Relieving Letter",
        0,
        1,
        'C'
    );

    $pdf->Ln(5);
}


// ==========================================
// OFFER / APPOINTMENT
// ==========================================

// Offer aur Appointment mein
// aapka existing heading/content same rakha gaya hai.


// ==========================================
// LETTER BODY
// ==========================================

$pdf->SetFont(
    'Arial',
    '',
    12
);

$body = "";


// ==========================================
// OFFER LETTER
// ==========================================

switch ($letter_type) {

    case "offer":

        $pdf->Ln(2);

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            8,
            "Sub: Offer for the post of - " .
            $employee['designation'],
            0,
            1
        );

        $pdf->Ln(4);

        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $body =
            "Dear Ms. " .
            $employee['cust_name'] .
            ",\n\n";


        $body .=
            "This has reference to your application for the above post and the subsequent interview we had with you.\n\n";


        $body .=
            "In this regard, we are pleased to offer you the position of \"" .
            $employee['designation'] .
            "\" for Shul Ventures Pvt. Ltd., Nagpur.\n\n";


        $body .=
            "Your Monthly pay scale as mutually discussed and agreed will be INR " .
            $employee['package'] .
            "/- (Rupees " .
            $employee['salary_words'] .
            " Only)- Per Month + 5% incentives on revenue generated by you.\n\n";


        // Reporting date
        if (
            safeDate(
                $employee['date']
            )
        ) {

            $reportingDate =
                formatDate(
                    $employee['date']
                );

        } else {

            $reportingDate =
                date('jS F, Y');
        }


        $body .=
            "Your reporting date is " .
            $reportingDate .
            " at 10.00 a.m. at office.\n\n";


        $body .=
            "Best wishes,";

        break;


// ==========================================
// APPOINTMENT LETTER
// ==========================================

    case "appointment":

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            8,
            "Subject: Appointment for post of " .
            $employee['designation'],
            0,
            1
        );

        $pdf->Ln(4);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "Dear " .
            $title .
            " " .
            $employee['cust_name'] .
            ",\n\n" .

            "We are pleased to offer you the position of " .
            $employee['designation'] .
            " with Shul Ventures Pvt. Ltd. on the following terms and conditions:\n\n"
        );


// ==========================================
// 1. COMMENCEMENT OF EMPLOYMENT
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "1. Commencement of Employment",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $joiningDate =
            safeDate($employee['date'])
                ? formatDate($employee['date'])
                : date('jS F, Y');


        $pdf->MultiCell(
            0,
            6,

            "Your employment will be effective from " .
            $joiningDate .
            ".\n\n"
        );


// ==========================================
// 2. JOB TITLE
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "2. Job Title",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "Your job title will be " .
            $employee['designation'] .
            " and you will report to the concerned reporting authority assigned by the management.\n\n"
        );


// ==========================================
// 3. EMPLOYEE AGREEMENT
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "3. Employee Agreement",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "- Ms. " .
            $employee['cust_name'] .
            " on joining the company from " .
            $joiningDate .
            ", undertakes to stay in employment with Shul Ventures Pvt. Ltd. for a minimum period of 6 Months (excluding notice period). Hence excluding the notice period, the minimum duration of employment would be 6 Months.\n\n" .

            "- Ms. " .
            $employee['cust_name'] .
            " undertakes not to leave the service of the company, for any whatsoever reason for a minimum period of 6 Months from the date of joining the services of the company.\n\n" .

            "- In case " .
            $employee['cust_name'] .
            " for any reason leaves the services of the company before the said period of 6 Months, then Shul Ventures Pvt. Ltd. will not provide Experience Letter & Relieving Letter to Ms. " .
            $employee['cust_name'] .
            " under any circumstances.\n\n"
        );


// ==========================================
// 4. SALARY
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "4. Salary",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "- Employees are paid Monthly on or about 4th day of each Month. If the regularly scheduled payroll date falls on Sunday, the Shul will attempt to deliver Salary on Monday. If the payroll date falls on Holiday, the Shul will attempt to deliver Salary on the next working day after the holiday.\n\n" .

            "- All reasonable expenses arising out of employment shall be reimbursed assuming that the same have been authorized prior to be incurred by appropriate receipts.\n\n"
        );


// ==========================================
// 5. HOURS OF WORK
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "5. Hours of Work",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "The workweek is generally from Monday through Saturday, with normal operating hours from 10:00 A.M. to 6:30 P.M. with 30 Minutes for lunch.\n\n"
        );


// ==========================================
// 6. ATTENDANCE & PUNCTUALITY
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "6. Attendance & Punctuality",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "It is important for you to report to work on time and to avoid unnecessary absences. The SHUL recognizes that illness or other circumstances beyond your control may cause you to be absent from work from time to time. However, frequent absenteeism or tardiness may result in disciplinary action, up to and including discharge.\n\n" .

            "You are expected to report at your workstation at 10:00 AM on each day. On delayed, you must need to do overtime and cover delayed minutes before leaving the office.\n\n" .

            "Regular delays in reporting to work will result in disciplinary action up to and including discharge from your employment.\n\n"
        );


// ==========================================
// 7. LEAVE / HOLIDAYS
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "7. Leave / Holidays",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "- The Employee is entitled to take 12 casual Leaves in a Year [Only 1 in a Month].\n\n" .

            "In the event that Employee is absent from work due to sickness or injury for more than 3 consecutive days Employee must need to submit Medical Certificate from their doctor.\n\n" .

            "In the event that Employee is absent from work without giving any prior intimation, it will result in disciplinary action up to and including termination of the employment.\n\n"
        );


// ==========================================
// 8. ORIENTATION & TRAINING
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "8. Orientation & Training",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "To help you become familiar with the SHUL and our way of doing things, the SHUL will provide an orientation and training session within the first few days after you begin work. Some of the content of the session will depend in large part on the nature of your responsibilities, while other parts will be applicable to all employees. In addition, the SHUL may periodically offer additional training or educational programs. Some programs may be voluntary, while others will be required.\n\n"
        );


// ==========================================
// 9. PERFORMANCE OF DUTIES
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "9. Performance of Duties",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "The Employee agrees that during the Employment Period, he/she shall devote his/her full business time to the business affairs of the SHUL and shall perform the duties assigned to him/her faithfully and efficiently, and shall endeavor, to the best of his/her abilities to achieve the goals and adhere to the parameters set by the SHUL.\n\n"
        );


// ==========================================
// 10. OBLIGATION OF EMPLOYEE
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "10. Obligation of the Employee",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "- Upon execution of joining, the Employee shall not engage in any sort of theft, fraud, misrepresentation or any other illegal act neither in the employment space nor outside the premise of employment. If he/she shall do so, the SHUL shall not be liable for such an act done at his own risk.\n\n" .

            "- The Employee further promises to never engage in any theft of the Employer's property or attempt to defraud the Employer in any manner.\n\n" .

            "- The Employee shall always ensure that his/her conduct is in accordance with all the rules, regulations and policies of the SHUL as notified from time to time.\n\n" .

            "- The Employee shall not take up part-time or full-time employment or consultation with any other party or be involved in any other business during the term of his/her employment with the SHUL.\n\n"
        );


// ==========================================
// 11. COMPETING BUSINESSES
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "11. Competing Businesses",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "During the part of organization the Employee agrees not to engage in any employment, consulting, or other activity involving that competes with the business, proposed business or business interests of the Employer, without the Employer's prior written consent.\n\n"
        );


// ==========================================
// 12. CONFIDENTIALITY
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "12. Confidentiality",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "The Employee acknowledges and agrees that in the course of performing your duties, you must maintain the confidentiality of business strategies and clientele information and will not violate the policies. In the event of disclosure of any such information to a Person, Firm or Organization, it will straightly result into termination of your employment.\n\n" .

            "The Employer owns any intellectual property created by the Employee during the course of the employment, or in relation to a certain field, and he shall thereon have all the necessary rights to retain it. After termination of employment, Employee shall not impose any rights on the intellectual property created. Any source code, software or other intellectual property developed, including but not limited to website design or functionality that was created by the employee, during the course of employment, shall belong to the Employer.\n\n"
        );


// ==========================================
// 13. LEAVING THE COMPANY
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "13. Leaving the Company",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "If you wish to resign your employment with the SHUL, we request that you notify your Manager of your anticipated departure date at least 1 Month in advance. This notice should be in the form of a written mail.\n\n"
        );


// ==========================================
// 14. ACCEPTANCE OF OFFER
// ==========================================

        $pdf->SetFont(
            'Arial',
            'BU',
            12
        );

        $pdf->Cell(
            0,
            6,
            "14. Acceptance of Offer",
            0,
            1
        );

        $pdf->Ln(2);


        $pdf->SetFont(
            'Arial',
            '',
            12
        );


        $pdf->MultiCell(
            0,
            6,

            "Please confirm your acceptance of this Contract of Employment by signing and returning the duplicate copy.\n\n" .

            "We welcome you, and look forward to receiving your acceptance and to working with you."
        );

        break;
// ==========================================
// RELIEVING LETTER
// ==========================================

case "relieving":

    // Resignation date missing ho to current date
    if (safeDate($employee['resignation_date'])) {

        $resignationDate =
            formatDate(
                $employee['resignation_date']
            );

    } else {

        $resignationDate =
            date('jS F, Y');
    }


    // Release date missing ho to current date
    if (safeDate($employee['release_date'])) {

        $releaseDate =
            formatDate(
                $employee['release_date']
            );

    } else {

        $releaseDate =
            date('jS F, Y');
    }


    // Joining date missing ho to current date
    if (safeDate($employee['date'])) {

        $joiningDate =
            formatDate(
                $employee['date']
            );

    } else {

        $joiningDate =
            date('jS F, Y');
    }


    $body =

        "Dear " .
        $employee['cust_name'] .
        ",\n\n";


    $body .=

        "This is to formally inform you that your resignation dated " .
        $resignationDate .
        " has been accepted, and your last working day with Shul Ventures Pvt. Ltd. was " .
        $releaseDate .
        ".\n\n";


    $body .=

        "We would like to confirm that you have been relieved from your duties as \"" .
        $employee['designation'] .
        "\" in Shul Ventures Pvt. Ltd. You were employed with us as \"" .
        $employee['designation'] .
        "\" from " .
        $joiningDate .
        " to " .
        $releaseDate .
        ".\n\n";


    $body .=

        "During your tenure, you have successfully completed your assigned responsibilities and contributed to the organization. We confirm that you have completed all the required formalities and handed over your duties and responsibilities.\n\n";


    $body .=

        "We appreciate your efforts and wish you all the best for your future endeavor.\n\n";


    $body .=

        "Best wishes,";

    break;


// ==========================================
// EXPERIENCE LETTER
// ==========================================

case "experience":

    // Joining date
    if (safeDate($employee['date'])) {

        $joiningDate =
            formatDate(
                $employee['date']
            );

    } else {

        $joiningDate =
            date('jS F, Y');
    }


    // Release date
    if (safeDate($employee['release_date'])) {

        $releaseDate =
            formatDate(
                $employee['release_date']
            );

    } else {

        $releaseDate =
            date('jS F, Y');
    }


    $body = "";


    $body .=

        "This is to certify that " .
        $title .
        " " .
        $employee['cust_name'] .
        " was employed with our organization Shul Ventures Pvt. Ltd. as an \"" .
        $employee['designation'] .
        "\" from " .
        $joiningDate .
        " to " .
        $releaseDate .
        ".\n\n";


    $body .=

        "During her tenure with us, " .
        $title .
        " " .
        $employee['cust_name'] .
        " demonstrated professionalism, dedication and commitment towards her responsibilities. She was actively involved in managing her assigned responsibilities and consistently exhibited strong communication, coordination and organizational skills.\n\n";


    $body .=

        "She is an efficient and assiduous professional with exemplary conduct. Throughout her association with the organization, she showed adequate leadership qualities, maintained an excellent track record, and her positive attitude contributed to her team members and the organization too.\n\n";


    $body .=

        "Her sincere approach to work and continuous pursuit of professional excellence were truly commendable.\n\n";


    $body .=

        "We appreciate her sincere work, dedication and contributions to the organization and wish her continued success in her future endeavors.\n\n";


    $body .=

        "Best wishes,";

    break;


// ==========================================
// HR INTERNSHIP
// ==========================================

case "internship":

    // Internship start date
    if (safeDate($employee['date'])) {

        $internStartDate =
            formatDate(
                $employee['date']
            );

    } else {

        $internStartDate =
            date('jS F, Y');
    }


    // Internship end date
    if (safeDate($employee['release_date'])) {

        $internEndDate =
            formatDate(
                $employee['release_date']
            );

    } else {

        $internEndDate =
            date('jS F, Y');
    }


    /*
     * Agar designation database mein HR Recruiter hai
     * to document mein Human Resource Intern show hoga.
     */

    $body = "";


    $body .=

        "This is to certify that " .
        $title .
        " " .
        $employee['cust_name'] .
        " has successfully completed her internship with our organization Shul Ventures Pvt. Ltd. as a Human Resource Intern from " .
        $internStartDate .
        " to " .
        $internEndDate .
        ".\n\n";


    $body .=

        "During her tenure, she gained valuable hands-on experience in recruitment, sourcing, candidate analysis, and client coordination. She demonstrated a strong understanding of HR processes and consistently delivered quality work.\n\n";


    $body .=

        "Throughout her internship, " .
        $title .
        " " .
        $employee['cust_name'] .
        " exceeded expectations through her performance, dedication, and eagerness to learn. She effectively carried out her responsibilities.\n\n";


    $body .=

        "She has successfully completed her industrial training in the field of Human Resource Management under the guidance of Shul Ventures Pvt. Ltd.\n\n";


    $body .=

        $title .
        " " .
        $employee['cust_name'] .
        " has been a sincere, efficient, and cooperative team member. We appreciate her contributions to the organization.\n\n";


    $body .=

        "We wish her continued success in all her future endeavors.\n\n";


    $body .=

        "Best Wishes,";

    break;


// ==========================================
// WEB DEVELOPER INTERNSHIP
// ==========================================

// NOTE:
// Agar aapke form mein letter_type = web_developer_internship
// hai to ye case chalega.

case "web_developer_internship":

    // Start date
    if (safeDate($employee['date'])) {

        $webStartDate =
            formatDate(
                $employee['date']
            );

    } else {

        $webStartDate =
            date('jS F, Y');
    }


    // End date
    if (safeDate($employee['release_date'])) {

        $webEndDate =
            formatDate(
                $employee['release_date']
            );

    } else {

        $webEndDate =
            date('jS F, Y');
    }


    $body = "";


    $body .=

        "This is to certify that " .
        $title .
        " " .
        $employee['cust_name'] .
        " has worked as an Industrial Intern at the position of Intern – Web Development with our organization Shul Ventures Pvt. Ltd. from " .
        $webStartDate .
        " to " .
        $webEndDate .
        ".\n\n";


    $body .=

        "During her internship tenure, she displayed a professional attitude, sincerity, and a strong willingness to learn. She was involved in development activities and effectively supported the project requirements.\n\n";


    $body .=

        "Her key responsibilities included:\n\n";


    $body .=

        "• Planning and requirement analysis during the initial project phases.\n" .

        "• Front-end development using HTML, CSS, and JavaScript.\n" .

        "• Assisting the development team in back-end programming using PHP.\n" .

        "• Managing and handling databases using MySQL.\n" .

        "• Contributing to various phases of the software development lifecycle.\n" .

        "• Ensuring design and development compliance with project specifications.\n" .

        "• Applying problem-solving and analytical skills to resolve technical challenges.\n" .

        "• Designing and developing features and modules for web-based applications.\n" .

        "• Demonstrating a results-oriented approach while meeting assigned timelines.\n\n";


    $body .=

        "Throughout her internship " .
        $title .
        " " .
        $employee['cust_name'] .
        " showed adequate leadership qualities that contributed to her team members and the organization too.\n\n";


    $body .=

        "We wish her all the best in her future endeavors.\n\n";


    $body .=

        "Best wishes,";

    break;


// ==========================================
// INVALID LETTER TYPE
// ==========================================

default:

    die("Invalid letter type: " . htmlspecialchars($letter_type));
}


// ==========================================
// PRINT BODY
// ==========================================

$pdf->SetFont(
    'Arial',
    '',
    12
);


// UTF-8 text ko FPDF compatible encoding
$body = utf8_decode($body);


$pdf->MultiCell(
    0,
    6,
    $body
);
// ==========================================
// SIGNATURE
// ==========================================

$pdf->Ln(10);

$pdf->SetFont(
    'Arial',
    '',
    12
);

$pdf->Cell(
    0,
    6,
    "For Shul Ventures Pvt. Ltd.",
    0,
    1
);

$pdf->Ln(12);

$pdf->Cell(
    0,
    6,
    "Atul Urkude",
    0,
    1
);

$pdf->Cell(
    0,
    6,
    "Director",
    0,
    1
);


// ==========================================
// PDF FILE NAME
// ==========================================

$filename =
    $letter_type .
    "_letter_" .
    (
        !empty($employee['emp_id'])
            ? $employee['emp_id']
            : $employee['Id']
    ) .
    ".pdf";


// ==========================================
// IMPORTANT
// ==========================================
// PDF SERVER PAR SAVE NAHI HOGA
// employee_documents TABLE MEIN INSERT NAHI HOGA
//
// Sirf browser mein PDF open hoga.
// ==========================================


// Clear any previous output
ob_end_clean();


// ==========================================
// SHOW PDF IN BROWSER
// ==========================================

$pdf->Output(
    'I',
    $filename
);

exit;

?>
