<?php
require_once 'db_connect.php';

if (isset($_POST['submit'])) {

      // ðŸ›‘ Honeypot
    if (!empty($_POST['website'])) {
        die("Bot detected");
    }

    // ðŸ§¹ Clean function
    function clean($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    // ðŸ“¥ Get & clean data
    $services      = clean($_POST['services']);
    $cust_name     = clean($_POST['cust_name']);
    $cust_phone    = clean($_POST['cust_phone']);
    $cust_address  = clean($_POST['cust_address']);
    $cust_email = !empty($_POST['cust_email']) ? clean($_POST['cust_email']) : null;
    $cust_message  = clean($_POST['cust_message']);
    
    $inq_date = date('Y-m-d'); // your inq_date column is DATE type
    $follow_up_date = isset($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
    $visit_date = isset($_POST['visit_date']) ? $_POST['visit_date'] : null;
    $service_date = isset($_POST['service_date']) ? $_POST['service_date'] : null;
    
    
    
    // ðŸš¨ Validation
    if (!preg_match("/^[a-zA-Z ]{3,50}$/", $cust_name)) {
        die("Invalid Name");
    }

    if (!preg_match("/^[6-9]\d{9}$/", $cust_phone)) {
        die("Invalid Phone");
    }

    if (!empty($cust_email) && !filter_var($cust_email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid Email");
}

    // ðŸ›‘ Spam filter
    if (preg_match('/http|https|www|\.ru|\.biz|\.click|\.shop|\.top|\.xyz/i', $cust_message)) {
        die("Spam detected");
    }
    
    if (preg_match('/\b[a-z0-9-]+\.[a-z]{2,}(\.[a-z]{2,})?\b/i', $cust_message)) {
    die("Spam detected");
}


    if (strlen($cust_message) > 300) {
        die("Message too long");
    }


    // Use prepared statement to insert data
    $stmt = $conn->prepare("INSERT INTO tbl_inquiry (services, cust_name, cust_phone, cust_address, cust_email, cust_message, inq_date, follow_up_date, visit_date, service_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $services, $cust_name, $cust_phone, $cust_address, $cust_email, $cust_message, $inq_date, $follow_up_date, $visit_date, $service_date);

    if ($stmt->execute()) {
        echo '<script>alert("Thank You For Contacting us..."); window.location.href="index.html";</script>';
    } else {
        echo "Database Error: " . $stmt->error;
    }

    // Send email
    $to = "support@shulventures.com, atulurkude765@gmail.com, prash.chunarkar@gmail.com";
    $subject = "Shul - Online Inquiry";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: $cust_name <$cust_email>\r\n";

    $email_body = "
        <h3>New Inquiry</h3>
        <p><strong>Name:</strong> $cust_name</p>
        <p><strong>Phone:</strong> $cust_phone</p>
        <p><strong>Services:</strong> $services</p>
        <p><strong>Message:</strong> $cust_message</p>
    ";

    if (mail($to, $subject, $email_body, $headers)) {
        echo "Your mail was sent successfully";
    } else {
        echo "We encountered an error sending your mail";
    }

    $stmt->close();
    $conn->close();
}
?>
