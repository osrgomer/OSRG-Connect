<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();

// Handle add friend
if ($_GET['add'] ?? false) {
    try {
        $friend_id = $_GET['add'];
        
        // Insert friend request
        $stmt = $pdo->prepare("INSERT IGNORE INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $friend_id]);
        
        // Get current user's username for the notification
        $stmt_user = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $current_user = $stmt_user->fetch();
        
        // Create notification for the recipient
        try {
            $notif_text = $current_user['username'] . " sent you a friend request";
            $stmt_notif = $pdo->prepare("INSERT INTO user_activity (user_id, type, description, link) VALUES (?, 'friend_request', ?, 'friends.php')");
            $stmt_notif->execute([$friend_id, $notif_text]);
        } catch (Exception $e) {
            // Continue even if notification fails
        }
        
        $message = 'Friend request sent!';
    } catch (Exception $e) {
        $message = 'Could not send friend request. Please try again.';
    }
}

// Get search term
$search = $_GET['search'] ?? '';

// Get users with their friendship status
if ($search) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username,
               CASE 
                   WHEN MAX(CASE WHEN f1.status = 'accepted' OR f2.status = 'accepted' THEN 1 ELSE 0 END) = 1 THEN 'friends'
                   WHEN MAX(CASE WHEN f1.status = 'pending' THEN 1 ELSE 0 END) = 1 THEN 'request_sent'
                   WHEN MAX(CASE WHEN f2.status = 'pending' THEN 1 ELSE 0 END) = 1 THEN 'request_received'
                   ELSE 'none'
               END as friendship_status
        FROM users u
        LEFT JOIN friends f1 ON (u.id = f1.friend_id AND f1.user_id = ?)
        LEFT JOIN friends f2 ON (u.id = f2.user_id AND f2.friend_id = ?)
        WHERE u.id != ? AND u.username LIKE ?
        GROUP BY u.id, u.username
        ORDER BY u.username
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], '%' . $search . '%']);
} else {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username,
               CASE 
                   WHEN MAX(CASE WHEN f1.status = 'accepted' OR f2.status = 'accepted' THEN 1 ELSE 0 END) = 1 THEN 'friends'
                   WHEN MAX(CASE WHEN f1.status = 'pending' THEN 1 ELSE 0 END) = 1 THEN 'request_sent'
                   WHEN MAX(CASE WHEN f2.status = 'pending' THEN 1 ELSE 0 END) = 1 THEN 'request_received'
                   ELSE 'none'
               END as friendship_status
        FROM users u
        LEFT JOIN friends f1 ON (u.id = f1.friend_id AND f1.user_id = ?)
        LEFT JOIN friends f2 ON (u.id = f2.user_id AND f2.friend_id = ?)
        WHERE u.id != ?
        GROUP BY u.id, u.username
        ORDER BY u.username
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
}
$users = $stmt->fetchAll();
// Set page variables for header
$page_title = 'Find Friends - OSRG Connect';
$additional_css = '
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { 
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.95)); 
            backdrop-filter: blur(20px); 
            color: #1877f2; 
            padding: 30px; 
            text-align: center; 
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1877f2, #42a5f5, #66bb6a, #ff9800, #e91e63);
        }
        .header h1 {
            font-size: 2.2em;
            background: linear-gradient(135deg, #1877f2, #42a5f5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            font-weight: 700;
        }
        .search-section { 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(20px); 
            padding: 25px; 
            margin: 20px 0; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .search-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-input {
            flex: 1;
            padding: 12px 18px;
            border: 2px solid rgba(24,119,242,0.2);
            border-radius: 25px;
            font-size: 16px;
            transition: all 0.3s ease;
            min-width: 200px;
        }
        .search-input:focus {
            outline: none;
            border-color: #1877f2;
            box-shadow: 0 0 0 3px rgba(24,119,242,0.1);
        }
        .search-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #1877f2, #42a5f5);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(24,119,242,0.3);
        }
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(24,119,242,0.4);
        }
        .clear-btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #666, #888);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102,102,102,0.3);
        }
        .clear-btn:hover {
            transform: translateY(-2px);
            color: white;
        }
        .users-section { 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(20px); 
            padding: 30px; 
            margin: 20px 0; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .section-title {
            color: #1877f2;
            margin-bottom: 25px;
            font-size: 1.4em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 20px 15px; 
            border-bottom: 1px solid rgba(0,0,0,0.08); 
            transition: all 0.3s ease;
            border-radius: 12px;
            margin-bottom: 8px;
        }
        .user-item:hover {
            background: rgba(24,119,242,0.02);
            transform: translateX(5px);
        }
        .user-item:last-child { border-bottom: none; }
        .username {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        .user-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .profile-btn {
            background: linear-gradient(135deg, #42a5f5, #66bb6a);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(66,165,245,0.3);
        }
        .profile-btn:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 6px 20px rgba(66,165,245,0.4);
        }
        .add-btn { 
            background: linear-gradient(135deg, #1877f2, #42a5f5);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(24,119,242,0.3);
        }
        .add-btn:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 6px 20px rgba(24,119,242,0.4);
        }
        .status-friends {
            color: #4caf50;
            font-weight: 600;
            background: rgba(76,175,80,0.1);
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 14px;
        }
        .status-sent {
            color: #ff9800;
            font-weight: 600;
            background: rgba(255,152,0,0.1);
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 14px;
        }
        .status-pending {
            color: #2196f3;
            font-weight: 600;
            background: rgba(33,150,243,0.1);
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 14px;
        }
        .message { 
            background: linear-gradient(135deg, rgba(76,175,80,0.1), rgba(139,195,74,0.1));
            color: #2e7d32;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
            font-weight: 600;
        }
        .no-results {
            text-align: center;
            color: #888;
            padding: 40px 20px;
            font-size: 16px;
            background: rgba(136,136,136,0.05);
            border-radius: 15px;
            border: 2px dashed rgba(136,136,136,0.2);
        }
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .header { padding: 25px 20px; }
            .search-form { flex-direction: column; align-items: stretch; }
            .search-input { min-width: auto; }
            .user-item { flex-direction: column; gap: 15px; text-align: center; }
            .user-actions { justify-content: center; }
        }
';

require_once 'header.php';
?>
    
    <div class="container">
        <div class="header">
            <h1>Find Friends</h1>
        </div>

        <?php if (isset($message)): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <!-- Search Form -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="🔍 Search by username..." value="<?= htmlspecialchars($search) ?>" class="search-input">
                <button type="submit" class="search-btn">Search</button>
                <?php if ($search): ?>
                    <a href="users.php" class="clear-btn">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="users-section">
            <?php if ($search): ?>
                <h3 class="section-title">🔍 Search Results for "<?= htmlspecialchars($search) ?>" (<?= count($users) ?> found)</h3>
            <?php else: ?>
                <h3 class="section-title">👥 All Users</h3>
            <?php endif; ?>
            
            <?php if ($users): ?>
                <?php foreach ($users as $user): ?>
                <div class="user-item">
                    <div class="username"><?= htmlspecialchars($user['username']) ?></div>
                    <div class="user-actions">
                        <a href="profile.php?id=<?= $user['id'] ?>" class="profile-btn">👤 View Profile</a>
                        <?php if ($user['friendship_status'] == 'friends'): ?>
                            <span class="status-friends">✓ Friends</span>
                        <?php elseif ($user['friendship_status'] == 'request_sent'): ?>
                            <span class="status-sent">📫 Request Sent</span>
                        <?php elseif ($user['friendship_status'] == 'request_received'): ?>
                            <span class="status-pending">⏳ Pending Request</span>
                        <?php else: ?>
                            <a href="?add=<?= $user['id'] ?>" class="add-btn">➕ Add Friend</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <?php if ($search): ?>
                        🔍 No users found matching "<?= htmlspecialchars($search) ?>"
                        <br><small>Try a different search term</small>
                    <?php else: ?>
                        👥 No other users found
                        <br><small>Be the first to invite friends!</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
