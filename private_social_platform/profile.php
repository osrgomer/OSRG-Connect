<?php
require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();
$user_id = $_GET['id'] ?? $_SESSION['user_id'];

// Get basic user data with bio
try {
    $stmt = $pdo->prepare("SELECT username, email, created_at, avatar, bio FROM users WHERE id = ? AND approved = 1");
    $stmt->execute([$user_id]);
    $profile_user = $stmt->fetch();
} catch (Exception $e) {
    // Fallback without bio column
    $stmt = $pdo->prepare("SELECT username, email, created_at, avatar FROM users WHERE id = ? AND approved = 1");
    $stmt->execute([$user_id]);
    $profile_user = $stmt->fetch();
    if ($profile_user) $profile_user['bio'] = null;
}

if (!$profile_user) {
    header('Location: index.php');
    exit;
}

// Get user's posts
$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$user_posts = $stmt->fetchAll();

// Get post count
$post_count = count($user_posts);

$is_own_profile = ($user_id == $_SESSION['user_id']);

// Get current user data for header navigation
$stmt = $pdo->prepare("SELECT username, avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_nav = $stmt->fetch();

// Set variables for header.php
$page_title = htmlspecialchars($profile_user['username']) . ' - Profile';
$additional_css = '
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .profile-header { 
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.95)); 
            backdrop-filter: blur(20px); 
            padding: 40px; 
            border-radius: 20px; 
            margin-bottom: 30px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15); 
            text-align: center; 
            border: 1px solid rgba(255,255,255,0.3);
            position: relative;
            overflow: hidden;
        }
        .profile-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1877f2, #42a5f5, #66bb6a, #ff9800, #e91e63);
        }
        .avatar-large { 
            width: 140px; 
            height: 140px; 
            border-radius: 50%; 
            margin: 0 auto 25px; 
            object-fit: cover; 
            border: 5px solid #fff;
            box-shadow: 0 10px 30px rgba(24,119,242,0.3);
            transition: transform 0.3s ease;
        }
        .avatar-large:hover { transform: scale(1.05); }
        .username { 
            font-size: 2.5em; 
            background: linear-gradient(135deg, #1877f2, #42a5f5); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text;
            margin-bottom: 15px; 
            font-weight: 700;
        }
        .bio-text {
            color: #555;
            font-size: 16px;
            line-height: 1.6;
            margin: 20px 0;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            background: rgba(255,255,255,0.5);
            padding: 15px 20px;
            border-radius: 12px;
            border-left: 4px solid #1877f2;
        }
        .stats { 
            display: flex; 
            justify-content: center; 
            gap: 40px; 
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .stat { 
            text-align: center;
            background: rgba(255,255,255,0.7);
            padding: 20px 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            min-width: 120px;
        }
        .stat:hover { transform: translateY(-5px); }
        .stat-number { 
            font-size: 28px; 
            font-weight: 800; 
            background: linear-gradient(135deg, #1877f2, #42a5f5); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text;
            margin-bottom: 5px;
        }
        .stat-label { 
            color: #666; 
            font-size: 14px; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .edit-profile-btn {
            background: linear-gradient(135deg, #1877f2, #42a5f5);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            display: inline-block;
            margin-top: 25px;
            box-shadow: 0 8px 25px rgba(24,119,242,0.3);
            transition: all 0.3s ease;
        }
        .edit-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(24,119,242,0.4);
            color: white;
        }
        .posts-section { 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(20px); 
            padding: 30px; 
            margin: 20px 0; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .posts-title {
            color: #1877f2;
            margin-bottom: 25px;
            font-size: 1.5em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .post-item {
            border-bottom: 1px solid rgba(0,0,0,0.08);
            padding: 25px 0;
            transition: all 0.3s ease;
        }
        .post-item:hover {
            background: rgba(24,119,242,0.02);
            border-radius: 12px;
            padding: 25px 15px;
        }
        .post-item:last-child { border-bottom: none; }
        .post-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 15px;
        }
        .post-author {
            font-weight: 600;
            color: #1877f2;
            font-size: 16px;
        }
        .post-date {
            color: #888;
            font-size: 13px;
            background: rgba(136,136,136,0.1);
            padding: 4px 12px;
            border-radius: 12px;
        }
        .post-content { 
            margin: 15px 0; 
            line-height: 1.7;
            color: #333;
            font-size: 15px;
        }
        .post-contentt {
            overflow: scroll;
        }
        .no-posts {
            text-align: center;
            color: #888;
            padding: 60px 20px;
            font-size: 16px;
            background: rgba(136,136,136,0.05);
            border-radius: 15px;
            border: 2px dashed rgba(136,136,136,0.2);
        }
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .profile-header { padding: 25px 20px; }
            .username { font-size: 2em; }
            .stats { gap: 20px; }
            .stat { min-width: 100px; padding: 15px 20px; }
            .avatar-large { width: 120px; height: 120px; }
        }
';

require_once 'header.php';
?>

<div class="container">
    <div class="profile-header">
        <?php 
        $user_avatar = $profile_user['avatar'] ?? null;
        $random_avatars = ['👤', '👨', '👩', '🧑', '👶', '🐱', '🐶', '🦊'];
        $default_avatar = $random_avatars[($user_id ?? 0) % count($random_avatars)];
        ?>
        <?php if ($user_avatar && strpos($user_avatar, 'avatars/') === 0): ?>
            <img src="/<?= htmlspecialchars($user_avatar) ?>" alt="Avatar" class="avatar-large">
        <?php elseif ($user_avatar): ?>
            <div style="font-size: 120px; margin-bottom: 20px;"><?= htmlspecialchars($user_avatar) ?></div>
        <?php else: ?>
            <div style="font-size: 120px; margin-bottom: 20px;"><?= $default_avatar ?></div>
        <?php endif; ?>
        
        <h1 class="username"><?= htmlspecialchars($profile_user['username']) ?></h1>
        
        <?php if (!empty($profile_user['bio'])): ?>
            <div class="bio-text">
                <?= nl2br(htmlspecialchars($profile_user['bio'])) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-number"><?= $post_count ?></div>
                <div class="stat-label">Posts</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?= date('M Y', strtotime($profile_user['created_at'])) ?></div>
                <div class="stat-label">Joined</div>
            </div>
        </div>
        
        <?php if ($is_own_profile): ?>
            <div>
                <a href="settings.php" class="edit-profile-btn">✏️ Edit Profile</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="posts-section">
        <h3 class="posts-title">📝 Posts by <?= htmlspecialchars($profile_user['username']) ?></h3>
        
        <?php if ($user_posts): ?>
            <?php foreach ($user_posts as $index => $post): ?>
                <div class="post-item">
                    <div class="post-header">
                        <div class="post-author"><?= htmlspecialchars($profile_user['username']) ?></div>
                        <div class="post-date">
                            <?= date('M j, Y H:i', strtotime($post['created_at'])) ?>
                        </div>
                    </div>
                    <div class="<?= $index === 0 ? 'post-contentt' : 'post-content' ?>">
                        <?= nl2br(htmlspecialchars($post['content'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-posts">
                📝 No posts yet
                <?php if ($is_own_profile): ?>
                    <br><small>Start sharing your thoughts with the world!</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>