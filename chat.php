<?php                                                                                
session_start();
require_once 'db_connect.php';
include 'header.php';

$sender = $_SESSION['email'];
$receiver = $_GET['with'] ?? '';

// ================= MARK AS READ =================
if ($receiver) {
    mysqli_query($conn, "
        UPDATE chats 
        SET is_read = 1 
        WHERE sender_email = '$receiver' 
        AND receiver_email = '$sender'
    ");
}


// ================= USER LIST =================
$chat_users = mysqli_query($conn, "
    SELECT u.name, u.email, u.last_active, u.status,
    (
        SELECT COUNT(*) 
        FROM chats 
        WHERE sender_email = u.email 
        AND receiver_email = '$sender' 
        AND is_read = 0
    ) as unread_count
    FROM users u
    WHERE u.email != '$sender'
    ORDER BY u.last_active DESC
");

// ================= CURRENT CHAT USER =================
$receiver_name = "Select user";
$receiver_status = "";

if ($receiver) {
    $get = mysqli_query($conn, "
        SELECT name, status, last_active 
        FROM users 
        WHERE email='$receiver'
    ");

    $r = mysqli_fetch_assoc($get);

    if ($r) {

        $is_online = (
            $r['status'] === 'online' &&
            (time() - strtotime($r['last_active']) < 60)
        );

        $receiver_name = $r['name'];

        $receiver_status = $is_online
            ? "🟢 Online"
            : "🔴”´ Last seen: " . date("h:i A", strtotime($r['last_active']));
    }
}
?>

<style>


#main-content {
    width: 100%;
    padding: 46px;
    box-sizing: border-box;
    animation: chatFadeIn 0.35s ease;
}


.chat-wrapper{
  display: flex;
    width: 100%;
    height: calc(100vh - 120px);
    min-height: 550px;

    overflow: hidden;

    background: #ffffff;

    border-radius: 18px;

    border: 1px solid rgba(0, 0, 0, 0.08);

    box-shadow:
        0 15px 45px rgba(0, 0, 0, 0.12),
        0 4px 12px rgba(0, 0, 0, 0.05);
}

.chat-users{
    width:30%;
    background:#111b21;
    color:#fff;
    overflow-y:auto;
}

.chat-user{
    padding:12px;
    border-bottom:1px solid #1f2c33;
    cursor:pointer;
}

.chat-user:hover{
    background:#202c33;
}

.chat-main{
    width:70%;
    display:flex;
    flex-direction:column;
    background:#efeae2;
}

.chat-header{
    padding:15px;
    background:#202c33;
    color:#fff;
}

.chat-box{
    flex:1;
    padding:15px;
    overflow-y:auto;
    background:#fff;
}

.message{
    padding:10px 14px;
    margin:6px 0;
    border-radius:10px;
    max-width:65%;
    font-size:14px;
    word-wrap:break-word;
}

.sent{
    background:#d9fdd3;
    margin-left:auto;
}

.received{
    background:#fff;
    margin-right:auto;
}

.chat-input{
    display:flex;
    padding:10px;
    background:#202c33;
}

.chat-input input{
    flex:1;
    padding:10px;
    border:none;
    border-radius:20px;
}

.chat-input button{
    margin-left:10px;
    padding:10px 18px;
    background:#25d366;
    border:none;
    border-radius:20px;
}
</style>

<section id="main-content">
    <section class="wrapper">
<div class="chat-wrapper">

    <!-- ================= USERS ================= -->
    <div class="chat-users">
        <h4 style="padding:10px;">Chats</h4>

        <?php while ($u = mysqli_fetch_assoc($chat_users)): ?>

            <?php
$is_online = (
    $u['status'] === 'online' &&
    (time() - strtotime($u['last_active']) < 60)
);
?>

            <div class="chat-user"
                 onclick="window.location='chat.php?with=<?php echo $u['email']; ?>'">

                <b><?php echo htmlspecialchars($u['name']); ?></b><br>

                <small>
        <?php echo $is_online ? "🟢 Online" : "🔴 Offline"; ?>
    </small><br>

                <small style="font-size:11px;color:#888;">
<?php
        echo $is_online
            ? "Active now"
            : "Last seen: " . date("h:i A", strtotime($u['last_active']));
        ?>
                </small>

                <?php if ($u['unread_count'] > 0): ?>
                    <span style="background:#25d366;padding:3px 8px;border-radius:50%;">
                        <?php echo $u['unread_count']; ?>
                    </span>
                <?php endif; ?>

            </div>

        <?php endwhile; ?>
    </div>

    <!-- ================= CHAT AREA ================= -->
    <div class="chat-main">

        <div class="chat-header">
            <div style="font-weight:bold;"><?php echo $receiver_name; ?></div>
            <div style="font-size:12px;"><?php echo $receiver_status; ?></div>
        </div>

        <div class="chat-box" id="chatBox">

            <?php
            if ($receiver) {
                $chats = mysqli_query($conn, "
                    SELECT * FROM chats
                    WHERE (sender_email='$sender' AND receiver_email='$receiver')
                       OR (sender_email='$receiver' AND receiver_email='$sender')
                    ORDER BY timestamp ASC
                ");

                while ($chat = mysqli_fetch_assoc($chats)) {

                    $class = ($chat['sender_email'] == $sender) ? 'sent' : 'received';

                    echo "<div class='message $class'>";

                    if (!empty($chat['message'])) {
                        echo htmlspecialchars($chat['message']);
                    }

                   if(!empty($chat['file'])){
                                echo "<br><a href='uploads/".$chat['file']."' target='_blank'>🔴“Ž File</a>";
                            }
                            
                            
                    // ✔✔” tick HERE
                            if($chat['sender_email'] == $sender){
                                echo "<div style='font-size:10px;text-align:right;'>
                                ".($chat['is_read'] ? "✔✔" : "✔")."
                                </div>";
                            }

                    echo "</div>";
                }
            }
            ?>

        </div>

        <?php if ($receiver): ?>
        <form id="chatForm" class="chat-input" enctype="multipart/form-data">
            <input type="text" name="message" placeholder="Type message...">
            <input type="file" name="file">
            <button type="submit">Send</button>
        </form>
        <?php endif; ?>

    </div>

</div>
</section>
</section>


<script>
function scrollBottom(){
    let box = document.getElementById("chatBox");
    box.scrollTop = box.scrollHeight;
}
scrollBottom();

$("#chatForm").submit(function(e){
    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "send_message.php?with=<?php echo $receiver; ?>",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(){
            $("#chatForm")[0].reset();
            loadChat();
        }
    });
});

function loadChat(){
    $.ajax({
        url: "fetch_chat.php?with=<?php echo $receiver; ?>",
        success: function(data){
            $("#chatBox").html(data);
            scrollBottom();
        }
    });
}

 setInterval(function(){
    $.get("update_last_seen.php");
}, 10000); // every 10 sec

</script>

<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>