<?php
// Connect to the database
require_once 'db_connect.php';

// Check if the 'id' is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prepare the DELETE SQL query
    $sql = "DELETE FROM tbl_registration WHERE Id = ?";
    
    // Prepare the statement
    if ($stmt = mysqli_prepare($conn, $sql)) {
        // Bind the ID parameter to the prepared statement
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            // Redirect to the previous page (or a list page)
            header('Location: view-student.php'); // Or the appropriate page where the list is displayed
            exit;
        } else {
            echo "Error deleting record: " . mysqli_error($conn);
        }
        
        // Close the prepared statement
        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement: " . mysqli_error($conn);
    }
}

// Close the database connection
mysqli_close($conn);
?>
