<?php
session_start();
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
}
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location: login.php');
}

require_once 'db_connect.php';
include 'header.php';

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $sql = "SELECT * FROM users WHERE Id = $id";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $name = $row['name'];
        $phone = $row['phone'];
        $email = $row['email'];
        $password = $row['password'];
        $role = $row['role'];
        $branch = $row['branch'];
        $access_modules = explode(' , ', $row['access_modules']); // Convert to array
    } else {
        echo "User not found.";
        exit();
    }
} else {
    echo "Missing ID.";
    exit();
}
?>

<style>

/* =========================================================
   UPDATE USER PAGE
   MATCH ADD EXPENSES DESIGN
========================================================= */

.inquiry-page {
    width: 100%;
    min-height: calc(100vh - 60px);

    display: flex;
    justify-content: center;
    align-items: flex-start;

    padding: 25px 15px 50px;

    box-sizing: border-box;
}


/* =========================================================
   MAIN CARD
========================================================= */

.inquiry-card {
    width: 500px;
    max-width: 100%;

    background: #ffffff;

    border: 2px solid #2455dc;
    border-radius: 14px;

    box-shadow:
        0 12px 30px rgba(30, 64, 175, .18);

    overflow: hidden;

    padding: 0;

    box-sizing: border-box;
}

.user-update-card {
    width: 100%;
}


/* =========================================================
   HEADER
========================================================= */

.inquiry-page-header {
    width: 100%;

    padding: 14px 15px 13px;

    text-align: center;

    background: linear-gradient(
        135deg,
        #2058d8,
        #6034e8
    );

    color: #fff;

    box-sizing: border-box;
}

.inquiry-page-header h1 {
    margin: 0;

    color: #fff;

  
    font-weight: 700;

    line-height: 1.2;
}



/* =========================================================
   FORM AREA
========================================================= */

.user-update-card form {
    padding: 12px 20px 18px;
}


/* =========================================================
   FORM FIELD
========================================================= */

.user-form-field {
    margin-bottom: 10px;

    padding: 8px 9px;

    background: #f5f8fc;

    border: 1px solid #e1e8f2;

    border-radius: 8px;

    box-sizing: border-box;
}

.user-form-field label {
    display: block;

    margin: 0 0 5px;

    color: #38557d;

  
    font-weight: 700;

    letter-spacing: .3px;
}


/* =========================================================
   INPUT
========================================================= */

.refollowup-input {
    width: 100%;

    height: 32px;

    border: 1px solid #cbd8e8;

    border-radius: 7px;

    background: #fff;

    color: #334155;



    padding: 0 10px;

    outline: none;

    box-sizing: border-box;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;
}

.refollowup-input:focus {
    border-color: #4f70e8;

    box-shadow:
        0 0 0 3px
        rgba(79, 70, 229, .08);
}


/* =========================================================
   MODULE ACCESS TITLE
========================================================= */

.user-module-access {
    margin-top: 15px;
}

.user-module-access > label {
    display: block;

    margin-bottom: 10px;

    color: #38557d;

   
    font-weight: 700;
}


/* =========================================================
   MODULE DROPDOWN
========================================================= */

.module-dropdown {
    width: 100%;

    margin-bottom: 6px;

    border: 1px solid #cbd8e8;

    border-radius: 7px;

    background: #f7f9fc;

    overflow: hidden;

    box-sizing: border-box;
}


/* =========================================================
   DROPDOWN BUTTON
========================================================= */

.module-dropdown-btn {
    width: 100%;

    height: 32px;

    border: 0;

    background: #f7f9fc;

    padding: 0 9px;

    text-align: left;

    color: #334155;

   
    font-weight: 700;

    cursor: pointer;
}

.module-dropdown-btn:hover {
    background: #eef3fa;
}

.module-dropdown-btn span {
    display: inline-block;

    width: 18px;

    color: #2058d8;

    transition: transform .2s ease;
}


/* =========================================================
   DROPDOWN CONTENT
========================================================= */
.module-dropdown-content {
    display: block;
    padding: 7px 10px;
    background: #fff;
    border-top: 1px solid #e3e9f1;
}

.module-dropdown.open .module-dropdown-content {
    display: block;
}

.module-dropdown.open .module-dropdown-btn span {
    transform: rotate(90deg);
}


/* =========================================================
   CHECKBOX LABEL
========================================================= */

.module-dropdown-content label {
    display: block;

    margin: 5px 0;


  
    cursor: pointer;
}

.module-dropdown-content label:not(:first-child) {
    padding-left: 15px;
}

.module-dropdown-content input[type="checkbox"] {
    margin-right: 6px;

    cursor: pointer;
}


/* =========================================================
   UPDATE BUTTON
========================================================= */

.refollowup-filter-bar {
    width: 100%;

    display: flex;

    margin-top: 15px;
}

.refollowup-search-btn {
    width: 100%;

    height: 36px;
font-size:15px;
    border: 0;

    border-radius: 7px;

    background: linear-gradient(
        135deg,
        #5135e8,
        #2058d8
    );

    color: #fff;


    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 5px 12px
        rgba(32, 88, 216, .20);
}

.refollowup-search-btn:hover {
    background: linear-gradient(
        135deg,
        #4328d7,
        #1748c7
    );
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 500px) {

    .inquiry-page {
        padding: 15px 8px 30px;
    }

    .inquiry-card {
        width: 100%;
        max-width: 365px;
    }

    .user-update-card form {
        padding: 12px 15px 18px;
    }

   
}
body,
#main-content,
.wrapper,
.inquiry-page {
    padding:8px!important;
    background: #ffffff !important;
    background-image: none !important;
}
</style>


<body>
 <section id="main-content">

    <section class="wrapper">
        <div class="inquiry-page">

    <div class="inquiry-card">
        <div class="user-update-card">

            <div class="inquiry-page-header">
                <h1>Update User</h1>
            
            </div>

       <form action="" method="post">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id); ?>">
<div class="user-form-field">

    <label>USER NAME *</label>

   <input
    type="text"
    class="refollowup-input"
    name="name"
    placeholder="USER NAME"
    required
    value="<?= htmlspecialchars($name); ?>"
>
</div>


<div class="user-form-field">

    <label>MOBILE NUMBER</label>

   <input
    type="text"
    class="refollowup-input"
    name="phone"
    placeholder="MOBILE NUMBER"
    maxlength="10"
    value="<?= htmlspecialchars($phone); ?>"
>

</div>


<div class="user-form-field">

    <label>EMAIL *</label>

    <input
    type="email"
    class="refollowup-input"
    name="email"
    placeholder="EMAIL"
    required
    value="<?= htmlspecialchars($email); ?>"
>

</div>


<div class="user-form-field">

    <label>USER PASSWORD *</label>

    <input
    type="text"
    class="refollowup-input"
    name="password"
    placeholder="USER PASSWORD"
    required
    value="<?= htmlspecialchars($password); ?>"
>

</div>


<div class="user-form-field">

    <label>ROLE *</label>

   <input
    type="text"
    class="refollowup-input"
    name="role"
    placeholder="ROLE"
    required
    value="<?= htmlspecialchars($role); ?>"
>

</div>


<div class="user-form-field">

    <label>BRANCH</label>

    <input
    type="text"
    class="refollowup-input"
    name="branch"
    placeholder="BRANCH"
    value="<?= htmlspecialchars($branch); ?>"
>

</div>

<div class="user-module-access">

    <label><b>Module Access:</b></label><br><br>

    <!-- =====================================================
         INQUIRY DROPDOWN
    ====================================================== -->
    <div class="module-dropdown">

        <button type="button" class="module-dropdown-btn">
          <span>▼</span> Inquiry
        </button>

        <div class="module-dropdown-content">

            <label>
                <input type="checkbox" name="modules[]" value="inquiry"
                <?= in_array('inquiry', $access_modules) ? 'checked' : '' ?>>
                All Inquiry
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="inquiry:view"
                <?= in_array('inquiry:view', $access_modules) ? 'checked' : '' ?>>
                View
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="inquiry:followup"
                <?= in_array('inquiry:followup', $access_modules) ? 'checked' : '' ?>>
                Followup
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="inquiry:schedule"
                <?= in_array('inquiry:schedule', $access_modules) ? 'checked' : '' ?>>
                Schedule
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="inquiry:quotations"
                <?= in_array('inquiry:quotations', $access_modules) ? 'checked' : '' ?>>
                Quotations
            </label>

        </div>
    </div>


    <!-- =====================================================
         CLIENTS DROPDOWN
    ====================================================== -->
    <div class="module-dropdown">

        <button type="button" class="module-dropdown-btn">
            <span>▼</span> Clients
        </button>

        <div class="module-dropdown-content">

            <label>
                <input type="checkbox" name="modules[]" value="clients"
                <?= in_array('clients', $access_modules) ? 'checked' : '' ?>>
                All Clients
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="clients:view"
                <?= in_array('clients:view', $access_modules) ? 'checked' : '' ?>>
                View Clients
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="clients:certificates"
                <?= in_array('clients:certificates', $access_modules) ? 'checked' : '' ?>>
                Certificate Clients
            </label>
            <label>
                <input type="checkbox" name="modules[]" value="clients:amc"
                <?= in_array('clients:amc', $access_modules) ? 'checked' : '' ?>>
                AMC Clients
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="pending_service"
                <?= in_array('pending_service', $access_modules) ? 'checked' : '' ?>>
                Pending Service
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="due_payments"
                <?= in_array('due_payments', $access_modules) ? 'checked' : '' ?>>
                Due Payments (Support_team)
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="complaints"
                <?= in_array('complaints', $access_modules) ? 'checked' : '' ?>>
                Complaints
            </label>

        </div>
    </div>


    <!-- =====================================================
         ACCOUNTS DROPDOWN
    ====================================================== -->
    <div class="module-dropdown">

        <button type="button" class="module-dropdown-btn">
            <span>▼</span> Accounts
        </button>

        <div class="module-dropdown-content">

            <label>
                <input type="checkbox" name="modules[]" value="accounts"
                <?= in_array('accounts', $access_modules) ? 'checked' : '' ?>>
                All Accounts
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="accounts:payments_view"
                <?= in_array('accounts:payments_view', $access_modules) ? 'checked' : '' ?>>
                Payments View
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="accounts:payments_due"
                <?= in_array('accounts:payments_due', $access_modules) ? 'checked' : '' ?>>
                Payments Due (All)
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="accounts:payments_defaulter"
                <?= in_array('accounts:payments_defaulter', $access_modules) ? 'checked' : '' ?>>
                Payments Defaulter
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="accounts:gst_view"
                <?= in_array('accounts:gst_view', $access_modules) ? 'checked' : '' ?>>
                GST View
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="accounts:gst_generate"
                <?= in_array('accounts:gst_generate', $access_modules) ? 'checked' : '' ?>>
                Generate GST
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="accounts:expenses_add/view"
                <?= in_array('accounts:expenses_add/view', $access_modules) ? 'checked' : '' ?>>
                Expenses Add/View
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="accounts:advance_view/add"
                <?= in_array('accounts:advance_view/add', $access_modules) ? 'checked' : '' ?>>
                Advance View/Add
            </label>

            <label>
                <input type="checkbox" name="modules[]" value="accounts:TDS_view/add"
                <?= in_array('accounts:TDS_view/add', $access_modules) ? 'checked' : '' ?>>
                TDS View/Add
            </label>

        </div>
    </div>

<!-- =====================================================
     EMPLOYEE DROPDOWN
====================================================== -->
<div class="module-dropdown">

    <button type="button" class="module-dropdown-btn">
        <span>▼</span> Employee
    </button>

    <div class="module-dropdown-content">

        <label>
            <input type="checkbox" name="modules[]" value="employee_management"
            <?= in_array('employee_management', $access_modules) ? 'checked' : '' ?>>
            All Employee
        </label>

        <label>
            <input type="checkbox" name="modules[]" value="employee_management:add"
            <?= in_array('employee_management:add', $access_modules) ? 'checked' : '' ?>>
            Add Employee
        </label>

        <label>
            <input type="checkbox" name="modules[]" value="employee_management:view"
            <?= in_array('employee_management:view', $access_modules) ? 'checked' : '' ?>>
            View Employee
        </label>

    </div>
</div>
<!-- =====================================================
     ATTENDANCE
====================================================== -->
<div class="module-dropdown">

    <button type="button" class="module-dropdown-btn">
        <span>▼</span> Attendance
    </button>

    <div class="module-dropdown-content">

        <label>
            <input type="checkbox" name="modules[]" value="attendance"
            <?= in_array('attendance', $access_modules) ? 'checked' : '' ?>>
            All Attendance
        </label>

        <!-- Add Attendance sub-modules here -->

    </div>
</div>



<!-- =====================================================
     INVENTORY
====================================================== -->
<div class="module-dropdown">

    <button type="button" class="module-dropdown-btn">
        <span>▼</span> Inventory
    </button>

    <div class="module-dropdown-content">

        <label>
            <input type="checkbox" name="modules[]" value="all_inventory"
            <?= in_array('all_inventory', $access_modules) ? 'checked' : '' ?>>
            All Inventory
        </label>

        <!-- Add Inventory sub-modules here -->

    </div>
</div>


<div class="refollowup-filter-bar">

    <input
        type="submit"
        name="update"
        value="Update"
        class="refollowup-search-btn"
    >

</div>
              </form>

    </div>
</div>
        <?php
        if (isset($_POST['update'])) {
            $name     = mysqli_real_escape_string($conn, $_POST['name']);
            $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
            $email    = mysqli_real_escape_string($conn, $_POST['email']);
            $password = mysqli_real_escape_string($conn, $_POST['password']);
            $role     = mysqli_real_escape_string($conn, $_POST['role']);
            $branch   = mysqli_real_escape_string($conn, $_POST['branch']);
            $modules  = isset($_POST['modules']) ? $_POST['modules'] : [];
            $access_modules_str = implode(' , ', $modules);
            
             // Check if email already exists for another user
    $check_email_sql = "SELECT Id FROM users WHERE email='$email' AND Id != $id";
    $check_result = mysqli_query($conn, $check_email_sql);

    if (mysqli_num_rows($check_result) > 0) {
        // Email exists for another user
        echo "<script>alert('Email already exists for another user! Please choose a different email.');</script>";
    } else {

            $update_sql = "UPDATE users SET 
                            name = '$name',
                            phone = '$phone',
                            email = '$email',
                            password = '$password',
                            role = '$role',
                            branch = '$branch',
                            access_modules = '$access_modules_str'
                            WHERE Id = $id";

            if (mysqli_query($conn, $update_sql)) {
                echo "<script>alert('User updated successfully'); window.location.href='view-users.php';</script>";
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        }
        }
        ?>
    </div>
</div>

</section>
</section>
<script src="js/bootstrap.js"></script>
    <script src="js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/jquery.nicescroll.js"></script>
    <script src="js/jquery.scrollTo.js"></script>
  
</body>
</html>