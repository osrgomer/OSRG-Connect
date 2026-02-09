<?php
// API endpoint to get live online user counts
header('Content-Type: application/json');
session_start();

require_once 'db.php';
$pdo = getDB();

// Count online users (last 2 minutes)
$onlineCount = $pdo->query("
    SELECT COUNT(*) FROM users 
    WHERE last_active >= NOW() - INTERVAL 2 MINUTE 
    OR manual_status = 'online'
")->fetchColumn();

// Count idle users (2-5 minutes)
$idleCount = $pdo->query("
    SELECT COUNT(*) FROM users 
    WHERE last_active < NOW() - INTERVAL 2 MINUTE 
    AND last_active >= NOW() - INTERVAL 5 MINUTE
    AND manual_status != 'online'
    AND manual_status != 'offline'
")->fetchColumn();

// Count offline users
$offlineCount = $pdo->query("
    SELECT COUNT(*) FROM users 
    WHERE (last_active < NOW() - INTERVAL 5 MINUTE OR manual_status = 'offline')
    AND manual_status != 'online'
")->fetchColumn();

// Total users
$totalCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

echo json_encode([
    'online' => (int)$onlineCount,
    'idle' => (int)$idleCount,
    'offline' => (int)$offlineCount,
    'total' => (int)$totalCount
]);
