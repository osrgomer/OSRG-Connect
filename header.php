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
        .nav { background: white; padding: 10px; margin-bottom: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .nav-links { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .nav-links a { color: #1877f2; text-decoration: none; margin-right: 0; padding: 6px 8px; border-radius: 6px; }
        .nav-links a:hover { background: #f0f4ff; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: transform 0.15s; display: block; object-fit: cover; }
        .user-avatar:hover { transform: scale(1.05); }
        .nav > div:last-child { display: flex; align-items: center; z-index: 999; }
        .avatar-container { display: flex; align-items: center; gap: 10px; }
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
    <!-- Make sure FontAwesome is loaded -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Notification Styles */
        #notifBtn { background: none; border: none; cursor: pointer; position: relative; padding: 8px; margin-right: 8px; font-size: 1.2rem; color: #555; }
        #notifBtn:hover { color: #1877f2; background-color: #f0f2f5; border-radius: 50%; }
        #notifBadge { position: absolute; top: -6px; right: -6px; background: #e41e3f; color: white; font-size: 0.7rem; font-weight: bold; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white; }
        #notifDropdown { display: none; position: absolute; top: 60px; right: 20px; width: 320px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; overflow: hidden; border: 1px solid #ddd; }
        #notifDropdown.show { display: block; }
        .notif-header { padding: 12px 16px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f9fafb; }
        .notif-header h3 { margin: 0; font-size: 1rem; color: #1f2937; }
        .notif-header button { background: none; border: none; color: #1877f2; cursor: pointer; font-size: 0.85rem; }
        .notif-list { max-height: 400px; overflow-y: auto; }
        .notif-item { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: start; gap: 12px; transition: background 0.2s; text-decoration: none; color: inherit; }
        .notif-item:hover { background: #f0f2f5; }
        .notif-item.unread { background: #e7f3ff; }
        .notif-icon { width: 36px; height: 36px; border-radius: 50%; background: #e4e6eb; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
        .notif-content { flex: 1; font-size: 0.9rem; }
        .notif-time { display: block; font-size: 0.75rem; color: #65676b; margin-top: 4px; }
        .notif-action { display: inline-flex; margin-top: 6px; font-size: 0.75rem; color: #1877f2; font-weight: 600; letter-spacing: 0.01em; }
        .notif-item.unread .notif-action { color: #0b64d2; }
        .empty-notif { padding: 40px 20px; text-align: center; color: #65676b; }
        /* Compact live network status */
        .live-status { display: inline-flex; gap: 8px; align-items: center; margin-right: 12px; }
        .live-status .dot { width:10px; height:10px; border-radius:50%; background:#22c55e; display:inline-block; }
        .live-status .count { font-size:0.85rem; color:#374151; }
        .live-dropdown { position: absolute; top: 48px; right: 60px; background: white; border: 1px solid #ddd; border-radius: 8px; padding: 8px; box-shadow: 0 6px 16px rgba(0,0,0,0.12); display:none; z-index:10001; max-width:260px; }
        .live-dropdown.show { display:block; }
        .live-item { padding:6px 8px; font-size:0.9rem; color:#374151; border-bottom:1px solid #f3f4f6; }
        .live-item:last-child{ border-bottom:none; }
        @media (max-width:768px){ .live-dropdown{ right:20px; left:20px; } }
    </style>
    <script>
        // Notification Logic
        document.addEventListener('DOMContentLoaded', function() {
            const notifBtn = document.getElementById('notifBtn');
            const notifDropdown = document.getElementById('notifDropdown');
            const notifBadge = document.getElementById('notifBadge');
            const notifList = document.getElementById('notifList');
            
            // Toggle Dropdown
            notifBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notifDropdown.classList.toggle('show');
                if (notifDropdown.classList.contains('show')) {
                    markAllAsRead();
                }
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                    notifDropdown.classList.remove('show');
                }
            });

            // Load Notifications
            function loadNotifications() {
                // Determine API endpoint based on available files
                // Using SeriesList API if available, otherwise mock or local
                const apiUrl = 'series_api_activity.php?bg=1'; 
                
                fetch(apiUrl)
                    .then(response => {
                        if (!response.ok) return { activities: [] };
                        return response.json().catch(() => ({ activities: [] }));
                    })
                    .then(data => {
                        if (data.activities && data.activities.length > 0) {
                            renderNotifications(data.activities);
                            const unreadCount = data.activities.filter(a => !isRead(a.id)).length;
                            updateBadge(unreadCount);
                        } else {
                            notifList.innerHTML = '<div class="empty-notif">No updates yet</div>';
                            updateBadge(0);
                        }
                    })
                    .catch(() => {});
            }

            function renderNotifications(activities) {
                const html = activities
                    .slice()
                    .sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0))
                    .map(act => {
                        const isUnread = !isRead(act.id);
                        const icon = getIconForType(act.type);
                        const link = getNotificationLink(act);
                        const summary = act.description || act.text || 'New activity';
                        const actionLabel = getNotificationActionLabel(act);
                        
                        return `
                            <a href="${link}" class="notif-item ${isUnread ? 'unread' : ''}">
                                <div class="notif-icon">${icon}</div>
                                <div class="notif-content">
                                    <div>${summary}</div>
                                    <span class="notif-time">${formatTime(act.timestamp)}</span>
                                    <span class="notif-action">${actionLabel}</span>
                                </div>
                            </a>
                        `;
                    }).join('');
                notifList.innerHTML = html;
            }

            function getIconForType(type) {
                const icons = {
                    'post': '📝',
                    'comment': '💬',
                    'like': '❤️',
                    'follow': '👋',
                    'reel': '🎬',
                    'game': '🎮',
                    'system': '🔧',
                    'message': '✉️',
                    'friend_request': '🤝'
                };
                return icons[type] || '🔔';
            }

            function getNotificationLink(act) {
                if (act.link && act.link !== '#') {
                    return act.link;
                }
                if (act.type === 'message') {
                    return 'messages.php';
                }
                if (act.type === 'friend_request') {
                    return 'index.php#friend-requests';
                }
                return '#';
            }

            function getNotificationActionLabel(act) {
                switch (act.type) {
                    case 'message':
                        return 'Open chat';
                    case 'friend_request':
                        return 'Review requests';
                    case 'system':
                        return 'View details';
                    default:
                        return 'View';
                }
            }

            function formatTime(timestamp) {
                const userTime = new Date(timestamp * 1000); // Assuming PHP sends seconds
                const now = new Date();
                const diff = (now - userTime) / 1000;
                
                if (diff < 60) return 'Just now';
                if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
                if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
                return Math.floor(diff / 86400) + 'd ago';
            }

            function updateBadge(count) {
                if (count > 0) {
                    notifBadge.textContent = count > 99 ? '99+' : count;
                    notifBadge.style.display = 'flex';
                } else {
                    notifBadge.style.display = 'none';
                }
            }

            // Simple local storage read/unread tracking
            function isRead(id) {
                const read = JSON.parse(localStorage.getItem('read_notifs') || '[]');
                return read.includes(id);
            }

            function markAllAsRead() {
                const items = document.querySelectorAll('.notif-item');
                // In a real app, you'd send this to server. 
                // For now, we clear the badge visually
                updateBadge(0);
            }

            // Poll for notifications
            loadNotifications();
            setInterval(loadNotifications, 60000); // Poll every minute
        });
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
            <a href="global_news.html">Global News</a>
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
            if (isset($_SESSION['user_id'])) {
                if (!isset($user_nav) || !$user_nav) {
                    $pdo_nav = get_db();
                    if ($pdo_nav) {
                        $stmt_nav = $pdo_nav->prepare("SELECT username, avatar FROM users WHERE id = ?");
                        $stmt_nav->execute([$_SESSION['user_id']]);
                        $user_nav = $stmt_nav->fetch();
                    }
                }
            }
            if (isset($user_nav) && $user_nav && ($user_nav['username'] === 'OSRG' || $user_nav['username'] === 'backup' || $user_nav['username'] === 'Omer Shalom Rimon')): ?>
                <a href="admin.php" style="color: #d32f2f; font-weight: bold;">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
        
        <!-- Right Side: Notifications + Avatar -->
        <div style="display: flex; align-items: center;">
            
            <!-- Notification Bell -->
            <div style="position: relative; margin-right: 15px;">
                <button id="notifBtn">
                    <i class="fas fa-bell"></i>
                    <span id="notifBadge" style="display: none;">0</span>
                </button>
                <div id="notifDropdown">
                    <div class="notif-header">
                        <h3>Notifications</h3>
                        <button onclick="document.getElementById('notifList').innerHTML='';">Clear</button>
                    </div>
                    <div id="notifList" class="notif-list">
                        <!-- Items will be injected here -->
                    </div>
                </div>
            </div>

            <div class="avatar-container">
                <?php
                $avatar = ($user_nav && !empty($user_nav['avatar'])) ? $user_nav['avatar'] : null;
                $random_avatars = ['👤', '👨', '👩', '🧑', '👶', '🐱', '🐶', '🦊'];
                $default_avatar = $random_avatars[($_SESSION['user_id'] ?? 0) % count($random_avatars)];
                ?>
                <a href="settings.php#profile" style="text-decoration: none;">
                    <?php 
                    $is_image = $avatar && (
                        strpos($avatar, 'sp_avatars/') === 0 || 
                        strpos($avatar, 'avatars/') === 0 || 
                        strpos($avatar, 'http') === 0 ||
                        preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $avatar)
                    );
                    ?>
                    <?php if ($is_image): ?>
                        <?php 
                        $avatar_url = (strpos($avatar, 'http') === 0) ? $avatar : 'serve_asset.php?file=' . basename($avatar);
                        ?>
                        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar" class="user-avatar" style="object-fit: cover; width: 40px; height: 40px; border-radius: 50%;">
                    <?php elseif (!empty($avatar)): ?>
                        <span style="font-size: 32px; cursor: pointer; display: inline-block; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                            <?= htmlspecialchars($avatar) ?>
                        </span>
                    <?php else: ?>
                        <span style="font-size: 32px; cursor: pointer; display: inline-block; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                            <?= $default_avatar ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
    <div class="container">
