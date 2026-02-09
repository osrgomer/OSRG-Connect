<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();

// Get friend to message
$friend_id = $_GET['friend'] ?? null;
$friend_name = '';

if ($friend_id) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$friend_id]);
    $friend = $stmt->fetch();
    $friend_name = $friend ? $friend['username'] : '';
}

// Send message
if ($_POST['content'] ?? false && $friend_id) {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $friend_id, $_POST['content']]);
    
    // Check if receiver wants email notifications
    $stmt = $pdo->prepare("SELECT u.email, u.username, u.email_notifications, s.username as sender_name FROM users u, users s WHERE u.id = ? AND s.id = ?");
    $stmt->execute([$friend_id, $_SESSION['user_id']]);
    $notification_data = $stmt->fetch();
    
    if ($notification_data && $notification_data['email_notifications']) {
        $subject = "New Message - OSRG Connect";
        $body = "Hi " . $notification_data['username'] . ",\n\n";
        $body .= "You have received a new message from " . $notification_data['sender_name'] . " on OSRG Connect.\n\n";
        $body .= "Login to read and reply: https://connect.osrg.lol/messages\n\n";
        $body .= "Best regards,\nOSRG Connect Team";
        
        $headers = "From: OSRG Connect <omer@osrg.lol>\r\n";
        $headers .= "Reply-To: omer@osrg.lol\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        ini_set('SMTP', 'smtp.hostinger.com');
        ini_set('smtp_port', '465');
        ini_set('sendmail_from', 'omer@osrg.lol');
        
        mail($notification_data['email'], $subject, $body, $headers);
    }
    
    header("Location: messages.php?friend=$friend_id");
    exit;
}

// Get messages between users
$messages = [];
if ($friend_id) {
    $stmt = $pdo->prepare("
        SELECT m.content, m.created_at, u.username, m.sender_id
        FROM messages m 
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll();
}

// Get friends list
$stmt = $pdo->prepare("
    SELECT u.id, u.username
    FROM users u
    JOIN friends f ON (
        (f.user_id = ? AND f.friend_id = u.id AND f.status = 'accepted') OR
        (f.friend_id = ? AND f.user_id = u.id AND f.status = 'accepted')
    )
    GROUP BY u.id, u.username
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$friends = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <title>Messages - OSRG Connect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; display: flex; gap: 25px; }
        .header { background: linear-gradient(135deg, #1877f2, #42a5f5); color: white; padding: 25px; text-align: center; margin-bottom: 25px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .header h1 { font-size: 2.2em; margin-bottom: 8px; }
        .friends-list { width: 280px; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 20px; border-radius: 15px; height: fit-content; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .friends-list h3 { color: #1877f2; margin-bottom: 15px; font-size: 1.3em; }
        .chat-area { flex: 1; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 15px; display: flex; flex-direction: column; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .messages { flex: 1; padding: 20px; max-height: 500px; overflow-y: auto; }
        .message { margin: 15px 0; padding: 12px 16px; border-radius: 18px; max-width: 70%; word-wrap: break-word; }
        .message.sent { background: linear-gradient(135deg, #1877f2, #42a5f5); color: white; margin-left: auto; margin-right: 0; }
        .message.received { background: #f1f3f4; color: #333; margin-right: auto; margin-left: 0; }
        .message small { opacity: 0.8; font-size: 11px; display: block; margin-top: 5px; }
        .message-form { padding: 20px; border-top: 1px solid rgba(0,0,0,0.1); background: rgba(248,249,250,0.8); border-radius: 0 0 15px 15px; }
        .friend-item { padding: 12px 15px; border-bottom: 1px solid rgba(0,0,0,0.05); cursor: pointer; border-radius: 8px; margin: 5px 0; transition: all 0.3s; }
        .friend-item:hover { background: rgba(24,119,242,0.1); transform: translateX(5px); }
        .friend-item.active { background: linear-gradient(135deg, rgba(24,119,242,0.1), rgba(66,165,245,0.1)); border-left: 3px solid #1877f2; }
        input, textarea { width: 100%; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 12px; font-size: 14px; transition: all 0.3s; background: rgba(255,255,255,0.9); }
        input:focus, textarea:focus { outline: none; border-color: #1877f2; box-shadow: 0 0 0 3px rgba(24,119,242,0.1); }
        button { background: linear-gradient(135deg, #1877f2, #42a5f5); color: white; padding: 12px 20px; border: none; border-radius: 12px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(24,119,242,0.3); }
        .chat-header { padding: 20px; border-bottom: 1px solid rgba(0,0,0,0.1); background: rgba(248,249,250,0.8); border-radius: 15px 15px 0 0; }
        .chat-header h3 { color: #1877f2; margin: 0; }
        
        @media (max-width: 768px) {
            body {
                margin: 0;
                padding: 0;
            }
            .container {
                flex-direction: column;
                padding: 5px;
                gap: 10px;
                max-width: 100%;
                margin: 0;
            }
            .friends-list {
                width: 100%;
                max-height: 150px;
                overflow-y: auto;
                padding: 10px;
            }
            .chat-area {
                height: calc(100vh - 300px);
                min-height: 300px;
                width: 100%;
            }
            .messages {
                max-height: calc(100vh - 400px);
                min-height: 200px;
                padding: 10px;
            }
            .message {
                margin: 8px 0;
                padding: 8px 12px;
                max-width: 85%;
                word-wrap: break-word;
            }
            .message.sent {
                margin-left: 15%;
                margin-right: 0;
            }
            .message.received {
                margin-right: 15%;
                margin-left: 0;
            }
            .message-form {
                padding: 8px;
                position: sticky;
                bottom: 0;
                background: white;
            }
            .message-form textarea {
                font-size: 16px;
                resize: none;
                min-height: 40px;
            }
            .message-form button {
                padding: 8px 16px;
                white-space: nowrap;
            }
            .header {
                margin-bottom: 5px;
                padding: 8px;
            }
            .nav {
                padding: 5px;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }
            .nav a {
                display: inline-block;
                padding: 8px 12px;
                margin: 2px 4px;
                background: #f0f2f5;
                border-radius: 20px;
                font-size: 14px;
                white-space: nowrap;
            }
        }
    </style>
    <script>
        let lastMessageCount = 0;
        let currentFriendId = <?= $friend_id ? $friend_id : 'null' ?>;
        
        function checkNewMessages() {
            if (currentFriendId) {
                fetch('check_messages.php?friend=' + currentFriendId)
                    .then(response => response.json())
                    .then(data => {
                        if (lastMessageCount > 0 && data.count > lastMessageCount) {
                            if ('Notification' in window && Notification.permission === 'granted') {
                                new Notification('New Message!', {
                                    body: data.latest_message,
                                    icon: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%231877f2"><path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>'
                                });
                            }
                            // Reload page to show new messages
                            location.reload();
                        }
                        lastMessageCount = data.count;
                    });
            }
        }
        
        // Auto-refresh every 3 seconds
        setInterval(function() {
            if (currentFriendId) {
                checkNewMessages();
            }
        }, 3000);
        
        // Request notification permission on page load
        function handleEnter(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                event.target.form.submit();
            }
        }
        
        function sendMessage(event) {
            return true; // Allow form submission
        }
        
        window.onload = function() {
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
            if (currentFriendId) {
                // Get initial message count
                fetch('check_messages.php?friend=' + currentFriendId)
                    .then(response => response.json())
                    .then(data => {
                        lastMessageCount = data.count;
                    });
            }
        }
    </script>
</head>
<body>
<?php require_once 'header.php'; ?>
    
    <div class="header">
        <h1>Private Messages</h1>
    </div>

    <div class="container">
        <div class="friends-list">
            <h3>Friends</h3>
            <?php foreach ($friends as $friend): ?>
            <div class="friend-item <?= $friend['id'] == $friend_id ? 'active' : '' ?>" 
                 onclick="location.href='messages.php?friend=<?= $friend['id'] ?>'">
                <?= htmlspecialchars($friend['username']) ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-area">
            <?php if ($friend_id): ?>
                <div class="chat-header">
                    <h3>💬 Chat with <?= htmlspecialchars($friend_name) ?></h3>
                </div>
                
                <div class="messages">
                    <?php foreach ($messages as $message): ?>
                    <div class="message <?= $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?>">
                        <div><?= htmlspecialchars($message['content']) ?></div>
                        <small><?= date('M j, H:i', strtotime($message['created_at'])) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" class="message-form" onsubmit="return sendMessage(event)">
                    <div style="display: flex; gap: 10px;">
                        <textarea name="content" placeholder="Type your message..." rows="2" required onkeydown="handleEnter(event)"></textarea>
                        <button type="submit">Send</button>
                    </div>
                </form>
            <?php else: ?>
                <div style="padding: 50px; text-align: center; color: #666;">
                    Select a friend to start messaging
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>