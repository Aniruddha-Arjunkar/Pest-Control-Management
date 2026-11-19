<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

/* =========================
   CHECK COMPLAINT ID
========================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>
        alert('Invalid complaint ID.');
        window.location.href='view-complaints.php';
    </script>";
    exit();
}

$complaint_id = intval($_GET['id']);


/* =========================
   FETCH COMPLAINT DETAILS
========================= */

$sql = "SELECT 
            c.Id,
            c.details,
            c.complaint_date,
            cl.cust_name,
            cl.services,
            cl.cust_phone,
            cl.cust_address,
            cl.due_date,
            cl.operator_name,
            rc.resolver_name,
            rc.resolve_date
        FROM tbl_complaints c
        JOIN tbl_client cl 
            ON c.client_id = cl.Id
        LEFT JOIN tbl_resolved_complaints rc 
            ON c.Id = rc.client_id 
            AND rc.complaint_date = c.complaint_date
        WHERE c.Id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $complaint_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $complaint = $result->fetch_assoc();

} else {

    echo "<script>
        alert('Complaint not found.');
        window.location.href='view-complaints.php';
    </script>";

    exit();
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

    <title>Complaint Details</title>

</head>


<body>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<section id="main-content">

    <section class="wrapper">

        <div class="complaint-page">

            <div class="complaint-card">


                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="complaint-header">

                    <div class="complaint-header-left">

                        <div class="complaint-icon">
                            📢
                        </div>

                        <div>

                            <h1>
                                Complaint Details
                            </h1>

                            <p>
                                Customer complaint information
                            </p>

                        </div>

                    </div>


                    <!-- STATUS -->

                    <div class="complaint-status">

                        <?php if (!empty($complaint['resolver_name'])) { ?>

                            Resolved

                        <?php } else { ?>

                            Pending

                        <?php } ?>

                    </div>

                </div>


                <!-- =================================================
                     CONTENT
                ================================================== -->

                <div class="complaint-content">


                    <!-- =================================================
                         SECTION TITLE
                    ================================================== -->

                    <div class="complaint-details-title">

                        <span>
                            Client & Complaint Information
                        </span>

                    </div>


                    <!-- =================================================
                         DETAILS GRID
                    ================================================== -->

                    <div class="complaint-details-grid">


                        <!-- CLIENT NAME -->

                        <div class="complaint-detail-box">

                            <div class="complaint-detail-label">
                                Client Name
                            </div>

                            <div class="complaint-detail-value">

                                <?= htmlspecialchars(
                                    $complaint['cust_name'] ?? ''
                                ); ?>

                            </div>

                        </div>


                        <!-- SERVICE TYPE -->

                        <div class="complaint-detail-box">

                            <div class="complaint-detail-label">
                                Service Type
                            </div>

                            <div class="complaint-detail-value">

                                <?= htmlspecialchars(
                                    $complaint['services'] ?? ''
                                ); ?>

                            </div>

                        </div>


                        <!-- PHONE -->

                        <div class="complaint-detail-box">

                            <div class="complaint-detail-label">
                                Phone
                            </div>

                            <div class="complaint-detail-value">

                                <?= htmlspecialchars(
                                    $complaint['cust_phone'] ?? ''
                                ); ?>

                            </div>

                        </div>


                        <!-- ADDRESS -->

                        <div class="complaint-detail-box complaint-detail-full">

                            <div class="complaint-detail-label">
                                Address
                            </div>

                            <div class="complaint-detail-value">

                                <?= htmlspecialchars(
                                    $complaint['cust_address'] ?? ''
                                ); ?>

                            </div>

                        </div>


                        <!-- SERVICE DATE -->

                        <div class="complaint-detail-box">

                            <div class="complaint-detail-label">
                                Service Date
                            </div>

                            <div class="complaint-detail-value complaint-date-value">

                                <?= htmlspecialchars(
                                    $complaint['due_date'] ?? ''
                                ); ?>

                            </div>

                        </div>


                        <!-- OPERATOR -->

                        <div class="complaint-detail-box">

                            <div class="complaint-detail-label">
                                Operator Name
                            </div>

                            <div class="complaint-detail-value">

                                <?= htmlspecialchars(
                                    $complaint['operator_name'] ?? ''
                                ); ?>

                            </div>

                        </div>


                        <!-- COMPLAINT DATE -->

                        <div class="complaint-detail-box">

                            <div class="complaint-detail-label">
                                Complaint Date
                            </div>

                            <div class="complaint-detail-value complaint-date-value">

                                <?= htmlspecialchars(
                                    $complaint['complaint_date'] ?? ''
                                ); ?>

                            </div>

                        </div>


                        <!-- STATUS -->

                        <div class="complaint-detail-box">

                            <div class="complaint-detail-label">
                                Status
                            </div>

                            <div class="complaint-detail-value">

                                <?php

                                if (!empty($complaint['resolver_name'])) {

                                    echo '
                                    <span class="complaint-status-badge complaint-status-resolved">
                                        Resolved
                                    </span>';

                                } else {

                                    echo '
                                    <span class="complaint-status-badge complaint-status-pending">
                                        Pending
                                    </span>';

                                }

                                ?>

                            </div>

                        </div>


                        <!-- COMPLAINT DETAILS -->

                        <div class="complaint-detail-box complaint-detail-full">

                            <div class="complaint-detail-label">
                                Complaint Details
                            </div>

                            <div class="complaint-description">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $complaint['details'] ?? ''
                                    )
                                ); ?>

                            </div>

                        </div>


                        <!-- RESOLVED BY -->

                        <div class="complaint-detail-box">

                            <div class="complaint-detail-label">
                                Resolved By
                            </div>

                            <div class="complaint-detail-value">

                                <?php

                                if (!empty($complaint['resolver_name'])) {

                                    echo htmlspecialchars(
                                        $complaint['resolver_name']
                                    );

                                } else {

                                    echo '<span class="not-resolved">
                                            Not Resolved
                                          </span>';

                                }

                                ?>

                            </div>

                        </div>


                        <!-- RESOLVE DATE -->

                        <div class="complaint-detail-box">

                            <div class="complaint-detail-label">
                                Resolve Date
                            </div>

                            <div class="complaint-detail-value">

                                <?php

                                if (!empty($complaint['resolve_date'])) {

                                    echo htmlspecialchars(
                                        $complaint['resolve_date']
                                    );

                                } else {

                                    echo '<span class="not-resolved">
                                            Not Resolved
                                          </span>';

                                }

                                ?>

                            </div>

                        </div>


                    </div>


                    <!-- =================================================
                         ACTION BUTTONS
                    ================================================== -->

                    <div class="complaint-actions">


                        <!-- RESOLVE -->

                        <?php if (empty($complaint['resolver_name'])) { ?>

                            <a
                                href="resolve-complaint.php?id=<?= urlencode($complaint['Id']); ?>"
                                class="complaint-btn complaint-btn-success"
                            >

                                ✅ Resolve Complaint

                            </a>

                        <?php } ?>


                        <!-- UPDATE -->

                        <a
                            href="update-complaint.php?id=<?= urlencode($complaint['Id']); ?>"
                            class="complaint-btn complaint-btn-warning"
                        >

                            ✏️ Update Complaint

                        </a>


                        <!-- BACK -->

                        <a
                            href="view-complaints.php"
                            class="complaint-btn complaint-btn-primary"
                        >

                            ← Back to Complaints List

                        </a>


                    </div>

                </div>

            </div>

        </div>

    </section>

</section>


<!-- =====================================================
     CSS
===================================================== -->

<style>

/* =========================================================
   COMPLAINT DETAILS PAGE
========================================================= */

.complaint-page {

    width: 100%;

    min-height: calc(100vh - 70px);

    background: #f3f6fb;

    display: flex;

    justify-content: center;

    align-items: flex-start;

    padding: 30px 20px 40px;

    box-sizing: border-box;
}


/* =========================================================
   MAIN CARD
========================================================= */

.complaint-card {

    width: 100%;

    max-width: 900px;

    background: #ffffff;

    border-radius: 15px;

    overflow: hidden;

    border: 3px solid #294bd4;

    box-shadow:
        0 12px 30px rgba(37, 65, 150, 0.15);

}


/* =========================================================
   HEADER
========================================================= */

.complaint-header {

    min-height: 78px;

    background:
        linear-gradient(
            110deg,
            #1749cf 0%,
            #3946dc 48%,
            #7135e8 100%
        );

    color: #ffffff;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 14px 22px;

    box-sizing: border-box;
}


/* =========================================================
   HEADER LEFT
========================================================= */

.complaint-header-left {

    display: flex;

    align-items: center;

    gap: 11px;

    min-width: 0;
}


/* =========================================================
   ICON
========================================================= */

.complaint-icon {

    width: 38px;

    height: 38px;

    border-radius: 9px;

    background: rgba(255,255,255,0.15);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    flex-shrink: 0;
}


/* =========================================================
   HEADER TITLE
========================================================= */

.complaint-header h1 {

    margin: 0;

    padding: 0;

    color: #ffffff;

    font-size: 20px;

    font-weight: 700;

    line-height: 25px;
}


/* =========================================================
   HEADER TEXT
========================================================= */

.complaint-header p {

    margin: 2px 0 0;

    color: rgba(255,255,255,0.95);

    font-size: 10px;

    font-weight: 400;
}


/* =========================================================
   HEADER STATUS
========================================================= */

.complaint-status {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 25px;

    padding: 4px 11px;

    border-radius: 20px;

    background: rgba(255,255,255,0.18);

    border: 1px solid rgba(255,255,255,0.35);

    color: #ffffff;

    font-size: 9px;

    font-weight: 700;

    white-space: nowrap;

    text-transform: uppercase;
}


/* =========================================================
   CONTENT
========================================================= */

.complaint-content {

    padding: 18px 22px 22px;

    background: #ffffff;

    box-sizing: border-box;
}


/* =========================================================
   DETAILS TITLE
========================================================= */

.complaint-details-title {

    display: flex;

    align-items: center;

    margin-bottom: 12px;

    padding-bottom: 8px;

    border-bottom: 1px solid #e5eaf2;
}


.complaint-details-title span {

    color: #253b70;

    font-size: 12px;

    font-weight: 700;

    letter-spacing: 0.2px;
}


/* =========================================================
   DETAILS GRID
========================================================= */

.complaint-details-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 8px;
}


/* =========================================================
   DETAIL BOX
========================================================= */

.complaint-detail-box {

    min-width: 0;

    padding: 10px 12px;

    background: #f7f9fc;

    border: 1px solid #e5eaf2;

    border-radius: 9px;

    box-sizing: border-box;

    transition: all 0.2s ease;
}


.complaint-detail-box:hover {

    border-color: #cbd5ed;

    box-shadow:
        0 3px 8px rgba(37,65,150,0.06);
}


/* =========================================================
   FULL WIDTH
========================================================= */

.complaint-detail-full {

    grid-column: 1 / -1;
}


/* =========================================================
   LABEL
========================================================= */

.complaint-detail-label {

    margin-bottom: 4px;

    color: #52627a;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: 0.3px;

    text-transform: uppercase;
}


/* =========================================================
   VALUE
========================================================= */

.complaint-detail-value {

    min-height: 17px;

    color: #14264d;

    font-family: Arial, sans-serif;

    font-size: 11px;

    font-weight: 500;

    line-height: 17px;

    word-break: break-word;
}


/* =========================================================
   DATE
========================================================= */

.complaint-date-value {

    color: #294bd4;

    font-weight: 700;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.complaint-description {

    min-height: 40px;

    color: #14264d;

    font-size: 11px;

    line-height: 18px;

    word-break: break-word;
}


/* =========================================================
   STATUS BADGES
========================================================= */

.complaint-status-badge {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 21px;

    padding: 3px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 700;

    text-transform: uppercase;
}


/* =========================================================
   RESOLVED
========================================================= */

.complaint-status-resolved {

    background: #e8f8ef;

    color: #16834d;

    border: 1px solid #bce8cf;
}


/* =========================================================
   PENDING
========================================================= */

.complaint-status-pending {

    background: #fff5df;

    color: #a96d00;

    border: 1px solid #f3d89a;
}


/* =========================================================
   NOT RESOLVED
========================================================= */

.not-resolved {

    color: #a96d00;

    font-size: 10px;

    font-weight: 600;
}


/* =========================================================
   ACTIONS
========================================================= */

.complaint-actions {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-top: 15px;

    padding-top: 13px;

    border-top: 1px solid #e5eaf2;
}


/* =========================================================
   COMMON BUTTON
========================================================= */

.complaint-btn {

    min-height: 32px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 7px 13px;

    box-sizing: border-box;

    border-radius: 8px;

    text-decoration: none !important;

    font-size: 10px;

    font-weight: 700;

    line-height: 16px;

    cursor: pointer;

    transition: all 0.2s ease;

    white-space: nowrap;
}


/* =========================================================
   SUCCESS
========================================================= */

.complaint-btn-success {

    background:
        linear-gradient(
            100deg,
            #159957,
            #13b86b
        );

    color: #ffffff !important;

    box-shadow:
        0 4px 10px rgba(21,153,87,0.20);
}


/* =========================================================
   WARNING
========================================================= */

.complaint-btn-warning {

    background:
        linear-gradient(
            100deg,
            #e89b18,
            #f0b323
        );

    color: #ffffff !important;

    box-shadow:
        0 4px 10px rgba(232,155,24,0.20);
}


/* =========================================================
   PRIMARY
========================================================= */

.complaint-btn-primary {

    background:
        linear-gradient(
            100deg,
            #4b3de4,
            #194fd4
        );

    color: #ffffff !important;

    box-shadow:
        0 4px 10px rgba(50,66,210,0.20);
}


/* =========================================================
   HOVER
========================================================= */

.complaint-btn:hover {

    transform: translateY(-1px);

    color: #ffffff !important;
}


.complaint-btn-success:hover {

    box-shadow:
        0 6px 14px rgba(21,153,87,0.30);
}


.complaint-btn-warning:hover {

    box-shadow:
        0 6px 14px rgba(232,155,24,0.30);
}


.complaint-btn-primary:hover {

    box-shadow:
        0 6px 14px rgba(50,66,210,0.30);
}


/* =========================================================
   ACTIVE
========================================================= */

.complaint-btn:active {

    transform: translateY(0);
}


/* =========================================================
   TABLET / MOBILE
========================================================= */

@media (max-width: 768px) {

    .complaint-page {

        padding: 15px 10px 30px;
    }


    .complaint-card {

        max-width: 100%;

        border-width: 2px;
    }


    /* HEADER */

    .complaint-header {

        padding: 12px 14px;

        min-height: 70px;
    }


    .complaint-header h1 {

        font-size: 17px;

        line-height: 21px;
    }


    .complaint-header p {

        font-size: 9px;
    }


    .complaint-icon {

        width: 34px;

        height: 34px;

        font-size: 17px;
    }


    .complaint-status {

        font-size: 8px;

        padding: 3px 8px;
    }


    /* CONTENT */

    .complaint-content {

        padding: 14px 12px 18px;
    }


    /* GRID */

    .complaint-details-grid {

        grid-template-columns: 1fr;

        gap: 7px;
    }


    .complaint-detail-full {

        grid-column: auto;
    }


    /* BUTTONS */

    .complaint-actions {

        display: flex;

        flex-direction: column;

        gap: 7px;
    }


    .complaint-btn {

        width: 100%;

        min-height: 34px;

        font-size: 10px;
    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .complaint-page {

        padding: 10px 7px 25px;
    }


    .complaint-header {

        align-items: flex-start;
    }


    .complaint-header-left {

        gap: 8px;
    }


    .complaint-icon {

        width: 31px;

        height: 31px;

        font-size: 15px;
    }


    .complaint-header h1 {

        font-size: 15px;

        line-height: 19px;
    }


    .complaint-header p {

        font-size: 8px;
    }


    .complaint-status {

        font-size: 7px;

        padding: 3px 6px;
    }


    .complaint-content {

        padding: 12px 10px 15px;
    }


    .complaint-details-title span {

        font-size: 10px;
    }


    .complaint-detail-box {

        padding: 8px 10px;
    }


    .complaint-detail-label {

        font-size: 8px;
    }


    .complaint-detail-value,

    .complaint-description {

        font-size: 10px;
    }

}

</style>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<script src="js/jquery.scrollTo.js"></script>


</body>

</html>

<?php

$stmt->close();
$conn->close();

?>