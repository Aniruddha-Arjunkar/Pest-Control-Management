<?php
/* =========================================================
   SHUL HEADER + SIDEBAR
   ========================================================= */

   if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------------------------
   LOGIN CHECK
   ---------------------------- */
   if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

/* ---------------------------
   DATABASE
   ---------------------------- */
   if (!isset($conn) || !($conn instanceof mysqli)) {
    include_once __DIR__ . '/db_connect.php';
}

$role = $_SESSION['role'] ?? '';

/* ---------------------------
   HEADER USER
   ---------------------------- */
   $header_role  = $_SESSION['role'] ?? '';
   $header_email = $_SESSION['email'] ?? '';
   $header_name  = $_SESSION['name'] ?? '';

   if ($header_name === '' && $header_email !== '') {
    $header_name = ucfirst(strtok($header_email, '@'));
}

if ($header_name === '') {
    $header_name = 'Admin';
}

/* =========================================================
   ACCESS FUNCTION
   ========================================================= */
   if (!function_exists('hasAccess')) {

    function hasAccess($module, $sub = null)
    {
        if (($_SESSION['role'] ?? '') === 'super_admin') {
            return true;
        }

        $modules = $_SESSION['modules'] ?? [];

        if (!isset($modules[$module])) {
            return false;
        }

        if (empty($modules[$module])) {
            return true;
        }

        if ($sub === null) {
            return true;
        }

        if (!is_array($modules[$module])) {
            return false;
        }

        return in_array($sub, $modules[$module], true);
    }
}

/* =========================================================
   PENDING SERVICE COUNT
   ========================================================= */

   $pending_service_count = 0;

   if (isset($conn) && $conn instanceof mysqli) {

    $branch_filter = '';

    if (
        $role !== 'super_admin' &&
        !empty($_SESSION['branch'])
    ) {
        $branch_safe = mysqli_real_escape_string(
            $conn,
            $_SESSION['branch']
        );

        $branch_filter = " AND c.branch = '$branch_safe' ";
    }

    $pending_sql = "
    SELECT COUNT(*) AS total
    FROM tbl_pending_services ps
    JOIN tbl_client c
    ON ps.client_Id = c.Id
    WHERE ps.status = 'Pending'
    $branch_filter
    ";

    $pending_result = @mysqli_query($conn, $pending_sql);

    if ($pending_result) {

        $pending_row = mysqli_fetch_assoc($pending_result);

        $pending_service_count =
        (int)($pending_row['total'] ?? 0);
    }
}

/* =========================================================
   COMPLAINT COUNT
   ========================================================= */

   $complaint_count = 0;

   if (isset($conn) && $conn instanceof mysqli) {

    $branch_filter_complaint = '';

    if (
        $role !== 'super_admin' &&
        !empty($_SESSION['branch'])
    ) {

        $branch_safe = mysqli_real_escape_string(
            $conn,
            $_SESSION['branch']
        );

        $branch_filter_complaint =
        " AND cl.branch = '$branch_safe' ";
    }

    $complaint_sql = "
    SELECT COUNT(*) AS total
    FROM tbl_complaints cmp
    JOIN tbl_client cl
    ON cmp.client_id = cl.Id
    WHERE 1=1
    $branch_filter_complaint
    ";

    $complaint_result =
    @mysqli_query($conn, $complaint_sql);

    if ($complaint_result) {

        $complaint_row =
        mysqli_fetch_assoc($complaint_result);

        $complaint_count =
        (int)($complaint_row['total'] ?? 0);
    }
}

include_once __DIR__ . '/notifications.php';

/* =========================================================
   CHAT UNREAD COUNT
   ========================================================= */

   $totalUnread = 0;
   $latestSenderName = '';

   if (
    !empty($_SESSION['email']) &&
    isset($conn) &&
    $conn instanceof mysqli
) {

    $receiver = mysqli_real_escape_string(
        $conn,
        $_SESSION['email']
    );

    $chat_result = @mysqli_query(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM chats
        WHERE receiver_email = '$receiver'
        AND is_read = 0
        "
    );

    if ($chat_result) {

        $chat_row =
        mysqli_fetch_assoc($chat_result);

        $totalUnread =
        (int)($chat_row['total'] ?? 0);
    }

    $sender_result = @mysqli_query(
        $conn,
        "
        SELECT u.name
        FROM chats c
        JOIN users u
        ON c.sender_email = u.email
        WHERE c.receiver_email = '$receiver'
        AND c.is_read = 0
        ORDER BY c.id DESC
        LIMIT 1
        "
    );

    if (
        $sender_result &&
        mysqli_num_rows($sender_result) > 0
    ) {

        $sender_row =
        mysqli_fetch_assoc($sender_result);

        $latestSenderName =
        $sender_row['name'] ?? '';
    }
}
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
    >

    <title>SHUL Ventures Pvt. Ltd.</title>

    <link
    rel="shortcut icon"
    type="image/png"
    href="image/logo.png"
    >

    <link
    rel="stylesheet"
    href="css/font-awesome.css"
    >

    <link
    rel="stylesheet"
    href="css/shul-dashboard.css"
    >

<link
    rel="stylesheet"
    href="css/SolarModule.css"
>

</head>

<body>

    <div
    id="shul-app"
    class="shul-app-layout"
    >

<!-- =====================================================
     HEADER
===================================================== -->

<header class="shul-header">

    <!-- LEFT HEADER -->
    <div class="shul-header-left">

        <div class="shul-brand">

            <img
            src="image/logo.png"
            alt="SHUL"
            >

            <div class="shul-brand-text">

                <div class="shul-brand-title">
                    SHUL
                </div>

                <div class="shul-brand-sub">
                    Ventures Pvt. Ltd.
                </div>

            </div>

        </div>

        <button
        type="button"
        id="shulSidebarToggle"
        class="shul-sidebar-toggle"
        title="Toggle Sidebar"
        >
        «
    </button>

</div>


<!-- HEADER CONTENT -->

<div class="shul-header-content">

    <div class="shul-header-greeting">

        <div class="shul-greeting-title">

            Good Morning,
            <?= htmlspecialchars($header_name) ?>

            👋

        </div>

        <div class="shul-greeting-date">

            <?= date('l, d F Y') ?>

        </div>

    </div>


    <div class="shul-header-actions">

        <!-- SEARCH -->

        <div class="shul-search-wrap">

            <span class="shul-search-icon">
                🔍
            </span>

            <input
            type="text"
            id="sidebarSearch"
            placeholder="Search clients..."
            autocomplete="off"
            onkeyup="searchSidebar(event)"
            onkeydown="handleEnterSearch(event)"
            >

            <div
            id="searchResults"
            class="global-search-results"
            ></div>

        </div>


        <!-- LIVE -->

        <div class="shul-live-badge">

            <span></span>

            Live

        </div>


        <!-- CHAT -->

        <button
        type="button"
        class="shul-icon-button"
        title="Chat"
        onclick="window.location.href='chat.php'"
        >
        💬
    </button>


    <!-- NOTIFICATION -->

    <div class="shul-notification-wrap">

        <button
        type="button"
        class="shul-icon-button notification-button"
        title="Notifications"
        id="shulNotificationButton"
        >
        🔔
    </button>

    <div id="shulNotificationMenu" class="shul-notification-menu">

        <?php if ($notification_total > 0): ?>
            <?php foreach ($notifications as $note): ?>
                <a href="<?= htmlspecialchars($note['page']) ?>" class="shul-notification-item">
                    <span class="shul-notification-icon"><?= $note['icon'] ?></span>
                    <span><?= htmlspecialchars($note['text']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="shul-notification-empty">No new notifications</div>
        <?php endif; ?>

    </div>

</div>

<!-- PROFILE -->

<div class="shul-profile-wrap">

    <button
    type="button"
    class="shul-profile-button"
    id="shulProfileButton"
    >

    <span class="shul-avatar">

        <?= strtoupper(
            substr($header_name, 0, 1)
        ) ?>

    </span>


    <span class="shul-profile-copy">

        <strong>
            <?= htmlspecialchars($header_name) ?>
        </strong>

        <small>
            <?= htmlspecialchars(
                $header_role ?: 'Admin'
            ) ?>
        </small>

    </span>


    <span class="shul-profile-arrow">
        ▼
    </span>

</button>


<div
id="shulProfileMenu"
class="shul-profile-menu"
>

<a href="profile.php">
    <i>👤</i>
    Profile
</a>

<a
href="logout.php"
class="danger"
>
<i class="fa fa-sign-out"></i>
Log Out
</a>

</div>

</div>

</div>

</div>

</header>


<!-- MOBILE OVERLAY -->

<div
id="shulSidebarOverlay"
class="shul-sidebar-overlay"
></div>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside
id="shulSidebar"
class="shul-sidebar-scroll"
>

<div class="php-sidebar-navigation">

    <ul
    class="sidebar-menu"
    id="nav-accordion"
    >


<!-- DASHBOARD -->

<li>

    <a
    href="homepage.php"
    class="active"
    >

    🏠

    <span>
        Dashboard
    </span>

</a>

</li>

<!-- CHAT -->

<li>

    <a href="chat.php">

        💬
        
        <span>
            Chat
        </span>

        <?php if ($totalUnread > 0): ?>

            <span class="badge">

                <?= $totalUnread ?>

            </span>

        <?php endif; ?>

    </a>

    <?php if ($latestSenderName !== ''): ?>

        <div class="latest-sender">

            <?= htmlspecialchars($latestSenderName) ?>

        </div>

    <?php endif; ?>

</li>




<li class="sidebar-section-label">
    MODULES
</li>



<!-- =====================================================
     LEAD MANAGEMENT
===================================================== -->

<?php if (
    hasAccess('inquiry','add') ||
    hasAccess('inquiry','view') ||
    hasAccess('inquiry','followup') ||
    hasAccess('inquiry','schedule') ||
    hasAccess('inquiry','quotations')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        🎯 

        <span>
            Lead Management
        </span>

    </a>


    <ul class="sub">

       <!-- ADD INQUIRY -->

       <?php if (
        hasAccess('inquiry','add')
    ): ?>

    <li>

        <a href="add-inquiry.php">

            Add Inquiry

        </a>

    </li>

<?php endif; ?>   

<?php if (
    hasAccess('inquiry','view')
): ?>

<li>
    <a href="view-inquiry.php">
        View Inquiry
    </a>
</li>

<?php endif; ?>


<?php if (
    hasAccess('inquiry','followup')
): ?>

<li>
    <a href="view-all-inquiry-clients.php">
        View Followup
    </a>
</li>

<?php endif; ?>


<?php if (
    hasAccess('inquiry','schedule')
): ?>

<li>
    <a href="view-todays-schedule-client.php">
        Today's Schedule
    </a>
</li>

<?php endif; ?>


<?php if (
    hasAccess('inquiry','quotations')
): ?>

<li>
    <a href="view_quotations.php">
        Quotations
    </a>
</li>

<?php endif; ?>

</ul>

</li>

<?php endif; ?>


<!-- =====================================================
     PEST CONTROL
===================================================== -->

<?php if (
    $role !== 'support_team' &&
    (
        hasAccess('clients','view') ||
        hasAccess('clients','certificates') ||
        hasAccess('clients','amc') ||
        hasAccess('clients','pending_service') ||
        hasAccess('clients','complaints')
    )
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">
        🐛

        <span>
            Pest Control Management
        </span>

    </a>


    <ul class="sub">


<!-- CLIENTS -->

<?php if (
    hasAccess('clients','view') ||
    hasAccess('clients','certificates')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        <span>
            Clients
        </span>

    </a>


    <ul class="sub">

        <?php if (
            hasAccess('clients','view')
        ): ?>

        <li>
            <a href="view-client.php">
                View Clients
            </a>
        </li>

    <?php endif; ?>


    <?php if (
        hasAccess('clients','certificates')
    ): ?>

    <li>
        <a href="view-sent-certificates.php">
            Certificate Clients
        </a>
    </li>

<?php endif; ?>

</ul>

</li>

<?php endif; ?>


<!-- AMC -->

<?php if (
    hasAccess('clients','amc')
): ?>

<li>

    <a href="view-AMC.php">


        <span>
            AMC Services
        </span>

    </a>

</li>

<?php endif; ?>


<!-- PENDING SERVICE -->

<?php if (
    hasAccess('clients','pending_service')
): ?>

<li>

    <a href="view-pending-service.php">


        <span>
            Pending Service
        </span>

        <?php if (
            $pending_service_count > 0
        ): ?>

        <span class="badge">
            <?= $pending_service_count ?>
        </span>

    <?php endif; ?>

</a>

</li>

<?php endif; ?>


<!-- COMPLAINTS -->

<?php if (
    hasAccess('clients','complaints')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">


        <span>
            Complaints
        </span>

        <?php if (
            $complaint_count > 0
        ): ?>

        <span class="badge">
            <?= $complaint_count ?>
        </span>

    <?php endif; ?>

</a>


<ul class="sub">

    <li>
        <a href="view-complaints.php">
            View Complaints
        </a>
    </li>

    <li>
        <a href="view-resolved-complaints.php">
            Resolve Complaints
        </a>
    </li>

</ul>

</li>

<?php endif; ?>


</ul>

</li>

<?php endif; ?>


<!-- =====================================================
     ACCOUNTS
===================================================== -->

<?php if (
    hasAccess('accounts','payments_due') ||
    hasAccess('accounts','payments_view') ||
    hasAccess('accounts','payments_defaulter') ||
    hasAccess('accounts','gst_view') ||
    hasAccess('accounts','gst_generate') ||
    hasAccess('accounts','expenses_add/view') ||
    hasAccess('accounts','advance_view/add') ||
    hasAccess('accounts','TDS_view/add') ||
    hasAccess('consultancy','payment_due')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        💳

        <span>
            Accounts
        </span>

    </a>


    <ul class="sub">


<!-- PAYMENTS -->

<li class="sub-menu">

    <a href="javascript:void(0);">

        <i class="fa fa-money"></i>

        <span>
            Payments
        </span>

    </a>


    <ul class="sub">

        <?php if (
            hasAccess('accounts','payments_due')
        ): ?>

        <li>
            <a href="due-payments.php">
                Due Payments
            </a>
        </li>

    <?php endif; ?>


    <?php if (
        hasAccess('consultancy','payment_due') &&
        $role !== 'super_admin'
    ): ?>

    <li>
        <a href="view-candidate-payment.php">
            Due Payments (Consultancy)
        </a>
    </li>

<?php endif; ?>


<?php if (
    hasAccess('accounts','payments_view')
): ?>

<li>
    <a href="view-payments.php">
        View Payments
    </a>
</li>

<?php endif; ?>


<?php if (
    hasAccess('accounts','payments_defaulter')
): ?>

<li>
    <a href="view-defaulters.php">
        Defaulter Payments
    </a>
</li>

<?php endif; ?>

</ul>

</li>


<!-- GST -->

<?php if (
    hasAccess('accounts','gst_view')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        <i class="fa fa-money"></i>

        <span>
            GST Bills
        </span>

    </a>


    <ul class="sub">

        <?php if (
            hasAccess('accounts','gst_generate')
        ): ?>

        <li>
            <a href="view-gst-client.php">
                Generate Invoice
            </a>
        </li>

    <?php endif; ?>


    <li>
        <a href="view-gst-bills.php">
            GST Invoices
        </a>
    </li>

</ul>

</li>

<?php endif; ?>


<!-- EXPENSE -->

<?php if (
    hasAccess('accounts','expenses_add/view')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        <i class="fa fa-money"></i>

        <span>
            Expenses
        </span>

    </a>


    <ul class="sub">

        <li>
            <a href="add expenses.php">
                Add Expenses
            </a>
        </li>

        <li>
            <a href="view-expenses.php">
                View Expenses
            </a>
        </li>

    </ul>

</li>

<?php endif; ?>


<!-- ADVANCE -->

<?php if (
    hasAccess('accounts','advance_view/add')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        <i class="fa fa-money"></i>

        <span>
            Advance
        </span>

    </a>


    <ul class="sub">

        <li>
            <a href="add_advance.php">
                Add Advance
            </a>
        </li>

        <li>
            <a href="view_advance.php">
                View Advance
            </a>
        </li>

    </ul>

</li>

<?php endif; ?>


<!-- TDS -->

<?php if (
    hasAccess('accounts','TDS_view/add')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        <i class="fa fa-credit-card"></i>

        <span>
            TDS
        </span>

    </a>


    <ul class="sub">

        <li>
            <a href="due-tds-payments.php">
                Due TDS
            </a>
        </li>

        <li>
            <a href="view-paid-tds.php">
                Paid TDS
            </a>
        </li>

    </ul>

</li>

<?php endif; ?>


</ul>

</li>

<?php endif; ?>


<!-- =====================================================
     SUPPORT TEAM
===================================================== -->

<?php if (
    $role === 'support_team' &&
    hasAccess('clients')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        👤

        <span>
            Clients
        </span>

    </a>


    <ul class="sub">

        <li>
            <a href="view-client.php">
                View Clients
            </a>
        </li>

        <li>
            <a href="view-AMC.php">
                AMC Clients
            </a>
        </li>

        <li>
            <a href="view-sent-certificates.php">
                Certificate Clients
            </a>
        </li>

    </ul>

</li>

<?php endif; ?>


<?php if (
    $role === 'support_team' &&
    hasAccess('pending_service')
): ?>

<li>

    <a href="view-pending-service.php">

        <i class="fa fa-commenting"></i>

        <span>
            Pending Service
        </span>

        <?php if (
            $pending_service_count > 0
        ): ?>

        <span class="badge">
            <?= $pending_service_count ?>
        </span>

    <?php endif; ?>

</a>

</li>

<?php endif; ?>


<?php if (
    $role === 'support_team' &&
    hasAccess('complaints')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        <i class="fa fa-warning"></i>

        <span>
            Complaints
        </span>

        <?php if (
            $complaint_count > 0
        ): ?>

        <span class="badge">
            <?= $complaint_count ?>
        </span>

    <?php endif; ?>

</a>


<ul class="sub">

    <li>
        <a href="view-complaints.php">
            View Complaints
        </a>
    </li>

    <li>
        <a href="view-resolved-complaints.php">
            Resolve Complaints
        </a>
    </li>

</ul>

</li>

<?php endif; ?>


<?php if (
    $role === 'support_team' &&
    hasAccess('due_payments')
): ?>

<li>

    <a href="due-client-payment.php">

        💳

        <span>
            Due Payments
        </span>

    </a>

</li>

<?php endif; ?>


<!-- =====================================================
     EMPLOYEE MANAGEMENT
===================================================== -->

<?php if (
    hasAccess('employee_management','add') ||
    hasAccess('employee_management','view') ||
    hasAccess('employee_management','add_salary')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        👥

        <span>
            Employee Management
        </span>

    </a>


    <ul class="sub">


        <li class="sub-menu">

            <a href="javascript:void(0);">

                <i class="fa fa-user"></i>

                <span>
                    Employee
                </span>

            </a>


            <ul class="sub">

                <?php if (
                    hasAccess('employee_management','add')
                ): ?>

                <li>
                    <a href="add employee.php">
                        Add Employee
                    </a>
                </li>

            <?php endif; ?>


            <?php if (
                hasAccess('employee_management','view')
            ): ?>

            <li>
                <a href="view-employee.php">
                    View Employee
                </a>
            </li>

        <?php endif; ?>


        <li>
            <a href="generate_letter.php">
                Generate Letter
            </a>
        </li>

    </ul>

</li>


<?php if (
    hasAccess('employee_management','add_salary')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        <i class="fa fa-money"></i>

        <span>
            Salary
        </span>

    </a>


    <ul class="sub">

        <li>
            <a href="add-salary.php">
                Add Salary
            </a>
        </li>

    </ul>

</li>

<?php endif; ?>


</ul>

</li>

<?php endif; ?>


<!-- =====================================================
     ATTENDANCE
===================================================== -->

<?php if (
    hasAccess('attendance')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">
        📅

        <span>
            Attendance Management
        </span>

    </a>


    <ul class="sub">

        <li>
            <a href="add-emp-attendance.php">
                Add Attendance
            </a>
        </li>

        <li>
            <a href="view-all-emp-attendance.php">
                View Attendance
            </a>
        </li>

    </ul>

</li>

<?php endif; ?>


<!-- =====================================================
     INVENTORY
===================================================== -->

<?php if (
    hasAccess('all_inventory','purchese_view')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        📦

        <span>
            Inventory Management
        </span>

    </a>


    <ul class="sub">

        <li>
            <a href="add_material.php">
                Add Material Lists
            </a>
        </li>

        <li>
            <a href="view_materials.php">
                View Material Lists
            </a>
        </li>

        <li>
            <a href="add_stock.php">
                Update Purchase Stock
            </a>
        </li>

        <li>
            <a href="view_purchases.php">
                View Purchase Stock
            </a>
        </li>

        <li>
            <a href="view_stock.php">
                View Available Materials
            </a>
        </li>

        <li>
            <a href="stock-history-table.php">
                Update Used Stock History
            </a>
        </li>

        <li>
            <a href="view-materials-used.php">
                View Used Materials Client
            </a>
        </li>

    </ul>

</li>

<?php endif; ?>





<!-- =====================================================
     SOLAR MANAGEMENT
===================================================== -->

<?php if (
    hasAccess('solar_management','inquiry_add') ||
    hasAccess('solar_management','inquiry_view') ||
    hasAccess('solar_management','inquiry_refollowup') ||
    hasAccess('solar_management','inquiry_visit') ||
    hasAccess('solar_management','inquiry_service') ||
    hasAccess('solar_management','inquiry_schedule') ||
    hasAccess('solar_management','client_add') ||
    hasAccess('solar_management','client_view') ||
    hasAccess('solar_management','pending_clients')||
    hasAccess('solar_management','vendor_add') ||
    hasAccess('solar_management','vendor_view') ||
    hasAccess('solar_management','trader_add') ||
    hasAccess('solar_management','trader_view')||
    hasAccess('solar_management','payment_due') ||
    hasAccess('solar_management','payment_view')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        ☀️

        <span>
            Solar Management
        </span>

    </a>

    <ul class="sub solar-sub">

        <!-- INQUIRY -->

        <?php if (
            hasAccess('solar_management','inquiry_add') ||
            hasAccess('solar_management','inquiry_view')
        ): ?>

        <li class="sub-menu">

            <a href="javascript:void(0);">
                <i class="fa fa-envelope"></i>
                <span>Inquiry</span>
            </a>

            <ul class="sub solar-subs-sub">

                <?php if (hasAccess('solar_management','inquiry_add')): ?>
                    <li>
                        <a href="solar-add-inquiry.php">
                            Add Inquiry
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (hasAccess('solar_management','inquiry_view')): ?>
                    <li>
                        <a href="solar-view-inquiry.php">
                            View Inquiry
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (hasAccess('solar_management','inquiry_refollowup')): ?>
                    <li>
                        <a href="solar-view-refollowup.php">
                            View Re-followup
                        </a>
                    </li>
                <?php endif; ?>

                
<!-- VIEW VISIT -->

<?php if (hasAccess('solar_management','inquiry_visit')): ?>
    <li>
        <a href="solar-view-visit.php">
            View Visit
        </a>
    </li>
<?php endif; ?>


<!-- VIEW FUTURE SERVICES -->

<?php if (hasAccess('solar_management','inquiry_service')): ?>
    <li>
        <a href="solar-view-future.php">
            View Future Services
        </a>
    </li>
<?php endif; ?>


<!-- VIEW SCHEDULE -->

<?php if (hasAccess('solar_management','inquiry_schedule')): ?>
    <li>
        <a href="solar-view-schedule.php">
            View Schedule
        </a>
    </li>
<?php endif; ?>



</ul>

</li>

<?php endif; ?>



<!-- VENDOR -->

<?php if (
    hasAccess('solar_management','vendor_add') ||
    hasAccess('solar_management','vendor_view')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">
        <i class="fa fa-building"></i>
        <span>Vendor</span>
    </a>

    <ul class="sub solar-subs-sub">

        <?php if (hasAccess('solar_management','vendor_add')): ?>
            <li>
                <a href="solar-add-vendor.php">
                    Add Vendor
                </a>
            </li>
        <?php endif; ?>

        <?php if (hasAccess('solar_management','vendor_view')): ?>
            <li>
                <a href="solar-view-vendor.php">
                    View Vendor
                </a>
            </li>
        <?php endif; ?>

    </ul>

</li>

<?php endif; ?>


<!-- TRADER -->

<?php if (
    hasAccess('solar_management','trader_add') ||
    hasAccess('solar_management','trader_view')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">
        <i class="fa fa-exchange"></i>
        <span>Trader</span>
    </a>

    <ul class="sub solar-subs-sub">

        <?php if (hasAccess('solar_management','trader_add')): ?>
            <li>
                <a href="solar-add-trader.php">
                    Add Trader
                </a>
            </li>
        <?php endif; ?>

        <?php if (hasAccess('solar_management','trader_view')): ?>
            <li>
                <a href="solar-view-trader.php">
                    View Trader
                </a>
            </li>
        <?php endif; ?>

    </ul>

</li>

<?php endif; ?>


<!-- CLIENT -->

<?php if (
    hasAccess('solar_management','client_view') ||
    hasAccess('solar_management','client_view_gst')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">
        <i class="fa fa-users"></i>
        <span>Clients</span>
    </a>

    <ul class="sub solar-subs-sub">

        <?php if (hasAccess('solar_management','client_view')): ?>
            <li>
                <a href="solar-view-client.php">
                    View Clients
                </a>
            </li>
        <?php endif; ?>

        <?php if (hasAccess('solar_management','client_view_gst')): ?>
            <li>
                <a href="solar-view-gst-client.php">
                    GST Clients
                </a>
            </li>
        <?php endif; ?>

        <?php if (hasAccess('solar_management','pending_clients')): ?>
            <li>
                <a href="solar-all-pending-work.php">
                    View Pending Clients
                </a>
            </li>
        <?php endif; ?>

    </ul>

</li>

<?php endif; ?>


<!-- =====================================================
     SOLAR PAYMENTS
===================================================== -->

<?php if (
    hasAccess('solar_management','payment_due') ||
    hasAccess('solar_management','payment_view')
): ?>

<li class="sub-menu">

    <a href="javascript:void(0);">

        <i class="fa fa-money"></i>

        <span>
            Payments
        </span>

    </a>

    <ul class="sub solar-subs-sub">

        <!-- DUE PAYMENTS -->

        <?php if (
            hasAccess('solar_management','payment_due')
        ): ?>

        <li>
            <a href="solar-due-payment.php">
                Due Payments
            </a>
        </li>

        <?php endif; ?>


        <!-- VIEW PAYMENTS -->

        <?php if (
            hasAccess('solar_management','payment_view')
        ): ?>

        <li>
            <a href="solar-view-payment.php">
                View Payments
            </a>
        </li>

        <?php endif; ?>

    </ul>

</li>

<?php endif; ?>

</ul>

</li>

<?php endif; ?>








<!-- =====================================================
     SUPER ADMIN
===================================================== -->

<?php if (
    $role === 'super_admin'
): ?>

<li>

    <a href="view-users.php">

        👤

        <span>
            Users
        </span>

    </a>

</li>


<li>

    <a href="trash.php">

        🗑️

        <span>
            Trash
        </span>

    </a>

</li>

<?php endif; ?>



<!-- OPERATOR -->

<?php if (
    $role === 'operator'
): ?>

<li>

    <a href="stock-history-table.php">

        <i class="fa fa-history"></i>

        <span>
            Update Used Stock History
        </span>

    </a>

</li>

<?php endif; ?>


<!-- LOGOUT -->

<li>

    <a
    href="logout.php"
    class="logout-link"
    >

    <i class="fa fa-sign-out"></i>

    <span>
        Log Out
    </span>

</a>

</li>


</ul>

</div>

</aside>


<!-- =====================================================
     MAIN CONTENT START
===================================================== -->

<div
id="shul-main-content"
class="shul-main-content"
>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        const sidebar = document.getElementById('shulSidebar');
        const app = document.getElementById('shul-app');
        const toggle = document.getElementById('shulSidebarToggle');
        const overlay = document.getElementById('shulSidebarOverlay');

        if (!sidebar) {
            return;
        }


    /* =====================================================
       SIDEBAR PARENT MENU
    ===================================================== */

        sidebar
        .querySelectorAll('.sidebar-menu li.sub-menu > a')
        .forEach(function (link) {

            link.addEventListener('click', function (event) {

                /*
                 * Parent menu has javascript:void(0)
                 * so it should ONLY open/close submenu.
                 */
                const href = this.getAttribute('href');

                if (
                    href === 'javascript:void(0);' ||
                    href === '#' ||
                    !href
                    ) {

                    event.preventDefault();
                event.stopPropagation();

                const li = this.parentElement;

                    /*
                     * Only toggle this submenu.
                     */
                li.classList.toggle('open');

            }

        });

        });


    /* =====================================================
       CHILD LINKS
    ===================================================== */

        sidebar
        .querySelectorAll('.sidebar-menu ul.sub a')
        .forEach(function (link) {

            link.addEventListener('click', function (event) {

                /*
                 * Allow normal PHP navigation.
                 *
                 * DO NOT use preventDefault() here.
                 */

                event.stopPropagation();

            });

        });


    /* =====================================================
       ACTIVE CURRENT PHP PAGE
    ===================================================== */

        const currentPage =
        window.location.pathname
        .split('/')
        .pop()
        .toLowerCase();


        sidebar
        .querySelectorAll('.sidebar-menu a[href]')
        .forEach(function (link) {

            const href =
            link.getAttribute('href');

            if (!href) {
                return;
            }

            if (
                href === '#' ||
                href === 'javascript:void(0);'
                ) {
                return;
        }

        const linkPage =
        href
        .split('/')
        .pop()
        .split('?')[0]
        .toLowerCase();


        if (
            linkPage === currentPage
            ) {

            link.classList.add('active');


                /*
                 * Automatically open all parent
                 * submenus of the current page.
                 */

        let parent =
        link.closest('li');

        while (parent) {

            const parentMenu =
            parent.parentElement?.closest(
                'li.sub-menu'
                );

            if (!parentMenu) {
                break;
            }

            parentMenu.classList.add('open');

            parent =
            parentMenu;

        }

    }

});


    /* =====================================================
       SIDEBAR TOGGLE
    ===================================================== */

        if (toggle) {

            toggle.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();


                    if (
                        window.innerWidth <= 768
                        ) {

                        app.classList.toggle(
                            'sidebar-mobile-open'
                            );

                    return;

                }


                app.classList.toggle(
                    'sidebar-collapsed-state'
                    );


                if (
                    app.classList.contains(
                        'sidebar-collapsed-state'
                        )
                    ) {

                    this.textContent = '»';

            } else {

                this.textContent = '«';

            }

        }
        );

        }


    /* =====================================================
       MOBILE OVERLAY
    ===================================================== */

        if (overlay) {

            overlay.addEventListener(
                'click',
                function () {

                    app.classList.remove(
                        'sidebar-mobile-open'
                        );

                }
                );

        }


    /* =====================================================
       CLOSE MOBILE SIDEBAR AFTER PAGE LINK
    ===================================================== */

        sidebar
        .querySelectorAll(
            'a:not([href="javascript:void(0);"])'
            )
        .forEach(function (link) {

            link.addEventListener(
                'click',
                function () {

                    if (
                        window.innerWidth <= 768
                        ) {

                        app.classList.remove(
                            'sidebar-mobile-open'
                            );

                }

            }
            );

        });

    });



/* =====================================================
   NOTIFICATION DROPDOWN
===================================================== */

const notifButton = document.getElementById('shulNotificationButton');
const notifMenu = document.getElementById('shulNotificationMenu');

if (notifButton && notifMenu) {

    notifButton.addEventListener('click', function (event) {
        event.stopPropagation();
        notifMenu.classList.toggle('open');
    });

    document.addEventListener('click', function (event) {
        if (!notifMenu.contains(event.target) && event.target !== notifButton) {
            notifMenu.classList.remove('open');
        }
    });

}


/* =====================================================
   PROFILE DROPDOWN
===================================================== */

const profileButton = document.getElementById('shulProfileButton');
const profileMenu = document.getElementById('shulProfileMenu');

if (profileButton && profileMenu) {

    profileButton.addEventListener('click', function (event) {
        event.stopPropagation();
        profileMenu.classList.toggle('open');
    });

    document.addEventListener('click', function (event) {
        if (!profileMenu.contains(event.target) && event.target !== profileButton) {
            profileMenu.classList.remove('open');
        }
    });

}
</script>
<script src="js/search.js"></script>