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
if (!isset($_SESSION['user_activity'][$currentUserId])) {
    $_SESSION['user_activity'][$currentUserId] = [];
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'log_activity':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $activityType = $data['type'] ?? '';
            $showTitle = $data['show'] ?? '';
            $rating = $data['rating'] ?? null;
            $progress = $data['progress'] ?? null;
            
            if (!$activityType || !$showTitle) {
                echo json_encode(['success' => false, 'message' => 'Missing data']);
                exit;
            }
            
            // Get user avatar from global_users
            $userAvatar = $_SESSION['global_users'][$currentUserId]['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($currentUserName) . '&background=4f46e5&color=fff';
            
            // Create activity entry
            $activity = [
                'user' => [
                    'id' => $currentUserId,
                    'username' => $currentUserName,
                    'avatar' => $userAvatar,
                    'online' => true
                ],
                'action' => $activityType,
                'show' => $showTitle,
                'time' => time(),
                'rating' => $rating,
                'progress' => $progress
            ];
            
            // Add to user's activity log
            $_SESSION['user_activity'][$currentUserId][] = $activity;
            
            // Keep only last 50 activities per user
            if (count($_SESSION['user_activity'][$currentUserId]) > 50) {
                $_SESSION['user_activity'][$currentUserId] = array_slice($_SESSION['user_activity'][$currentUserId], -50);
            }
            
            echo json_encode(['success' => true]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
