<?php
session_start();
if (!isset($_SESSION['email'])) {
  header('location: login.php');
  exit();
}
require 'db_connect.php';
include 'header.php';

$sql = "
SELECT st.transfer_date AS date, m.name material, st.quantity qty,
 bf.name from_branch, bt.name to_branch
FROM stock_transfers st
JOIN materials m ON st.material_id=m.id
JOIN branches bf ON st.from_branch=bf.id
JOIN branches bt ON st.to_branch=bt.id
ORDER BY st.transfer_date DESC";
$res = $conn->query($sql);
?>
<style>
  .view-clients-table {
    width: 100%;
    min-width: 0 !important;
    table-layout: fixed;
}

.view-clients-table th:nth-child(1),
.view-clients-table td:nth-child(1) {
    width: 130px;
}

.view-clients-table th:nth-child(2),
.view-clients-table td:nth-child(2) {
    width: 220px;
}

.view-clients-table th:nth-child(3),
.view-clients-table td:nth-child(3) {
    width: 80px;
    text-align: center;
}

.view-clients-table th:nth-child(4),
.view-clients-table td:nth-child(4) {
    width: 220px;
}

.view-clients-table tbody td {
    padding: 9px 10px;
}
</style>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stock Transfer History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <section id="main-content">
    <section class="wrapper">
       <div class="view-clients-page">

    <div class="view-clients-header">
        <div class="view-clients-title-wrap">
            <h3 class="view-clients-title">
                <span class="view-clients-title-icon">ðŸ“¦</span>
                Transfer History Log
            </h3>
            <p class="view-clients-subtitle">
                View all stock transfer records
            </p>
        </div>
    </div>

    <div class="view-clients-card">

<div class="view-clients-table-wrapper">
          <table class="view-clients-table">
                 <thead>
      <tr>
        <th>Date</th>
        <th>Material</th>
        <th>Qty</th>
        <th>From â†’ To</th>
      </tr>
    </thead>
   <tbody>
<?php while($r = $res->fetch_assoc()): ?>
    <tr>
        <td class="view-client-date-cell">
            <?= htmlspecialchars($r['date']) ?>
        </td>

        <td class="view-client-name">
            <?= htmlspecialchars($r['material']) ?>
        </td>

        <td>
            <?= number_format($r['qty'], 3) ?>
        </td>

        <td class="view-client-service">
            <?= htmlspecialchars($r['from_branch']) ?>
            â†’
            <?= htmlspecialchars($r['to_branch']) ?>
        </td>
    </tr>
<?php endwhile; ?>
</tbody>
      </table>
</div>
<BR>
<BR>
<a href="stock_transfer.php" class="view-client-view-btn">
    Transfer More
</a>

    </div>
</div>
</section>
</section>
<script src="js/bootstrap.js"></script>
    <script src="js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/jquery.slimscroll.js"></script>
    <script src="js/jquery.nicescroll.js"></script>
    <script src="js/jquery.scrollTo.js"></script>
</body>
</html>