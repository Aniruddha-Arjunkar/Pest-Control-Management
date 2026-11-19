<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';


/* =========================================================
   GET GENERATED QUOTATIONS
========================================================= */

$sql = "
    SELECT 
        g.*,
        i.cust_name,
        i.cust_phone
    FROM tbl_generated_quotations g
    JOIN tbl_inquiry i
        ON g.inquiry_id = i.Id
    ORDER BY g.created_at DESC
";

$result = $conn->query($sql);


/* =========================================================
   TOTAL QUOTATION COUNT
========================================================= */

$total_records = 0;

if ($result) {
    $total_records = $result->num_rows;
}

?>

<style>

/* =========================================================
   QUOTATION TABLE OVERRIDE
   Main design remains from external CSS
========================================================= */

#payments-table th:first-child,
#payments-table td:first-child {

    display: table-cell !important;

    width: 55px !important;

    min-width: 55px !important;

    max-width: 55px !important;

    text-align: center !important;
}


/* Keep all table cells visible */

#payments-table th,
#payments-table td {

    visibility: visible !important;

}


/* =========================================================
   ACTION CELL
========================================================= */

.inquiry-action-cell {

    position: relative;

    min-width: 140px;

}


/* =========================================================
   ACTION BUTTON
========================================================= */

.action-btn {

    background: linear-gradient(
        45deg,
        #0066ff,
        #33ccff
    );

    color: #fff !important;

    border: none;

    font-weight: 600;

    padding: 7px 14px;

    border-radius: 6px;

    cursor: pointer;

    transition: 0.3s;

    white-space: nowrap;

}


.action-btn:hover {

    background: linear-gradient(
        45deg,
        #0052cc,
        #1ab2ff
    );

}


/* =========================================================
   ACTION DROPDOWN
========================================================= */

.action-buttons {

    position: absolute;

    top: 42px;

    right: 10px;

    width: 170px;

    background: #fff;

    border-radius: 10px;

    box-shadow:
        0 5px 20px rgba(0,0,0,0.15);

    padding: 8px;

    z-index: 999;

    flex-direction: column;

    gap: 5px;

    animation: fadeSlide 0.20s ease;

}


@keyframes fadeSlide {

    from {

        opacity: 0;

        transform: translateY(8px);

    }

    to {

        opacity: 1;

        transform: translateY(0);

    }

}


/* =========================================================
   ACTION ITEMS
========================================================= */

.action-buttons button {

    width: 100% !important;

    border: none;

    border-radius: 5px;

    padding: 8px 10px;

    font-size: 13px;

    cursor: pointer;

}


.action-buttons button a {

    color: #000;

    text-decoration: none;

    display: block;

    width: 100%;

}


/* Open PDF */

button.primary6 {

    background: #e8f0ff;

}


button.primary6:hover {

    background: #d6e5ff;

}


/* Delete */

button.primary3 {

    background: #fff0f0;

}


button.primary3 a {

    color: #e53935 !important;

}


button.primary3:hover {

    background: #ffe0e0;

}


/* =========================================================
   SEARCH AREA
========================================================= */

.refollowup-filter-bar {

    display: flex;

    align-items: center;

    margin-bottom: 20px;

}


.refollowup-search-wrap {

    position: relative;

    width: 300px;

}


.refollowup-search-icon {

    position: absolute;

    left: 15px;

    top: 50%;

    transform: translateY(-50%);

    z-index: 2;

}


.refollowup-input {

    width: 100%;

    height: 42px;

    border: 1px solid #d5dce8;

    border-radius: 8px;

    padding: 8px 15px 8px 38px;

    font-size: 14px;

    outline: none;

    background: #fff;

}


.refollowup-input:focus {

    border-color: #2864dc;

    box-shadow:
        0 0 0 2px rgba(40,100,220,0.10);

}


/* =========================================================
   COUNT
========================================================= */

.inquiry-page-header p {

    margin-top: 5px;

    color: #6b7890;

    font-size: 14px;

}


/* =========================================================
   NO RECORD
========================================================= */

.no-inquiry {

    text-align: center !important;

    padding: 25px !important;

    color: #777 !important;

    font-size: 16px !important;

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    .refollowup-filter-bar {

        width: 100%;

    }


    .refollowup-search-wrap {

        width: 100%;

    }


    .inquiry-table-wrapper {

        overflow-x: auto;

    }


    #payments-table {

        min-width: 900px;

    }

}

</style>


<body>


<!-- =========================================================
     MAIN CONTENT START
========================================================= -->

<section id="main-content">

    <section class="wrapper">


        <div class="inquiry-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="inquiry-page-header">

                <h1>
                    📄 All Generated Quotations
                </h1>

                <p id="quotation-count">
                    <?= $total_records; ?> quotation records
                </p>

            </div>


            <!-- =================================================
                 CARD
            ================================================== -->

            <div class="inquiry-card">


                <!-- =================================================
                     SEARCH
                ================================================== -->

                <div class="refollowup-filter-bar">


                    <div class="refollowup-search-wrap">

                        <span class="refollowup-search-icon">
                            🔍
                        </span>


                        <input
                            type="text"
                            id="txt_searchall"
                            class="refollowup-input"
                            placeholder="Search customer..."
                            autocomplete="off"
                        >

                    </div>


                </div>


                <!-- =================================================
                     TABLE WRAPPER
                ================================================== -->

                <div
                    class="inquiry-table-wrapper refollowup-table-wrapper" 
                    id="scrollable-content"
                >


                    <!-- =================================================
                         TABLE
                    ================================================== -->

                    <table
                        class="inquiry-table"
                        id="payments-table"
                    >


                        <thead>

                            <tr>

                                <th style="width:55px;">
                                    #
                                </th>

                                <th>
                                    CUSTOMER
                                </th>

                                <th>
                                    PHONE
                                </th>

                                <th>
                                    SERVICE
                                </th>

                                <th>
                                    TOTAL (₹)
                                </th>

                                <th>
                                    DATE
                                </th>

                                <th>
                                    VIEW
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php

                        if (
                            $result &&
                            $result->num_rows > 0
                        ) {

                            $sr_no = 1;


                            while (
                                $row =
                                $result->fetch_assoc()
                            ) {


                                /* =========================
                                   SAFE DATA
                                ========================== */

                                $id =
                                    (int)($row['id'] ?? 0);


                                $customer =
                                    htmlspecialchars(
                                        $row['cust_name'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $phone =
                                    htmlspecialchars(
                                        $row['cust_phone'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $service =
                                    htmlspecialchars(
                                        $row['service'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $total =
                                    number_format(
                                        (float)(
                                            $row['total'] ?? 0
                                        ),
                                        2
                                    );


                                $created_at =
                                    htmlspecialchars(
                                        $row['created_at'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );


                                $file_path =
                                    htmlspecialchars(
                                        $row['file_path'] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                        ?>


                            <!-- =================================================
                                 TABLE ROW
                            ================================================== -->

                            <tr>


                                <!-- SR NO -->

                                <td class="inquiry-sr">

                                    <?= $sr_no++; ?>

                                </td>


                                <!-- CUSTOMER -->

                                <td class="inquiry-name">

                                    <?= $customer; ?>

                                </td>


                                <!-- PHONE -->

                                <td class="inquiry-phone">

                                    <?= $phone; ?>

                                </td>


                                <!-- SERVICE -->

                                <td class="inquiry-service">

                                    <?= $service; ?>

                                </td>


                                <!-- TOTAL -->

                                <td>

                                    ₹<?= $total; ?>

                                </td>


                                <!-- DATE -->

                                <td class="inquiry-date">

                                    <?= $created_at; ?>

                                </td>


                                <!-- ACTION -->

                                <td class="inquiry-action-cell">


                                    <!-- ACTION BUTTON -->

                                    <button
                                        type="button"
                                        class="action-btn"
                                        onclick="toggleActions(this)"
                                    >

                                        ⚙️ Actions ▼

                                    </button>


                                    <!-- ACTION MENU -->

                                    <div
                                        class="action-buttons"
                                        style="display:none;"
                                    >


                                        <!-- OPEN PDF -->

                                        <button
                                            type="button"
                                            class="primary6"
                                        >

                                            <a
                                                href="<?= $file_path; ?>"
                                                target="_blank"
                                            >

                                                📄 Open PDF

                                            </a>

                                        </button>


                                        <!-- DELETE -->

                                        <button
                                            type="button"
                                            class="primary3"
                                        >

                                            <a
                                                href="delete_quotation.php?id=<?= $id; ?>"
                                                onclick="return confirm('Are you sure to delete this quotation?');"
                                            >

                                                🗑 Delete

                                            </a>

                                        </button>


                                    </div>


                                </td>


                            </tr>


                        <?php

                            }

                        } else {

                        ?>


                            <!-- =================================================
                                 NO RECORD
                            ================================================== -->

                            <tr>

                                <td
                                    colspan="7"
                                    class="no-inquiry"
                                >

                                    No Generated Quotations Found.

                                </td>

                            </tr>


                        <?php

                        }

                        ?>


                        </tbody>

                    </table>


                </div>


            </div>


        </div>


    </section>

</section>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


/* =========================================================
   TOGGLE ACTION MENU
========================================================= */

function toggleActions(button) {

    const container =
        button.nextElementSibling;


    /*
     * Close all other menus
     */

    document
        .querySelectorAll('.action-buttons')
        .forEach(function(menu) {

            if (menu !== container) {

                menu.style.display = 'none';

            }

        });


    /*
     * Reset other buttons
     */

    document
        .querySelectorAll('.action-btn')
        .forEach(function(btn) {

            if (btn !== button) {

                btn.innerHTML =
                    '⚙️ Actions ▼';

            }

        });


    /*
     * Toggle current menu
     */

    if (
        container.style.display === 'none' ||
        container.style.display === ''
    ) {

        container.style.display = 'flex';

        button.innerHTML =
            '⚙️ Actions ▲';

    } else {

        container.style.display = 'none';

        button.innerHTML =
            '⚙️ Actions ▼';

    }

}


/* =========================================================
   SEARCH
========================================================= */

document
    .getElementById('txt_searchall')
    .addEventListener(
        'input',
        function() {


            const searchValue =
                this.value
                    .toLowerCase()
                    .trim();


            const rows =
                document.querySelectorAll(
                    '#payments-table tbody tr'
                );


            let visibleCount = 0;


            rows.forEach(function(row) {


                /*
                 * Skip no-record row
                 */

                if (!row.cells[1]) {

                    return;

                }


                const rowText =
                    row.textContent
                        .toLowerCase();


                if (
                    rowText.includes(
                        searchValue
                    )
                ) {

                    row.style.display = '';

                    visibleCount++;

                } else {

                    row.style.display = 'none';

                }

            });


            /*
             * Update count after search
             */

            const countElement =
                document.getElementById(
                    'quotation-count'
                );


            if (searchValue !== '') {

                countElement.textContent =
                    visibleCount +
                    ' quotation records';

            } else {

                countElement.textContent =
                    <?= $total_records; ?> +
                    ' quotation records';

            }


            updateSerialNumbers();

        }
    );


/* =========================================================
   UPDATE SERIAL NUMBERS
========================================================= */

function updateSerialNumbers() {

    const rows =
        document.querySelectorAll(
            '#payments-table tbody tr'
        );


    let counter = 1;


    rows.forEach(function(row) {


        if (
            row.style.display !== 'none'
        ) {


            const srCell =
                row.querySelector(
                    '.inquiry-sr'
                );


            if (srCell) {

                srCell.textContent =
                    counter++;

            }

        }

    });

}


/* =========================================================
   CLOSE ACTION MENU ON OUTSIDE CLICK
========================================================= */

document.addEventListener(
    'click',
    function(event) {


        if (
            !event.target.closest(
                '.inquiry-action-cell'
            )
        ) {


            document
                .querySelectorAll(
                    '.action-buttons'
                )
                .forEach(
                    function(menu) {

                        menu.style.display =
                            'none';

                    }
                );


            document
                .querySelectorAll(
                    '.action-btn'
                )
                .forEach(
                    function(btn) {

                        btn.innerHTML =
                            '⚙️ Actions ▼';

                    }
                );

        }

    }
);


/* =========================================================
   ESC KEY
========================================================= */

document.addEventListener(
    'keydown',
    function(event) {


        if (
            event.key === 'Escape'
        ) {


            document
                .querySelectorAll(
                    '.action-buttons'
                )
                .forEach(
                    function(menu) {

                        menu.style.display =
                            'none';

                    }
                );


            document
                .querySelectorAll(
                    '.action-btn'
                )
                .forEach(
                    function(btn) {

                        btn.innerHTML =
                            '⚙️ Actions ▼';

                    }
                );

        }

    }
);

</script>


<!-- =========================================================
     EXISTING JS
========================================================= -->

<script src="js/bootstrap.js"></script>

<script src="js/jquery.dcjqaccordion.2.7.js"></script>

<script src="js/scripts.js"></script>

<script src="js/jquery.slimscroll.js"></script>

<script src="js/jquery.nicescroll.js"></script>

<!--[if lte IE 8]>

<script
    language="javascript"
    type="text/javascript"
    src="js/flot-chart/excanvas.min.js">
</script>

<![endif]-->

<script src="js/jquery.scrollTo.js"></script>

<script src="js/search.js"></script>


</body>
</html>
