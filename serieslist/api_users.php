<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CRITICAL: Set header BEFORE any output
header('Content-Type: application/json');

// Simple error handling - just output errors as JSON
ini_set('display_errors', 0);
error_reporting(0);

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Load database connection
require_once 'db.php';

// Update current user's last active timestamp in database
if (isset($_SESSION['user_id'])) {
    updateLastActive($_SESSION['user_id']);
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
        
        // Search in MySQL database
        $db = getDB();
        $currentUserId = $_SESSION['user_id'];
        
        // Search for users by username or email (exclude current user)
        $stmt = $db->prepare("
            SELECT id, username, email, avatar 
            FROM users 
            WHERE (username LIKE ? OR email LIKE ?) 
            AND id != ?
            LIMIT 20
        ");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm, $currentUserId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format results
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => $user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['username']),
                'online' => getUserStatus($user['id']) === 'online'
            ];
        }
        
        echo json_encode(['success' => true, 'users' => $results]);
        exit;
        break;
        
    case 'get_friends':
        // Get friends from MySQL
        $db = getDB();
        $currentUserId = $_SESSION['user_id'];
        
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.email, u.avatar, f.created_at
            FROM friendships f
            JOIN users u ON f.friend_id = u.id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$currentUserId]);
        $friendsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format with online status
        $friends = [];
        foreach ($friendsData as $friend) {
            $friends[] = [
                'id' => $friend['id'],
                'username' => $friend['username'],
                'avatar' => $friend['avatar'],
                'online' => getUserStatus($friend['id']) === 'online',
                'addedAt' => strtotime($friend['created_at']) * 1000 // Convert to JS timestamp
            ];
        }
        
        echo json_encode(['success' => true, 'friends' => $friends]);
        exit;
        
    case 'get_requests':
        // Requests not yet migrated to MySQL
        echo json_encode(['success' => true, 'requests' => []]);
        exit;
        
    case 'add_friend':
        // Add friend using MySQL
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $friendId = $data['friend_id'] ?? null;
            
            if (!$friendId) {
                echo json_encode(['success' => false, 'message' => 'Friend ID required']);
                exit;
            }
            
            $db = getDB();
            $currentUserId = $_SESSION['user_id'];
            
            // Check if already friends
            $stmt = $db->prepare("SELECT id FROM friendships WHERE user_id = ? AND friend_id = ?");
            $stmt->execute([$currentUserId, $friendId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Already friends']);
                exit;
            }
            
            // Add bidirectional friendship
            $stmt = $db->prepare("INSERT INTO friendships (user_id, friend_id) VALUES (?, ?), (?, ?)");
            $result = $stmt->execute([$currentUserId, $friendId, $friendId, $currentUserId]);
            
            // Get friend info
            $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$friendId]);
            $friend = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => "You're now friends with {$friend['username']}!"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'POST required']);
        }
        exit;
        
    case 'remove_friend':
        // Remove friend using MySQL
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $friendId = $data['friend_id'] ?? null;
            
            if (!$friendId) {
                echo json_encode(['success' => false, 'message' => 'Friend ID required']);
                exit;
            }
            
            $db = getDB();
            $currentUserId = $_SESSION['user_id'];
            
            // Remove bidirectional friendship
            $stmt = $db->prepare("DELETE FROM friendships WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $result = $stmt->execute([$currentUserId, $friendId, $friendId, $currentUserId]);
            
            echo json_encode(['success' => true, 'message' => 'Friend removed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'POST required']);
        }
        exit;
        
    case 'get_activity':
        // Get activity feed from MySQL
        $db = getDB();
        $currentUserId = $_SESSION['user_id'];
        
        // Get activities from friends only
        $stmt = $db->prepare("
            SELECT ua.*, u.username, u.avatar
            FROM user_activity ua
            JOIN users u ON ua.user_id = u.id
            JOIN friendships f ON (f.friend_id = ua.user_id AND f.user_id = ?)
            ORDER BY ua.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$currentUserId]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format activities
        $formattedActivities = [];
        foreach ($activities as $activity) {
            $formattedActivities[] = [
                'id' => $activity['id'],
                'userId' => $activity['user_id'],
                'username' => $activity['username'],
                'avatar' => $activity['avatar'],
                'action' => $activity['action'],
                'showTitle' => $activity['show_title'],
                'rating' => $activity['rating'],
                'progress' => $activity['progress'],
                'timestamp' => strtotime($activity['created_at']) * 1000 // Convert to JS timestamp
            ];
        }
        
        echo json_encode(['success' => true, 'activities' => $formattedActivities]);
        exit;
        
    case 'set_online_status':
        // Use api_set_status.php instead (this is duplicate)
        echo json_encode(['success' => false, 'message' => 'Use api_set_status.php']);
        exit;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
