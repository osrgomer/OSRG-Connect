<?php
require_once 'db.php';

echo "<h2>All Users in Database:</h2>";
echo "<pre>";

$db = getDB();
$stmt = $db->query("SELECT id, email, username, manual_status, last_active FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Username: {$user['username']}\n";
    echo "manual_status: {$user['manual_status']}\n";
    echo "last_active: {$user['last_active']}\n";
    echo "Status check: " . getUserStatus($user['id']) . "\n";
    echo "---\n\n";
}

echo "</pre>";

echo "<h2>Test Manual Update for User 2:</h2>";
echo "<pre>";
$result = updateUserStatus(2, 'online');
echo "updateUserStatus(2, 'online') returned: " . ($result ? 'TRUE' : 'FALSE') . "\n";

// Check again
$stmt = $db->prepare("SELECT manual_status FROM users WHERE id = 2");
$stmt->execute();
$newStatus = $stmt->fetchColumn();
echo "Database now shows: " . $newStatus . "\n";
echo "</pre>";
