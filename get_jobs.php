<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

$sql = "SELECT 
            job_profile,
            position_level,
            skills_required,
            education
        FROM tbl_requirement
        ORDER BY Id DESC";

$result = mysqli_query($conn, $sql);

$jobs = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $jobs[] = $row;
    }
}

echo json_encode($jobs);
?>