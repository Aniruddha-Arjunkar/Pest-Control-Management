<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['email'])) {
    header('location:login.php');
    exit();
}

if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT email, name FROM tbl_registration WHERE Id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && mysqli_num_rows($result) > 0) {
        $student = $result->fetch_assoc();
        $to = $student['email'];

        // Validate email format
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['msg'] = "Invalid email address.";
            header('location:view-student.php');
            exit();
        }

        $subject = "Placement Consultation Agreement (PCA)";
        $message = createAgreementMessage($student['name']);
        $headers = "From: hr@shulventures.com"; // Replace with your email

        // Send email
        if (mail($to, $subject, $message, $headers)) {
            $_SESSION['msg'] = "Agreement sent successfully!";
        } else {
            $_SESSION['msg'] = "Failed to send agreement.";
        }
    } else {
        $_SESSION['msg'] = "Student not found.";
    }

    header('location:view-student.php'); // Redirect back to the main page
    exit();
} else {
    $_SESSION['msg'] = "Invalid request.";
    header('location:view-student.php');
    exit();
}

function createAgreementMessage($name) {
    $message = "Dear " . htmlspecialchars($name) . ",\n\n";
    $message .= "Greetings for the day!\n\n";
    $message .= "With reference to our telephonic discussion, I am sending you the PLACEMENT CONSULTATION AGREEMENT (PCA) for your placement assistance. "
        . "I would request you to go through the agreement and read it carefully. If you agree with the terms, please reply to this email.\n\n";
    
    $message .= "PLACEMENT CONSULTATION AGREEMENT\n\n";
    $message .= "ENTERED INTO ON 21st DAY OF OCTOBER, 2024\n\n";
    $message .= "BETWEEN\n\n";
    $message .= "SHUL Ventures Pvt. Ltd.\n\n";
    $message .= "AND\n\n";
    $message .= "Ms. " . htmlspecialchars($name) . "\n\n";
    $message .= "To promote interaction and co-operation.\n\n";
    $message .= "This Placement Consultation Agreement (PCA) is entered between SHUL Ventures Pvt. Ltd. a company incorporated under the Indian Companies Act, 1956 having its registered office at Nagpur and represented by its undersigned of the ONE PART.\n\n";
    $message .= "And Ms. " . htmlspecialchars($name) . " represented by undersigned of the OTHER PART.\n\n";
    $message .= "Whereas SHUL Ventures Pvt. Ltd. and Ms. " . htmlspecialchars($name) . " shall be hereinafter jointly referred to as the “Parties” and singularly as a “Party”.\n\n";
    $message .= "The SHUL Ventures Pvt. Ltd. and Ms. " . htmlspecialchars($name) . " has decided to sign the Placement Consultation Agreement (PCA) in the area of mutual interest and in accordance with terms and conditions set forth in this Placement Consultation Agreement (PCA), SHUL Ventures Pvt. Ltd. And Ms. " . htmlspecialchars($name) . " owing activities:\n\n";
    $message .= "1. SHUL Ventures Pvt. Ltd. will charge 60% of 1st salary (CTC) from Ms. " . htmlspecialchars($name) . " against the Placement Consultation.\n";
    $message .= "2. Ms. " . htmlspecialchars($name) . " agrees to pay 60% from her 1st salary (CTC) on the date of joining [Not on the issuance of Appointment Letter].\n";
    $message .= "3. In case Ms. " . htmlspecialchars($name) . " does not comply with the said amount to SHUL Ventures Pvt. Ltd. on her joining date then –\n";
    $message .= "   Case 1: The Employer would be requested to terminate Ms. " . htmlspecialchars($name) . ". ASAP and SHUL Ventures Pvt. Ltd. would be sending a replacement of you.\n";
    $message .= "   Case 2: SHUL Ventures Pvt. Ltd. is free to pursue legal court proceedings against Ms. " . htmlspecialchars($name) . ". For breach of contract.\n";
    $message .= "   Case 3: SHUL Ventures Pvt. Ltd. will take action in accordance with the law of India as applicable.\n";
    $message .= "4. This agreement will be valid for only 1 placement of Ms. " . htmlspecialchars($name) . ".\n";
    $message .= "5. Ms. " . htmlspecialchars($name) . " agreeing to this PCA and giving confirmation by attaching a soft copy of Aadhar Card.\n\n";
    $message .= "This PCA will be effective from the date it is signed by representatives of the parties. It will remain valid till the Placement of another party. "
        . "Once the other party gets placed in any company and pays the committed 60% payment from her first salary to SHUL Ventures Pvt. Ltd., this agreement will be terminated automatically.\n\n";
    $message .= "However, SHUL Ventures Pvt. Ltd. and Ms. " . htmlspecialchars($name) . " will ensure that all activities in progress are allowed to be completed successfully.\n\n";
    $message .= "IN WITNESS WHEREOF the two parties have signed this understanding on the day and date as above.\n\n";
    $message .= "*Attach Aadhar Card Photo\n\n";
    $message .= "For Ms. " . htmlspecialchars($name) . "\n\n";
    $message .= "For Shul Ventures Pvt. Ltd.\n\n";
    $message .= "Thanks and Regards\n\n";
    $message .= "Ankita Sawarkar\n";
    $message .= "HR RECRUITER\n";
    $message .= "Shul Ventures Pvt. Ltd.\n";
    $message .= "Email: hr@shulventures.com\n";
    $message .= "Direct ct.: 9307376934\n";
    $message .= "Website: www.shulventures.com\n";
    $message .= "https://www.shulventures.com/img/logo.jpg\n";

    return $message;
}
?>
