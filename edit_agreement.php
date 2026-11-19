<?php
session_start();
ob_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';
include 'header.php';


if (!isset($_GET['id'])) {
    die("Invalid request: ID missing.");
}

$id = intval($_GET['id']);

$query = "SELECT * FROM tbl_agreement WHERE Id = $id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Record not found.");
}

$data = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add shipping_address and gst_number to fields list
    $fields = [
        'name', 'email', 'phone', 'dob', 'agreement_date', 'gender', 'ten_th',
        'ten_th_percentage', 'ten_th_year_of_passing', 'twelve_th', 'twelve_th_percentage',
        'twelve_th_year_of_passing', 'graduation', 'graduation_percentage', 'graduation_year_of_passing',
        'post_graduation', 'post_graduation_percentage', 'post_graduation_year_of_passing',
        'address', 'shipping_address', 'gst_number', 'experience', 'skills', 'experience_description', 'status'
    ];

    $updates = [];
    foreach ($fields as $field) {
        // Check if field exists in POST to avoid errors
        $value = isset($_POST[$field]) ? mysqli_real_escape_string($conn, $_POST[$field]) : '';
        $updates[] = "$field = '$value'";
    }

    $update_sql = "UPDATE tbl_agreement SET " . implode(', ', $updates) . " WHERE Id = $id";

    if (mysqli_query($conn, $update_sql)) {
        header("Location: view-agreement-student.php");
        exit();
    } else {
        echo "Update failed: " . mysqli_error($conn);
    }
}
?>
<style>
    /*new form design*/
.form-glass {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    border-radius: 20px;
    padding: 40px 30px;
    width: 100%;
    max-width: 900px;
    box-sizing: border-box;
  }

  .form-glass h2 {
    text-align: center;
    margin-bottom: 30px;
  }

  .form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
  }

  .form-group {
    flex: 1 1 48%;
    display: flex;
    flex-direction: column;
  }

  .form-group.full-width {
    flex: 1 1 100%;
  }

  label {
    font-size: 15px;
    color: #333;
    margin-bottom: 6px;
  }

  input, select, textarea {
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #734D26;
    font-size: 14px;
    transition: border 0.3s ease;
  }

  input:focus, select:focus, textarea:focus {
    border-color: #d9b38c;
    outline: none;
  }

  .hidden {
    display: none !important;
  }

 

  @media (max-width: 768px) {
    .w3layouts-main {
      margin-left: 0 !important;
      width: 100% !important;
      padding-left: 15px !important;
      padding-right: 15px !important;
      box-sizing: border-box;
    }
    .form-group {
      flex: 1 1 48%;
      display: flex;
      flex-direction: column;
    }

    .form-group.full-width {
      flex: 1 1 100%;
    }
  }

/*  end new design*/

  h3{
    margin-bottom: 15px;
    margin-top: 25px;
    color: #000;
  }

  .form-group {
    margin-bottom: 0 !important;
  }

</style>

<body>
<body>
<section id="main-content">
<div class="w3layouts-main" style="width: 800px;">
    
    <form method="post" class="form-glass">
        <h2 class="mb-4 text-center">Edit Student Agreement</h2>

        <!-- Personal Information -->
        <h3>Personal Information</h3>
            <div class="form-row">
            
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($data['phone']) ?>" required>
            </div>

            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="dob" value="<?= htmlspecialchars($data['dob']) ?>">
            </div>

            <div class="form-group">
                <label>Agreement Date</label>
                <input type="date" name="agreement_date" value="<?= htmlspecialchars($data['agreement_date']) ?>">
            </div>

            <div class="form-group">
                <label>Gender</label>
                <select name="gender">
                    <option <?= $data['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option <?= $data['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    <option <?= $data['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
        </div>

        <!-- 10th Details -->
        <h3>10th Details</h3>
        <div class="form-row">
        
        <div class="form-group">    
            <label>Board</label>
            <input type="text" name="ten_th" value="<?= htmlspecialchars($data['ten_th']) ?>">
        </div>
        
        <div class="form-group">
            <label>Percentage</label>
            <input type="number" step="0.01" name="ten_th_percentage" value="<?= $data['ten_th_percentage'] ?>">
        </div>

        <div class="form-group">
            <label>Year of Passing</label>
            <input type="text" name="ten_th_year_of_passing" value="<?= htmlspecialchars($data['ten_th_year_of_passing']) ?>">
        </div>
    </div>

        <!-- 12th Details -->
        <h3>12th Details</h3>

        <div class="form-row">
        
        <div class="form-group">  
            <label>Board</label>
            <input type="text" name="twelve_th" value="<?= htmlspecialchars($data['twelve_th']) ?>">
        </div>

        <div class="form-group">
            <label>Percentage</label>
            <input type="number" step="0.01" name="twelve_th_percentage" value="<?= $data['twelve_th_percentage'] ?>">
        </div>

        <div class="form-group">
            <label>Year of Passing</label>
            <input type="text" name="twelve_th_year_of_passing" value="<?= htmlspecialchars($data['twelve_th_year_of_passing']) ?>">
        </div>
    </div>


        <!-- Graduation -->
        <h3>Graduation</h3>

        <div class="form-row">
        
        <div class="form-group">
            <label>Degree</label>
            <input type="text" name="graduation" value="<?= htmlspecialchars($data['graduation']) ?>">
        </div>

        <div class="form-group"> 
            <label>Percentage</label>
            <input type="number" step="0.01" name="graduation_percentage" value="<?= $data['graduation_percentage'] ?>">
        </div>

        <div class="form-group">
            <label>Year of Passing</label>
            <input type="text" name="graduation_year_of_passing" value="<?= htmlspecialchars($data['graduation_year_of_passing']) ?>">
        </div>
    </div>

        <!-- Post Graduation -->  
        <h3>Post Graduation</h3>
        <div class="form-row">
        
        <div class="form-group">
            <label>Degree</label>
            <input type="text" name="post_graduation" value="<?= htmlspecialchars($data['post_graduation']) ?>">
        </div>

        <div class="form-group">
            <label>Percentage</label>
            <input type="number" step="0.01" name="post_graduation_percentage" value="<?= $data['post_graduation_percentage'] ?>">
        </div>

        <div class="form-group">
            <label>Year of Passing</label>
            <input type="text" name="post_graduation_year_of_passing" value="<?= htmlspecialchars($data['post_graduation_year_of_passing']) ?>">
        </div>
    </div>

        <!-- Experience & Skills -->
        <h3>Experience & Skills</h3>

        <div class="form-row">
        
        <div class="form-group">
            <label>Experience</label>
            <input type="text" name="experience" value="<?= htmlspecialchars($data['experience']) ?>">
        </div>

        <div class="form-group">
            <label>Experience Description</label>
            <textarea name="experience_description" rows="1"><?= htmlspecialchars($data['experience_description']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Skills</label>
            <textarea name="skills" rows="3"><?= htmlspecialchars($data['skills']) ?></textarea>
        </div>
    </div>

    <h3></h3>
    <div class="form-row">
        <!-- Address -->
        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="1"><?= htmlspecialchars($data['address']) ?></textarea>
        </div>
        
        <!-- Shipping Address -->
        <div class="form-group">
            <label>Shipping Address</label>
            <input type="text" name="shipping_address" value="<?= htmlspecialchars($data['shipping_address']) ?>">
        </div>


        <!-- GST Number -->
        <div class="form-group">
            <label>GST Number</label>
            <input type="text" name="gst_number" value="<?= htmlspecialchars($data['gst_number']) ?>">
        </div>

        <!-- Status -->
        <div class="form-group">
            <label>Agreement Status</label>
            <select name="status">
                <option value="Agreed" <?= $data['status'] === 'Agreed' ? 'selected' : '' ?>>Agreed</option>
                <option value="Not Agreed" <?= $data['status'] === 'Not Agreed' ? 'selected' : '' ?>>Not Agreed</option>
            </select>
        </div>
    </div>
        <!-- Submit -->
            <input name="submit" type="submit" value="Update">
    </form>
</div>
</section>

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>
