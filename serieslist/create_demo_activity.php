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

// Initialize activity storage
if (!isset($_SESSION['user_activity'])) {
    $_SESSION['user_activity'] = [];
}

// Create demo activity for current user's existing shows
// This is a one-time script to populate activity for shows added before tracking was implemented

$action = $_GET['action'] ?? '';

if ($action === 'create_demo_activity') {
    // Get user avatar
    $userAvatar = $_SESSION['global_users'][$currentUserId]['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($currentUserName) . '&background=4f46e5&color=fff';
    
    // Demo shows for omersr12@gmail.com
    $demoActivities = [
        ['action' => 'added to watchlist', 'show' => 'Breaking Bad', 'time_ago' => 7200], // 2 hours ago
        ['action' => 'completed', 'show' => 'Stranger Things', 'time_ago' => 14400], // 4 hours ago
        ['action' => 'updated progress on', 'show' => 'The Office', 'time_ago' => 21600], // 6 hours ago
        ['action' => 'added to watchlist', 'show' => 'Game of Thrones', 'time_ago' => 43200], // 12 hours ago
        ['action' => 'completed', 'show' => 'Better Call Saul', 'time_ago' => 86400], // 1 day ago
    ];
    
    // Initialize user activity if not exists
    if (!isset($_SESSION['user_activity'][$currentUserId])) {
        $_SESSION['user_activity'][$currentUserId] = [];
    }
    
    // Add demo activities
    foreach ($demoActivities as $demo) {
        $activity = [
            'user' => [
                'id' => $currentUserId,
                'username' => $currentUserName,
                'avatar' => $userAvatar,
                'online' => true
            ],
            'action' => $demo['action'],
            'show' => $demo['show'],
            'time' => time() - $demo['time_ago'],
            'rating' => null,
            'progress' => null
        ];
        
        $_SESSION['user_activity'][$currentUserId][] = $activity;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Created ' . count($demoActivities) . ' demo activities',
        'count' => count($demoActivities)
    ]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
