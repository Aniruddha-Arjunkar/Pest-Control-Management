<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = ($_POST['password']); // Hash the password
    $role = $_POST['role'];
    $branch = $_POST['branch'];
    $modules = isset($_POST['modules']) ? implode(',', $_POST['modules']) : '';
    
     // ✅ STEP 1: Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Email exists — show alert and stop execution
        echo "<script>
                alert('Email already exists for another user! Please choose a different email.');
                window.location.href='view-users.php';
              </script>";
        exit();
    }

    $check->close();

    // Prepare an insert statement
    $stmt = $conn->prepare("INSERT INTO users (name, phone, email, password, role, branch, access_modules) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $phone, $email, $password, $role,
        $branch,
        $modules);

    if ($stmt->execute()) {
        $_SESSION['msg'] = "User created successfully.";
    } else {
        $_SESSION['msg'] = "Error creating user: " . $stmt->error;
    }

    $stmt->close();
    header("Location: view-users.php"); // Redirect back to the main page
    exit();
}
?>
