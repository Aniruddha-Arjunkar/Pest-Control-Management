```php
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

include 'header.php';
require_once 'db_connect.php';

$sql = "SELECT s.id, s.sent_on, s.validity, c.cust_name, c.cust_address
        FROM tbl_sent_certificates s
        JOIN tbl_client c ON s.client_id = c.Id
        ORDER BY s.sent_on DESC";

$result = $conn->query($sql);

$total_records = ($result) ? $result->num_rows : 0;
?>

<body>

<section id="main-content">

    <section class="wrapper">

        <!-- ===============================
             SEND CERTIFICATES CLIENTS PAGE
        ================================ -->

        <div class="view-clients-page">

            <!-- ===============================
                 PAGE HEADER
            ================================ -->

            <div class="view-clients-header">

                <div class="view-clients-title-wrap">

                    <h1 class="view-clients-title">

                        <span class="view-clients-title-icon">
                            📜
                        </span>

                        Send Certificates Clients

                    </h1>

                    <p class="view-clients-subtitle">
                        View clients to whom certificates have been sent
                    </p>

                </div>

                <!-- RECORD COUNT -->

                <span class="view-clients-count">

                    <?= $total_records; ?>

                    <?= ($total_records == 1)
                        ? 'record'
                        : 'records';
                    ?>

                </span>

            </div>


            <!-- ===============================
                 CARD
            ================================ -->

            <div class="view-clients-card">

                <!-- ===============================
                     TABLE WRAPPER
                ================================ -->

                <div
                    class="view-clients-table-wrapper"
                    id="scrollable-content"
                >

                    <table
                        class="view-clients-table"
                        id="payments-table"
                    >

                        <!-- ===============================
                             TABLE HEADER
                        ================================ -->

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Client Name
                                </th>

                                <th>
                                    Address
                                </th>

                                <th>
                                    Validity
                                </th>

                                <th>
                                    Sent On
                                </th>

                            </tr>

                        </thead>


                        <!-- ===============================
                             TABLE BODY
                        ================================ -->

                        <tbody>

                        <?php if ($total_records > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <tr>

                                    <!-- ID -->

                                    <td class="view-client-sr">

                                        <?= htmlspecialchars(
                                            $row['id'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- CLIENT NAME -->

                                    <td class="view-client-name">

                                        <?= htmlspecialchars(
                                            $row['cust_name'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- ADDRESS -->

                                    <td class="view-client-address">

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $row['cust_address'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        ); ?>

                                    </td>


                                    <!-- VALIDITY -->

                                    <td class="view-client-service">

                                        <?= htmlspecialchars(
                                            $row['validity'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>


                                    <!-- SENT ON -->

                                    <td class="view-client-date-cell">

                                        <?= htmlspecialchars(
                                            $row['sent_on'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="view-client-no-data"
                                >

                                    No certificate clients found.

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <!-- ===============================
                     BOTTOM HORIZONTAL SCROLLBAR
                ================================ -->

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


<!-- ===============================
     BOTTOM SCROLLBAR SYNC
================================ -->

<script>

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


        /* Bottom scrollbar -> table */

        bottomScroll.addEventListener(
            "scroll",
            function () {

                tableWrapper.scrollLeft =
                    bottomScroll.scrollLeft;

            }
        );


        /* Table -> bottom scrollbar */

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


<!-- ===============================
     EXISTING JS
================================ -->

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>

</body>
</html>
```
