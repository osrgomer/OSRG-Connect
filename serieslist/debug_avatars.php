<?php
session_start();
require_once 'db.php';

echo "<h1>Avatar Debug</h1>";

$db = getDB();
$stmt = $db->query("SELECT id, username, email, avatar FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Avatar URL</th><th>Preview</th></tr>";

foreach ($users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td style='font-size:10px; max-width:300px; word-break:break-all;'>{$user['avatar']}</td>";
    echo "<td><img src='{$user['avatar']}' style='width:50px; height:50px; border-radius:50%;'></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Test API Response</h2>";
$_GET['action'] = 'get_stats';
ob_start();
include 'api_admin.php';
$response = ob_get_clean();

echo "<pre>";
$data = json_decode($response, true);
if (isset($data['bubbles'])) {
    echo "Bubbles found: " . count($data['bubbles']) . "\n\n";
    foreach ($data['bubbles'] as $bubble) {
        echo "User: {$bubble['username']}\n";
        echo "Avatar: {$bubble['avatar']}\n\n";
    }
} else {
    echo "No bubbles in response!\n";
    print_r($data);
}
echo "</pre>";
?>
