<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';

$role = $_SESSION['role'] ?? '';
$branch = $_SESSION['branch'] ?? '';

$serviceCondition = '';

/* =========================================================
   ROLE BASED SERVICE CONDITION
========================================================= */

if ($role == 'support_team') {

    $serviceCondition = " AND c.services != 'Consultancy' ";

} elseif ($role == 'HR' || $role == 'trainer') {

    $serviceCondition = " AND c.services = 'Consultancy' ";

}


/* =========================================================
   TOTAL / COUNT
========================================================= */

$total_due_amount = 0;
$total_records = 0;

?>

<section id="main-content">

    <section class="wrapper">

        <div class="view-clients-page">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="view-clients-header">

                <div class="view-clients-title-wrap">

                    <h1 class="view-clients-title">

                        <span class="view-clients-title-icon">
                            💰
                        </span>

                        Due Payments

                    </h1>

                    <p class="view-clients-subtitle">
                        View and manage due payment records
                    </p>

                </div>


                <!-- RECORD COUNT -->

                <span class="view-clients-count">

                    Due Records:
                    <span id="record-count">0</span>

                </span>

            </div>


            <!-- =================================================
                 CARD
            ================================================== -->

            <div class="view-clients-card">


                <!-- =================================================
                     SEARCH
                ================================================== -->

                <div class="view-clients-filter">

                    <div class="view-client-search-wrap">

                        <span class="view-client-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            id="txt_searchall"
                            class="view-client-search"
                            placeholder="Search by Name, Branch or Phone"
                            autocomplete="off"
                        >

                    </div>

                </div>


                <!-- =================================================
                     TABLE
                ================================================== -->

                <div
                    class="view-clients-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="view-clients-table"
                        id="payments-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Branch
                                </th>

                                <th>
                                    Service
                                </th>

                                <th>
                                    Name
                                </th>

                                <th>
                                    Phone No
                                </th>

                                <th>
                                    Total Amount
                                </th>

                                <th>
                                    Paid Amount
                                </th>

                                <th>
                                    Due Amount
                                </th>

                                <th>
                                    Due Date
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

<?php

/* =========================================================
   PAYMENT QUERY
========================================================= */

$sql = "

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
        - SUM(
            CASE
                WHEN p.tds_paid = 0
                THEN p.tds_amount
                ELSE 0
            END
        )
    ) AS due_amount,

    MAX(p.due_date) AS due_date

FROM tbl_client c

LEFT JOIN tbl_payment p
    ON c.Id = p.client_Id

WHERE p.status != 'defaulted'

$serviceCondition

GROUP BY c.Id

HAVING due_amount > 0


UNION


SELECT

    MAX(p.Id) AS payment_id,

    p.client_Id,

    'Nagpur' AS branch,

    'Consultancy' AS services,

    IF(
        p.payment_type = 'Company Pay',

        CONCAT(
            pl.company_name,
            ' (',
            pl.candidate_name,
            ')'
        ),

        CONCAT(
            pl.candidate_name,
            ' (',
            pl.company_name,
            ')'
        )
    ) AS cust_name,


    IF(
        p.payment_type = 'Company Pay',

        COALESCE(
            ag.phone,
            a.phone
        ),

        COALESCE(
            a.phone,
            ag.phone
        )
    ) AS cust_phone,


    p.total_amount,

    SUM(p.paid_amount) AS total_paid,


    (
        p.total_amount
        - SUM(p.paid_amount)
        - SUM(
            CASE
                WHEN p.tds_paid = 0
                THEN p.tds_amount
                ELSE 0
            END
        )
    ) AS due_amount,


    MAX(p.due_date) AS due_date


FROM tbl_payment p


LEFT JOIN tbl_placement pl
    ON p.interview_id = pl.interview_id


LEFT JOIN tbl_agreement ag
    ON pl.candidate_id = ag.id


LEFT JOIN tbl_associate a
    ON pl.candidate_id = a.Id


WHERE p.status != 'defaulted'


GROUP BY
    p.client_Id,
    p.total_amount


HAVING due_amount > 0


UNION


SELECT

    MAX(p.Id) AS payment_id,

    p.client_Id,

    'Nagpur' AS branch,

    'Internship' AS services,

    sa.name AS cust_name,

    sa.phone AS cust_phone,

    p.total_amount,

    SUM(p.paid_amount) AS total_paid,

    (
        p.total_amount
        - SUM(p.paid_amount)
    ) AS due_amount,

    MAX(p.due_date) AS due_date


FROM tbl_student_admissions sa


LEFT JOIN tbl_payment p
    ON sa.student_id = p.client_Id


WHERE p.status != 'defaulted'


GROUP BY sa.student_id


HAVING due_amount > 0


ORDER BY due_date ASC

";


$result = mysqli_query(
    $conn,
    $sql
);


if (!$result) {

    echo '

    <tr>

        <td
            colspan="9"
            class="view-client-no-data"
        >

            Error:
            ' .
            htmlspecialchars(
                mysqli_error($conn)
            )
            . '

        </td>

    </tr>

    ';

} elseif (
    mysqli_num_rows($result) > 0
) {


    while (
        $row = mysqli_fetch_assoc($result)
    ) {


        /* =================================================
           SKIP EMPTY NAME
        ================================================= */

        if (
            empty(
                $row['cust_name']
            )
        ) {

            continue;

        }


        /* =================================================
           TOTAL
        ================================================= */

        $total_due_amount +=
            (float)(
                $row['due_amount'] ?? 0
            );


        $total_records++;


        /* =================================================
           DUE DATE
        ================================================= */

        $dueDate = null;

        if (
            !empty(
                $row['due_date']
            )
        ) {

            try {

                $dueDate =
                    new DateTime(
                        $row['due_date']
                    );

            } catch (
                Exception $e
            ) {

                $dueDate = null;

            }

        }


        /* =================================================
           BRANCH COLOR LOGIC
        ================================================= */

        $branchClass = 'blue';

        $branchLower =
            strtolower(
                trim(
                    $row['branch'] ?? ''
                )
            );


        if (
            $branchLower === 'nagpur'
        ) {

            $branchClass = 'blue';

        } elseif (
            $branchLower === 'bramhapuri'
        ) {

            $branchClass = 'green';

        } elseif (
            $branchLower === 'chandrapur'
            ||
            $branchLower === 'chandreapur'
        ) {

            $branchClass = 'orange';

        } else {

            $branchClass = 'blue';

        }

?>

<tr>


    <!-- =================================================
         BRANCH
    ================================================== -->

    <td>

        <span
            class="
                view-client-branch
                <?= $branchClass; ?>
            "
        >

            <?= htmlspecialchars(
                $row['branch'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </span>

    </td>


    <!-- =================================================
         SERVICE
    ================================================== -->

    <td class="view-client-service">

        <?= htmlspecialchars(
            $row['services'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        ); ?>

    </td>


    <!-- =================================================
         NAME
    ================================================== -->

    <td class="view-client-name">

        <a
            href="view-allclient-details.php?client_id=<?= urlencode(
                $row['client_Id']
            ); ?>"
            target="_blank"
        >

            <?= htmlspecialchars(
                $row['cust_name'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </a>

    </td>


    <!-- =================================================
         PHONE
    ================================================== -->

    <td class="view-client-phone">

        <?= htmlspecialchars(
            $row['cust_phone'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        ); ?>

    </td>


    <!-- =================================================
         TOTAL AMOUNT
    ================================================== -->

    <td class="view-client-total-amount">

        ₹
        <?= htmlspecialchars(
            number_format(
                (float)(
                    $row['total_amount'] ?? 0
                ),
                2
            ),
            ENT_QUOTES,
            'UTF-8'
        ); ?>

    </td>


    <!-- =================================================
         PAID AMOUNT
    ================================================== -->

    <td class="view-client-paid-amount">

        ₹
        <?= htmlspecialchars(
            number_format(
                (float)(
                    $row['total_paid'] ?? 0
                ),
                2
            ),
            ENT_QUOTES,
            'UTF-8'
        ); ?>

    </td>


    <!-- =================================================
         DUE AMOUNT
    ================================================== -->

    <td class="view-client-due-amount">

        ₹
        <?= htmlspecialchars(
            number_format(
                (float)(
                    $row['due_amount'] ?? 0
                ),
                2
            ),
            ENT_QUOTES,
            'UTF-8'
        ); ?>

    </td>


    <!-- =================================================
         DUE DATE
    ================================================== -->

    <td class="view-client-date-cell">

        <span
            class="due-date"
            data-due-date="<?= $dueDate
                ? $dueDate->format('Y-m-d')
                : ''
            ?>"
        >

            <?= htmlspecialchars(
                (string)(
                    $row['due_date'] ?? ''
                ),
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </span>

    </td>


   <td class="view-client-action">

    <button
        type="button"
        class="view-client-action-btn toggle-action-btn"
    >
        Action
        <span class="view-client-action-arrow">▼</span>
    </button>

    <div class="view-client-action-buttons">

        <a
            href="add-payment.php?payment_id=<?= urlencode($row['payment_id']); ?>"
            class="view-client-update-btn"
        >
            Update
        </a>

        <button 
    type="button" 
    class="view-client-defaulter-btn open-defaulter-modal" 
    data-clientid="<?= htmlspecialchars($row['client_Id']); ?>" 
    data-name="<?= htmlspecialchars($row['cust_name']); ?>"
>
    Mark Defaulter
</button>

    </div>

</td>


</tr>

<?php

    }

} else {

?>

<tr>

    <td
        colspan="9"
        class="view-client-no-data"
    >

        No results found

    </td>

</tr>

<?php

}

?>

                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     BOTTOM HORIZONTAL SCROLL
                ================================================== -->

                <div
                    class="view-client-bottom-scroll"
                    id="viewClientBottomScroll"
                >

                    <div
                        class="view-client-bottom-scroll-inner"
                        id="viewClientBottomScrollInner"
                    ></div>

                </div>


                <!-- =================================================
                     TOTAL DUE
                ================================================== -->

                <section id="total-due-amount">

                    <h4>

                        Total Due Amount (INR):

                        <span id="total-due">

                            <?= htmlspecialchars(
                                number_format(
                                    $total_due_amount,
                                    2
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>

                        </span>

                    </h4>

                </section>


            </div>

        </div>

    </section>

</section>


<!-- =========================================================
     DEFAULTER MODAL
========================================================= -->

<div
    class="modal fade"
    id="defaulterModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="defaulterModalLabel"
>

    <div
        class="modal-dialog"
        role="document"
    >

        <form
            method="POST"
            action="mark-defaulter.php"
        >

            <div class="modal-content">


                <div class="modal-header">

                    <h4
                        class="modal-title"
                        id="defaulterModalLabel"
                    >
                        Mark Client as Defaulter
                    </h4>


                    <button
                        type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="Close"
                    >

                        &times;

                    </button>

                </div>


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="client_Id"
                        id="modal-client-id"
                    >


                    <div class="form-group">

                        <label>
                            Client Name:
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="modal-client-name"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label
                            for="defaulter_note"
                        >
                            Reason:
                        </label>

                        <textarea
                            class="form-control"
                            name="defaulter_note"
                            id="defaulter_note"
                            rows="4"
                            required
                        ></textarea>

                    </div>


                </div>


                <div class="modal-footer">

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        Submit
                    </button>


                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"
                    >
                        Cancel
                    </button>

                </div>


            </div>

        </form>

    </div>

</div>


<style>

    
    /* =========================================================
   DEFAULTER MODAL - MODERN DESIGN
========================================================= */

#defaulterModal {
    display: none;
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 99999;

    background: rgba(15, 23, 42, 0.62);
    backdrop-filter: blur(4px);

    overflow-y: auto;
    padding: 20px;
    box-sizing: border-box;
}

#defaulterModal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* MODAL BOX */
#defaulterModal .modal-dialog {
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
    animation: defaulterModalIn 0.25s ease;
}

@keyframes defaulterModalIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.97);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

#defaulterModal .modal-content {
    position: relative;

    background: #ffffff;

    border: none;
    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 25px 60px rgba(0, 0, 0, 0.25),
        0 8px 25px rgba(0, 0, 0, 0.12);
}


/* =========================================================
   MODAL HEADER
========================================================= */

#defaulterModal .modal-header {
    position: relative;

    display: flex;
    align-items: center;

    padding: 20px 24px;

    background: linear-gradient(
        135deg,
        #fff5f5,
        #ffffff
    );

    border-bottom: 1px solid #f1f1f1;
}

#defaulterModal .modal-title {
    margin: 0;

    padding-right: 35px;

    font-size: 21px;
    font-weight: 700;

    color: #1f2937;

    line-height: 1.3;
}

/* RED ICON BEFORE TITLE */
#defaulterModal .modal-title::before {
    content: "⚠";

    display: inline-flex;
    align-items: center;
    justify-content: center;

    width: 38px;
    height: 38px;

    margin-right: 11px;

    border-radius: 10px;

    background: #fee2e2;
    color: #dc2626;

    font-size: 19px;

    vertical-align: middle;
}


/* =========================================================
   CLOSE BUTTON
========================================================= */

#defaulterModal .close {
    position: absolute;

    top: 17px;
    right: 18px;

    width: 34px;
    height: 34px;

    padding: 0;

    border: none;
    border-radius: 50%;

    background: #f3f4f6;

    color: #6b7280;

    font-size: 22px;
    line-height: 32px;

    opacity: 1;

    cursor: pointer;

    transition: all 0.2s ease;
}

#defaulterModal .close:hover {
    background: #fee2e2;
    color: #dc2626;

    transform: rotate(90deg);
}


/* =========================================================
   MODAL BODY
========================================================= */

#defaulterModal .modal-body {
    padding: 24px;
}


/* FORM GROUP */
#defaulterModal .form-group {
    margin-bottom: 20px;
}

#defaulterModal .form-group:last-child {
    margin-bottom: 0;
}


/* LABEL */
#defaulterModal label {
    display: block;

    margin-bottom: 8px;

    font-size: 14px;
    font-weight: 600;

    color: #374151;
}


/* INPUT + TEXTAREA */
#defaulterModal .form-control {
    width: 100%;

    box-sizing: border-box;

    padding: 12px 14px;

    border: 1px solid #d1d5db;
    border-radius: 9px;

    background: #ffffff;

    color: #1f2937;

    font-size: 14px;

    outline: none;

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease;
}

#defaulterModal .form-control:focus {
    border-color: #ef4444;

    box-shadow:
        0 0 0 3px rgba(239, 68, 68, 0.12);
}


/* CLIENT NAME READONLY */
#defaulterModal #modal-client-name {
    background: #f8fafc;
    color: #4b5563;
    font-weight: 600;
}


/* TEXTAREA */
#defaulterModal textarea.form-control {
    min-height: 110px;

    resize: vertical;

    line-height: 1.5;
}


/* =========================================================
   MODAL FOOTER
========================================================= */

#defaulterModal .modal-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;

    gap: 10px;

    padding: 17px 24px;

    background: #fafafa;

    border-top: 1px solid #eeeeee;
}


/* =========================================================
   SUBMIT BUTTON
========================================================= */

#defaulterModal .btn-danger {
    min-width: 125px;

    padding: 10px 18px;

    border: none;
    border-radius: 8px;

    background: #dc2626;
    color: #ffffff;

    font-size: 14px;
    font-weight: 600;

    cursor: pointer;

    box-shadow:
        0 4px 10px rgba(220, 38, 38, 0.20);

    transition: all 0.2s ease;
}

#defaulterModal .btn-danger:hover {
    background: #b91c1c;

    transform: translateY(-1px);

    box-shadow:
        0 6px 14px rgba(220, 38, 38, 0.25);
}


/* =========================================================
   CANCEL BUTTON
========================================================= */

#defaulterModal .btn-secondary {
    min-width: 90px;

    padding: 10px 18px;

    border: 1px solid #d1d5db;
    border-radius: 8px;

    background: #ffffff;
    color: #4b5563;

    font-size: 14px;
    font-weight: 600;

    cursor: pointer;

    transition: all 0.2s ease;
}

#defaulterModal .btn-secondary:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

    #defaulterModal {
        padding: 15px;
    }

    #defaulterModal .modal-dialog {
        max-width: 100%;
    }

    #defaulterModal .modal-header {
        padding: 17px 18px;
    }

    #defaulterModal .modal-title {
        font-size: 18px;
    }

    #defaulterModal .modal-title::before {
        width: 34px;
        height: 34px;

        font-size: 17px;
    }

    #defaulterModal .modal-body {
        padding: 20px 18px;
    }

    #defaulterModal .modal-footer {
        padding: 15px 18px;
    }

}

</style>

<!-- =========================================================
     EXISTING JS
========================================================= -->

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>


<script>

/* =========================================================
   RECORD COUNT
========================================================= */

function calculateRecordCount() {

    let count = 0;

    document
        .querySelectorAll(
            '#payments-table tbody tr'
        )
        .forEach(function(row) {

            if (
                row.style.display !== 'none'
                &&
                !row.querySelector(
                    '.view-client-no-data'
                )
            ) {

                count++;

            }

        });


    document.getElementById(
        'record-count'
    ).textContent = count;

}


/* =========================================================
   TOTAL DUE
========================================================= */

function calculateTotalDue() {

    let totalDue = 0;


    document
        .querySelectorAll(
            '#payments-table tbody tr'
        )
        .forEach(function(row) {


            if (
                row.style.display !== 'none'
                &&
                !row.querySelector(
                    '.view-client-no-data'
                )
            ) {


                const dueCell =
                    row.querySelector(
                        '.view-client-due-amount'
                    );


                if (!dueCell) {
                    return;
                }


                const dueAmount =
                    parseFloat(
                        dueCell.textContent
                            .replace(/,/g, '')
                            .replace(/₹/g, '')
                            .trim()
                    ) || 0;


                totalDue += dueAmount;

            }

        });


    document.getElementById(
        'total-due'
    ).textContent =
        totalDue.toFixed(2);

}


/* =========================================================
   SEARCH
========================================================= */

function searchPayments() {


    const input =
        document.getElementById(
            'txt_searchall'
        );


    const query =
        input.value
            .toLowerCase()
            .trim();


    const rows =
        document.querySelectorAll(
            '#payments-table tbody tr'
        );


    rows.forEach(function(row) {


        const noData =
            row.querySelector(
                '.view-client-no-data'
            );


        if (noData) {
            return;
        }


        const name =
            row.querySelector(
                '.view-client-name'
            )
            ?.textContent
            .toLowerCase() || '';


        const branch =
            row.querySelector(
                '.view-client-branch'
            )
            ?.textContent
            .toLowerCase() || '';


        const phone =
            row.querySelector(
                '.view-client-phone'
            )
            ?.textContent
            .toLowerCase() || '';


        const service =
            row.querySelector(
                '.view-client-service'
            )
            ?.textContent
            .toLowerCase() || '';


        if (
            query === ''
            ||
            name.includes(query)
            ||
            branch.includes(query)
            ||
            phone.includes(query)
            ||
            service.includes(query)
        ) {

            row.style.display = '';

        } else {

            row.style.display = 'none';

        }

    });


    calculateTotalDue();
    calculateRecordCount();

}


/* =========================================================
   SEARCH EVENT
========================================================= */

const searchInput =
    document.getElementById(
        'txt_searchall'
    );


if (searchInput) {

    searchInput.addEventListener(
        'input',
        searchPayments
    );

}


/* =========================================================
   ACTION BUTTON TOGGLE
========================================================= */

document.querySelectorAll('.toggle-action-btn').forEach(function (btn) {

    btn.addEventListener('click', function (e) {

        e.preventDefault();
        e.stopPropagation();

        const actionDiv =
            this.parentElement.querySelector(
                '.view-client-action-buttons'
            );

        const isOpen =
            actionDiv.classList.contains('show');

        /* Close all other action menus */
        document.querySelectorAll(
            '.view-client-action-buttons'
        ).forEach(function (menu) {

            menu.classList.remove('show');

        });

        document.querySelectorAll(
            '.toggle-action-btn'
        ).forEach(function (button) {

            button.classList.remove('active');

        });


        /* Open clicked menu */
        if (!isOpen) {

            actionDiv.classList.add('show');

            this.classList.add('active');

        }

    });

});


/* =========================================================
   CLOSE ACTION MENU WHEN CLICK OUTSIDE
========================================================= */

document.addEventListener('click', function (e) {

    if (
        !e.target.closest('.view-client-action')
    ) {

        document.querySelectorAll(
            '.view-client-action-buttons'
        ).forEach(function (menu) {

            menu.classList.remove('show');

        });

        document.querySelectorAll(
            '.toggle-action-btn'
        ).forEach(function (button) {

            button.classList.remove('active');

        });

    }

});

/* =========================================================
   DEFAULTER MODAL
========================================================= */
/* =========================================================
   DEFAULTER MODAL
========================================================= */

document
    .querySelectorAll('.open-defaulter-modal')
    .forEach(function(button) {

        button.addEventListener('click', function(e) {

            e.preventDefault();
            e.stopPropagation();

            const clientId =
                this.getAttribute('data-clientid');

            const name =
                this.getAttribute('data-name');

            document.getElementById(
                'modal-client-id'
            ).value = clientId;

            document.getElementById(
                'modal-client-name'
            ).value = name;

            document.getElementById(
                'defaulterModal'
            ).classList.add('show');

        });

    });


/* =========================================================
   CLOSE DEFAULTER MODAL
========================================================= */

document
    .querySelectorAll(
        '#defaulterModal [data-dismiss="modal"]'
    )
    .forEach(function(button) {

        button.addEventListener('click', function() {

            document.getElementById(
                'defaulterModal'
            ).classList.remove('show');

        });

    });


/* =========================================================
   CLOSE MODAL WHEN CLICKING OUTSIDE
========================================================= */

document
    .getElementById('defaulterModal')
    .addEventListener('click', function(e) {

        if (e.target === this) {

            this.classList.remove('show');

        }

    });


/* =========================================================
   DUE DATE HIGHLIGHT
========================================================= */

function highlightDueDates() {


    const now =
        new Date();


    const today =
        new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate()
        );


    document
        .querySelectorAll(
            '.due-date'
        )
        .forEach(function(span) {


            const dueDateValue =
                span.getAttribute(
                    'data-due-date'
                );


            if (!dueDateValue) {
                return;
            }


            const parts =
                dueDateValue.split('-');


            if (
                parts.length !== 3
            ) {
                return;
            }


            const dueDate =
                new Date(
                    parseInt(parts[0]),
                    parseInt(parts[1]) - 1,
                    parseInt(parts[2])
                );


            if (
                dueDate <= today
            ) {

                span.classList.add(
                    'highlight-today'
                );

            }

        });

}


/* =========================================================
   BOTTOM SCROLLBAR SYNC
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function() {


        const bottomScroll =
            document.getElementById(
                'viewClientBottomScroll'
            );


        const bottomInner =
            document.getElementById(
                'viewClientBottomScrollInner'
            );


        const tableWrapper =
            document.querySelector(
                '.view-clients-table-wrapper'
            );


        if (
            !bottomScroll
            ||
            !bottomInner
            ||
            !tableWrapper
        ) {

            return;

        }


        function updateScrollWidth() {

            bottomInner.style.width =
                tableWrapper.scrollWidth
                + 'px';

        }


        updateScrollWidth();


        window.addEventListener(
            'resize',
            updateScrollWidth
        );


        bottomScroll.addEventListener(
            'scroll',
            function() {

                tableWrapper.scrollLeft =
                    bottomScroll.scrollLeft;

            }
        );


        tableWrapper.addEventListener(
            'scroll',
            function() {

                bottomScroll.scrollLeft =
                    tableWrapper.scrollLeft;

            }
        );

    }
);


/* =========================================================
   PAGE LOAD
========================================================= */

window.addEventListener(
    'load',
    function() {

        calculateTotalDue();

        calculateRecordCount();

        highlightDueDates();

    }
);

</script>


</body>
</html>