<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Initialize global users storage (in production, use database)
if (!isset($_SESSION['global_users'])) {
    $_SESSION['global_users'] = [];
}

// Register current user in global users if not exists
$currentUserId = $_SESSION['user_email'] ?? 'user@example.com';
$currentUserName = $_SESSION['user_name'] ?? 'User';

if (!isset($_SESSION['global_users'][$currentUserId])) {
    $_SESSION['global_users'][$currentUserId] = [
        'id' => $currentUserId,
        'username' => $currentUserName,
        'email' => $currentUserId,
        'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($currentUserName) . '&background=4f46e5&color=fff',
        'registered_at' => time()
    ];
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'search_users':
        $query = $_GET['q'] ?? '';
        
        if (strlen($query) < 2) {
            echo json_encode(['success' => true, 'users' => []]);
            exit;
        }
        
        // Search in registered users
        $results = [];
        foreach ($_SESSION['global_users'] as $user) {
            // Don't show current user or already friends
            if ($user['id'] === $currentUserId) continue;
            
            $isFriend = false;
            if (isset($_SESSION['friends_data']['friends'])) {
                foreach ($_SESSION['friends_data']['friends'] as $friend) {
                    if ($friend['id'] === $user['id']) {
                        $isFriend = true;
                        break;
                    }
                }
            }
            
            if ($isFriend) continue;
            
            // Search by username or email
            if (stripos($user['username'], $query) !== false || stripos($user['email'], $query) !== false) {
                $results[] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'avatar' => $user['avatar'],
                    'online' => rand(0, 1) == 1 // Mock online status for now
                ];
            }
        }
        
        echo json_encode(['success' => true, 'users' => $results]);
        break;
        
    case 'get_friends':
        echo json_encode([
            'success' => true,
            'friends' => $_SESSION['friends_data']['friends'] ?? []
        ]);
        break;
        
    case 'get_requests':
        echo json_encode([
            'success' => true,
            'requests' => $_SESSION['friends_data']['requests'] ?? []
        ]);
        break;
        
    case 'add_friend':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $friendId = $data['friend_id'] ?? null;
            
            if (!$friendId) {
                echo json_encode(['success' => false, 'message' => 'Invalid friend ID']);
                exit;
            }
            
            // Get friend data from global users
            $friendData = $_SESSION['global_users'][$friendId] ?? null;
            
            if (!$friendData) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            
            // Initialize friends data if not exists
            if (!isset($_SESSION['friends_data'])) {
                $_SESSION['friends_data'] = ['friends' => [], 'requests' => [], 'blocked' => []];
            }
            
            // Check if already friends
            foreach ($_SESSION['friends_data']['friends'] as $friend) {
                if ($friend['id'] == $friendId) {
                    echo json_encode(['success' => false, 'message' => 'Already friends']);
                    exit;
                }
            }
            
            // Add friend
            $_SESSION['friends_data']['friends'][] = [
                'id' => $friendData['id'],
                'username' => $friendData['username'],
                'email' => $friendData['email'],
                'avatar' => $friendData['avatar'],
                'online' => rand(0, 1) == 1,
                'addedAt' => time() * 1000
            ];
            
            echo json_encode([
                'success' => true,
                'message' => "You're now friends with {$friendData['username']}!"
            ]);
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
        
    case 'get_activity':
        // Generate activity from friends
        $activities = [];
        $friends = $_SESSION['friends_data']['friends'] ?? [];
        
        foreach ($friends as $friend) {
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
