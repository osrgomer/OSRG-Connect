<?php
session_start();
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = 1;

require_once __DIR__ . '/db.php';
$db = getDB();

$newAvatar = 'uploads/avatars/avatar_af9237232b07134baee830e8fcf421d0.jpg';

echo "<h1>Manual Avatar Update Test</h1>";

$stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
$result = $stmt->execute([$newAvatar, 1]);

echo "<p>Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
echo "<p>Rows affected: " . $stmt->rowCount() . "</p>";

// Check new value
$stmt = $db->query("SELECT avatar FROM users WHERE id = 1");
$currentAvatar = $stmt->fetchColumn();

echo "<p>New avatar in DB: " . htmlspecialchars($currentAvatar) . "</p>";
echo "<img src='$currentAvatar' style='width:150px; height:150px; border-radius:50%;'>";
?>
