<?php
header('Content-Type: application/json');

include 'db_connect.php';

$response = [];

$query = $_GET['query'] ?? '';
$query = trim($query);

if ($query == '') {
    echo json_encode([]);
    exit;
}

$query_safe = mysqli_real_escape_string($conn, $query);

/* =====================================================
   COMMON SEARCH CONDITION (tbl_client / tbl_inquiry)
===================================================== */

$search_condition = "
(
    cust_name LIKE '%$query_safe%'
    OR cust_phone LIKE '%$query_safe%'
    OR cust_address LIKE '%$query_safe%'
    OR services LIKE '%$query_safe%'
)
";

/* =====================================================
   1. CLIENT SEARCH
===================================================== */

$sql1 = "
SELECT Id, cust_name, cust_phone, services
FROM tbl_client
WHERE $search_condition
LIMIT 5
";

$result1 = mysqli_query($conn, $sql1);

if ($result1) {
    while ($row = mysqli_fetch_assoc($result1)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "Clients",
            "url"      => "view-client.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   2. INQUIRY CLIENT SEARCH
===================================================== */

$sql_inquiry = "
SELECT Id, cust_name, cust_phone, services
FROM tbl_inquiry
WHERE
(
    cust_name LIKE '%$query_safe%'
    OR cust_phone LIKE '%$query_safe%'
    OR cust_address LIKE '%$query_safe%'
    OR services LIKE '%$query_safe%'
    OR cust_message LIKE '%$query_safe%'
)
LIMIT 5
";

$result_inquiry = mysqli_query($conn, $sql_inquiry);

if ($result_inquiry) {
    while ($row = mysqli_fetch_assoc($result_inquiry)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "Inquiry Clients",
            "url"      => "view-inquiry.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   3. FOLLOWUP SEARCH
===================================================== */

$sql2 = "
SELECT Id, cust_name, cust_phone, services
FROM tbl_inquiry
WHERE
(
    follow_up_date IS NOT NULL
    OR visit_date IS NOT NULL
    OR service_date IS NOT NULL
)
AND
(
    cust_name LIKE '%$query_safe%'
    OR cust_phone LIKE '%$query_safe%'
    OR cust_address LIKE '%$query_safe%'
    OR services LIKE '%$query_safe%'
    OR cust_message LIKE '%$query_safe%'
)
LIMIT 5
";

$result2 = mysqli_query($conn, $sql2);

if ($result2) {
    while ($row = mysqli_fetch_assoc($result2)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "Followup",
            "url"      => "view-all-inquiry-clients.php?client=" . urlencode($row['cust_name'])
        ];
    }
}

/* =====================================================
   4. RE-FOLLOWUP SEARCH
===================================================== */

$sql3 = "
SELECT Id, cust_name, cust_phone, services
FROM tbl_inquiry
WHERE
follow_up_date IS NOT NULL
AND visit_date IS NULL
AND service_date IS NULL
AND
(
    cust_name LIKE '%$query_safe%'
    OR cust_phone LIKE '%$query_safe%'
    OR cust_address LIKE '%$query_safe%'
    OR services LIKE '%$query_safe%'
    OR cust_message LIKE '%$query_safe%'
)
LIMIT 5
";

$result3 = mysqli_query($conn, $sql3);

if ($result3) {
    while ($row = mysqli_fetch_assoc($result3)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "Re-Followup",
            "url"      => "view-re-followup.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   5. VISITS SEARCH
===================================================== */

$sql4 = "
SELECT Id, cust_name, cust_phone, services
FROM tbl_inquiry
WHERE
visit_date IS NOT NULL
AND service_date IS NULL
AND
(
    cust_name LIKE '%$query_safe%'
    OR cust_phone LIKE '%$query_safe%'
    OR cust_address LIKE '%$query_safe%'
    OR services LIKE '%$query_safe%'
    OR cust_message LIKE '%$query_safe%'
)
LIMIT 5
";

$result4 = mysqli_query($conn, $sql4);

if ($result4) {
    while ($row = mysqli_fetch_assoc($result4)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "Visits",
            "url"      => "view-visit.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   6. TODAY'S SCHEDULE SEARCH
===================================================== */

$sql6 = "
SELECT Id, cust_name, cust_phone, services, schedule_date
FROM tbl_inquiry
WHERE schedule_date IS NOT NULL
AND
(
    cust_name LIKE '%$query_safe%'
    OR cust_phone LIKE '%$query_safe%'
    OR cust_address LIKE '%$query_safe%'
    OR services LIKE '%$query_safe%'
    OR cust_message LIKE '%$query_safe%'
)
LIMIT 5
";

$result6 = mysqli_query($conn, $sql6);

if ($result6) {
    while ($row = mysqli_fetch_assoc($result6)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "Today's Schedule",
            "url"      => "view-todays-schedule-client.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   7. QUOTATIONS SEARCH
===================================================== */

$sql7 = "
SELECT g.id, g.total, g.created_at, g.service,
       i.Id AS inquiry_id, i.cust_name, i.cust_phone
FROM tbl_generated_quotations g
JOIN tbl_inquiry i ON g.inquiry_id = i.Id
WHERE
(
    i.cust_name LIKE '%$query_safe%'
    OR i.cust_phone LIKE '%$query_safe%'
    OR g.service LIKE '%$query_safe%'
)
LIMIT 5
";

$result7 = mysqli_query($conn, $sql7);

if ($result7) {
    while ($row = mysqli_fetch_assoc($result7)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "Quotations",
            "url"      => "view_quotations.php?client_id=" . $row['inquiry_id']
        ];
    }
}

/* =====================================================
   8. SENT CERTIFICATES SEARCH
   NOTE: tbl_sent_certificates uses `client_Id` (capital I)
===================================================== */

$sql8 = "
SELECT s.Id, s.sent_on, s.validity,
       c.Id AS client_id, c.cust_name, c.cust_phone, c.cust_address
FROM tbl_sent_certificates s
JOIN tbl_client c ON s.client_Id = c.Id
WHERE
(
    c.cust_name LIKE '%$query_safe%'
    OR c.cust_phone LIKE '%$query_safe%'
    OR c.cust_address LIKE '%$query_safe%'
    OR s.validity LIKE '%$query_safe%'
)
LIMIT 5
";

$result8 = mysqli_query($conn, $sql8);

if ($result8) {
    while ($row = mysqli_fetch_assoc($result8)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "Sent Certificates",
            "url"      => "view-sent-certificates.php?client_id=" . $row['client_id']
        ];
    }
}

/* =====================================================
   9. AMC CLIENT SEARCH
===================================================== */

$sql_amc = "
SELECT c.Id, c.cust_name, c.cust_phone, c.cust_address, c.services
FROM tbl_client c
INNER JOIN tbl_amc a ON a.client_Id = c.Id
WHERE $search_condition
GROUP BY c.Id
LIMIT 5
";

$result_amc = mysqli_query($conn, $sql_amc);

if ($result_amc) {
    while ($row = mysqli_fetch_assoc($result_amc)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "AMC Clients",
            "url"      => "view-AMC.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   10. COMPLAINTS SEARCH
===================================================== */

$sql_complaints = "
SELECT c.Id, c.details, c.complaint_date, cl.cust_name, cl.services
FROM tbl_complaints c
INNER JOIN tbl_client cl ON c.client_id = cl.Id
WHERE
(
    cl.cust_name LIKE '%$query_safe%'
    OR cl.services LIKE '%$query_safe%'
    OR c.details LIKE '%$query_safe%'
)
LIMIT 5
";

$result_complaints = mysqli_query($conn, $sql_complaints);

if ($result_complaints) {
    while ($row = mysqli_fetch_assoc($result_complaints)) {
        $response[] = [
            "name"     => $row['cust_name'] . " - Complaint",
            "location" => "Complaints",
            "url"      => "view-complaints.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   11. RESOLVED COMPLAINTS SEARCH
===================================================== */

$sql_resolved = "
SELECT rc.Id, rc.details, c.cust_name, c.services
FROM tbl_resolved_complaints rc
JOIN tbl_client c ON rc.client_id = c.Id
WHERE
(
    c.cust_name LIKE '%$query_safe%'
    OR c.services LIKE '%$query_safe%'
    OR rc.details LIKE '%$query_safe%'
)
LIMIT 5
";

$result_resolved = mysqli_query($conn, $sql_resolved);

if ($result_resolved) {
    while ($row = mysqli_fetch_assoc($result_resolved)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['services'] . ")",
            "location" => "Resolved Complaints",
            "url"      => "view-resolved-complaints.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   12. DUE PAYMENTS
   NOTE: tbl_payment already has a `due_amount` column;
         using it directly instead of recalculating.
===================================================== */

$sql_due = "
SELECT
    c.Id,
    c.cust_name,
    c.cust_phone,
    c.branch,
    c.services,
    p.total_amount,
    p.paid_amount,
    p.due_amount,
    p.due_date,
    p.Id AS payment_id
FROM tbl_client c
INNER JOIN tbl_payment p ON p.client_Id = c.Id
WHERE
(
    c.cust_name LIKE '%$query_safe%'
    OR c.cust_phone LIKE '%$query_safe%'
    OR c.branch LIKE '%$query_safe%'
    OR c.services LIKE '%$query_safe%'
)
AND p.status != 'defaulted'
AND p.due_amount > 0
LIMIT 5
";

$result_due = mysqli_query($conn, $sql_due);

if ($result_due) {
    while ($row = mysqli_fetch_assoc($result_due)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (Due: ₹" . $row['due_amount'] . ")",
            "location" => "Due Payments",
            "url"      => "due-payments.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   13. VIEW PAYMENTS
===================================================== */

$sql_payments = "
SELECT
    c.Id,
    c.cust_name,
    c.cust_phone,
    c.branch,
    c.services,
    p.paid_amount,
    p.payment_date,
    p.payment_gateway,
    c.source
FROM tbl_client c
INNER JOIN tbl_payment p ON c.Id = p.client_Id
WHERE
(
    c.cust_name LIKE '%$query_safe%'
    OR c.cust_phone LIKE '%$query_safe%'
    OR c.branch LIKE '%$query_safe%'
    OR c.services LIKE '%$query_safe%'
    OR p.payment_gateway LIKE '%$query_safe%'
    OR c.source LIKE '%$query_safe%'
)
AND p.paid_amount IS NOT NULL
LIMIT 5
";

$result_payments = mysqli_query($conn, $sql_payments);

if ($result_payments) {
    while ($row = mysqli_fetch_assoc($result_payments)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (Paid: ₹" . $row['paid_amount'] . ")",
            "location" => "Payment History",
            "url"      => "view-payments.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   14. DEFAULTERS
   NOTE: Using tbl_payment's existing `due_amount` column.
===================================================== */

$sql_defaulters = "
SELECT
    c.Id,
    c.cust_name,
    c.cust_phone,
    c.branch,
    c.services,
    p.total_amount,
    p.paid_amount,
    p.due_amount,
    p.due_date
FROM tbl_client c
INNER JOIN tbl_payment p ON c.Id = p.client_Id
WHERE
(
    c.cust_name LIKE '%$query_safe%'
    OR c.cust_phone LIKE '%$query_safe%'
    OR c.branch LIKE '%$query_safe%'
    OR c.services LIKE '%$query_safe%'
)
AND p.status = 'defaulted'
AND p.due_amount > 0
LIMIT 5
";

$result_defaulters = mysqli_query($conn, $sql_defaulters);

if ($result_defaulters) {
    while ($row = mysqli_fetch_assoc($result_defaulters)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (Default Due: ₹" . $row['due_amount'] . ")",
            "location" => "Defaulters Payments",
            "url"      => "view-defaulters.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   15. GST CLIENTS
   NOTE: tbl_gst_invoice uses `client_id` (lowercase),
         joined on entity_id for Pest Control source.
===================================================== */

$sql_gst = "
SELECT
    c.Id,
    c.branch,
    c.services,
    c.cust_name,
    c.cust_phone,
    c.cust_address,
    c.due_date,
    c.final_amount,
    IFNULL(SUM(CAST(p.paid_amount AS DECIMAL(10,2))), 0) AS paid_amount
FROM tbl_client c
LEFT JOIN tbl_payment p ON p.client_Id = c.Id AND p.status = 'active'
WHERE
(
    c.cust_name LIKE '%$query_safe%'
    OR c.cust_phone LIKE '%$query_safe%'
    OR c.branch LIKE '%$query_safe%'
    OR c.services LIKE '%$query_safe%'
    OR c.cust_address LIKE '%$query_safe%'
)
AND c.cgst > 0
AND c.sgst > 0
GROUP BY c.Id
HAVING c.final_amount > paid_amount
LIMIT 5
";

$result_gst = mysqli_query($conn, $sql_gst);

if ($result_gst) {
    while ($row = mysqli_fetch_assoc($result_gst)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "GST Clients",
            "url"      => "view-gst-client.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   16. GST BILLS / INVOICES
   NOTE: tbl_gst_invoice joins on `entity_id` (not client_id)
         for source_type = 'Pest Control'.
===================================================== */

$sql_gst_bills = "
SELECT
    c.Id,
    c.branch,
    c.services,
    c.cust_name,
    c.cust_phone,
    c.cust_address,
    gi.invoice_number,
    gi.invoice_date,
    gi.amount,
    gi.total_amount
FROM tbl_client c
LEFT JOIN tbl_gst_invoice gi
    ON gi.entity_id = c.Id AND gi.source_type = 'Pest Control'
WHERE
(
    c.cust_name LIKE '%$query_safe%'
    OR c.cust_phone LIKE '%$query_safe%'
    OR c.branch LIKE '%$query_safe%'
    OR c.services LIKE '%$query_safe%'
    OR gi.invoice_number LIKE '%$query_safe%'
)
AND c.cgst > 0
AND c.sgst > 0
LIMIT 5
";

$result_gst_bills = mysqli_query($conn, $sql_gst_bills);

if ($result_gst_bills) {
    while ($row = mysqli_fetch_assoc($result_gst_bills)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "GST Bills",
            "url"      => "view-gst-bills.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   17. EXPENSES
   NOTE: `paid_by` column does NOT exist in tbl_expenses.
         Actual columns: Id, expenses, services, amount,
         date, payment_gateway, branch.
         Searching on: branch, expenses, services, payment_gateway.
===================================================== */

$sql_expenses = "
SELECT
    Id,
    branch,
    expenses,
    services,
    date,
    payment_gateway,
    amount
FROM tbl_expenses
WHERE
(
    branch LIKE '%$query_safe%'
    OR expenses LIKE '%$query_safe%'
    OR services LIKE '%$query_safe%'
    OR payment_gateway LIKE '%$query_safe%'
)
LIMIT 5
";

$result_expenses = mysqli_query($conn, $sql_expenses);

if ($result_expenses) {
    while ($row = mysqli_fetch_assoc($result_expenses)) {
        $response[] = [
            "name"     => $row['expenses'] . " (₹" . $row['amount'] . ")",
            "location" => "Expenses",
            "url"      => "view-expenses.php"
        ];
    }
}

/* =====================================================
   18. EMPLOYEES
===================================================== */

$sql_employee = "
SELECT
    Id,
    title,
    cust_name,
    cust_phone,
    cust_email,
    designation,
    date
FROM tbl_employee
WHERE resignation_type IS NULL
AND
(
    cust_name LIKE '%$query_safe%'
    OR cust_phone LIKE '%$query_safe%'
    OR cust_email LIKE '%$query_safe%'
    OR designation LIKE '%$query_safe%'
    OR title LIKE '%$query_safe%'
)
LIMIT 5
";

$result_employee = mysqli_query($conn, $sql_employee);

if ($result_employee) {
    while ($row = mysqli_fetch_assoc($result_employee)) {
        $full_name = $row['title'] . " " . $row['cust_name'];
        $response[] = [
            "name"     => $full_name . " (" . $row['designation'] . ")",
            "location" => "Employees",
            "url"      => "view-employee-detail.php?employee_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   19. PENDING SERVICES SEARCH
===================================================== */

$sql5 = "
SELECT c.Id, c.cust_name, c.cust_phone, c.services
FROM tbl_client c
JOIN tbl_pending_services ps ON ps.client_Id = c.Id
WHERE $search_condition
GROUP BY c.Id
LIMIT 5
";

$result5 = mysqli_query($conn, $sql5);

if ($result5) {
    while ($row = mysqli_fetch_assoc($result5)) {
        $response[] = [
            "name"     => $row['cust_name'] . " (" . $row['cust_phone'] . ")",
            "location" => "Pending Services",
            "url"      => "view-pending-service.php?client_id=" . $row['Id']
        ];
    }
}

/* =====================================================
   OUTPUT
===================================================== */

echo json_encode($response);

exit;
?>