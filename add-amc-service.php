<?php
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: login.php');
    exit();
}

/* =========================
   DATABASE
========================= */
require_once 'db_connect.php';
include 'header.php';

/* =========================
   GET CLIENT ID
========================= */
$client_id = isset($_POST['client_id'])
    ? intval($_POST['client_id'])
    : (isset($_GET['client_id']) ? intval($_GET['client_id']) : 0);


/* =========================
   ADD SERVICE DATES
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['num_services'])) {

    $num_services = isset($_POST['num_services'])
        ? intval($_POST['num_services'])
        : 0;

    $start_date = (
        isset($_POST['start_date']) &&
        !empty($_POST['start_date'])
    )
        ? $_POST['start_date']
        : date('Y-m-d');


    /* =========================
       GENERATE SERVICE DATES
    ========================= */

    $service_dates = [];

    switch ($num_services) {

        /* 2 SERVICES - EVERY 6 MONTHS */
        case 2:

            $service_dates[] = $start_date;

            $service_dates[] = date(
                'Y-m-d',
                strtotime(
                    "+6 months",
                    strtotime($start_date)
                )
            );

            break;


        /* 3 SERVICES - EVERY 4 MONTHS */
        case 3:

            for ($i = 0; $i < $num_services; $i++) {

                $months_to_add = $i * 4;

                $service_dates[] = date(
                    'Y-m-d',
                    strtotime(
                        "+$months_to_add months",
                        strtotime($start_date)
                    )
                );
            }

            break;


        /* 4 SERVICES - EVERY 3 MONTHS */
        case 4:

            for ($i = 0; $i < $num_services; $i++) {

                $months_to_add = $i * 3;

                $service_dates[] = date(
                    'Y-m-d',
                    strtotime(
                        "+$months_to_add months",
                        strtotime($start_date)
                    )
                );
            }

            break;


        /* 12 SERVICES - MONTHLY */
        case 12:

            for ($i = 0; $i < $num_services; $i++) {

                $service_dates[] = date(
                    'Y-m-d',
                    strtotime(
                        "+$i month",
                        strtotime($start_date)
                    )
                );
            }

            break;


        /* 24 SERVICES - EVERY 15 DAYS */
        case 24:

            for ($i = 0; $i < $num_services; $i++) {

                $days_to_add = $i * 15;

                $service_dates[] = date(
                    'Y-m-d',
                    strtotime(
                        "+$days_to_add days",
                        strtotime($start_date)
                    )
                );
            }

            break;


        /* 36 SERVICES - EVERY 10 DAYS */
        case 36:

            for ($i = 0; $i < $num_services; $i++) {

                $days_to_add = $i * 10;

                $service_dates[] = date(
                    'Y-m-d',
                    strtotime(
                        "+$days_to_add days",
                        strtotime($start_date)
                    )
                );
            }

            break;


        /* 52 SERVICES - EVERY 7 DAYS */
        case 52:

            for ($i = 0; $i < $num_services; $i++) {

                $days_to_add = $i * 7;

                $service_dates[] = date(
                    'Y-m-d',
                    strtotime(
                        "+$days_to_add days",
                        strtotime($start_date)
                    )
                );
            }

            break;


        default:

            echo "
            <script>
                alert('Invalid number of services. Please select a valid option.');
                history.back();
            </script>
            ";

            exit;
    }


    /* =========================
       INSERT SERVICE DATES
    ========================= */

    $stmt_service = $conn->prepare(
        "INSERT INTO tbl_amc (client_id, service_date)
         VALUES (?, ?)"
    );

    if (!$stmt_service) {

        die(
            "Prepare failed: " .
            $conn->error
        );
    }


    foreach ($service_dates as $service_date) {

        $stmt_service->bind_param(
            "is",
            $client_id,
            $service_date
        );


        if (!$stmt_service->execute()) {

            echo "
            <script>
                alert('Error while adding service dates.');
                history.back();
            </script>
            ";

            exit;
        }
    }


    $stmt_service->close();


    /* =========================
       SUCCESS
    ========================= */

    echo "
    <script>
        alert('Service Dates Added Successfully');
        window.location.href='view-AMC.php?client_id=$client_id';
    </script>
    ";

    exit;
}

?>


<!-- =========================================
     MAIN CONTENT
========================================= -->

<section id="main-content">

    <section class="wrapper">

        <div class="reg-w3">

            <div class="service-date-card">


                <!-- =================================
                     HEADER
                ================================== -->

                <div class="service-date-header">

                    <div class="service-date-title">

                        <span class="service-date-icon">
                            📅
                        </span>

                        <div>

                            <h2>
                                Add Service Dates
                            </h2>

                            <p>
                                Schedule customer service dates
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
                    class="service-date-form"
                >

                    <!-- CLIENT ID -->

                    <input
                        type="hidden"
                        name="client_id"
                        value="<?= htmlspecialchars($client_id); ?>"
                    >


                    <!-- NUMBER OF SERVICES -->

                    <div class="form-group">

                        <label for="num_services">

                            How Many Services?

                            <span>*</span>

                        </label>


                        <select
                            id="num_services"
                            name="num_services"
                            required
                        >

                            <option value="">
                                Select Service Schedule
                            </option>

                            <option value="2">
                                2 Services - Every 6 Months
                            </option>

                            <option value="3">
                                3 Services - Every 4 Months
                            </option>

                            <option value="4">
                                4 Services - Every 3 Months
                            </option>

                            <option value="12">
                                12 Services - Monthly
                            </option>

                            <option value="24">
                                24 Services - Every 15 Days
                            </option>

                            <option value="36">
                                36 Services - Every 10 Days
                            </option>

                            <option value="52">
                                52 Services - Every 7 Days
                            </option>

                        </select>

                    </div>


                    <!-- START SERVICE DATE -->

                    <div class="form-group">

                        <label for="start_date">

                            Service Date

                            <span>*</span>

                        </label>

                        <input
                            type="date"
                            id="start_date"
                            name="start_date"
                            value="<?= date('Y-m-d'); ?>"
                            required
                        >

                    </div>


                    <!-- INFORMATION -->

                    <div class="schedule-info">

                        <span class="info-icon">ℹ</span>

                        <span>
                            Service dates will be automatically
                            generated according to the selected schedule.
                        </span>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        <span>📅</span>

                        Add Service Dates

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =========================================
     CSS
========================================= -->

<style>

/* ================================
   SERVICE DATE PAGE
================================ */

.reg-w3 {

    width: 100%;

    min-height: calc(100vh - 70px);

    background: #f3f6fb;

    display: flex;

    justify-content: center;

    align-items: flex-start;

    padding: 30px 20px 40px;

    box-sizing: border-box;
}


/* ================================
   MAIN CARD
================================ */

.service-date-card {

    width: 100%;

    max-width: 405px;

    background: #ffffff;

    border-radius: 15px;

    overflow: hidden;

    border: 3px solid #294bd4;

    box-shadow:
        0 12px 30px rgba(37, 65, 150, 0.15);
}


/* ================================
   HEADER
================================ */

.service-date-header {

    min-height: 78px;

    background: linear-gradient(
        110deg,
        #1749cf 0%,
        #3946dc 48%,
        #7135e8 100%
    );

    color: #ffffff;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 12px 20px;

    box-sizing: border-box;
}


/* ================================
   HEADER TITLE
================================ */

.service-date-title {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 10px;

    text-align: center;
}


.service-date-icon {

    font-size: 21px;

    line-height: 1;
}


.service-date-header h2 {

    margin: 0;

    padding: 0;

    color: #ffffff;

    font-size: 20px;

    font-weight: 700;

    line-height: 25px;

    text-align: center;
}


.service-date-header p {

    margin: 1px 0 0;

    color: rgba(255,255,255,0.95);

    font-size: 10px;

    font-weight: 400;

    text-align: center;
}


/* ================================
   FORM
================================ */

.service-date-form {

    padding: 12px 22px 20px;

    background: #ffffff;
}


/* ================================
   FORM GROUP
================================ */

.service-date-form .form-group {

    margin-bottom: 10px;

    padding: 8px 10px;

    background: #f7f9fc;

    border: 1px solid #e5eaf2;

    border-radius: 9px;

    box-sizing: border-box;
}


/* ================================
   LABEL
================================ */

.service-date-form .form-group label {

    display: block;

    margin-bottom: 4px;

    color: #52627a;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 0.3px;

    text-transform: uppercase;
}


.service-date-form .form-group label span {

    color: #e33b3b;
}


/* ================================
   INPUT + SELECT
================================ */

.service-date-form .form-group input,
.service-date-form .form-group select {

    width: 100%;

    height: 30px;

    display: block;

    box-sizing: border-box;

    border: 1px solid #d4dce8;

    border-radius: 7px;

    background: #ffffff;

    color: #14264d;

    font-family: Arial, sans-serif;

    font-size: 11px;

    outline: none;

    transition: all 0.2s ease;
}


/* INPUT */

.service-date-form .form-group input {

    padding: 5px 9px;
}


/* SELECT */

.service-date-form .form-group select {

    padding: 5px 9px;

    cursor: pointer;
}


/* ================================
   FOCUS
================================ */

.service-date-form .form-group input:focus,
.service-date-form .form-group select:focus {

    border-color: #4656df;

    box-shadow:
        0 0 0 2px rgba(70, 86, 223, 0.10);
}


/* ================================
   DATE
================================ */

.service-date-form
.form-group
input[type="date"] {

    color: #657187;

    cursor: pointer;
}


.service-date-form
.form-group
input[type="date"]::-webkit-calendar-picker-indicator {

    cursor: pointer;

    opacity: 0.8;
}


/* ================================
   INFO BOX
================================ */

.schedule-info {

    display: flex;

    align-items: flex-start;

    gap: 7px;

    margin: 5px 2px 12px;

    padding: 8px 10px;

    background: #eef3ff;

    border: 1px solid #d9e2ff;

    border-radius: 8px;

    color: #52627a;

    font-size: 9px;

    line-height: 14px;
}


.info-icon {

    flex-shrink: 0;

    width: 15px;

    height: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #4656df;

    color: #ffffff;

    font-size: 9px;

    font-weight: bold;
}


/* ================================
   SUBMIT BUTTON
================================ */

.service-date-form .submit-btn {

    width: 100%;

    height: 34px;

    border: 0;

    border-radius: 8px;

    background: linear-gradient(
        100deg,
        #4b3de4,
        #194fd4
    );

    color: #ffffff;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 5px 12px rgba(50, 66, 210, 0.22);

    transition: all 0.2s ease;

    margin-top: 2px;
}


.service-date-form .submit-btn span {

    margin-right: 5px;
}


.service-date-form .submit-btn:hover {

    transform: translateY(-1px);

    box-shadow:
        0 7px 16px rgba(50, 66, 210, 0.30);
}


.service-date-form .submit-btn:active {

    transform: translateY(0);
}


/* ================================
   RESPONSIVE
================================ */

@media (max-width: 600px) {

    .reg-w3 {

        padding: 15px 10px 30px;
    }


    .service-date-card {

        max-width: 100%;
    }


    .service-date-form {

        padding: 12px 14px 18px;
    }


    .service-date-header h2 {

        font-size: 18px;
    }

}

</style>


<!-- =========================================
     JAVASCRIPT
========================================= -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>


</body>
</html>

<?php
$conn->close();
?>
