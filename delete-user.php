<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['email'])) {
    $_SESSION['msg'] = "You must log in first";
    header('location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare the delete statement
    $sql = "DELETE FROM users WHERE id = ?";
    
    // Using prepared statements to prevent SQL injection
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id); // Bind the ID parameter
        if ($stmt->execute()) {
            // User deleted successfully
            header('Location: view-users.php'); // Redirect to the user list
            exit();
        } else {
            echo "Error deleting user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    echo "Invalid user ID.";
}
?>
