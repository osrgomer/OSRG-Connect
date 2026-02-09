<?php
// Ensure user is logged in for header display
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <?= isset($mobile_viewport) ? $mobile_viewport : '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">' ?>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="manifest" href="site.webmanifest">
    <title><?= $page_title ?? 'OSRG Connect' ?></title>
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1Y8S6WHNH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-Y1Y8S6WHNH');
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .nav { background: white; padding: 10px; margin-bottom: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .nav-links { display: flex; align-items: center; }
        .nav-links a { color: #1877f2; text-decoration: none; margin-right: 15px; }
        .user-avatar { width: 40px !important; height: 40px !important; border-radius: 50% !important; cursor: pointer !important; transition: transform 0.2s !important; display: block !important; }
        .user-avatar:hover { transform: scale(1.1) !important; }
        .nav > div:last-child { display: flex !important; align-items: center !important; z-index: 999 !important; }
        .avatar-container { display: flex !important; align-items: center !important; }
        .hamburger { display: none; flex-direction: column; cursor: pointer; }
        .hamburger span { width: 25px; height: 3px; background: #1877f2; margin: 3px 0; transition: 0.3s; }
        
        @media (max-width: 768px) {
            .hamburger { display: flex !important; }
            .nav-links { display: none; position: absolute; top: 60px; left: 0; right: 0; background: white; flex-direction: column; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 1000; }
            .nav-links.active { display: flex !important; }
            .nav-links a { margin: 10px 0; padding: 10px; border-bottom: 1px solid #f0f0f0; }
            .nav { position: relative; }
        }
        /* Mobile content overflow prevention */
        @media (max-width: 768px) {
            .post, .post * {
                word-wrap: break-word !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
                max-width: 100% !important;
            }
            .post {
                overflow: hidden !important;
            }
            .post div, .post p, .post span {
                max-width: 100% !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            .post a {
                word-break: break-all !important;
                display: inline-block !important;
                max-width: 100% !important;
            }
        }
        <?= $additional_css ?? '' ?>
    </style>
    <?= $additional_head ?? '' ?>
    <script>
        // Update user activity every 2 minutes
        setInterval(function() {
            fetch('update_activity.php');
        }, 120000);
        
        // Update on page load
        fetch('update_activity.php');
        
        // Hamburger menu toggle function
        function toggleNav() {
            var navLinks = document.getElementById('navLinks');
            if (navLinks) {
                navLinks.classList.toggle('active');
            }
        }
    </script>
</head>
<body>
    <div class="nav">
        <div class="hamburger" onclick="toggleNav()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="series_index.php" style="color: #4c1130; font-weight: bold;">📺 Series List</a>
            <a href="reels.php">🎬 Reels</a>
            <a href="games.php">🎮 Games</a>
            <a href="quiz_Quizz_Generator.html" style="font-weight: bold;">❓ Quiz</a>
            <a href="mine_index.php" style="color: #3b82f6; font-weight: bold;">💎 MineMod</a>
            <a href="users.php">Find Friends</a>
            <a href="friends.php">My Friends</a>
            <a href="messages.php">Messages</a>
            <a href="profile.php">My Profile</a>
            <a href="settings.php">Settings</a>
            <?php
            if (!isset($user_nav) || !$user_nav) {
                $pdo_nav = get_db();
                $stmt_nav = $pdo_nav->prepare("SELECT username, avatar FROM users WHERE id = ?");
                $stmt_nav->execute([$_SESSION['user_id']]);
                $user_nav = $stmt_nav->fetch();
            }
            if ($user_nav && ($user_nav['username'] === 'OSRG' || $user_nav['username'] === 'backup')):
            ?>
            <a href="admin.php" style="color: #d32f2f; font-weight: bold;">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
        
        <div class="avatar-container">
            <?php
            $avatar = ($user_nav && isset($user_nav['avatar'])) ? $user_nav['avatar'] : null;
            $random_avatars = ['👤', '👨', '👩', '🧑', '👶', '🐱', '🐶', '🦊'];
            $default_avatar = $random_avatars[($_SESSION['user_id'] ?? 0) % count($random_avatars)];
            ?>
            <a href="settings.php#profile" style="text-decoration: none;">
                <?php if ($avatar && (strpos($avatar, 'sp_avatars/') === 0 || strpos($avatar, 'avatars/') === 0 || strpos($avatar, '.') !== false)): ?>
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="user-avatar" style="object-fit: cover;">
                <?php elseif ($avatar): ?>
                    <span style="font-size: 40px; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <?= htmlspecialchars($avatar) ?>
                    </span>
                <?php else: ?>
                    <span style="font-size: 40px; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <?= $default_avatar ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </div>
    <div class="container">
