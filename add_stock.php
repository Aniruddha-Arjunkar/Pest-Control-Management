<?php

session_start();

/* =========================================
   LOGIN CHECK
========================================= */

if (!isset($_SESSION['email'])) {

    $_SESSION['msg'] = "You must log in first";

    header('Location: login.php');
    exit();
}


/* =========================================
   LOGOUT
========================================= */

if (isset($_GET['logout'])) {

    session_destroy();

    unset($_SESSION['email']);

    header('Location: login.php');
    exit();
}


/* =========================================
   DATABASE
========================================= */

require 'db_connect.php';

include 'header.php';


/* =========================================
   MESSAGE VARIABLES
========================================= */

$success = '';
$error   = '';


/* =========================================
   FETCH MATERIALS & BRANCHES
========================================= */

$materials = $conn->query(
    "SELECT * FROM materials ORDER BY name"
);

$branches = $conn->query(
    "SELECT * FROM branches ORDER BY name"
);


/* =========================================
   HANDLE FORM SUBMISSION
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $material_id = intval(
        $_POST['material_id'] ?? 0
    );

    $branch_id = intval(
        $_POST['branch_id'] ?? 0
    );

    $vendor_name = trim(
        $_POST['vendor_name'] ?? ''
    );

    $quantity = floatval(
        $_POST['quantity'] ?? 0
    );

    $purchase_date = trim(
        $_POST['purchase_date'] ?? ''
    );


    /* =========================================
       VALIDATION
    ========================================= */

    if ($material_id <= 0) {

        $error = "Please select material.";

    } elseif ($branch_id <= 0) {

        $error = "Please select branch.";

    } elseif ($vendor_name === '') {

        $error = "Vendor name is required.";

    } elseif ($quantity <= 0) {

        $error = "Quantity must be greater than zero.";

    } elseif ($purchase_date === '') {

        $error = "Purchase date is required.";

    } else {

        try {

            /* =========================================
               START TRANSACTION
            ========================================= */

            $conn->begin_transaction();


            /* =========================================
               GET MATERIAL RATE
            ========================================= */

            $rate_stmt = $conn->prepare(
                "SELECT rate_per_unit
                 FROM materials
                 WHERE id = ?"
            );

            if (!$rate_stmt) {

                throw new Exception(
                    "Failed to prepare material query."
                );
            }

            $rate_stmt->bind_param(
                "i",
                $material_id
            );

            if (!$rate_stmt->execute()) {

                throw new Exception(
                    "Failed to fetch material rate."
                );
            }

            $rate_result = $rate_stmt->get_result();


            if ($rate_result->num_rows === 0) {

                throw new Exception(
                    "Invalid material selected."
                );
            }


            $material = $rate_result->fetch_assoc();

            $rate = floatval(
                $material['rate_per_unit']
            );


            /* =========================================
               INSERT PURCHASE
            ========================================= */

            $stmt = $conn->prepare(
                "INSERT INTO stock_purchases
                (
                    material_id,
                    branch_id,
                    vendor_name,
                    quantity,
                    rate,
                    purchase_date
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );


            if (!$stmt) {

                throw new Exception(
                    "Failed to prepare purchase query."
                );
            }


            $stmt->bind_param(
                "iisdds",
                $material_id,
                $branch_id,
                $vendor_name,
                $quantity,
                $rate,
                $purchase_date
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Error saving stock purchase: " .
                    $stmt->error
                );
            }


            /* =========================================
               CHECK EXISTING BRANCH STOCK
            ========================================= */

            $check = $conn->prepare(
                "SELECT quantity
                 FROM branch_stock
                 WHERE material_id = ?
                 AND branch_id = ?"
            );


            if (!$check) {

                throw new Exception(
                    "Failed to prepare stock check."
                );
            }


            $check->bind_param(
                "ii",
                $material_id,
                $branch_id
            );


            if (!$check->execute()) {

                throw new Exception(
                    "Failed to check existing stock."
                );
            }


            $result = $check->get_result();


            /* =========================================
               UPDATE EXISTING STOCK
            ========================================= */

            if ($result->num_rows > 0) {

                $update = $conn->prepare(
                    "UPDATE branch_stock
                     SET quantity = quantity + ?
                     WHERE material_id = ?
                     AND branch_id = ?"
                );


                if (!$update) {

                    throw new Exception(
                        "Failed to prepare stock update."
                    );
                }


                $update->bind_param(
                    "dii",
                    $quantity,
                    $material_id,
                    $branch_id
                );


                if (!$update->execute()) {

                    throw new Exception(
                        "Stock update failed: " .
                        $update->error
                    );
                }


                if ($update->affected_rows < 1) {

                    throw new Exception(
                        "Stock was not updated."
                    );
                }


            } else {


                /* =========================================
                   INSERT NEW STOCK
                ========================================= */

                $insert = $conn->prepare(
                    "INSERT INTO branch_stock
                    (
                        material_id,
                        branch_id,
                        quantity
                    )
                    VALUES (?, ?, ?)"
                );


                if (!$insert) {

                    throw new Exception(
                        "Failed to prepare stock insert."
                    );
                }


                $insert->bind_param(
                    "iid",
                    $material_id,
                    $branch_id,
                    $quantity
                );


                if (!$insert->execute()) {

                    throw new Exception(
                        "Stock insert failed: " .
                        $insert->error
                    );
                }
            }


            /* =========================================
               COMMIT
            ========================================= */

            $conn->commit();


            /* =========================================
               SUCCESS
            ========================================= */

            $success =
                "Stock purchased successfully.";


            /*
             * Clear form values after success
             */
            $_POST = [];


        } catch (Exception $e) {


            /* =========================================
               ROLLBACK
            ========================================= */

            $conn->rollback();


            $error = $e->getMessage();
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

                        <span class="phone-icon">📦</span>

                        <div>

                            <h2>
                                Purchase Material Stock
                            </h2>

                            <p>
                                Record new material stock purchase
                            </p>

                        </div>

                    </div>

                </div>


                <!-- =================================
                     FORM
                ================================== -->

                <form
                    method="POST"
                    action=""
                    class="refollowup-form"
                >


                    <!-- =================================
                         MATERIAL + BRANCH
                    ================================== -->

                    <div class="form-row">


                        <!-- MATERIAL -->

                        <div class="form-group">

                            <label>
                                Material <span>*</span>
                            </label>


                            <select
                                name="material_id"
                                required
                            >

                                <option value="">
                                    Select Material
                                </option>


                                <?php

                                if ($materials) {

                                    mysqli_data_seek(
                                        $materials,
                                        0
                                    );

                                    while (
                                        $m =
                                        $materials->fetch_assoc()
                                    ):

                                ?>

                                    <option
                                        value="<?= htmlspecialchars($m['id']) ?>"
                                        <?=
                                            (
                                                isset($_POST['material_id']) &&
                                                $_POST['material_id'] == $m['id']
                                            )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $m['name']
                                        ) ?>


                                        <?php
                                        if (
                                            isset($m['rate_unit']) &&
                                            $m['rate_unit'] !== ''
                                        ):
                                        ?>

                                            (
                                            <?= htmlspecialchars(
                                                $m['rate_unit']
                                            ) ?>
                                            )

                                        <?php endif; ?>

                                    </option>

                                <?php

                                    endwhile;

                                }

                                ?>

                            </select>

                        </div>


                        <!-- BRANCH -->

                        <div class="form-group">

                            <label>
                                Branch <span>*</span>
                            </label>


                            <select
                                name="branch_id"
                                required
                            >

                                <option value="">
                                    Select Branch
                                </option>


                                <?php

                                if ($branches) {

                                    mysqli_data_seek(
                                        $branches,
                                        0
                                    );

                                    while (
                                        $b =
                                        $branches->fetch_assoc()
                                    ):

                                ?>

                                    <option
                                        value="<?= htmlspecialchars($b['id']) ?>"
                                        <?=
                                            (
                                                isset($_POST['branch_id']) &&
                                                $_POST['branch_id'] == $b['id']
                                            )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $b['name']
                                        ) ?>

                                    </option>

                                <?php

                                    endwhile;

                                }

                                ?>

                            </select>

                        </div>

                    </div>


                    <!-- =================================
                         VENDOR
                    ================================== -->

                    <div class="form-group">

                        <label>
                            Vendor Name <span>*</span>
                        </label>


                        <input
                            type="text"
                            name="vendor_name"
                            placeholder="Vendor Name"
                            value="<?= htmlspecialchars(
                                $_POST['vendor_name'] ?? ''
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- =================================
                         QUANTITY + DATE
                    ================================== -->

                    <div class="form-row">


                        <!-- QUANTITY -->

                        <div class="form-group">

                            <label>
                                Quantity <span>*</span>
                            </label>


                            <input
                                type="number"
                                name="quantity"
                                placeholder="Enter Quantity"
                                step="0.01"
                                min="0.01"
                                value="<?= htmlspecialchars(
                                    $_POST['quantity'] ?? ''
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- PURCHASE DATE -->

                        <div class="form-group">

                            <label>
                                Purchase Date <span>*</span>
                            </label>


                            <input
                                type="date"
                                name="purchase_date"
                                value="<?= htmlspecialchars(
                                    $_POST['purchase_date']
                                    ?? date('Y-m-d')
                                ) ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- =================================
                         SUBMIT
                    ================================== -->

                    <button
                        type="submit"
                        class="submit-btn"
                    >

                        <span>➕</span>

                        Record Purchase

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>


<!-- =========================================
     SUCCESS / ERROR POPUP
========================================= -->

<?php if (!empty($success)): ?>

<script>

alert(
    <?= json_encode($success) ?>
);

</script>

<?php endif; ?>


<?php if (!empty($error)): ?>

<script>

alert(
    <?= json_encode($error) ?>
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
