<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check admin access - allow if ORIGINAL user is admin (even when impersonating)
$isAdmin = false;
if (isset($_SESSION['admin_origin'])) {
    // User is in God Mode - check if they were originally admin
    $originalUserId = $_SESSION['admin_origin'];
    $db = getDB();
    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$originalUserId]);
    $email = $stmt->fetchColumn();
    $isAdmin = ($email === 'omersr12@gmail.com');
} elseif (isset($_SESSION['user_email'])) {
    // Normal mode - check current user
    $isAdmin = ($_SESSION['user_email'] === 'omersr12@gmail.com');
}

if (!$isAdmin) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once 'db.php';

$action = $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'get_stats':
        // Get ALL stats in one call for efficiency
        
        // 1. Latest activities
        $stmt = $db->prepare("
            SELECT ua.*, u.username, u.avatar as user_avatar
            FROM user_activity ua
            JOIN users u ON ua.user_id = u.id
            ORDER BY ua.created_at DESC
            LIMIT 20
        ");
        $stmt->execute();
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Normalize activities to have avatar_url
        $activities = array_map(function($activity) {
            $activity['avatar_url'] = $activity['user_avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($activity['username']);
            unset($activity['user_avatar']);
            return $activity;
        }, $activities);
        
        // 2. Trending shows
        $stmt = $db->query("
            SELECT 
                title,
                COUNT(*) as total_fans,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'Watching' THEN 1 ELSE 0 END) as watching
            FROM series
            GROUP BY title
            HAVING COUNT(*) > 0
            ORDER BY total_fans DESC
            LIMIT 5
        ");
        $trending = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. All users with status (for bubble map)
        $stmt = $db->query("
            SELECT u.id, u.username, u.email, u.avatar, u.manual_status, u.last_active,
                   TIMESTAMPDIFF(SECOND, u.last_active, NOW()) as seconds_ago,
                   (SELECT ua.action FROM user_activity ua WHERE ua.user_id = u.id ORDER BY ua.created_at DESC LIMIT 1) as last_action
            FROM users u
            ORDER BY u.last_active DESC
        ");
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Categorize for bubble colors
        $bubbles = [];
        foreach ($allUsers as $user) {
            $secondsAgo = $user['seconds_ago'];
            $status = 'offline';
            $color = 'slate';
            
            if ($secondsAgo < 120 || $user['manual_status'] === 'online') {
                $status = 'online';
                $color = 'green';
            } elseif ($secondsAgo < 300 && $user['manual_status'] !== 'offline') {
                $status = 'idle';
                $color = 'amber';
            }
            
            $bubbles[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar_url' => $user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['username']),
                'status' => $status,
                'color' => $color,
                'last_action' => $user['last_action'],
                'seconds_ago' => $secondsAgo
            ];
        }
        
        // Normalize users array to match bubbles format
        $users = array_map(function($user) {
            return [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar_url' => $user['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['username']),
                'manual_status' => $user['manual_status'],
                'last_active' => $user['last_active'],
                'seconds_ago' => $user['seconds_ago']
            ];
        }, $allUsers);
        
        // 4. Server load (if available)
        $serverLoad = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
        
        // 5. Total counts
        $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalShows = $db->query("SELECT COUNT(*) FROM series")->fetchColumn();
        $totalFriendships = $db->query("SELECT COUNT(*) FROM friendships")->fetchColumn() / 2; // Bidirectional
        
        echo json_encode([
            'success' => true,
            'activities' => $activities,
            'trending' => $trending,
            'users' => $users,
            'bubbles' => $bubbles,
            'server_load' => $serverLoad,
            'stats' => [
                'total_users' => $totalUsers,
                'total_shows' => $totalShows,
                'total_friendships' => $totalFriendships
            ]
        ]);
        break;
        
    case 'get_activity':
        // Get latest 20 activities
        $stmt = $db->prepare("
            SELECT ua.*, u.username, u.avatar 
            FROM user_activity ua
            JOIN users u ON ua.user_id = u.id
            ORDER BY ua.created_at DESC
            LIMIT 20
        ");
        $stmt->execute();
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'activities' => $activities]);
        break;
        
    case 'get_trending':
        // Get top 5 trending shows
        $stmt = $db->query("
            SELECT 
                title,
                COUNT(*) as total_fans,
                SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'Watching' THEN 1 ELSE 0 END) as watching
            FROM series
            GROUP BY title
            HAVING COUNT(*) > 0
            ORDER BY total_fans DESC
            LIMIT 5
        ");
        $trending = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'trending' => $trending]);
        break;
        
    case 'impersonate':
        // God mode - impersonate a user
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $targetUserId = $data['user_id'] ?? null;
        
        if ($targetUserId) {
            $_SESSION['admin_origin'] = $_SESSION['user_id'];
            $_SESSION['user_id'] = $targetUserId;
            
            $stmt = $db->prepare("SELECT email, username FROM users WHERE id = ?");
            $stmt->execute([$targetUserId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['username'] = $user['username'];
            
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No user ID']);
        }
        break;
        
    case 'exit_impersonate':
        // Exit impersonation mode
        if (isset($_SESSION['admin_origin'])) {
            $_SESSION['user_id'] = $_SESSION['admin_origin'];
            unset($_SESSION['admin_origin']);
            
            // Restore admin credentials
            $db = getDB(); // MUST create $db here!
            $stmt = $db->prepare("SELECT email, username FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['username'] = $admin['username'];
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Not impersonating']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
?>
