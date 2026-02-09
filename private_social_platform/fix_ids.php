<?php
require_once 'config.php';
init_db();

$pdo = get_db();

echo "Fixing user IDs to be sequential...<br><br>";

// Get all users ordered by current ID
$stmt = $pdo->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll();

// Start transaction
$pdo->beginTransaction();

try {
    // Create temporary table with new sequential IDs
    $pdo->exec("CREATE TEMPORARY TABLE users_temp AS SELECT * FROM users WHERE 1=0");
    
    $new_id = 1;
    $id_mapping = [];
    
    foreach ($users as $user) {
        $old_id = $user['id'];
        $id_mapping[$old_id] = $new_id;
        
        // Insert with new sequential ID
        $stmt = $pdo->prepare("INSERT INTO users_temp (id, username, email, password_hash, approved, created_at, timezone, email_notifications, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$new_id, $user['username'], $user['email'], $user['password_hash'], $user['approved'], $user['created_at'], $user['timezone'], $user['email_notifications'], $user['avatar']]);
        
        echo "User {$user['username']}: ID {$old_id} → {$new_id}<br>";
        $new_id++;
    }
    
    // Update related tables with new IDs
    echo "<br>Updating related tables...<br>";
    
    // Update posts
    foreach ($id_mapping as $old_id => $new_id) {
        $stmt = $pdo->prepare("UPDATE posts SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$new_id, $old_id]);
    }
    
    // Update friends table
    foreach ($id_mapping as $old_id => $new_id) {
        $stmt = $pdo->prepare("UPDATE friends SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$new_id, $old_id]);
        $stmt = $pdo->prepare("UPDATE friends SET friend_id = ? WHERE friend_id = ?");
        $stmt->execute([$new_id, $old_id]);
    }
    
    // Update messages
    foreach ($id_mapping as $old_id => $new_id) {
        $stmt = $pdo->prepare("UPDATE messages SET sender_id = ? WHERE sender_id = ?");
        $stmt->execute([$new_id, $old_id]);
        $stmt = $pdo->prepare("UPDATE messages SET receiver_id = ? WHERE receiver_id = ?");
        $stmt->execute([$new_id, $old_id]);
    }
    
    // Update reactions
    foreach ($id_mapping as $old_id => $new_id) {
        $stmt = $pdo->prepare("UPDATE reactions SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$new_id, $old_id]);
    }
    
    // Update comments
    foreach ($id_mapping as $old_id => $new_id) {
        $stmt = $pdo->prepare("UPDATE comments SET user_id = ? WHERE user_id = ?");
        $stmt->execute([$new_id, $old_id]);
    }
    
    // Replace original table
    $pdo->exec("DELETE FROM users");
    $pdo->exec("INSERT INTO users SELECT * FROM users_temp");
    $pdo->exec("DROP TABLE users_temp");
    
    // Reset auto-increment
    $pdo->exec("UPDATE sqlite_sequence SET seq = " . count($users) . " WHERE name = 'users'");
    
    $pdo->commit();
    
    echo "<br><strong style='color: green;'>✅ User IDs fixed successfully!</strong><br>";
    echo "<strong>Now you have sequential IDs 1-" . count($users) . "</strong><br>";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "<br><strong style='color: red;'>❌ Error: " . $e->getMessage() . "</strong><br>";
}
?>