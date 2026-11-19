```php
<?php
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: login.php');
    exit;
}

/* =========================
   LOGOUT
========================= */
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('Location: login.php');
    exit;
}

/* =========================
   DATABASE
========================= */
require_once 'db_connect.php';
include 'header.php';


/* =========================
   INSERT ASSOCIATE
========================= */
if (isset($_POST['submit'])) {

    $company_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['company_name'] ?? '')
    );

    $location = mysqli_real_escape_string(
        $conn,
        trim($_POST['location'] ?? '')
    );

    $working_domain = mysqli_real_escape_string(
        $conn,
        trim($_POST['working_domain'] ?? '')
    );

    $working_technology = mysqli_real_escape_string(
        $conn,
        trim($_POST['working_technology'] ?? '')
    );

    $no_of_employee = mysqli_real_escape_string(
        $conn,
        trim($_POST['no_of_employee'] ?? '')
    );

    $contact_person = mysqli_real_escape_string(
        $conn,
        trim($_POST['contact_person'] ?? '')
    );

    $phone = mysqli_real_escape_string(
        $conn,
        trim($_POST['phone'] ?? '')
    );

    $email = mysqli_real_escape_string(
        $conn,
        trim($_POST['email'] ?? '')
    );

    $manager_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['manager_name'] ?? '')
    );

    $manager_phone = mysqli_real_escape_string(
        $conn,
        trim($_POST['manager_phone'] ?? '')
    );

    $manager_email = mysqli_real_escape_string(
        $conn,
        trim($_POST['manager_email'] ?? '')
    );

    $pay_package_fresher = mysqli_real_escape_string(
        $conn,
        trim($_POST['pay_package_fresher'] ?? '')
    );

    $pay_package_experienced = mysqli_real_escape_string(
        $conn,
        trim($_POST['pay_package_experienced'] ?? '')
    );

    $sla_agreed = mysqli_real_escape_string(
        $conn,
        trim($_POST['sla_agreed'] ?? 'NO')
    );

    $pay_for_fresher = mysqli_real_escape_string(
        $conn,
        trim($_POST['pay_for_fresher'] ?? '')
    );

    $pay_for_experienced = mysqli_real_escape_string(
        $conn,
        trim($_POST['pay_for_experienced'] ?? '')
    );

    $invoice_send_text = mysqli_real_escape_string(
        $conn,
        trim($_POST['invoice_send_text'] ?? '')
    );


    /* =========================
       REQUIRED VALIDATION
    ========================= */
    if (
        empty($company_name) ||
        empty($location) ||
        empty($working_domain) ||
        empty($working_technology) ||
        empty($no_of_employee) ||
        empty($contact_person) ||
        empty($phone) ||
        empty($email) ||
        empty($pay_package_fresher) ||
        empty($pay_package_experienced)
    ) {

        echo "<script>
            alert('Please fill all required fields');
        </script>";

    } else {

        /* =========================
           SLA DOCUMENT UPLOAD
        ========================= */

        $sla_document = '';

        if (
            isset($_FILES['sla_document']) &&
            !empty($_FILES['sla_document']['name'])
        ) {

            $allowed_ext = [
                'pdf',
                'doc',
                'docx',
                'jpg',
                'png'
            ];

            $ext = strtolower(
                pathinfo(
                    $_FILES['sla_document']['name'],
                    PATHINFO_EXTENSION
                )
            );

            if (in_array($ext, $allowed_ext)) {

                $upload_dir = 'uploads/sla/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $sla_document =
                    'SLA_' . time() . '_' .
                    rand(1000, 9999) . '.' . $ext;

                $upload_path =
                    $upload_dir . $sla_document;

                if (
                    !move_uploaded_file(
                        $_FILES['sla_document']['tmp_name'],
                        $upload_path
                    )
                ) {

                    $sla_document = '';

                    echo "<script>
                        alert('SLA document upload failed');
                    </script>";
                }

            } else {

                echo "<script>
                    alert('Invalid SLA file type. Please upload PDF, DOC, DOCX, JPG or PNG.');
                </script>";

                $sla_document = '';
            }
        }


        /* =========================
           INSERT QUERY
        ========================= */

        $sql = "INSERT INTO tbl_associate (
            company_name,
            location,
            working_domain,
            working_technology,
            no_of_employee,
            contact_person,
            phone,
            email,
            manager_name,
            manager_phone,
            manager_email,
            pay_package_fresher,
            pay_package_experienced,
            sla_agreed,
            pay_for_fresher,
            pay_for_experienced,
            invoice_send_text,
            sla_document
        ) VALUES (
            '$company_name',
            '$location',
            '$working_domain',
            '$working_technology',
            '$no_of_employee',
            '$contact_person',
            '$phone',
            '$email',
            '$manager_name',
            '$manager_phone',
            '$manager_email',
            '$pay_package_fresher',
            '$pay_package_experienced',
            '$sla_agreed',
            '$pay_for_fresher',
            '$pay_for_experienced',
            '$invoice_send_text',
            '$sla_document'
        )";


        if (mysqli_query($conn, $sql)) {

            echo "<script>
                alert('Added Successfully');
                window.location.href='view-associate.php';
            </script>";

            exit;

        } else {

            echo "<script>
                alert('Insert failed: " .
                addslashes(mysqli_error($conn)) .
                "');
            </script>";
        }
    }
}
?>


<!-- =========================================
     MAIN CONTENT
========================================= -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">


                <!-- =================================
                     HEADER
                ================================== -->

                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">🏢</span>

                        <div>

                            <h2>Associates</h2>

                            <p>
                                Add associate company details
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    action=""
                    method="post"
                    enctype="multipart/form-data"
                    class="refollowup-form"
                >


                    <!-- COMPANY NAME -->

                    <div class="form-group">

                        <label>
                            Company Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="company_name"
                            placeholder="Company Name"
                            value="<?= htmlspecialchars($_POST['company_name'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- LOCATION -->

                    <div class="form-group">

                        <label>
                            Location <span>*</span>
                        </label>

                        <input
                            type="text"
                            name="location"
                            placeholder="Location"
                            value="<?= htmlspecialchars($_POST['location'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- DOMAIN + TECHNOLOGY -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Working Domain <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="working_domain"
                                placeholder="Working Domain"
                                value="<?= htmlspecialchars($_POST['working_domain'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Working Technology <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="working_technology"
                                placeholder="Working Technology"
                                value="<?= htmlspecialchars($_POST['working_technology'] ?? ''); ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- EMPLOYEE + CONTACT PERSON -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                No. of Employee <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="no_of_employee"
                                placeholder="No. of Employee"
                                value="<?= htmlspecialchars($_POST['no_of_employee'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Contact Person <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="contact_person"
                                placeholder="Contact Person"
                                value="<?= htmlspecialchars($_POST['contact_person'] ?? ''); ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- PHONE + EMAIL -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Mobile Number <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="phone"
                                maxlength="21"
                                placeholder="Mobile Number"
                                value="<?= htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Email <span>*</span>
                            </label>

                            <input
                                type="email"
                                name="email"
                                placeholder="Email"
                                value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- MANAGER NAME + PHONE -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Manager Name
                            </label>

                            <input
                                type="text"
                                name="manager_name"
                                placeholder="Manager Name"
                                value="<?= htmlspecialchars($_POST['manager_name'] ?? ''); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Manager Phone
                            </label>

                            <input
                                type="text"
                                name="manager_phone"
                                maxlength="10"
                                placeholder="Manager Phone"
                                value="<?= htmlspecialchars($_POST['manager_phone'] ?? ''); ?>"
                            >

                        </div>

                    </div>


                    <!-- MANAGER EMAIL -->

                    <div class="form-group">

                        <label>
                            Manager Email
                        </label>

                        <input
                            type="email"
                            name="manager_email"
                            placeholder="Manager Email"
                            value="<?= htmlspecialchars($_POST['manager_email'] ?? ''); ?>"
                        >

                    </div>


                    <!-- PAY PACKAGE -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Pay Package Fresher <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="pay_package_fresher"
                                placeholder="Pay Package Fresher"
                                value="<?= htmlspecialchars($_POST['pay_package_fresher'] ?? ''); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Pay Package Experienced <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="pay_package_experienced"
                                placeholder="Pay Package Experienced"
                                value="<?= htmlspecialchars($_POST['pay_package_experienced'] ?? ''); ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- SLA AGREED -->

                    <div class="form-group">

                        <label>
                            SLA Agreed
                        </label>

                        <select
                            id="sla_agreed"
                            name="sla_agreed"
                            onchange="updatePayPackages()"
                        >

                            <option value="YES"
                                <?= (($_POST['sla_agreed'] ?? 'YES') == 'YES') ? 'selected' : ''; ?>>
                                Yes
                            </option>

                            <option value="NO"
                                <?= (($_POST['sla_agreed'] ?? '') == 'NO') ? 'selected' : ''; ?>>
                                No
                            </option>

                        </select>

                    </div>


                    <!-- PAY FOR FRESHER + EXPERIENCED -->

                    <div
                        id="pay_fields"
                        class="form-row"
                        style="display:none;"
                    >

                        <div class="form-group">

                            <label>
                                Pay For Fresher
                            </label>

                            <input
                                type="text"
                                id="pay_for_fresher"
                                name="pay_for_fresher"
                                placeholder="Pay For Fresher"
                                value="<?= htmlspecialchars($_POST['pay_for_fresher'] ?? ''); ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Pay For Experienced
                            </label>

                            <input
                                type="text"
                                id="pay_for_experienced"
                                name="pay_for_experienced"
                                placeholder="Pay For Experienced"
                                value="<?= htmlspecialchars($_POST['pay_for_experienced'] ?? ''); ?>"
                            >

                        </div>

                    </div>


                    <!-- SLA DOCUMENT -->

                    <div class="form-group">

                        <label>
                            Upload SLA Document
                        </label>

                        <input
                            type="file"
                            name="sla_document"
                            accept=".pdf,.doc,.docx,.jpg,.png"
                        >

                    </div>


                    <!-- INVOICE SEND TEXT -->

                    <div class="form-group">

                        <label>
                            Invoice Send Text
                        </label>

                        <input
                            type="text"
                            name="invoice_send_text"
                            placeholder="Invoice Send Text (optional)"
                            value="<?= htmlspecialchars($_POST['invoice_send_text'] ?? ''); ?>"
                        >

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>🏢</span>

                        Submit

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =========================================
     JAVASCRIPT
========================================= -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>


<script type="text/javascript">

function updatePayPackages() {

    var slaAgreed =
        document.getElementById('sla_agreed').value;

    var payFields =
        document.getElementById('pay_fields');

    if (slaAgreed === 'YES') {

        payFields.style.display = 'flex';

    } else {

        payFields.style.display = 'none';

    }
}


/* =========================
   PAGE LOAD
========================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        updatePayPackages();

    }
);

</script>


</body>
</html>


<?php
mysqli_close($conn);
?>
```
