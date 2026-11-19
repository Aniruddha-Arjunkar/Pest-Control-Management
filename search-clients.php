<?php
require_once 'db_connect.php'; // Include database connection file


$query = isset($_GET['q']) ? $_GET['q'] : '';
$query = mysqli_real_escape_string($conn, $query);

$sql = "SELECT Id, cust_name FROM tbl_client WHERE cust_name LIKE ?";
$stmt = $conn->prepare($sql);
$search_query = '%' . $query . '%';
$stmt->bind_param("s", $search_query);
$stmt->execute();
$result = $stmt->get_result();

$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}

header('Content-Type: application/json');
echo json_encode($clients);
