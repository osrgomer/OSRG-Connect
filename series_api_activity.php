<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentUserId = $_SESSION['user_email'] ?? 'user@example.com';
$currentUserName = $_SESSION['user_name'] ?? 'User';


// Connect to DB
require_once 'series_db.php';
$pdo = get_db();

$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch Activities
$activities = [];

try {
    // Determine if admin
    $isAdmin = $currentUser && ($currentUser['username'] === 'OSRG' || $currentUser['username'] === 'backup' || $currentUser['username'] === 'Omer Shalom Rimon');
    
    // Build Query
    // Regular users see: their own notifications (friend requests targeted at them) + public activities
    // Admin sees: all public activities AND 'system' type notifications (registrations)
    
    $sql = "
        SELECT ua.*, u.username, u.avatar
        FROM user_activity ua
        JOIN users u ON ua.user_id = u.id
        WHERE 
    ";
    
    if ($isAdmin) {
        // Admin sees everything, including system notifications
        $sql .= " (ua.type = 'system' OR ua.type != 'system') "; // Effectively everything
    } else {
        // Regular users see:
        // 1. Friend requests sent TO them (where user_id = their ID and type = 'friend_request')
        // 2. Other public activities (excluding system notifications)
        $currentUserId = $currentUser['id'] ?? 0;
        $sql .= " (
            (ua.user_id = {$currentUserId} AND ua.type = 'friend_request')
            OR
            (ua.type NOT IN ('system', 'friend_request'))
        ) ";
    }
    
    $sql .= " ORDER BY ua.created_at DESC LIMIT 20";
    
    $stmt = $pdo->query($sql);
    $raw_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($raw_activities as $act) {
        $activities[] = [
            'id' => $act['id'],
            'type' => $act['type'],
            'description' => $act['description'],
            'text' => $act['description'], // compatibility
            'link' => $act['link'],
            'timestamp' => strtotime($act['created_at']),
            'username' => $act['username'],
            'avatar_url' => $act['avatar'] ? (strpos($act['avatar'], 'http') === 0 ? $act['avatar'] : 'serve_asset.php?file=' . basename($act['avatar'])) : null
        ];
    }

} catch (Exception $e) {
    // Silent fail or return empty
}

echo json_encode(['activities' => $activities]);
