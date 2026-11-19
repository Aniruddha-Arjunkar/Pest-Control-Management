<?php

/* =========================================================
   SHUL DASHBOARD
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['email'])) {

    $_SESSION['msg'] =
        "You must log in first";

    header("Location: login.php");

    exit();
}


/* =========================================================
   USER ROLE
========================================================= */

$role = $_SESSION['role'] ?? '';



/* =========================================================
   DATABASE
========================================================= */

include_once 'db_connect.php';

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


/* =========================================================
   DEFAULT VALUES
   These prevent undefined variable errors
========================================================= */

$selected_month = '';
$year = null;
$month = null;

$total_clients = 0;

$nagpur_clients = 0;
$bramhapuri_clients = 0;
$chandrapur_clients = 0;

$total_payments = 0;
$due_payments = 0;
$total_expenses = 0;

$pending_service_count = 0;
$complaint_count = 0;

$chart_year = (int)date('Y');

$chartData = array_fill(
    0,
    12,
    0
);


/* =========================================================
   ONLY SUPER ADMIN DASHBOARD DATA
========================================================= */

if ($role === 'super_admin') {


    /* =====================================================
       TABLE EXISTS
    ===================================================== */

    function tableExists($conn, $table)
    {
        $table = mysqli_real_escape_string(
            $conn,
            $table
        );

        $result = mysqli_query(
            $conn,
            "SHOW TABLES LIKE '$table'"
        );

        return (
            $result &&
            mysqli_num_rows($result) > 0
        );
    }


    /* =====================================================
       MONTH FILTER
    ===================================================== */

    $selected_month =
        $_GET['month'] ?? '';

    if (
        !empty($selected_month) &&
        preg_match(
            '/^\d{4}-\d{2}$/',
            $selected_month
        )
    ) {

        $year =
            (int)substr(
                $selected_month,
                0,
                4
            );

        $month =
            (int)substr(
                $selected_month,
                5,
                2
            );

        if (
            $month < 1 ||
            $month > 12
        ) {

            $year = null;
            $month = null;
            $selected_month = '';
        }

    } else {

        $year = null;
        $month = null;
        $selected_month = '';
    }


    /* =====================================================
       TOTAL CLIENTS
    ===================================================== */

    if (
        $year &&
        $month
    ) {

        $stmt = $conn->prepare(
            "
            SELECT COUNT(*) AS total
            FROM tbl_client
            WHERE YEAR(due_date)=?
            AND MONTH(due_date)=?
            "
        );

        $stmt->bind_param(
            "ii",
            $year,
            $month
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        $row =
            $result->fetch_assoc();

        $total_clients =
            (int)($row['total'] ?? 0);

        $stmt->close();

    } else {

        $result = $conn->query(
            "
            SELECT COUNT(*) AS total
            FROM tbl_client
            "
        );

        $row =
            $result->fetch_assoc();

        $total_clients =
            (int)($row['total'] ?? 0);
    }


    /* =====================================================
       BRANCH COUNT
    ===================================================== */

    function branchCount(
        $conn,
        $branch,
        $year = null,
        $month = null
    ) {

        $branch =
            strtolower(
                trim($branch)
            );

        if (
            $year &&
            $month
        ) {

            $stmt = $conn->prepare(
                "
                SELECT COUNT(*) AS total
                FROM tbl_client
                WHERE LOWER(TRIM(branch))=?
                AND YEAR(due_date)=?
                AND MONTH(due_date)=?
                "
            );

            $stmt->bind_param(
                "sii",
                $branch,
                $year,
                $month
            );

        } else {

            $stmt = $conn->prepare(
                "
                SELECT COUNT(*) AS total
                FROM tbl_client
                WHERE LOWER(TRIM(branch))=?
                "
            );

            $stmt->bind_param(
                "s",
                $branch
            );
        }

        $stmt->execute();

        $result =
            $stmt->get_result();

        $row =
            $result->fetch_assoc();

        $total =
            (int)(
                $row['total'] ?? 0
            );

        $stmt->close();

        return $total;
    }


    $nagpur_clients =
        branchCount(
            $conn,
            'nagpur',
            $year,
            $month
        );

    $bramhapuri_clients =
        branchCount(
            $conn,
            'bramhapuri',
            $year,
            $month
        );

    $chandrapur_clients =
        branchCount(
            $conn,
            'chandrapur',
            $year,
            $month
        );


    /* =====================================================
       TOTAL PAYMENTS
    ===================================================== */

    if (
        tableExists(
            $conn,
            'tbl_payment'
        )
    ) {

        if (
            $year &&
            $month
        ) {

            $stmt = $conn->prepare(
                "
                SELECT COUNT(*) AS total
                FROM tbl_payment
                WHERE YEAR(payment_date)=?
                AND MONTH(payment_date)=?
                AND paid_amount > 0
                "
            );

            $stmt->bind_param(
                "ii",
                $year,
                $month
            );

            $stmt->execute();

            $row =
                $stmt
                ->get_result()
                ->fetch_assoc();

            $total_payments =
                (int)(
                    $row['total'] ?? 0
                );

            $stmt->close();

        } else {

            $row =
                $conn
                ->query(
                    "
                    SELECT COUNT(*) AS total
                    FROM tbl_payment
                    WHERE paid_amount > 0
                    "
                )
                ->fetch_assoc();

            $total_payments =
                (int)(
                    $row['total'] ?? 0
                );
        }
    }


    /* =====================================================
       DUE PAYMENTS
    ===================================================== */

    if (
        tableExists(
            $conn,
            'tbl_payment'
        )
    ) {

        if (
            $year &&
            $month
        ) {

            $stmt = $conn->prepare(
                "
                SELECT COUNT(*) AS total
                FROM tbl_payment
                WHERE due_amount > 0
                AND
                (
                    (
                        payment_date IS NOT NULL
                        AND YEAR(payment_date)=?
                        AND MONTH(payment_date)=?
                    )
                    OR
                    (
                        due_date IS NOT NULL
                        AND YEAR(due_date)=?
                        AND MONTH(due_date)=?
                    )
                )
                "
            );

            $stmt->bind_param(
                "iiii",
                $year,
                $month,
                $year,
                $month
            );

            $stmt->execute();

            $row =
                $stmt
                ->get_result()
                ->fetch_assoc();

            $due_payments =
                (int)(
                    $row['total'] ?? 0
                );

            $stmt->close();

        } else {

            $row =
                $conn
                ->query(
                    "
                    SELECT COUNT(*) AS total
                    FROM tbl_payment
                    WHERE due_amount > 0
                    "
                )
                ->fetch_assoc();

            $due_payments =
                (int)(
                    $row['total'] ?? 0
                );
        }
    }


    /* =====================================================
       EXPENSES
    ===================================================== */

    if (
        tableExists(
            $conn,
            'tbl_expenses'
        )
    ) {

        if (
            $year &&
            $month
        ) {

            $stmt = $conn->prepare(
                "
                SELECT
                    COALESCE(
                        SUM(
                            CAST(
                                amount AS DECIMAL(12,2)
                            )
                        ),
                        0
                    ) AS total
                FROM tbl_expenses
                WHERE YEAR(`date`)=?
                AND MONTH(`date`)=?
                "
            );

            $stmt->bind_param(
                "ii",
                $year,
                $month
            );

            $stmt->execute();

            $row =
                $stmt
                ->get_result()
                ->fetch_assoc();

            $total_expenses =
                (float)(
                    $row['total'] ?? 0
                );

            $stmt->close();

        } else {

            $row =
                $conn
                ->query(
                    "
                    SELECT
                        COALESCE(
                            SUM(
                                CAST(
                                    amount AS DECIMAL(12,2)
                                )
                            ),
                            0
                        ) AS total
                    FROM tbl_expenses
                    "
                )
                ->fetch_assoc();

            $total_expenses =
                (float)(
                    $row['total'] ?? 0
                );
        }
    }


    


    /* =====================================================
       CHART DATA
    ===================================================== */

    $chart_year =
        $year ?: (int)date('Y');


    if (
        tableExists(
            $conn,
            'tbl_pending_services'
        )
    ) {

        $stmt =
            $conn->prepare(
                "
                SELECT
                    MONTH(service_date) AS m,
                    COUNT(*) AS total
                FROM tbl_pending_services
                WHERE YEAR(service_date)=?
                GROUP BY MONTH(service_date)
                ORDER BY MONTH(service_date)
                "
            );

        $stmt->bind_param(
            "i",
            $chart_year
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        while (
            $row =
            $result->fetch_assoc()
        ) {

            $m =
                (int)$row['m'];

            if (
                $m >= 1 &&
                $m <= 12
            ) {

                $chartData[$m - 1] =
                    (int)$row['total'];
            }
        }

        $stmt->close();
    }
}


/* =========================================================
   HEADER
========================================================= */

include 'header.php';

?>


<?php if ($role === 'super_admin'): ?>


<!-- =====================================================
     SUPER ADMIN DASHBOARD
===================================================== -->

<main class="shul-dashboard">


    <!-- PAGE HEADER -->

    <div class="shul-dashboard-header-row">

        <div>

            <h1 class="shul-dashboard-title">
                Dashboard
            </h1>

            <div class="shul-dashboard-subtitle">
                Overview of your business
            </div>

        </div>


        <!-- MONTH PICKER -->

        <div
            class="shul-month-picker"
            id="shulMonthPicker"
        >

            <button
                type="button"
                class="shul-month-trigger"
                id="shulMonthTrigger"
            >

                <span>📅</span>

                <span id="shulMonthLabel">

                    <?php

                    if ($selected_month) {

                        echo date(
                            'F Y',
                            strtotime(
                                $selected_month . '-01'
                            )
                        );

                    } else {

                        echo 'Select Month';
                    }

                    ?>

                </span>

                <span>▼</span>

            </button>


            <div
                class="shul-month-dropdown"
                id="shulMonthDropdown"
            >

                <div class="shul-month-header">

                    <button
                        type="button"
                        id="shulMonthPrev"
                    >
                        ‹
                    </button>

                    <strong id="shulMonthYear">
                        <?= $year ?: date('Y') ?>
                    </strong>

                    <button
                        type="button"
                        id="shulMonthNext"
                    >
                        ›
                    </button>

                </div>


                <div
                    class="shul-month-grid"
                    id="shulMonthGrid"
                ></div>


                <div class="shul-month-footer">

                    <span>

                        <?php

                        if ($selected_month) {

                            echo date(
                                'F Y',
                                strtotime(
                                    $selected_month . '-01'
                                )
                            );

                        } else {

                            echo 'No filter';
                        }

                        ?>

                    </span>


                    <?php if ($selected_month): ?>

                    <button
                        type="button"
                        onclick="window.location.href='homepage.php'"
                    >
                        ✕ Clear
                    </button>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         KPI CARDS
    ================================================= -->

    <div class="shul-kpi-grid">


        <!-- CLIENTS -->

        <div
            class="shul-kpi-card"
            id="clientKpi"
        >

            <div class="shul-kpi-bar cyan"></div>


            <div class="shul-kpi-top">

                <div>

                    <div class="shul-kpi-value">

                        <?= number_format(
                            $total_clients
                        ) ?>

                    </div>

                    <div class="shul-kpi-label">

                        <?= $selected_month
                            ? 'Monthly Clients'
                            : 'Total Clients'
                        ?>

                    </div>

                </div>


                <div class="shul-kpi-icon cyan-bg">
                    👥
                </div>

            </div>


            <div
                class="shul-kpi-toggle"
                id="branchToggle"
            >
                ▼ Click to see branches
            </div>


            <div
                class="shul-branch-dropdown"
                id="branchDropdown"
            >

                <div>

                    <span>
                        📍 Nagpur
                    </span>

                    <strong>
                        <?= $nagpur_clients ?>
                    </strong>

                </div>


                <div>

                    <span>
                        📍 Bramhapuri
                    </span>

                    <strong>
                        <?= $bramhapuri_clients ?>
                    </strong>

                </div>


                <div>

                    <span>
                        📍 Chandrapur
                    </span>

                    <strong>
                        <?= $chandrapur_clients ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- PAYMENTS -->

        <div class="shul-kpi-card">

            <div class="shul-kpi-bar green"></div>


            <div class="shul-kpi-top">

                <div>

                    <div class="shul-kpi-value">

                        <?= number_format(
                            $total_payments
                        ) ?>

                    </div>

                    <div class="shul-kpi-label">

                        <?= $selected_month
                            ? 'Monthly Payments'
                            : 'Total Payments'
                        ?>

                    </div>

                </div>


                <div class="shul-kpi-icon green-bg">
                    💰
                </div>

            </div>

        </div>


        <!-- DUE -->

        <div class="shul-kpi-card">

            <div class="shul-kpi-bar red"></div>


            <div class="shul-kpi-top">

                <div>

                    <div class="shul-kpi-value">

                        <?= number_format(
                            $due_payments
                        ) ?>

                    </div>

                    <div class="shul-kpi-label">

                        <?= $selected_month
                            ? 'Monthly Due Payments'
                            : 'Total Due Payments'
                        ?>

                    </div>

                </div>


                <div class="shul-kpi-icon red-bg">
                    💳
                </div>

            </div>

        </div>


        <!-- EXPENSE -->

        <div class="shul-kpi-card">

            <div class="shul-kpi-bar orange"></div>


            <div class="shul-kpi-top">

                <div>

                    <div class="shul-kpi-value expense">

                        ₹<?= number_format(
                            $total_expenses,
                            2
                        ) ?>

                    </div>

                    <div class="shul-kpi-label">

                        <?= $selected_month
                            ? 'Monthly Expenses'
                            : 'Total Expenses'
                        ?>

                    </div>

                </div>


                <div class="shul-kpi-icon orange-bg">
                    📈
                </div>

            </div>

        </div>


    </div>


    <!-- =================================================
         ALERTS
    ================================================= -->

    <div class="shul-alert-outer">

        <div class="shul-section-heading">

            <span>
                ⚠️
            </span>

            Pending Alerts

        </div>


        <div class="shul-alert-grid">


            <a
                href="view-pending-service.php"
                class="shul-alert-card danger"
            >

                <span class="shul-alert-icon">
                    🔴
                </span>


                <div>

                    <div class="shul-alert-label">
                        Pending Services
                    </div>

                    <div class="shul-alert-value danger">

                        <?= $pending_service_count ?>

                    </div>

                </div>


                <span class="alert-arrow">
                    →
                </span>

            </a>


            <a
                href="view-complaints.php"
                class="shul-alert-card warning"
            >

                <span class="shul-alert-icon">
                    ⚠️
                </span>


                <div>

                    <div class="shul-alert-label">
                        Open Complaints
                    </div>

                    <div class="shul-alert-value warning">

                        <?= $complaint_count ?>

                    </div>

                </div>


                <span class="alert-arrow">
                    →
                </span>

            </a>


        </div>

    </div>


    <!-- =================================================
         CHART
    ================================================= -->

    <div class="shul-chart-card">

        <div class="shul-chart-head">

            <div>

                <div class="shul-chart-title">

                    📊 Monthly Services Overview

                </div>

                <div class="shul-chart-subtitle">

                    Pending service count by month

                </div>

            </div>


            <div class="chart-year-badge">

                <?= $chart_year ?>

            </div>

        </div>


        <div class="shul-chart-box">

            <canvas
                id="shulServicesChart"
            ></canvas>

        </div>

    </div>


</main>


<?php else: ?>

<!-- =====================================================
     OTHER ROLES - WELCOME PAGE
===================================================== -->

<main class="shul-welcome-page">

    <div class="shul-welcome-content">

        <img
            src="image/logo.png"
            alt="SHUL Ventures Pvt. Ltd."
            class="shul-welcome-logo"
        >

        <h1>
            Welcome To Shul Ventures Pvt. Ltd.
        </h1>

    </div>

</main>

<?php endif; ?>



<?php if ($role === 'super_admin'): ?>


<!-- =====================================================
     SUPER ADMIN JAVASCRIPT
===================================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="js/search.js"></script>


<script>

(function () {


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const app =
        document.getElementById(
            'shul-app'
        );


    const toggle =
        document.getElementById(
            'shulSidebarToggle'
        );


    const overlay =
        document.getElementById(
            'shulSidebarOverlay'
        );


    

    const monthTrigger =
        document.getElementById(
            'shulMonthTrigger'
        );


    const monthDropdown =
        document.getElementById(
            'shulMonthDropdown'
        );


    const monthYear =
        document.getElementById(
            'shulMonthYear'
        );


    const monthGrid =
        document.getElementById(
            'shulMonthGrid'
        );


    const branchDropdown =
        document.getElementById(
            'branchDropdown'
        );


    const branchToggle =
        document.getElementById(
            'branchToggle'
        );





    /* =====================================================
       MOBILE OVERLAY
    ===================================================== */

    overlay?.addEventListener(
        'click',
        function () {

            app?.classList.remove(
                'sidebar-mobile-open'
            );

        }
    );





    /* =====================================================
       MONTH PICKER
    ===================================================== */

    let viewYear =
        <?= (int)(
            $year ?: date('Y')
        ) ?>;


    const selectedMonth =
        <?= json_encode(
            $selected_month
        ) ?>;


    const months = [

        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec'

    ];



    function renderMonths()
    {

        if (
            !monthYear ||
            !monthGrid
        ) {

            return;
        }


        monthYear.textContent =
            viewYear;


        monthGrid.innerHTML =
            '';



        months.forEach(
            function (
                monthName,
                index
            ) {


                const button =
                    document.createElement(
                        'button'
                    );


                button.type =
                    'button';


                const value =
                    viewYear +
                    '-' +
                    String(
                        index + 1
                    ).padStart(
                        2,
                        '0'
                    );


                button.textContent =
                    monthName;



                if (
                    value ===
                    selectedMonth
                ) {

                    button.classList.add(
                        'selected'
                    );

                }



                button.addEventListener(
                    'click',
                    function () {

                        window.location.href =
                            'homepage.php?month=' +
                            value;

                    }
                );



                monthGrid.appendChild(
                    button
                );

            }
        );

    }



    monthTrigger?.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();


            monthDropdown?.classList.toggle(
                'open'
            );


            renderMonths();

        }
    );



    monthDropdown?.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

        }
    );



    document
        .getElementById(
            'shulMonthPrev'
        )
        ?.addEventListener(
            'click',
            function () {

                viewYear--;

                renderMonths();

            }
        );



    document
        .getElementById(
            'shulMonthNext'
        )
        ?.addEventListener(
            'click',
            function () {

                viewYear++;

                renderMonths();

            }
        );



    /* =====================================================
       BRANCH DROPDOWN
    ===================================================== */

    branchToggle?.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();


            branchDropdown?.classList.toggle(
                'open'
            );



            if (
                branchDropdown?.classList.contains(
                    'open'
                )
            ) {

                branchToggle.textContent =
                    '▲ Hide branches';

            } else {

                branchToggle.textContent =
                    '▼ Click to see branches';

            }

        }
    );



    branchDropdown?.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

        }
    );



    /* =====================================================
       CLOSE DROPDOWNS
    ===================================================== */

    document.addEventListener(
        'click',
        function () {


            monthDropdown?.classList.remove(
                'open'
            );


            branchDropdown?.classList.remove(
                'open'
            );

        }
    );



    /* =====================================================
       CHART
    ===================================================== */

    const chartData =
        <?= json_encode(
            array_values(
                $chartData
            )
        ) ?>;


    const chartCanvas =
        document.getElementById(
            'shulServicesChart'
        );



    if (
        chartCanvas &&
        typeof Chart !== 'undefined'
    ) {


        new Chart(
            chartCanvas,
            {

                type: 'bar',


                data: {

                    labels: months,


                    datasets: [

                        {

                            label:
                                'Services',


                            data:
                                chartData,


                            backgroundColor:
                                '#6366f1',


                            borderRadius:
                                6,


                            maxBarThickness:
                                50

                        }

                    ]

                },


                options: {

                    responsive:
                        true,


                    maintainAspectRatio:
                        false,


                    plugins: {

                        legend: {

                            display:
                                false

                        },


                        tooltip: {

                            backgroundColor:
                                '#1e293b',


                            borderWidth:
                                0,


                            borderRadius:
                                8

                        }

                    },


                    scales: {

                        x: {

                            grid: {

                                display:
                                    false

                            },


                            ticks: {

                                color:
                                    '#64748b'

                            }

                        },


                        y: {

                            beginAtZero:
                                true,


                            ticks: {

                                color:
                                    '#64748b',


                                precision:
                                    0

                            },


                            grid: {

                                color:
                                    '#e2e8f0'

                            }

                        }

                    }

                }

            }
        );

    }

})();

</script>


<?php endif; ?>


<style>

/* =========================================================
   WELCOME PAGE - SAME AS PROVIDED IMAGE
========================================================= */

.shul-welcome-page {

    min-height: calc(100vh - 64px);

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f8fafc;

    padding: 30px;

    box-sizing: border-box;
}


/* =========================================================
   CONTENT
========================================================= */

.shul-welcome-content {

    width: 100%;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

    transform: translateY(-20px);
}


/* =========================================================
   LOGO
========================================================= */

.shul-welcome-logo {

    width: 400px;

    height: 400px;

    object-fit: contain;

    display: block;

    margin-bottom: -30px;
}


/* =========================================================
   WELCOME TEXT
========================================================= */

.shul-welcome-content h1 {

    margin: 0;

    color: #123f73;

    font-size: 34px;

    font-weight: 700;

    line-height: 1.25;

    letter-spacing: -0.5px;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 992px) {

    .shul-welcome-logo {

        width: 135px;

        height: 195px;

        margin-bottom: 22px;
    }


    .shul-welcome-content h1 {

        font-size: 30px;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    .shul-welcome-page {

        min-height: calc(100vh - 60px);

        padding: 20px;
    }


    .shul-welcome-content {

        transform: translateY(-10px);
    }


    .shul-welcome-logo {

        width: 120px;

        height: 170px;

        margin-bottom: 20px;
    }


    .shul-welcome-content h1 {

        font-size: 25px;

        line-height: 1.3;

        padding: 0 15px;
    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .shul-welcome-logo {

        width: 105px;

        height: 150px;
    }


    .shul-welcome-content h1 {

        font-size: 22px;
    }

}

</style>


</body>
</html>
