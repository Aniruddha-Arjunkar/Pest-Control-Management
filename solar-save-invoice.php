
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

mysqli_set_charset($conn, 'utf8mb4');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid Request');
}

/* ================= HELPER ================= */

function failRequest($message) {
    die($message);
}

/* ================= DELETE INVOICE ================= */

if (($_POST['action'] ?? '') === 'delete') {

    $client_id = (int)($_POST['client_id'] ?? 0);
    $created_at = trim($_POST['created_at'] ?? '');

    if ($client_id <= 0 || $created_at === '') {
        die('Invalid delete request');
    }

    $stmt = mysqli_prepare($conn, "
        SELECT pdf_file
        FROM tbl_solar_invoice
        WHERE client_id = ?
          AND created_at = ?
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt, 'is', $client_id, $created_at);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $invoice = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    mysqli_begin_transaction($conn);

    try {

        $stmt = mysqli_prepare($conn, "
            DELETE FROM tbl_solar_invoice
            WHERE client_id = ?
              AND created_at = ?
        ");

        mysqli_stmt_bind_param($stmt, 'is', $client_id, $created_at);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);

        mysqli_commit($conn);

        // Delete generated PDF after successful database deletion
        if (!empty($invoice['pdf_file'])) {

            $pdfPath = __DIR__ . '/' . ltrim($invoice['pdf_file'], '/\\');

            if (is_file($pdfPath)) {
                unlink($pdfPath);
            }
        }

        header('Location: solar-view-invoice.php?msg=Invoice+Deleted');
        exit;

    } catch (Throwable $e) {

        mysqli_rollback($conn);
        die('Delete failed: ' . $e->getMessage());
    }
}

/* ================= INPUTS ================= */

$client_id = (int)($_POST['client_id'] ?? 0);
$group_id = (int)($_POST['group_id'] ?? 0);

$inquiry_id = (int)($_POST['inquiry_id'] ?? 0);

$invoice_date = trim($_POST['invoice_date'] ?? '');
$due_date = trim($_POST['due_date'] ?? '');

$is_edit = ($_POST['edit_mode'] ?? '0') === '1';

$gst_invoice_no = trim($_POST['gst_invoice_no'] ?? '');
$created_at = trim($_POST['created_at'] ?? '');

$item_name = $_POST['item_name'] ?? [];
$hsn = $_POST['hsn'] ?? [];
$qty = $_POST['qty'] ?? [];

$item_total = $_POST['item_total'] ?? [];
$gst_mode = $_POST['gst_mode'] ?? [];

$service_charge = $_POST['service_charge'] ?? [];

// Add Invoice form sends GST percentage in rate[]
$posted_rate = $_POST['rate'] ?? [];

if ($client_id <= 0 || $invoice_date === '' || $due_date === '') {
    die('Required invoice details missing');
}

if (!is_array($item_name) || count($item_name) === 0) {
    die('Please add at least one item');
}

/* ================= DATE VALIDATION ================= */

function validDate($date) {
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if (!validDate($invoice_date) || !validDate($due_date)) {
    die('Invalid invoice date or due date');
}

/* ================= FETCH SOLAR CLIENT ================= */

$stmt = mysqli_prepare($conn, "
    SELECT *
    FROM tbl_solar_client
    WHERE id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, 'i', $client_id);
mysqli_stmt_execute($stmt);

$clientResult = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($clientResult);

mysqli_stmt_close($stmt);

if (!$client) {
    die('Solar client not found');
}

/* ================= CLIENT DETAILS ================= */

$is_trader = strtolower(trim($client['added_by'] ?? '')) === 'trader';

$cust_name = $client['cust_name'] ?? '';
$cust_phone = $client['cust_phone'] ?? '';
$cust_email = $client['cust_email'] ?? '';

$billing_address = $client['billing_address'] ?? ($client['cust_address'] ?? '');
$shipping_address = $client['shipping_address'] ?? ($client['cust_address'] ?? '');

/* ================= EDIT / NEW INVOICE ================= */

$pdf_file = '';

mysqli_begin_transaction($conn);

try {

    if ($is_edit) {

        if ($gst_invoice_no === '' || $created_at === '') {
            throw new Exception('Edit data missing');
        }

        // Verify existing invoice and preserve its PDF path
        $stmt = mysqli_prepare($conn, "
            SELECT pdf_file
            FROM tbl_solar_invoice
            WHERE client_id = ?
              AND created_at = ?
              AND gst_invoice_no = ?
            LIMIT 1
            FOR UPDATE
        ");

        mysqli_stmt_bind_param(
            $stmt,
            'iss',
            $client_id,
            $created_at,
            $gst_invoice_no
        );

        mysqli_stmt_execute($stmt);

        $existingResult = mysqli_stmt_get_result($stmt);
        $existing = mysqli_fetch_assoc($existingResult);

        mysqli_stmt_close($stmt);

        if (!$existing) {
            throw new Exception('Invoice not found');
        }

        $pdf_file = $existing['pdf_file'] ?? '';

        // Remove old invoice rows before inserting updated items
        $stmt = mysqli_prepare($conn, "
            DELETE FROM tbl_solar_invoice
            WHERE client_id = ?
              AND created_at = ?
              AND gst_invoice_no = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            'iss',
            $client_id,
            $created_at,
            $gst_invoice_no
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);

    } else {

        // Generate next invoice number
        $result = mysqli_query($conn, "
            SELECT MAX(CAST(gst_invoice_no AS UNSIGNED)) AS last_invoice
            FROM tbl_solar_invoice
            WHERE gst_invoice_no IS NOT NULL
              AND gst_invoice_no != ''
            FOR UPDATE
        ");

        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }

        $last = mysqli_fetch_assoc($result);

        $gst_invoice_no = (string)(((int)($last['last_invoice'] ?? 0)) + 1);

        $created_at = date('Y-m-d H:i:s');
    }

    /* ================= CALCULATE ITEMS ================= */

    $subtotal = 0;
    $total_cgst = 0;
    $total_sgst = 0;
    $grand_total = 0;

    $items_to_insert = [];

    for ($i = 0; $i < count($item_name); $i++) {

        $name = trim((string)($item_name[$i] ?? ''));

        if ($name === '') {
            continue;
        }

        $qty_val = (float)($qty[$i] ?? 0);

        if ($qty_val <= 0) {
            throw new Exception('Invalid quantity for item: ' . $name);
        }

        /* GST RATE */

        $gst = (float)($posted_rate[$i] ?? 0);

        if ($gst == 18) {
            $cgst_per = 9;
            $sgst_per = 9;
        } elseif ($gst == 5) {
            $cgst_per = 2.5;
            $sgst_per = 2.5;
        } else {
            throw new Exception('Invalid GST rate for item: ' . $name);
        }

        /* GST MODE */

        $mode = strtolower(trim($gst_mode[$i] ?? ''));

        if (!in_array($mode, ['inclusive', 'exclusive'], true)) {
            throw new Exception('Invalid GST mode for item: ' . $name);
        }

        /* ================= TRADER ================= */

        if ($is_trader) {

            // Trader amount is per unit
            $raw_charge = $service_charge[$i] ?? '';

            if ($raw_charge === '' || !is_numeric($raw_charge)) {
                throw new Exception('Service charge missing for item: ' . $name);
            }

            $service_charge_value = (float)$raw_charge;

            if ($service_charge_value < 0) {
                throw new Exception('Invalid service charge for item: ' . $name);
            }

            $entered_total = $qty_val * $service_charge_value;

        } else {

            // Normal service client amount
            $raw_total = $item_total[$i] ?? '';

            if ($raw_total === '' || !is_numeric($raw_total)) {
                throw new Exception('Item amount missing for item: ' . $name);
            }

            $entered_total = (float)$raw_total;

            if ($entered_total < 0) {
                throw new Exception('Invalid item amount for item: ' . $name);
            }

            $service_charge_value = 0;
        }

        /* ================= GST CALCULATION ================= */

        if ($mode === 'exclusive') {

            $base_amount = $entered_total;

        } else {

            $base_amount = $entered_total / (1 + ($gst / 100));
        }

        $base_amount = round($base_amount, 2);

        $total_tax = round($base_amount * $gst / 100, 2);

        $cgst_amt = round($total_tax / 2, 2);

        $sgst_amt = round($total_tax - $cgst_amt, 2);

        $total_amt = round($base_amount + $total_tax, 2);

        // Rate column stores taxable amount per unit
        $rate = round($base_amount / $qty_val, 2);

        /* ================= TOTALS ================= */

        $subtotal += $base_amount;
        $total_cgst += $cgst_amt;
        $total_sgst += $sgst_amt;
        $grand_total += $total_amt;

        /* ================= STORE ITEM ================= */

        $items_to_insert[] = [
            'item_name' => $name,
            'hsn' => trim((string)($hsn[$i] ?? '')),
            'qty' => $qty_val,
            'rate' => $rate,
            'service_charge' => $service_charge_value,
            'gst_mode' => $mode,
            'base_amount' => $base_amount,
            'cgst_per' => $cgst_per,
            'cgst_amt' => $cgst_amt,
            'sgst_per' => $sgst_per,
            'sgst_amt' => $sgst_amt,
            'item_total' => $total_amt
        ];
    }

    if (empty($items_to_insert)) {
        throw new Exception('Please enter at least one valid item');
    }

    /* ================= FINAL TOTALS ================= */

    $subtotal = round($subtotal, 2);
    $total_cgst = round($total_cgst, 2);
    $total_sgst = round($total_sgst, 2);
    $grand_total = round($grand_total, 2);

    /* ================= INSERT ITEMS ================= */

    $insertSQL = "
        INSERT INTO tbl_solar_invoice (
            client_id,
            group_id,
            inquiry_id,
            gst_invoice_no,
            invoice_date,
            due_date,
            cust_name,
            cust_phone,
            cust_email,
            billing_address,
            shipping_address,
            item_name,
            hsn,
            qty,
            rate,
            service_charge,
            gst_mode,
            base_amount,
            cgst_per,
            cgst_amt,
            sgst_per,
            sgst_amt,
            item_total,
            subtotal,
            total_cgst,
            total_sgst,
            grand_total,
            pdf_file,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?
        )
    ";

    $stmt = mysqli_prepare($conn, $insertSQL);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    foreach ($items_to_insert as $item) {

        $itemNameValue = $item['item_name'];
        $hsnValue = $item['hsn'];
        $qtyValue = $item['qty'];
        $rateValue = $item['rate'];
        $serviceChargeValue = $item['service_charge'];
        $gstModeValue = $item['gst_mode'];
        $baseValue = $item['base_amount'];
        $cgstPerValue = $item['cgst_per'];
        $cgstAmtValue = $item['cgst_amt'];
        $sgstPerValue = $item['sgst_per'];
        $sgstAmtValue = $item['sgst_amt'];
        $itemTotalValue = $item['item_total'];

        $types = 'iii'
       . str_repeat('s', 10)
       . 'ddds'
       . str_repeat('d', 10)
       . 'ss';

mysqli_stmt_bind_param(
    $stmt,
    $types,
    $client_id,
    $group_id,
    $inquiry_id,
    $gst_invoice_no,
    $invoice_date,
    $due_date,
    $cust_name,
    $cust_phone,
    $cust_email,
    $billing_address,
    $shipping_address,
    $itemNameValue,
    $hsnValue,
    $qtyValue,
    $rateValue,
    $serviceChargeValue,
    $gstModeValue,
    $baseValue,
    $cgstPerValue,
    $cgstAmtValue,
    $sgstPerValue,
    $sgstAmtValue,
    $itemTotalValue,
    $subtotal,
    $total_cgst,
    $total_sgst,
    $grand_total,
    $pdf_file,
    $created_at
);



        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

} catch (Throwable $e) {

    mysqli_rollback($conn);

    die('Invoice save failed: ' . $e->getMessage());
}

/* ================= REDIRECT TO PDF ================= */

if ($is_trader) {
    $pdfPage = 'solar-generate-trader-invoice.php';
} else {
    $pdfPage = 'solar-generate-invoice.php';
}

header(
    'Location: ' . $pdfPage .
    '?client_id=' . $client_id .
    '&created_at=' . urlencode($created_at)
);

exit;
?>
