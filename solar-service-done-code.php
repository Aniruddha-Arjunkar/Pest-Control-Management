
<?php
session_start();
require_once 'db_connect.php';

/* =========================================================
   GET INQUIRY ID AND CLIENT TYPE
========================================================= */

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$client_type = $_POST['client_type'] ?? 'service';

if (!in_array($client_type, ['trader', 'service'], true)) {
    $client_type = 'service';
}

$is_trader = ($client_type === 'trader');

if ($id <= 0) {
    $_SESSION['status'] = "Invalid Inquiry ID!";
    header("Location: solar-view-inquiry.php");
    exit();
}

/* =========================================================
   GET SOLAR INQUIRY
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM tbl_solar_inquiry WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    $_SESSION['status'] = "Solar Inquiry not found!";
    header("Location: solar-complete-service.php?id=" . $id);
    exit();
}

$inq = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

/* =========================================================
   DUPLICATE SOLAR CLIENT CHECK
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM tbl_solar_client WHERE inquiry_id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$duplicateResult = mysqli_stmt_get_result($stmt);

if ($duplicateResult && mysqli_num_rows($duplicateResult) > 0) {

    $existingClient = mysqli_fetch_assoc($duplicateResult);

    $_SESSION['status'] =
        "Solar Client already added! Client ID: " .
        $existingClient['id'];

    mysqli_stmt_close($stmt);

    header("Location: solar-complete-service.php?id=" . $id);
    exit();
}

mysqli_stmt_close($stmt);

/* =========================================================
   POST VALUES
========================================================= */

$service = trim($_POST['service'] ?? '');
$service_date = trim($_POST['service_date'] ?? '');
$service_tc = trim($_POST['service_term_condition'] ?? '');

$total_amount = isset($_POST['total_amount'])
    ? (float)$_POST['total_amount']
    : 0;

$warranty = trim($_POST['warranty'] ?? '');
$service_covered = trim($_POST['service_covered'] ?? '');

$apply_gst = isset($_POST['apply_gst']) ? 1 : 0;

$gst_type = $_POST['gst_type'] ?? 'exclusive';

$gst_rate = isset($_POST['gst_rate'])
    ? (float)$_POST['gst_rate']
    : 18;

$gst_number = trim($_POST['gst_number'] ?? '');

$billing_address = trim($_POST['billing_address'] ?? '');
$shipping_address = trim($_POST['shipping_address'] ?? '');

$gst_invoice_no = trim($_POST['gst_invoice_no'] ?? '');

$consumer_no = trim($_POST['consumer_no'] ?? '');
$subdivision = trim($_POST['subdivision'] ?? '');
$technical_name = trim($_POST['technical_name'] ?? '');

/* =========================================================
   TRADER DEFAULT VALUES
========================================================= */

if ($is_trader) {

    $service = 'Trader';
    $service_date = date('Y-m-d');
    $warranty = '';
}

/* =========================================================
   VALIDATION
========================================================= */

if ($total_amount <= 0) {

    $_SESSION['status'] = "Please enter a valid amount!";

    header("Location: solar-complete-service.php?id=$id&type=$client_type");
    exit();
}

if ($is_trader && $service_covered === '') {

    $_SESSION['status'] = "Please enter the trader description!";

    header("Location: solar-complete-service.php?id=$id&type=trader");
    exit();
}

if (!in_array($gst_type, ['exclusive', 'inclusive'], true)) {
    $gst_type = 'exclusive';
}

if (!in_array($gst_rate, [5.0, 18.0], true)) {
    $gst_rate = 18.0;
}

if ($apply_gst == 0) {
    $gst_rate = 0;
}

/* =========================================================
   GST CALCULATION
========================================================= */

$gst_amount = 0;
$total_incl_gst = $total_amount;

if ($apply_gst == 1) {

    if ($gst_type === 'exclusive') {

        $gst_amount = round(
            $total_amount * $gst_rate / 100,
            2
        );

        $total_incl_gst = round(
            $total_amount + $gst_amount,
            2
        );

    } else {

        $total_incl_gst = round($total_amount, 2);

        $basic_amount = round(
            $total_amount / (1 + ($gst_rate / 100)),
            2
        );

        $gst_amount = round(
            $total_incl_gst - $basic_amount,
            2
        );
    }
}

/* =========================================================
   CUSTOMER DETAILS FROM SOLAR INQUIRY
========================================================= */

$cust_name = $inq['name'] ?? '';
$cust_phone = $inq['phone'] ?? '';
$cust_email = $inq['email'] ?? '';
$cust_address = $inq['address'] ?? '';

$added_by = $is_trader ? 'trader' : 'admin';

/* =========================================================
   INSERT INTO SOLAR CLIENT
========================================================= */

$insertQuery = "
    INSERT INTO tbl_solar_client
    (
        cust_name,
        cust_phone,
        cust_email,
        cust_address,
        service,
        service_date,
        service_term_condition,
        total_amount,
        gst_amount,
        warranty,
        service_covered,
        inquiry_id,
        apply_gst,
        gst_type,
        gst_rate,
        gst_number,
        billing_address,
        shipping_address,
        gst_invoice_no,
        consumer_no,
        subdivision,
        technical_name,
        added_by
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$stmt = mysqli_prepare($conn, $insertQuery);

if (!$stmt) {

    $_SESSION['status'] = "Client query error: " . mysqli_error($conn);

    header("Location: solar-complete-service.php?id=$id&type=$client_type");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "sssssssddssiisdssssssss",
    $cust_name,
    $cust_phone,
    $cust_email,
    $cust_address,
    $service,
    $service_date,
    $service_tc,
    $total_amount,
    $gst_amount,
    $warranty,
    $service_covered,
    $id,
    $apply_gst,
    $gst_type,
    $gst_rate,
    $gst_number,
    $billing_address,
    $shipping_address,
    $gst_invoice_no,
    $consumer_no,
    $subdivision,
    $technical_name,
    $added_by
);

/* =========================================================
   SAVE CLIENT AND PAYMENT
========================================================= */

if (mysqli_stmt_execute($stmt)) {

    $client_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    $paymentAmount = (float)$total_incl_gst;

    $paymentStmt = mysqli_prepare(
        $conn,
        "INSERT INTO tbl_solar_payment
        (client_id, total_amount, due_date)
        VALUES (?, ?, ?)"
    );

    if ($paymentStmt) {

        mysqli_stmt_bind_param(
            $paymentStmt,
            "ids",
            $client_id,
            $paymentAmount,
            $service_date
        );

        if (mysqli_stmt_execute($paymentStmt)) {

            $_SESSION['status'] =
                "Solar Client Added Successfully! " .
                "GST: ₹" . number_format($gst_amount, 2) .
                " | Final Amount: ₹" .
                number_format($total_incl_gst, 2);

        } else {

            $_SESSION['status'] =
                "Client added, but Solar payment entry failed: " .
                mysqli_stmt_error($paymentStmt);
        }

        mysqli_stmt_close($paymentStmt);

    } else {

        $_SESSION['status'] =
            "Client added, but payment query failed: " .
            mysqli_error($conn);
    }

    header("Location: solar-view-client.php");
    exit();

} else {

    $_SESSION['status'] =
        "Failed to Add Solar Client! " .
        mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    header("Location: solar-service.php?id=$id&type=$client_type");
    exit();
}

?>
