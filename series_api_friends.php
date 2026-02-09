<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Initialize friends data in session if not exists
if (!isset($_SESSION['friends_data'])) {
    $_SESSION['friends_data'] = [
        'friends' => [],
        'requests' => [],
        'blocked' => []
    ];
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_friends':
        echo json_encode([
            'success' => true,
            'friends' => $_SESSION['friends_data']['friends']
        ]);
        break;
        
    case 'get_requests':
        echo json_encode([
            'success' => true,
            'requests' => $_SESSION['friends_data']['requests']
        ]);
        break;
        
    case 'add_friend':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $friendId = $data['friend_id'] ?? null;
            $friendUsername = $data['username'] ?? 'Unknown';
            $friendAvatar = $data['avatar'] ?? '';
            
            if ($friendId) {
                // Check if already friends
                $exists = false;
                foreach ($_SESSION['friends_data']['friends'] as $friend) {
                    if ($friend['id'] == $friendId) {
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    $_SESSION['friends_data']['friends'][] = [
                        'id' => $friendId,
                        'username' => $friendUsername,
                        'avatar' => $friendAvatar,
                        'online' => rand(0, 1) == 1,
                        'addedAt' => time() * 1000
                    ];
                    
                    echo json_encode([
                        'success' => true,
                        'message' => "You're now friends with {$friendUsername}!"
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Already friends'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid friend ID'
                ]);
            }
        }
        break;
        
    case 'remove_friend':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $friendId = $data['friend_id'] ?? null;
            
            if ($friendId) {
                $_SESSION['friends_data']['friends'] = array_values(
                    array_filter($_SESSION['friends_data']['friends'], function($f) use ($friendId) {
                        return $f['id'] != $friendId;
                    })
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend removed'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid friend ID'
                ]);
            }
        }
        break;
        
    case 'send_request':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $data['user_id'] ?? null;
            
            if ($userId) {
                $_SESSION['friends_data']['requests'][] = [
                    'id' => $userId,
                    'username' => $data['username'] ?? 'User',
                    'avatar' => $data['avatar'] ?? '',
                    'timestamp' => time() * 1000
                ];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Friend request sent'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid user ID'
                ]);
            }
        }
        break;
        
    case 'get_activity':
        // Mock activity feed
        $activities = [];
        foreach ($_SESSION['friends_data']['friends'] as $friend) {
            if (rand(0, 1) == 1) {
                $activities[] = [
                    'user' => $friend,
                    'action' => ['started watching', 'completed', 'added to watchlist'][rand(0, 2)],
                    'show' => ['Breaking Bad', 'The Office', 'Stranger Things', 'Game of Thrones'][rand(0, 3)],
                    'time' => (time() - rand(3600, 86400)) * 1000,
                    'rating' => rand(0, 1) == 1 ? (rand(70, 100) / 10) : null
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'activities' => $activities
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
