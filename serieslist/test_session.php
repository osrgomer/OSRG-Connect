<?php
session_start();

// Fix old sessions that don't have user_id
if (isset($_SESSION['user_email']) && !isset($_SESSION['user_id'])) {
    require_once 'db.php';
    $user = getUserByEmail($_SESSION['user_email']);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['username'];
        echo "<div style='background: #4ade80; color: white; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "✅ SESSION FIXED! Added user_id: {$user['id']}";
        echo "</div>";
    }
}

echo "<h2>Current Session Data:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "Full Session Contents:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h2>What should be happening:</h2>";
echo "<pre>";
if (isset($_SESSION['user_email'])) {
    require_once 'db.php';
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, email, username, manual_status FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['user_email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Email in session: {$_SESSION['user_email']}\n";
    echo "User in database:\n";
    print_r($user);
    
    if ($user) {
        echo "\nSession user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
        echo "Database user ID: " . $user['id'] . "\n";
        
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user['id']) {
            echo "\n⚠️ MISMATCH! Session has wrong user_id!\n";
        } elseif (isset($_SESSION['user_id'])) {
            echo "\n✅ Session user_id is correct!\n";
        } else {
            echo "\n⚠️ Session user_id is NOT SET!\n";
        }
    }
} else {
    echo "Not logged in!\n";
}
echo "</pre>";
