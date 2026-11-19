<?php
session_start();
require_once 'db_connect.php';

$sender = $_SESSION['email'];
$receiver = $_GET['with'];

$message = mysqli_real_escape_string($conn, $_POST['message'] ?? '');
$file_name = '';

if(!empty($_FILES['file']['name'])){
    $file_name = time().'_'.$_FILES['file']['name'];
    move_uploaded_file($_FILES['file']['tmp_name'], "uploads/".$file_name);
}

if(!empty($message) || !empty($file_name)){
    mysqli_query($conn, "
        INSERT INTO chats (sender_email, receiver_email, message, file)
        VALUES ('$sender', '$receiver', '$message', '$file_name')
    ");
}
?>