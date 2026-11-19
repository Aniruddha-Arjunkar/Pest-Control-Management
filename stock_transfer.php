<?php

session_start();

if (!isset($_SESSION['email'])) {

    $_SESSION['msg'] = "You must log in first";

    header('Location: login.php');

    exit();
}


require 'db_connect.php';

include 'header.php';


/* =========================================
   FETCH MATERIALS
========================================= */

$materials = [];

$result = $conn->query(
    "SELECT * FROM materials ORDER BY name"
);

while ($row = $result->fetch_assoc()) {

    $materials[$row['id']] = $row;
}


/* =========================================
   FETCH BRANCHES
========================================= */

$branches = [];

$resBranches = $conn->query(
    "SELECT id, name FROM branches ORDER BY name"
);

while ($row = $resBranches->fetch_assoc()) {

    $branches[$row['id']] = $row['name'];
}


$message = '';

$message_type = '';


/* =========================================
   HANDLE FORM SUBMISSION
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $from_branch_id = intval(
        $_POST['from_branch'] ?? 0
    );

    $to_branch_id = intval(
        $_POST['to_branch'] ?? 0
    );

    $material_id = intval(
        $_POST['material_id'] ?? 0
    );

    $qty = floatval(
        $_POST['quantity'] ?? 0
    );

    $date = trim(
        $_POST['transfer_date'] ?? ''
    );


    /* =========================================
       BASIC VALIDATION
    ========================================= */

    if ($from_branch_id <= 0) {

        $message = "Please select source branch.";
        $message_type = "error";

    } elseif ($to_branch_id <= 0) {

        $message = "Please select destination branch.";
        $message_type = "error";

    } elseif ($from_branch_id === $to_branch_id) {

        $message =
            "Source and destination branches cannot be the same.";

        $message_type = "error";

    } elseif ($material_id <= 0) {

        $message = "Please select material.";
        $message_type = "error";

    } elseif ($qty <= 0) {

        $message = "Quantity must be greater than zero.";
        $message_type = "error";

    } elseif ($date === '') {

        $message = "Transfer date is required.";
        $message_type = "error";

    } else {


        /* =========================================
           CALCULATE AVAILABLE STOCK
        ========================================= */

        /*
         * Available stock =
         *
         * Total Purchased
         * - Total Used
         * - Total Transferred Out
         * + Total Transferred In
         */

        $sql_available = "

            SELECT

                IFNULL(
                    SUM(sp.quantity),
                    0
                ) AS total_purchased,


                IFNULL(

                    (

                        SELECT SUM(

                            CASE

                                WHEN
                                    m.rate_unit = 'Ltr'
                                    AND sm.quantity_unit = 'ml'

                                THEN
                                    sm.quantity_used / 1000


                                WHEN
                                    m.rate_unit = 'Ltr'
                                    AND sm.quantity_unit = 'Ltr'

                                THEN
                                    sm.quantity_used


                                WHEN
                                    m.rate_unit = 'Kg'
                                    AND sm.quantity_unit = 'gm'

                                THEN
                                    sm.quantity_used / 1000


                                WHEN
                                    m.rate_unit = 'Kg'
                                    AND sm.quantity_unit = 'Kg'

                                THEN
                                    sm.quantity_used


                                WHEN
                                    m.rate_unit = 'Pc'
                                    AND sm.quantity_unit = 'Pc'

                                THEN
                                    sm.quantity_used


                                ELSE 0

                            END

                        )

                        FROM service_materials sm

                        INNER JOIN materials m
                            ON m.id = sm.material_id

                        WHERE
                            sm.material_id = ?
                            AND sm.branch_id = ?

                    ),

                    0

                ) AS total_used,


                IFNULL(

                    (

                        SELECT SUM(st.quantity)

                        FROM stock_transfers st

                        WHERE
                            st.material_id = ?
                            AND st.from_branch = ?

                    ),

                    0

                ) AS total_transferred_out,


                IFNULL(

                    (

                        SELECT SUM(st.quantity)

                        FROM stock_transfers st

                        WHERE
                            st.material_id = ?
                            AND st.to_branch = ?

                    ),

                    0

                ) AS total_transferred_in


            FROM stock_purchases sp

            WHERE
                sp.material_id = ?
                AND sp.branch_id = ?

        ";


        $stmt = $conn->prepare(
            $sql_available
        );


        if (!$stmt) {

            $message =
                "Unable to calculate available stock.";

            $message_type = "error";

        } else {


            $stmt->bind_param(

                "iiiiiiii",

                $material_id,
                $from_branch_id,

                $material_id,
                $from_branch_id,

                $material_id,
                $from_branch_id,

                $material_id,
                $from_branch_id

            );


            if (!$stmt->execute()) {

                $message =
                    "Unable to calculate available stock.";

                $message_type = "error";

            } else {


                $res = $stmt->get_result();

                $data = $res->fetch_assoc();


                $available =

                    floatval(
                        $data['total_purchased']
                    )

                    -

                    floatval(
                        $data['total_used']
                    )

                    -

                    floatval(
                        $data['total_transferred_out']
                    )

                    +

                    floatval(
                        $data['total_transferred_in']
                    );


                /* =========================================
                   CHECK AVAILABLE STOCK
                ========================================= */

                if ($available < $qty) {

                    $branch_name =
                        $branches[$from_branch_id]
                        ?? 'selected branch';


                    $message =
                        "Only " .
                        $available .
                        " units available in branch " .
                        $branch_name .
                        ".";

                    $message_type = "error";


                } else {


                    /* =========================================
                       INSERT TRANSFER
                    ========================================= */

                    $insert = $conn->prepare(

                        "INSERT INTO stock_transfers
                        (
                            material_id,
                            quantity,
                            from_branch,
                            to_branch,
                            transfer_date
                        )
                        VALUES (?, ?, ?, ?, ?)"

                    );


                    if (!$insert) {

                        $message =
                            "Unable to prepare transfer query.";

                        $message_type = "error";

                    } else {


                        $insert->bind_param(

                            "idiis",

                            $material_id,
                            $qty,
                            $from_branch_id,
                            $to_branch_id,
                            $date

                        );


                        if ($insert->execute()) {


                            /* =========================================
                               SUCCESS
                            ========================================= */

                            $from_name =
                                $branches[$from_branch_id]
                                ?? 'source branch';

                            $to_name =
                                $branches[$to_branch_id]
                                ?? 'destination branch';


                            $message =
                                "Stock transferred successfully from " .
                                $from_name .
                                " to " .
                                $to_name .
                                ".";


                            $message_type = "success";


                            /*
                             * Clear submitted values
                             * after successful transfer
                             */

                            $_POST = [];


                        } else {

                            $message =
                                "Error saving stock transfer: " .
                                $insert->error;

                            $message_type = "error";
                        }
                    }
                }
            }
        }
    }
}

?>


<body>


<div class="reg-w3">

    <div class="refollowup-card">


        <!-- =================================
             HEADER
        ================================== -->

        <div class="refollowup-header">

            <div class="refollowup-title">

                <span class="phone-icon">📦</span>

                <h2>
                    Transfer Stock
                </h2>

            </div>

        </div>


        <!-- =================================
             FORM
        ================================== -->

        <form
            class="refollowup-form"
            method="POST"
        >


            <!-- =================================
                 FROM BRANCH
            ================================== -->

            <div class="form-group">

                <label>
                    From Branch
                </label>


                <select
                    name="from_branch"
                    required
                >

                    <option value="">
                        Select
                    </option>


                    <?php foreach (
                        $branches as $id => $name
                    ): ?>

                        <option
                            value="<?= htmlspecialchars($id) ?>"
                            <?=
                                (
                                    isset($_POST['from_branch'])
                                    &&
                                    $_POST['from_branch'] == $id
                                )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars($name) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================
                 TO BRANCH
            ================================== -->

            <div class="form-group">

                <label>
                    To Branch
                </label>


                <select
                    name="to_branch"
                    required
                >

                    <option value="">
                        Select
                    </option>


                    <?php foreach (
                        $branches as $id => $name
                    ): ?>

                        <option
                            value="<?= htmlspecialchars($id) ?>"
                            <?=
                                (
                                    isset($_POST['to_branch'])
                                    &&
                                    $_POST['to_branch'] == $id
                                )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars($name) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================
                 MATERIAL
            ================================== -->

            <div class="form-group">

                <label>
                    Material
                </label>


                <select
                    name="material_id"
                    required
                >

                    <option value="">
                        Select Material
                    </option>


                    <?php foreach (
                        $materials as $id => $material
                    ): ?>

                        <option
                            value="<?= htmlspecialchars($id) ?>"
                            <?=
                                (
                                    isset($_POST['material_id'])
                                    &&
                                    $_POST['material_id'] == $id
                                )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $material['name']
                            ) ?>

                            (
                            <?= htmlspecialchars(
                                $material['rate_unit']
                            ) ?>
                            )

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- =================================
                 QUANTITY
            ================================== -->

            <div class="form-group">

                <label>
                    Quantity
                </label>


                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="quantity"
                    placeholder="Enter Quantity"
                    value="<?= htmlspecialchars(
                        $_POST['quantity'] ?? ''
                    ) ?>"
                    required
                >

            </div>


            <!-- =================================
                 TRANSFER DATE
            ================================== -->

            <div class="form-group">

                <label>
                    Transfer Date
                </label>


                <input
                    type="date"
                    name="transfer_date"
                    value="<?= htmlspecialchars(
                        $_POST['transfer_date']
                        ?? date('Y-m-d')
                    ) ?>"
                    required
                >

            </div>


            <!-- =================================
                 SUBMIT
            ================================== -->

            <div class="form-group full-width">

                <button
                    type="submit"
                    name="submit"
                    class="submit-btn"
                >

                    <span>↗️</span>

                    Transfer Stock

                </button>

            </div>


        </form>

    </div>

</div>


<!-- =========================================
     SUCCESS / ERROR POPUP
========================================= -->

<?php if (!empty($message)): ?>

<script>

alert(
    <?= json_encode($message) ?>
);

</script>

<?php endif; ?>


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
