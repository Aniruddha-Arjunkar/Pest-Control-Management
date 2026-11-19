
<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: login.php');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';
?>

<section id="main-content">

    <section class="wrapper">

        <div class="view-clients-page">

            <!-- ================= PAGE HEADER ================= -->

            <div class="view-clients-header">

                <div class="view-clients-title-wrap">

                    <h1 class="view-clients-title">

                        <span class="view-clients-title-icon">
                            🗑️
                        </span>

                        Trash Requirements

                    </h1>

                    <p class="view-clients-subtitle">
                        View all closed requirement records
                    </p>

                </div>

                <?php
                $count_sql = "SELECT COUNT(*) AS total FROM tbl_delete2";
                $count_result = mysqli_query($conn, $count_sql);
                $count_row = mysqli_fetch_assoc($count_result);
                $total_records = $count_row['total'];
                ?>

                <span class="view-clients-count">

                    <?= $total_records; ?>

                    <?= ($total_records == 1) ? 'record' : 'records'; ?>

                </span>

            </div>


            <!-- ================= CARD ================= -->

            <div class="view-clients-card">

                <!-- ================= SEARCH ================= -->

                <form
                    method="POST"
                    action=""
                    class="view-clients-filter"
                >

                    <div class="view-client-search-wrap">

                        <span class="view-client-search-icon">
                            🔍
                        </span>

                        <input
                            type="text"
                            id="txt_searchall"
                            class="view-client-search"
                            placeholder="Search company, job profile, position..."
                            autocomplete="off"
                        >

                    </div>

                </form>


                <!-- ================= TABLE ================= -->

                <div
                    class="view-clients-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="view-clients-table"
                        id="trashRequirementTable"
                    >

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Company Name</th>

                                <th>Job Profile</th>

                                <th>Position Level</th>

                                <th>Years of Experience</th>

                                <th>Open Date</th>

                                <th>Close Date</th>

                                <th>Select Candidate</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php

                        $sql = "
                            SELECT *
                            FROM tbl_delete2
                            ORDER BY Id DESC
                        ";

                        $result = mysqli_query($conn, $sql);

                        if (!$result) {

                            ?>

                            <tr>

                                <td
                                    colspan="9"
                                    class="view-client-no-data"
                                >

                                    Database Error:
                                    <?= htmlspecialchars(mysqli_error($conn)); ?>

                                </td>

                            </tr>

                            <?php

                        } elseif (mysqli_num_rows($result) > 0) {

                            $i = 1;

                            while ($row = mysqli_fetch_assoc($result)) {

                                ?>

                                <tr>

                                    <!-- SR NO -->

                                    <td class="view-client-sr">

                                        <?= $i++; ?>

                                    </td>


                                    <!-- COMPANY -->

                                    <td class="view-client-name">

                                        <?= htmlspecialchars(
                                            $row['company_name'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- JOB PROFILE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['job_profile'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- POSITION LEVEL -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['position_level'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- EXPERIENCE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['years_of_experience'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- OPEN DATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['open_date'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- CLOSE DATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['close_date'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- SELECT CANDIDATE -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $row['select_candidate'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <a
                                            href="view-delete-requirement.php?id=<?= (int)$row['Id']; ?>"
                                            class="primary-btn"
                                        >
                                            View
                                        </a>

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

                                    No trash requirement records found.

                                </td>

                            </tr>

                            <?php

                        }

                        ?>

                        </tbody>

                    </table>

                </div>


                <!-- ================= BOTTOM SCROLL ================= -->

                <div
                    class="view-client-bottom-scroll"
                    id="viewClientBottomScroll"
                >

                    <div
                        class="view-client-bottom-scroll-inner"
                        id="viewClientBottomScrollInner"
                    ></div>

                </div>

            </div>

        </div>

    </section>

</section>


<!-- =========================================================
     SEARCH JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const searchInput =
            document.getElementById(
                "txt_searchall"
            );

        const table =
            document.getElementById(
                "trashRequirementTable"
            );

        if (
            !searchInput ||
            !table
        ) {
            return;
        }


        const tbody =
            table.querySelector("tbody");


        /* ================= SEARCH ================= */

        function filterRows() {

            const filter =
                searchInput.value
                    .toLowerCase()
                    .trim();

            const rows =
                tbody.querySelectorAll("tr");


            rows.forEach(
                function (row) {

                    const noData =
                        row.querySelector(
                            ".view-client-no-data"
                        );

                    if (noData) {
                        return;
                    }


                    const rowText =
                        row.innerText
                            .toLowerCase();


                    if (
                        filter === "" ||
                        rowText.includes(filter)
                    ) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                }
            );

        }


        searchInput.addEventListener(
            "input",
            filterRows
        );

    }
);


/* =========================================================
   BOTTOM HORIZONTAL SCROLLBAR SYNC
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const bottomScroll =
            document.getElementById(
                "viewClientBottomScroll"
            );

        const bottomInner =
            document.getElementById(
                "viewClientBottomScrollInner"
            );

        const tableWrapper =
            document.querySelector(
                ".view-clients-table-wrapper"
            );


        if (
            !bottomScroll ||
            !bottomInner ||
            !tableWrapper
        ) {
            return;
        }


        function updateScrollWidth() {

            bottomInner.style.width =
                tableWrapper.scrollWidth + "px";

        }


        updateScrollWidth();


        window.addEventListener(
            "resize",
            updateScrollWidth
        );


        bottomScroll.addEventListener(
            "scroll",
            function () {

                tableWrapper.scrollLeft =
                    bottomScroll.scrollLeft;

            }
        );


        tableWrapper.addEventListener(
            "scroll",
            function () {

                bottomScroll.scrollLeft =
                    tableWrapper.scrollLeft;

            }
        );

    }
);

</script>


<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>

</body>
</html>

