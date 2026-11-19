<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
include 'header.php';

if (!isset($_GET['client_id'])) {
    echo "Client ID is missing.";
    exit();
}

$client_id = intval($_GET['client_id']);
$used_date = $_GET['used_date'] ?? date('Y-m-d'); // Use passed date or fallback to today

// Fetch client name
$client_q = mysqli_query($conn, "SELECT cust_name FROM tbl_client WHERE id = $client_id");
$client = mysqli_fetch_assoc($client_q);

// Fetch available materials
$materials = mysqli_query($conn, "SELECT id, name FROM materials ORDER BY name ASC");
?>


    <style>
        
        .container {
            margin-top: 50px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        h3 {
            color: #333;
            margin-bottom: 25px;
        }
      
        
        .material-row{
    display:flex;
    flex-wrap:wrap;
    gap:15px;
    padding:15px;
    margin-bottom:15px;
    border:1px solid #ddd;
    border-radius:6px;
    background:#f9f9f9;
}

.field{
    flex:1;
    min-width:180px;
}

.field label{
    display:block;
    margin-bottom:5px;
    font-weight:bold;
    color:#000;
}

.input-control{
    width:100%;
    padding:8px;
    border:1px solid #ccc;
    border-radius:4px;
    box-sizing:border-box;
}

.btn{
    padding:10px 15px;
    border:none;
    border-radius:4px;
    cursor:pointer;
    color:#fff;
}

.btn-secondary{
    background:#6c757d;
}

.btn-success{
    background:#28a745;
}

.btn:hover{
    opacity:.9;
}

.mb-3{
    margin-bottom:15px;
}
.used-date strong{
    color:#000;
}
.used-date{
    display:inline-block;
    background:#e8f4ff;
    color:#0056b3;
    padding:10px 15px;
    border-radius:20px;
    font-weight:500;
    margin-bottom:20px;
}
    </style>
</head>
<body>
<section id="main-content">
  <section class="wrapper">
<div class="container">
    <h3>Add Materials for: <strong class="text-primary"><?= htmlspecialchars($client['cust_name']) ?></strong></h3>
    <p class="used-date"><strong>Used Date:</strong> <?= date('d/m/Y', strtotime($used_date)) ?></p>

    <form action="save-service-materials.php" method="POST">
        <input type="hidden" name="client_id" value="<?= $client_id ?>">
         <input type="hidden" name="used_date" value="<?= htmlspecialchars($used_date) ?>">

        <div id="material-section">
          <div class="material-row">

    <div class="field">
        <label>Branch</label>
        <select name="branch_id" class="input-control" required>
            <option value="">Select Branch</option>
            <?php
            $branches = mysqli_query($conn, "SELECT id,name FROM branches ORDER BY name");
            while($b = mysqli_fetch_assoc($branches)):
            ?>
            <option value="<?= $b['id'] ?>">
                <?= htmlspecialchars($b['name']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="field">
        <label>Material</label>
        <select name="material_id[]" class="input-control" required>
            <option value="">Select Material</option>
            <?php while ($m = mysqli_fetch_assoc($materials)): ?>
            <option value="<?= $m['id'] ?>">
                <?= htmlspecialchars($m['name']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="field">
        <label>Quantity</label>
        <input type="number" name="quantity_used[]" class="input-control" step="0.001" required>
    </div>

    <div class="field">
        <label>Unit</label>
        <select name="unit[]" class="input-control" required>
            <option value="ml">ml</option>
            <option value="Ltr">Ltr</option>
            <option value="gm">gm</option>
            <option value="Kg">Kg</option>
            <option value="Pc">Pc</option>
            <option value="Box">Box</option>
        </select>
    </div>

    <div class="field">
        <label>Remarks</label>
        <input type="text" name="remarks[]" class="input-control">
    </div>

</div>
        </div>

        <div class="mb-3">
            <button type="button" class="btn btn-secondary" onclick="addMaterialRow()">+ Add Another Material</button>
        </div>

        <div>
            <button type="submit" class="btn btn-success">Save Materials</button>
           
        </div>
    </form>
</div>
</section>
</section>

<script>
function addMaterialRow() {
    const row = document.querySelector('.material-row').cloneNode(true);
    row.querySelectorAll('input, select').forEach(el => {
        if (el.tagName === 'INPUT') el.value = '';
        else el.selectedIndex = 0;
    });
    document.getElementById('material-section').appendChild(row);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="js/flot-chart/excanvas.min.js"></script><![endif]-->
    <script src="js/jquery.scrollTo.js"></script>
    <script src="js/search.js"></script>
</body>
</html>
