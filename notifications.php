<?php
/* =========================================================
   HEADER NOTIFICATIONS
   Requires $conn, $role and an active session (included from header.php)
========================================================= */

$notifications = [];

$today = date("Y-m-d");


/* ---------------- 1. PENDING SERVICES ---------------- */
/* Reuses $pending_service_count already calculated above in header.php */

if ($pending_service_count > 0) {
    $notifications[] = [
        "icon"  => "🔴",
        "text"  => $pending_service_count . " Pending Services",
        "count" => $pending_service_count,
        "page"  => "view-pending-service.php"
    ];
}


/* ---------------- 2. COMPLAINTS ---------------- */
/* Reuses $complaint_count already calculated above in header.php */

if ($complaint_count > 0) {
    $notifications[] = [
        "icon"  => "⚠️",
        "text"  => $complaint_count . " Complaints",
        "count" => $complaint_count,
        "page"  => "view-complaints.php"
    ];
}


/* ---------------- 3. DUE PAYMENTS ---------------- */

$serviceCondition = '';

if ($role === 'support_team') {
    $serviceCondition = " AND c.services != 'Consultancy' ";
} elseif ($role === 'HR' || $role === 'trainer') {
    $serviceCondition = " AND c.services = 'Consultancy' ";
}

$normalPaymentsQuery = "
    SELECT
        MAX(p.Id) AS payment_id,
        c.Id AS client_Id,
        c.branch,
        c.services,
        c.cust_name,
        c.cust_phone,
        p.total_amount,
        SUM(p.paid_amount) AS total_paid,
        (
            p.total_amount
            - SUM(p.paid_amount)
            - SUM(CASE WHEN p.tds_paid = 0 THEN p.tds_amount ELSE 0 END)
        ) AS due_amount,
        MAX(p.due_date) AS due_date
    FROM tbl_client c
    LEFT JOIN tbl_payment p ON c.Id = p.client_Id
    WHERE p.status != 'defaulted'
    $serviceCondition
    GROUP BY c.Id
    HAVING due_amount > 0
";

$consultancyQuery = "
    SELECT
        MAX(p.Id) AS payment_id,
        p.client_Id,
        'Nagpur' AS branch,
        'Consultancy' AS services,
        IF(
            p.payment_type = 'Company Pay',
            CONCAT(pl.company_name, ' (', pl.candidate_name, ')'),
            CONCAT(pl.candidate_name, ' (', pl.company_name, ')')
        ) AS cust_name,
        IF(
            p.payment_type = 'Company Pay',
            COALESCE(ag.phone, a.phone),
            COALESCE(a.phone, ag.phone)
        ) AS cust_phone,
        p.total_amount,
        SUM(p.paid_amount) AS total_paid,
        (
            p.total_amount
            - SUM(p.paid_amount)
            - SUM(CASE WHEN p.tds_paid = 0 THEN p.tds_amount ELSE 0 END)
        ) AS due_amount,
        MAX(p.due_date) AS due_date
    FROM tbl_payment p
    LEFT JOIN tbl_placement pl ON p.interview_id = pl.interview_id
    LEFT JOIN tbl_agreement ag ON pl.candidate_id = ag.id
    LEFT JOIN tbl_associate a ON pl.candidate_id = a.Id
    WHERE p.status != 'defaulted'
    GROUP BY p.client_Id, p.total_amount
    HAVING due_amount > 0
";

$internshipQuery = "
    SELECT
        MAX(p.Id) AS payment_id,
        p.client_Id,
        'Nagpur' AS branch,
        'Internship' AS services,
        sa.name AS cust_name,
        sa.phone AS cust_phone,
        p.total_amount,
        SUM(p.paid_amount) AS total_paid,
        (p.total_amount - SUM(p.paid_amount)) AS due_amount,
        MAX(p.due_date) AS due_date
    FROM tbl_student_admissions sa
    LEFT JOIN tbl_payment p ON sa.student_id = p.client_Id
    WHERE p.status != 'defaulted'
    GROUP BY sa.student_id
    HAVING due_amount > 0
";

$duePaymentsSql = "
    SELECT * FROM (
        $normalPaymentsQuery
        UNION
        $consultancyQuery
        UNION
        $internshipQuery
    ) AS due_records
";

/* NOTE: renamed from $result -> $notif_due_result to avoid clobbering
   the caller page's $result (e.g. view-client.php's client list query) */
$notif_due_result = mysqli_query($conn, $duePaymentsSql);

$duePaymentCount = 0;

if ($notif_due_result) {
    while ($notif_due_row = mysqli_fetch_assoc($notif_due_result)) {
        if (empty($notif_due_row['cust_name'])) {
            continue;
        }
        $dueAmount = (float)($notif_due_row['due_amount'] ?? 0);
        if ($dueAmount <= 0) {
            continue;
        }
        $duePaymentCount++;
    }
}

if ($duePaymentCount > 0) {
    $notifications[] = [
        "icon"  => "💰",
        "text"  => $duePaymentCount . " Clients have Due Payments",
        "count" => $duePaymentCount,
        "page"  => "due-payments.php"
    ];
}


/* ---------------- 4. TODAY'S FOLLOW UPS ---------------- */

$sqlFollowups = "
    SELECT COUNT(*) AS total
    FROM tbl_inquiry
    WHERE follow_up_date = '$today'
";
$notif_followup_result = mysqli_query($conn, $sqlFollowups);
$followupCount = 0;
if ($notif_followup_result) {
    $notif_followup_row = mysqli_fetch_assoc($notif_followup_result);
    $followupCount = (int)($notif_followup_row['total'] ?? 0);
}
if ($followupCount > 0) {
    $notifications[] = [
        "icon"  => "📞",
        "text"  => $followupCount . " Follow Ups Today",
        "count" => $followupCount,
        "page"  => "view-all-inquiry-clients.php"
    ];
}


/* ---------------- 5. TODAY'S VISITS ---------------- */

$sqlVisits = "
    SELECT COUNT(*) AS total
    FROM tbl_inquiry
    WHERE visit_date IS NOT NULL
    AND visit_date != ''
    AND service_date IS NULL
    AND visit_date = '$today'
";
$notif_visit_result = mysqli_query($conn, $sqlVisits);
$visitCount = 0;
if ($notif_visit_result) {
    $notif_visit_row = mysqli_fetch_assoc($notif_visit_result);
    $visitCount = (int)($notif_visit_row['total'] ?? 0);
}
if ($visitCount > 0) {
    $notifications[] = [
        "icon"  => "🚗",
        "text"  => $visitCount . " Visits Today",
        "count" => $visitCount,
        "page"  => "view-visits.php"
    ];
}


/* ---------------- 6. TODAY'S SCHEDULED SERVICES ---------------- */

$sqlScheduled = "
    SELECT COUNT(*) AS total
    FROM tbl_inquiry
    WHERE schedule_date IS NOT NULL
    AND schedule_date != ''
    AND schedule_date = '$today'
";
$notif_scheduled_result = mysqli_query($conn, $sqlScheduled);
$scheduledCount = 0;
if ($notif_scheduled_result) {
    $notif_scheduled_row = mysqli_fetch_assoc($notif_scheduled_result);
    $scheduledCount = (int)($notif_scheduled_row['total'] ?? 0);
}
if ($scheduledCount > 0) {
    $notifications[] = [
        "icon"  => "🛠️",
        "text"  => $scheduledCount . " Services Scheduled Today",
        "count" => $scheduledCount,
        "page"  => "view-todays-schedule-client.php"
    ];
}


$notification_total = count($notifications);