<?php
session_start();
require_once 'db_connect.php';

$sender = $_SESSION['email'];
$receiver = $_GET['with'];

// ✅ MARK AS READ
if($receiver){
    mysqli_query($conn, "
        UPDATE chats 
        SET is_read = 1 
        WHERE sender_email = '$receiver' 
        AND receiver_email = '$sender'
    ");
}

$chats = mysqli_query($conn, "
    SELECT * FROM chats 
    WHERE (sender_email='$sender' AND receiver_email='$receiver') 
    OR (sender_email='$receiver' AND receiver_email='$sender')
    ORDER BY timestamp ASC
");

while($chat = mysqli_fetch_assoc($chats)){
    $class = ($chat['sender_email']==$sender) ? 'sent' : 'received';

    echo "<div class='message $class'>";

    if(!empty($chat['message'])){
        echo htmlspecialchars($chat['message']);
    }

    if(!empty($chat['file'])){
    echo "<br><a href='uploads/".$chat['file']."' target='_blank'>
            ðŸ“Ž ".preg_replace('/^\d+_/', '', $chat['file'])."
          </a>";
}

    echo "</div>";
}
?>