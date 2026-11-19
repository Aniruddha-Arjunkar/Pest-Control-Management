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

$total_due_amount = 0;
$total_records = 0;
?>

<!-- =========================================================
     MAIN CONTENT
========================================================= -->

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

                    <span id="record-count">
                        0
                    </span>

                </span>

            </div>


            <!-- =================================================
                 MAIN CARD
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
   FETCH DUE PAYMENTS
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
            p.total_amount - SUM(p.paid_amount)
        ) AS due_amount,

        MAX(p.due_date) AS due_date

    FROM tbl_client c

    LEFT JOIN tbl_payment p
        ON c.Id = p.client_Id
";


/* =========================================================
   BRANCH FILTER
========================================================= */

if (
    $_SESSION['role'] !== 'super_admin'
    &&
    !empty($_SESSION['branch'])
) {

    $userBranch = mysqli_real_escape_string(
        $conn,
        $_SESSION['branch']
    );

    $sql .= "
        WHERE c.branch = '$userBranch'
    ";

}


/* =========================================================
   GROUP + DUE FILTER
========================================================= */

$sql .= "
    GROUP BY c.Id

    HAVING due_amount > 0

    ORDER BY due_date ASC
";


$result = mysqli_query(
    $conn,
    $sql
);


/* =========================================================
   QUERY ERROR
========================================================= */

if (!$result) {

?>

<tr>

    <td
        colspan="9"
        class="view-client-no-data"
    >

        Error:
        <?= htmlspecialchars(
            mysqli_error($conn),
            ENT_QUOTES,
            'UTF-8'
        ); ?>

    </td>

</tr>

<?php

}


/* =========================================================
   DATA FOUND
========================================================= */

elseif (mysqli_num_rows($result) > 0) {


    while ($row = mysqli_fetch_assoc($result)) {


        /* =================================================
           SKIP EMPTY NAME
        ================================================== */

        if (
            empty(
                $row['cust_name']
            )
        ) {

            continue;

        }


        /* =================================================
           TOTAL
        ================================================== */

        $total_due_amount +=
            (float) (
                $row['due_amount'] ?? 0
            );

        $total_records++;


        /* =================================================
           DUE DATE
        ================================================== */

        $dueDate = null;

        if (
            !empty(
                $row['due_date']
            )
        ) {

            try {

                $dueDate = new DateTime(
                    $row['due_date']
                );

            } catch (Exception $e) {

                $dueDate = null;

            }

        }


        /* =================================================
           BRANCH COLOR
        ================================================== */

        $branchClass = 'blue';

        $branchLower = strtolower(
            trim(
                $row['branch'] ?? ''
            )
        );


        if (
            $branchLower === 'nagpur'
        ) {

            $branchClass = 'blue';

        }

        elseif (
            $branchLower === 'bramhapuri'
        ) {

            $branchClass = 'green';

        }

        elseif (
            $branchLower === 'chandrapur'
            ||
            $branchLower === 'chandreapur'
        ) {

            $branchClass = 'orange';

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
                (float) (
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
                (float) (
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
                (float) (
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
                $row['due_date'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </span>

    </td>


    <!-- =================================================
         ACTION
    ================================================== -->

    <td class="view-client-action">

        <button
            type="button"
            class="view-client-action-btn toggle-action-btn"
        >

            Action

            <span class="view-client-action-arrow">
                ▼
            </span>

        </button>


        <div class="view-client-action-buttons">

            <a
                href="add-payment.php?payment_id=<?= urlencode(
                    $row['payment_id']
                ); ?>"
                class="view-client-update-btn"
            >
                Update
            </a>

        </div>

    </td>


</tr>

<?php

    }

}


/* =========================================================
   NO DATA
========================================================= */

else {

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
     JAVASCRIPT
========================================================= -->

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

        }

        else {

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
   ACTION DROPDOWN
========================================================= */

document
    .querySelectorAll(
        '.toggle-action-btn'
    )
    .forEach(function(btn) {


        btn.addEventListener(
            'click',
            function(e) {

                e.preventDefault();

                e.stopPropagation();


                const actionDiv =
                    this.parentElement.querySelector(
                        '.view-client-action-buttons'
                    );


                const isOpen =
                    actionDiv.classList.contains(
                        'show'
                    );


                /* Close all */

                document
                    .querySelectorAll(
                        '.view-client-action-buttons'
                    )
                    .forEach(function(menu) {

                        menu.classList.remove(
                            'show'
                        );

                    });


                document
                    .querySelectorAll(
                        '.toggle-action-btn'
                    )
                    .forEach(function(button) {

                        button.classList.remove(
                            'active'
                        );

                    });


                /* Open selected */

                if (!isOpen) {

                    actionDiv.classList.add(
                        'show'
                    );

                    this.classList.add(
                        'active'
                    );

                }

            }
        );

    });


/* =========================================================
   CLOSE ACTION MENU
========================================================= */

document.addEventListener(
    'click',
    function(e) {


        if (
            !e.target.closest(
                '.view-client-action'
            )
        ) {


            document
                .querySelectorAll(
                    '.view-client-action-buttons'
                )
                .forEach(function(menu) {

                    menu.classList.remove(
                        'show'
                    );

                });


            document
                .querySelectorAll(
                    '.toggle-action-btn'
                )
                .forEach(function(button) {

                    button.classList.remove(
                        'active'
                    );

                });

        }

    }
);


/* =========================================================
   DUE DATE HIGHLIGHT
========================================================= */

function highlightDueDates() {


    const now = new Date();


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
   BOTTOM SCROLLBAR
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





<!-- =========================================================
     EXISTING JS
========================================================= -->

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>

</body>
</html>
