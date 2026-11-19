<?php 
session_start(); 

if (!isset($_SESSION['email'])) { 
    $_SESSION['msg'] = "You must log in first"; 
    header('location: login.php'); 
    exit(); 
} 
 
require_once 'db_connect.php'; 
 
if (!isset($_GET['id']) || empty($_GET['id'])) { 
    echo "Invalid request."; 
    exit(); 
} 
 
$material_id = intval($_GET['id']); 
$error = ''; 
$success = ''; 
 
// Handle form submission 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 

    $name = trim($_POST['name']); 
    $rate_unit = trim($_POST['rate_unit']); 
    $rate_per_unit = trim($_POST['rate_per_unit']); 
 
    if (empty($name) || empty($rate_unit) || empty($rate_per_unit)) { 

        $error = "All fields are required."; 

    } else { 

        $stmt = $conn->prepare(
            "UPDATE materials 
             SET name = ?, rate_unit = ?, rate_per_unit = ? 
             WHERE id = ?"
        ); 

        $stmt->bind_param(
            "ssdi", 
            $name, 
            $rate_unit, 
            $rate_per_unit, 
            $material_id
        ); 
 
        if ($stmt->execute()) { 
            $success = "Material updated successfully."; 
        } else { 
            $error = "Error updating material."; 
        } 
    } 
} 
 
// Fetch current material data 
$stmt = $conn->prepare("SELECT * FROM materials WHERE id = ?"); 
$stmt->bind_param("i", $material_id); 
$stmt->execute(); 
$result = $stmt->get_result(); 
 
if ($result->num_rows !== 1) { 
    echo "Material not found."; 
    exit(); 
} 
 
$material = $result->fetch_assoc(); 
?> 
 
<?php include 'header.php'; ?>


<section id="main-content">
    <section class="wrapper">

        <div class="reg-w3">

            <div class="refollowup-card">

                <!-- HEADER -->
                <div class="refollowup-header">

                    <div class="refollowup-title">

                        <span class="phone-icon">📦</span>

                        <div>
                            <h2>Update Material</h2>
                            <p>Update material details</p>
                        </div>

                    </div>

                </div>


                <!-- FORM -->
                <form method="POST" class="refollowup-form">

                    <!-- SUCCESS / ERROR MESSAGE -->
                    <?php if ($error): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error); ?>
                        </div>

                    <?php endif; ?>


                    <?php if ($success): ?>

                        <div class="alert alert-success">
                            <?= htmlspecialchars($success); ?>
                        </div>

                    <?php endif; ?>


                    <!-- ROW 1 -->
                    <div class="form-row">

                        <div class="form-group">

                            <label for="name">
                                Material Name
                            </label>

                            <input 
                                type="text" 
                                id="name"
                                name="name"
                                value="<?= htmlspecialchars($material['name']); ?>"
                                placeholder="Enter material name"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="rate_unit">
                                Rate Unit
                            </label>

                            <input 
                                type="text" 
                                id="rate_unit"
                                name="rate_unit"
                                value="<?= htmlspecialchars($material['rate_unit']); ?>"
                                placeholder="Enter rate unit"
                                required
                            >

                        </div>

                    </div>


                    <!-- ROW 2 -->
                    <div class="form-row">

                        <div class="form-group full-width">

                            <label for="rate_per_unit">
                                Rate Per Unit
                            </label>

                            <input 
                                type="number"
                                step="0.01"
                                id="rate_per_unit"
                                name="rate_per_unit"
                                value="<?= htmlspecialchars($material['rate_per_unit']); ?>"
                                placeholder="Enter rate per unit"
                                required
                            >

                        </div>

                    </div>


                    <!-- SUBMIT -->
                    <div class="form-row">

                        <div class="form-group full-width">

                            <button 
                                type="submit"
                                name="submit"
                                class="submit-btn"
                            >
                                📦 Update Material
                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </section>
</section>


<!-- Footer -->

<script src="js/bootstrap.js"></script> 
<script src="js/jquery.dcjqaccordion.2.7.js"></script> 
<script src="js/scripts.js"></script> 
<script src="js/jquery.slimscroll.js"></script> 
<script src="js/jquery.nicescroll.js"></script> 
<script src="js/jquery.scrollTo.js"></script> 

</body> 
</html>