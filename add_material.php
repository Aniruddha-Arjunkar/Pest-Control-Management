<?php
session_start();

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location:login.php');
    exit();
}

require 'db_connect.php';
include 'header.php';


/* =========================
   ADD MATERIAL
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $rate = trim($_POST['rate'] ?? '');

    if ($name === '' || $unit === '' || $rate === '') {

        echo "<script>
            alert('Please fill all required fields');
        </script>";

    } else {

        $stmt = $conn->prepare("
            INSERT INTO materials
            (name, rate_unit, rate_per_unit)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param(
            "ssd",
            $name,
            $unit,
            $rate
        );

        if ($stmt->execute()) {

            echo "<script>
                alert('Material added successfully');
                window.location.href='add-material.php';
            </script>";

            exit();

        } else {

            echo "<script>
                alert('Error adding material: " .
                addslashes($stmt->error) .
                "');
            </script>";
        }

        $stmt->close();
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

                            <h2>Add Material</h2>

                            <p>
                                Add material and rate details
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


                    <!-- MATERIAL NAME -->

                    <div class="form-group">

                        <label for="name">
                            Material Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Enter material name"
                            value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- UNIT -->

                    <div class="form-group">

                        <label for="unit">
                            Unit <span>*</span>
                        </label>

                        <select
                            id="unit"
                            name="unit"
                            required
                        >

                            <option value="">
                                Select Unit
                            </option>

                            <option value="ml"
                                <?= (($_POST['unit'] ?? '') === 'ml') ? 'selected' : ''; ?>>
                                ml
                            </option>

                            <option value="Ltr"
                                <?= (($_POST['unit'] ?? '') === 'Ltr') ? 'selected' : ''; ?>>
                                Ltr
                            </option>

                            <option value="Kg"
                                <?= (($_POST['unit'] ?? '') === 'Kg') ? 'selected' : ''; ?>>
                                Kg
                            </option>

                            <option value="Pc"
                                <?= (($_POST['unit'] ?? '') === 'Pc') ? 'selected' : ''; ?>>
                                Pc
                            </option>

                            <option value="Box"
                                <?= (($_POST['unit'] ?? '') === 'Box') ? 'selected' : ''; ?>>
                                Box
                            </option>

                        </select>

                    </div>


                    <!-- RATE -->

                    <div class="form-group">

                        <label for="rate">
                            Rate per Unit <span>*</span>
                        </label>

                        <input
                            type="number"
                            id="rate"
                            name="rate"
                            step="0.01"
                            min="0"
                            placeholder="Enter rate per unit"
                            value="<?= htmlspecialchars($_POST['rate'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >

                        <span>ðŸ“¦</span>

                        Add Material

                    </button>


                </form>

            </div>

        </div>

    </section>

</section>


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
mysqli_close($conn);
?>
