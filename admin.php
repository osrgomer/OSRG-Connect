<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();

// Check if user is admin (OSRG)
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || ($user['username'] !== 'OSRG' && $user['username'] !== 'backup')) {
    header('Location: index.php');
    exit;
}

$message = '';

// Handle user deletion
if ($_GET['delete'] ?? false) {
    $delete_id = $_GET['delete'];
    
    // Get username of user to delete
    $stmt_check = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_check->execute([$delete_id]);
    $user_to_delete = $stmt_check->fetch();
    
    // Can't delete self or other admin users
    if ($delete_id != $_SESSION['user_id'] && $user_to_delete && !($user_to_delete['username'] === 'OSRG' || $user_to_delete['username'] === 'backup')) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        // Clean up related data
        $stmt = $pdo->prepare("DELETE FROM posts WHERE user_id = ?");
        $stmt->execute([$delete_id]);
        
        $stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? OR friend_id = ?");
        $stmt->execute([$delete_id, $delete_id]);
        
        $stmt = $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
        $stmt->execute([$delete_id, $delete_id]);
        
        $message = 'User deleted successfully!';
    }
}

// Handle post deletion
if ($_GET['delete_post'] ?? false) {
    $post_id = $_GET['delete_post'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $message = 'Post deleted successfully!';
}

// Handle user approval
if ($_GET['approve'] ?? false) {
    $user_id = $_GET['approve'];
    
    // Get user details before approval
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    // Approve user
    $stmt = $pdo->prepare("UPDATE users SET approved = 1 WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Send approval email
    if ($user_data) {
        $subject = "Account Approved - OSRG Connect";
        $body = "Hi " . $user_data['username'] . ",\n\n";
        $body .= "Great news! Your OSRG Connect account has been approved.\n\n";
        $body .= "You can now login and start connecting with friends:\n";
        $body .= "https://connect.osrg.lol/login\n\n";
        $body .= "Welcome to OSRG Connect!\n\n";
        $body .= "Best regards,\nOSRG Connect Team";
        
        $headers = "From: OSRG Connect <omer@osrg.lol>\r\n";
        $headers .= "Reply-To: omer@osrg.lol\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        ini_set('SMTP', 'smtp.hostinger.com');
        ini_set('smtp_port', '465');
        ini_set('sendmail_from', 'omer@osrg.lol');
        
        mail($user_data['email'], $subject, $body, $headers);
    }
    
    $message = 'User approved successfully! Approval email sent.';
}

// Handle user rejection
if ($_GET['reject'] ?? false) {
    $user_id = $_GET['reject'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $message = 'User registration rejected!';
}

// Handle database backup
if ($_GET['backup'] ?? false) {
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.db';
    if (copy('private_social.db', 'backups/' . $backup_file)) {
        if (!is_dir('backups')) mkdir('backups', 0755, true);
        copy('private_social.db', 'backups/' . $backup_file);
        $message = 'Database backup created: ' . $backup_file;
    } else {
        $message = 'Backup failed!';
    }
}

// Handle cleanup old data
if ($_GET['cleanup'] ?? false) {
    // Delete posts older than 1 year
    $stmt = $pdo->prepare("DELETE FROM posts WHERE created_at < datetime('now', '-1 year')");
    $stmt->execute();
    $deleted_posts = $stmt->rowCount();
    
    // Delete orphaned reactions and comments
    $pdo->exec("DELETE FROM reactions WHERE post_id NOT IN (SELECT id FROM posts)");
    $pdo->exec("DELETE FROM comments WHERE post_id NOT IN (SELECT id FROM posts)");
    
    $message = "Cleanup completed. Deleted {$deleted_posts} old posts and orphaned data.";
}

// Handle website backup
if ($_GET['website_backup'] ?? false) {
    $backup_name = 'website_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($backup_name, ZipArchive::CREATE) === TRUE) {
        // Add all PHP files
        $files = glob('*.php');
        foreach ($files as $file) {
            $zip->addFile($file);
        }
        
        // Add database
        if (file_exists('private_social.db')) {
            $zip->addFile('private_social.db');
        }
        
        // Add uploads folder
        if (is_dir('sp_uploads')) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('sp_uploads'));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), $file->getPathname());
                }
            }
        }
        
        // Add avatars folder
        if (is_dir('avatars')) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('avatars'));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), $file->getPathname());
                }
            }
        }
        
        $zip->close();
        
        // Download the file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $backup_name . '"');
        header('Content-Length: ' . filesize($backup_name));
        readfile($backup_name);
        unlink($backup_name); // Delete after download
        exit;
    } else {
        $message = 'Failed to create website backup!';
    }
}

// Get pending users
$stmt = $pdo->query("SELECT id, username, email, created_at FROM users WHERE approved = 0 ORDER BY created_at DESC");
$pending_users = $stmt->fetchAll();

// Get all users
$stmt = $pdo->query("SELECT id, username, email, created_at, approved FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Get all posts
$stmt = $pdo->query("SELECT p.id, p.content, p.created_at, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$posts = $stmt->fetchAll();

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$user_count = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM posts");
$post_count = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM messages");
$message_count = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM reactions");
$reaction_count = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM comments");
$comment_count = $stmt->fetch()['count'];

// Get recent activity
$stmt = $pdo->query("SELECT u.username, 'registered' as action, u.created_at as timestamp FROM users u WHERE u.created_at > datetime('now', '-7 days') UNION ALL SELECT u.username, 'posted' as action, p.created_at as timestamp FROM posts p JOIN users u ON p.user_id = u.id WHERE p.created_at > datetime('now', '-7 days') ORDER BY timestamp DESC LIMIT 10");
$recent_activity = $stmt->fetchAll();

// Get user engagement analytics with error handling
try {
    $stmt = $pdo->query("SELECT u.id, u.username, u.created_at, COUNT(DISTINCT p.id) as post_count, COUNT(DISTINCT f.friend_id) as friend_count, COUNT(DISTINCT m.id) as message_count FROM users u LEFT JOIN posts p ON u.id = p.user_id LEFT JOIN friends f ON u.id = f.user_id LEFT JOIN messages m ON u.id = m.sender_id WHERE u.approved = 1 GROUP BY u.id ORDER BY post_count DESC");
    $user_analytics = $stmt->fetchAll();
} catch (Exception $e) {
    $user_analytics = [];
}

// Get daily activity stats for last 30 days
try {
    $stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as posts FROM posts WHERE created_at > datetime('now', '-30 days') GROUP BY DATE(created_at) ORDER BY date DESC");
    $daily_posts = $stmt->fetchAll();
} catch (Exception $e) {
    $daily_posts = [];
}

// Get most active users this month
try {
    $stmt = $pdo->query("SELECT u.username, COUNT(p.id) as posts_this_month FROM users u LEFT JOIN posts p ON u.id = p.user_id AND p.created_at > datetime('now', '-30 days') WHERE u.approved = 1 GROUP BY u.id ORDER BY posts_this_month DESC LIMIT 5");
    $top_users = $stmt->fetchAll();
} catch (Exception $e) {
    $top_users = [];
}

// Get system info
$db_size = filesize('private_social.db');
$uploads_size = 0;
if (is_dir('sp_uploads')) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('sp_uploads'));
    foreach ($files as $file) {
        if ($file->isFile()) $uploads_size += $file->getSize();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <title>Admin Panel - OSRG Connect</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header { background: #d32f2f; color: white; padding: 15px; text-align: center; }
        .nav { background: white; padding: 10px; margin-bottom: 20px; border-radius: 8px; }
        .nav a { color: #1877f2; text-decoration: none; margin-right: 15px; }
        .admin-nav { color: #d32f2f; font-weight: bold; }
        .post { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-box { background: white; padding: 20px; border-radius: 8px; text-align: center; flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #1877f2; }
        .user-item, .post-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        .delete-btn { background: #f44336; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; }
        .message { color: green; padding: 10px; background: #e8f5e8; border-radius: 5px; margin-bottom: 10px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: white; border-radius: 5px; cursor: pointer; }
        .tab.active { background: #1877f2; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelector('[onclick="showTab(\'' + tabName + '\')"]').classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }
    </script>
</head>
<body>
<?php
require_once 'config.php';
require_once 'header.php';
?>
    
    <div class="container">
        <div class="header">
            <h1>🛡️ Admin Panel</h1>
            <p>Welcome, OSRG</p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?= $user_count ?></div>
                <div>Total Users</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $post_count ?></div>
                <div>Total Posts</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $message_count ?></div>
                <div>Total Messages</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $reaction_count ?></div>
                <div>Total Reactions</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= $comment_count ?></div>
                <div>Total Comments</div>
            </div>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="showTab('pending')">Pending Approvals <?= count($pending_users) > 0 ? '(' . count($pending_users) . ')' : '' ?></div>
            <div class="tab" onclick="showTab('users')">Manage Users</div>
            <div class="tab" onclick="showTab('posts')">Manage Posts</div>
            <div class="tab" onclick="showTab('activity')">Activity Monitor</div>
            <div class="tab" onclick="showTab('analytics')">User Analytics</div>
            <div class="tab" onclick="showTab('system')">System Info</div>
            <div class="tab" onclick="showTab('tools')">Admin Tools</div>
        </div>

        <div id="pending" class="tab-content active">
            <div class="post">
                <h3>Pending User Approvals</h3>
                <?php if ($pending_users): ?>
                    <?php foreach ($pending_users as $user): ?>
                    <div class="user-item">
                        <div>
                            <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                            <small><?= htmlspecialchars($user['email']) ?> • Registered <?= date('M j, Y', strtotime($user['created_at'])) ?></small>
                        </div>
                        <div>
                            <a href="?approve=<?= $user['id'] ?>" style="background: #4caf50; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;">Approve</a>
                            <a href="?reject=<?= $user['id'] ?>" class="delete-btn" onclick="return confirm('Reject this registration?')">Reject</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No pending approvals</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="users" class="tab-content">
            <div class="post">
                <h3>All Users</h3>
                <?php foreach ($users as $user): ?>
                <div class="user-item">
                    <div>
                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                        <?php if (!$user['approved']): ?>
                            <span style="color: #ff9800; font-size: 12px;">(Pending)</span>
                        <?php endif; ?><br>
                        <small><?= htmlspecialchars($user['email']) ?> • Joined <?= date('M j, Y', strtotime($user['created_at'])) ?></small>
                    </div>
                    <?php if ($user['id'] != $_SESSION['user_id'] && !($user['username'] === 'OSRG' || $user['username'] === 'backup')): ?>
                        <a href="?delete=<?= $user['id'] ?>" class="delete-btn" onclick="return confirm('Delete this user?')">Delete</a>
                    <?php elseif ($user['username'] === 'OSRG' || $user['username'] === 'backup'): ?>
                        <span style="color: #4caf50;">Admin</span>
                    <?php else: ?>
                        <span style="color: #4caf50;">You</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="posts" class="tab-content">
            <div class="post">
                <h3>All Posts</h3>
                <?php foreach ($posts as $post): ?>
                <div class="post-item">
                    <div>
                        <strong><?= htmlspecialchars($post['username']) ?></strong><br>
                        <span><?= htmlspecialchars(substr($post['content'], 0, 100)) ?>...</span><br>
                        <small><?= $post['created_at'] ?></small>
                    </div>
                    <a href="?delete_post=<?= $post['id'] ?>" class="delete-btn" onclick="return confirm('Delete this post?')">Delete</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="activity" class="tab-content">
            <div class="post">
                <h3>📊 Recent Activity (Last 7 Days)</h3>
                <?php if ($recent_activity): ?>
                    <?php foreach ($recent_activity as $activity): ?>
                    <div style="padding: 8px; border-bottom: 1px solid #eee; font-size: 14px;">
                        <strong><?= htmlspecialchars($activity['username']) ?></strong> 
                        <?= $activity['action'] === 'registered' ? '🆕 registered' : '📝 posted' ?>
                        <small style="color: #666; float: right;"><?= date('M j, H:i', strtotime($activity['timestamp'])) ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 20px;">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="system" class="tab-content">
            <div class="post">
                <h3>💻 System Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <strong>Database Size</strong><br>
                        <span style="color: #1877f2; font-size: 18px;"><?= round($db_size / 1024 / 1024, 2) ?> MB</span>
                    </div>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <strong>Uploads Size</strong><br>
                        <span style="color: #1877f2; font-size: 18px;"><?= round($uploads_size / 1024 / 1024, 2) ?> MB</span>
                    </div>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <strong>PHP Version</strong><br>
                        <span style="color: #1877f2; font-size: 18px;"><?= phpversion() ?></span>
                    </div>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <strong>Server Time</strong><br>
                        <span style="color: #1877f2; font-size: 18px;"><?= date('H:i:s') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div id="analytics" class="tab-content">
            <div class="post">
                <h3>📈 User Engagement Analytics</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <h4>📊 Top Active Users (This Month)</h4>
                        <?php foreach ($top_users as $user): ?>
                        <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #ddd;">
                            <span><?= htmlspecialchars($user['username']) ?></span>
                            <span style="color: #1877f2; font-weight: bold;"><?= $user['posts_this_month'] ?> posts</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <h4>📅 Daily Posts (Last 7 Days)</h4>
                        <?php foreach (array_slice($daily_posts, 0, 7) as $day): ?>
                        <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #ddd;">
                            <span><?= date('M j', strtotime($day['date'])) ?></span>
                            <span style="color: #28a745; font-weight: bold;"><?= $day['posts'] ?> posts</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <h4>👥 Detailed User Analytics</h4>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <thead>
                            <tr style="background: #1877f2; color: white;">
                                <th style="padding: 10px; text-align: left;">User</th>
                                <th style="padding: 10px; text-align: center;">Posts</th>
                                <th style="padding: 10px; text-align: center;">Friends</th>
                                <th style="padding: 10px; text-align: center;">Messages</th>
                                <th style="padding: 10px; text-align: center;">Joined</th>
                                <th style="padding: 10px; text-align: center;">Last Seen</th>
                                <th style="padding: 10px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_analytics as $user): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px; font-weight: bold;"><?= htmlspecialchars($user['username']) ?></td>
                                <td style="padding: 10px; text-align: center; color: #1877f2;"><?= $user['post_count'] ?></td>
                                <td style="padding: 10px; text-align: center; color: #28a745;"><?= $user['friend_count'] ?></td>
                                <td style="padding: 10px; text-align: center; color: #ff9800;"><?= $user['message_count'] ?></td>
                                <td style="padding: 10px; text-align: center; font-size: 12px;"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td style="padding: 10px; text-align: center; font-size: 12px;">N/A</td>
                                <td style="padding: 10px; text-align: center;"><span style="color: #6c757d;">⚫ Unknown</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tools" class="tab-content">
            <div class="post">
                <h3>🔧 Admin Tools</h3>
                <div class="post-item">
                    <div>
                        <strong>Fix Corrupted Posts</strong><br>
                        <span>Clean up HTML corruption from WYSIWYG editor in post content</span>
                    </div>
                    <a href="fix_posts.php" style="background: #ffc107; color: #212529; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-weight: bold;">Run Tool</a>
                </div>
                <div class="post-item">
                    <div>
                        <strong>Database Backup</strong><br>
                        <span>Create a backup copy of the database</span>
                    </div>
                    <a href="?backup=1" style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-weight: bold;" onclick="return confirm('Create database backup?')">Backup Now</a>
                </div>
                <div class="post-item">
                    <div>
                        <strong>Cleanup Old Data</strong><br>
                        <span>Remove posts older than 1 year and orphaned data</span>
                    </div>
                    <a href="?cleanup=1" style="background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-weight: bold;" onclick="return confirm('This will permanently delete old data. Continue?')">Cleanup</a>
                </div>
                <div class="post-item">
                    <div>
                        <strong>Download Website Backup</strong><br>
                        <span>Download complete ZIP backup of all files, database, and uploads</span>
                    </div>
                    <a href="?website_backup=1" style="background: #6f42c1; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-weight: bold;" onclick="return confirm('Create and download complete website backup?')">Download ZIP</a>
                </div>
                <div class="post-item">
                    <div>
                        <strong>Fix User IDs</strong><br>
                        <span>Make user IDs sequential (1, 2, 3...) and update all related data</span>
                    </div>
                    <a href="fix_ids.php" style="background: #ff5722; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-weight: bold;" onclick="return confirm('This will renumber all user IDs. Make sure no one is using the system. Continue?')">Fix IDs</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
