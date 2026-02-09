<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();

// Add missing columns if they don't exist
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar TEXT");
} catch (Exception $e) {}

// Fix avatar for user ID 1 (OSRG)
$stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
$stmt->execute(['avatars/avatar_1_1756484138.jpg', 1]);

echo "Avatar fixed for user ID 1!<br>";
echo '<a href="settings.php">Back to Settings</a>';
?>