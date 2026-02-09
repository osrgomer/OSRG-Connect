<?php
session_start();

// Fix session if needed
if (isset($_SESSION['user_email']) && !isset($_SESSION['user_id'])) {
    require_once 'db.php';
    $user = getUserByEmail($_SESSION['user_email']);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
    }
}

require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userId = $_SESSION['user_id'];

// Get user from database
$db = getDB();
$stmt = $db->prepare("SELECT manual_status FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h1>Header Debug for User ID: $userId</h1>";
echo "<pre>";
echo "Database manual_status: " . ($user['manual_status'] ?? 'NOT FOUND') . "\n";
echo "getUserStatus() returns: " . getUserStatus($userId) . "\n";
echo "\$isOnline should be: " . (getUserStatus($userId) === 'online' ? 'TRUE' : 'FALSE') . "\n";
echo "Checkbox should be: " . (getUserStatus($userId) === 'online' ? 'CHECKED' : 'UNCHECKED') . "\n";
echo "</pre>";

echo "<h2>Go to friends.php and inspect the checkbox:</h2>";
echo "<p>Right-click the toggle area, click Inspect, and look for:</p>";
echo "<pre>&lt;input type=\"checkbox\" id=\"statusToggle\" ... &gt;</pre>";
echo "<p>Does it have the word <strong>checked</strong> in it?</p>";
