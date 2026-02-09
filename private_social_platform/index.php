<?php

/**
 * Plugin Name:       OSRG Connect themes
 * Description:       themes for OSRG Connect.
 * Version:           3.4
 * Author:            OSRG
 *
 * License: All Rights Reserved
 * Text Domain: osrg-connect
 * 
 * This file is part of OSRG Connect.
 * (c) OSRG, 2024. All rights reserved.
 *
 * For support or contributions, visit: https://osrg.lol/help
 *
 */

require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get posts with reactions and comments
$pdo = get_db();
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.content, p.created_at, u.username, u.avatar, p.file_path, p.file_type, p.post_type,
               COUNT(DISTINCT CASE WHEN r.reaction_type = 'like' THEN r.id END) as like_count,
               COUNT(DISTINCT CASE WHEN r.reaction_type = 'love' THEN r.id END) as love_count,
               COUNT(DISTINCT CASE WHEN r.reaction_type = 'laugh' THEN r.id END) as laugh_count,
               COUNT(DISTINCT c.id) as comment_count,
               ur.reaction_type as user_reaction
        FROM posts p 
        JOIN users u ON p.user_id = u.id
        LEFT JOIN reactions r ON p.id = r.post_id
        LEFT JOIN comments c ON p.id = c.post_id
        LEFT JOIN reactions ur ON p.id = ur.post_id AND ur.user_id = ?
        WHERE u.approved = 1 AND (p.post_type IS NULL OR p.post_type != 'reel')
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $posts = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback for old database
    $stmt = $pdo->query("SELECT p.id, p.content, p.created_at, u.username, u.avatar, NULL as file_path, NULL as file_type, 'post' as post_type,
                         0 as like_count, 0 as love_count, 0 as laugh_count, 0 as comment_count, NULL as user_reaction
                         FROM posts p JOIN users u ON p.user_id = u.id 
                         ORDER BY p.created_at DESC");
    $posts = $stmt->fetchAll();
}

// Get friend requests
$stmt = $pdo->prepare("SELECT f.id, u.username FROM friends f JOIN users u ON f.user_id = u.id WHERE f.friend_id = ? AND f.status = 'pending'");
$stmt->execute([$_SESSION['user_id']]);
$friend_requests = $stmt->fetchAll();

// Handle friend request actions
if ($_GET['accept'] ?? false) {
    $stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE id = ?");
    $stmt->execute([$_GET['accept']]);
    header('Location: home');
    exit;
}
if ($_GET['decline'] ?? false) {
    $stmt = $pdo->prepare("DELETE FROM friends WHERE id = ?");
    $stmt->execute([$_GET['decline']]);
    header('Location: home');
    exit;
}

// Handle reactions
if ($_POST['reaction'] ?? false) {
    $post_id = $_POST['post_id'];
    $reaction = $_POST['reaction'];
    
    // Remove existing reaction or add new one
    $stmt = $pdo->prepare("DELETE FROM reactions WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    
    if ($reaction !== 'remove') {
        $stmt = $pdo->prepare("INSERT INTO reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id'], $reaction]);
    }
    
    header('Location: home');
    exit;
}

// Handle comments
if ($_POST['comment'] ?? false) {
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['post_id'], $_SESSION['user_id'], $_POST['comment']]);
    header('Location: home');
    exit;
}

// Handle delete post
if ($_GET['delete_post'] ?? false) {
    $post_id = $_GET['delete_post'];
    // Verify user owns the post
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post_owner = $stmt->fetch();
    
    if ($post_owner && $post_owner['user_id'] == $_SESSION['user_id']) {
        // Delete related data first
        $stmt = $pdo->prepare("DELETE FROM reactions WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        // Delete the post
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
    }
    header('Location: home');
    exit;
}

// Handle new post
if (isset($_POST['content'])) {
    // Check if user has remember me token and verify reCAPTCHA
    $needs_captcha = isset($_COOKIE['remember_token']);
    if ($needs_captcha && (!isset($_POST['g-recaptcha-response']) || !verify_recaptcha($_POST['g-recaptcha-response']))) {
        // Redirect with error for remember me users
        $_SESSION['post_error'] = 'Security verification failed. Please try again.';
        header('Location: home');
        exit;
    }
    $file_path = null;
    $file_type = null;
    
    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $allowed = ['mp4', 'mp3', 'png', 'jpg', 'jpeg'];
        $filename = $_FILES['file']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $file_size = $_FILES['file']['size'];
        $max_size = 10 * 1024 * 1024; // 10MB limit
        
        if ($file_size <= $max_size && in_array($file_ext, $allowed)) {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
                $file_path = $upload_path;
                $file_type = $file_ext;
            }
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, file_path, file_type, post_type) VALUES (?, ?, ?, ?, 'post')");
        $stmt->execute([$_SESSION['user_id'], $_POST['content'], $file_path, $file_type]);
    } catch (Exception $e) {
        // Fallback for old database
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, file_path, file_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $_POST['content'], $file_path, $file_type]);
    }
    
    header('Location: home');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <title>Feed - OSRG Connect</title>
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1Y8S6WHNH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-Y1Y8S6WHNH');
    </script>
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="OSRG Connect - Public Feed">
    <meta property="og:description" content="Connect with friends and share your thoughts on OSRG Connect - A private social media platform.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://osrg.lol/osrg/private_social_platform/index.php">
    <meta property="og:site_name" content="OSRG Connect">
    <meta property="og:locale" content="en_US">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="OSRG Connect - Public Feed">
    <meta name="twitter:description" content="Connect with friends and share your thoughts on OSRG Connect - A private social media platform.">
    <link rel="stylesheet" href="index.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
    <!-- Additional Meta Tags -->
    <meta name="description" content="OSRG Connect - Share posts, connect with friends, and engage with your community on our private social platform.">
    <meta name="keywords" content="social media, private platform, friends, posts, community, OSRG">
    <meta name="author" content="OSRG">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <!-- reCAPTCHA v3 for remember me users -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
    <script>
        let lastPostCount = 0;
        let quill;
        
        // Initialize WYSIWYG editor
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile detection for file input
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
            if (isMobile) {
                const fileInput = document.getElementById('fileInput');
                fileInput.removeAttribute('capture');
                // Default to gallery on mobile
                fileInput.setAttribute('accept', 'image/*,video/*,audio/*');
            }
            
            quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: "What's on your mind?",
                modules: {
                    toolbar: [
                        ['bold', 'italic'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }]
                    ]
                }
            });
            
            // Update hidden textarea when form is submitted
            document.getElementById('postForm').addEventListener('submit', function(e) {
                document.getElementById('content').value = quill.root.innerHTML;
                const content = quill.getText().trim();
                if (content.length === 0) {
                    e.preventDefault();
                    alert('Please write something before posting!');
                    return false;
                }
                
                // Check if user has remember me token (needs reCAPTCHA)
                const hasRememberToken = document.cookie.includes('remember_token');
                if (hasRememberToken) {
                    e.preventDefault();
                    const submitBtn = document.querySelector('#postForm button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Verifying...';
                    
                    grecaptcha.ready(function() {
                        grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'post'}).then(function(token) {
                            // Add hidden input for reCAPTCHA token
                            let tokenInput = document.getElementById('post-recaptcha-token');
                            if (!tokenInput) {
                                tokenInput = document.createElement('input');
                                tokenInput.type = 'hidden';
                                tokenInput.name = 'g-recaptcha-response';
                                tokenInput.id = 'post-recaptcha-token';
                                document.getElementById('postForm').appendChild(tokenInput);
                            }
                            tokenInput.value = token;
                            document.getElementById('postForm').submit();
                        });
                    });
                }
                return true;
            });
        });
        
        let notificationsEnabled = false;
        let notificationInterval = null;
        
        function toggleNotifications() {
            if (notificationsEnabled) {
                // Disable notifications
                notificationsEnabled = false;
                localStorage.setItem('notificationsEnabled', 'false');
                if (notificationInterval) {
                    clearInterval(notificationInterval);
                    notificationInterval = null;
                }
                document.getElementById('notif-btn').textContent = 'Enable Notifications';
                document.getElementById('notif-btn').style.background = '#666';
            } else {
                // Enable notifications
                if ('Notification' in window) {
                    if (Notification.permission === 'granted') {
                        // Already granted - enable native notifications
                        notificationsEnabled = true;
                        localStorage.setItem('notificationsEnabled', 'true');
                        document.getElementById('notif-btn').textContent = 'Disable Notifications';
                        document.getElementById('notif-btn').style.background = '#4caf50';
                        checkForNewPosts();
                    } else if (Notification.permission === 'default') {
                        // Request permission
                        Notification.requestPermission().then(function(permission) {
                            if (permission === 'granted') {
                                notificationsEnabled = true;
                                localStorage.setItem('notificationsEnabled', 'true');
                                document.getElementById('notif-btn').textContent = 'Disable Notifications';
                                document.getElementById('notif-btn').style.background = '#4caf50';
                                checkForNewPosts();
                            } else {
                                // User denied - fallback to visual alerts
                                notificationsEnabled = true;
                                localStorage.setItem('notificationsEnabled', 'true');
                                document.getElementById('notif-btn').textContent = 'Disable Notifications';
                                document.getElementById('notif-btn').style.background = '#ff9800';
                                checkForNewPosts();
                            }
                        });
                    } else {
                        // Permission denied - use visual alerts
                        notificationsEnabled = true;
                        localStorage.setItem('notificationsEnabled', 'true');
                        document.getElementById('notif-btn').textContent = 'Disable Notifications';
                        document.getElementById('notif-btn').style.background = '#ff9800';
                        checkForNewPosts();
                    }
                } else {
                    // Browser doesn't support notifications
                    notificationsEnabled = true;
                    localStorage.setItem('notificationsEnabled', 'true');
                    document.getElementById('notif-btn').textContent = 'Disable Notifications';
                    document.getElementById('notif-btn').style.background = '#ff9800';
                    checkForNewPosts();
                }
            }
        }
        
        function showVisualAlert(message) {
            // Create visual notification for iOS/unsupported browsers
            const alert = document.createElement('div');
            alert.style.cssText = 'position:fixed;top:20px;right:20px;background:#1877f2;color:white;padding:15px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.3);z-index:9999;max-width:300px;animation:slideIn 0.3s ease;';
            alert.innerHTML = '<strong>🔔 New Activity!</strong><br>' + message;
            document.body.appendChild(alert);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                alert.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 4000);
        }
        
        function checkForNewPosts() {
            if (notificationInterval) {
                clearInterval(notificationInterval);
            }
            
            notificationInterval = setInterval(function() {
                if (!notificationsEnabled) return;
                
                fetch('check_posts.php')
                    .then(response => response.json())
                    .then(data => {
                        if (lastPostCount > 0 && data.count > lastPostCount) {
                            // Try native notification first
                            if ('Notification' in window && Notification.permission === 'granted') {
                                new Notification('New Post!', {
                                    body: data.latest_post,
                                    icon: 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%231877f2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'
                                });
                            } else {
                                // Fallback visual alert for iOS/unsupported
                                showVisualAlert(data.latest_post);
                            }
                            
                            // Update page title for additional notification
                            document.title = '🔔 New Post - OSRG Connect';
                            setTimeout(() => document.title = 'Feed - OSRG Connect', 3000);
                        }
                        lastPostCount = data.count;
                    });
            }, 5000);
        }
        
        function editPost(postId) {
            const contentDiv = document.getElementById('content-' + postId);
            const currentContent = contentDiv.textContent.trim();
            
            const textarea = document.createElement('textarea');
            textarea.value = currentContent;
            textarea.style.width = '100%';
            textarea.style.minHeight = '100px';
            textarea.style.padding = '10px';
            textarea.style.border = '2px solid #1877f2';
            textarea.style.borderRadius = '8px';
            textarea.style.fontSize = '16px';
            
            const saveBtn = document.createElement('button');
            saveBtn.textContent = 'Save';
            saveBtn.style.background = '#4caf50';
            saveBtn.style.color = 'white';
            saveBtn.style.padding = '8px 15px';
            saveBtn.style.border = 'none';
            saveBtn.style.borderRadius = '5px';
            saveBtn.style.marginRight = '10px';
            saveBtn.style.cursor = 'pointer';
            
            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Cancel';
            cancelBtn.style.background = '#f44336';
            cancelBtn.style.color = 'white';
            cancelBtn.style.padding = '8px 15px';
            cancelBtn.style.border = 'none';
            cancelBtn.style.borderRadius = '5px';
            cancelBtn.style.cursor = 'pointer';
            
            const buttonDiv = document.createElement('div');
            buttonDiv.style.marginTop = '10px';
            buttonDiv.appendChild(saveBtn);
            buttonDiv.appendChild(cancelBtn);
            
            contentDiv.innerHTML = '';
            contentDiv.appendChild(textarea);
            contentDiv.appendChild(buttonDiv);
            
            saveBtn.onclick = function() {
                const newContent = textarea.value.trim();
                if (newContent) {
                    fetch('edit_post.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'post_id=' + postId + '&content=' + encodeURIComponent(newContent)
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result === 'success') {
                            location.reload();
                        } else {
                            alert('Error updating post');
                        }
                    });
                }
            };
            
            cancelBtn.onclick = function() {
                contentDiv.innerHTML = currentContent;
            };
        }
        
        window.onload = function() {
            // Restore scroll position after comment submission
            const scrollPos = sessionStorage.getItem('scrollPos');
            if (scrollPos) {
                // Mobile detection
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
                
                if (isMobile) {
                    // Use setTimeout for mobile compatibility
                    setTimeout(() => {
                        window.scrollTo(0, parseInt(scrollPos));
                        sessionStorage.removeItem('scrollPos');
                    }, 100);
                } else {
                    // Immediate scroll for desktop
                    window.scrollTo(0, parseInt(scrollPos));
                    sessionStorage.removeItem('scrollPos');
                }
            }
            
            // Check if notifications were previously enabled
            const savedPreference = localStorage.getItem('notificationsEnabled');
            
            if (savedPreference === 'true') {
                // Restore enabled state
                if ('Notification' in window && Notification.permission === 'granted') {
                    notificationsEnabled = true;
                    document.getElementById('notif-btn').textContent = 'Disable Notifications';
                    document.getElementById('notif-btn').style.background = '#4caf50';
                    checkForNewPosts();
                } else if ('Notification' in window && Notification.permission === 'denied') {
                    notificationsEnabled = true;
                    document.getElementById('notif-btn').textContent = 'Disable Notifications';
                    document.getElementById('notif-btn').style.background = '#ff9800';
                    checkForNewPosts();
                } else {
                    // Permission not granted yet, show enable button
                    document.getElementById('notif-btn').textContent = 'Enable Notifications';
                    document.getElementById('notif-btn').style.background = '#666';
                }
            } else {
                // Default disabled state
                document.getElementById('notif-btn').textContent = 'Enable Notifications';
                document.getElementById('notif-btn').style.background = '#666';
            }
        }
    </script>
</head>
<body>
<?php require_once 'header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1>Public Feed</h1>
            <button id="notif-btn" class="notification-btn" onclick="toggleNotifications()">Enable Notifications</button>
        </div>

        <?php
        // Check for pending user approvals (admin only)
        $pending_approvals = [];
        if ($user_nav && ($user_nav['username'] === 'OSRG' || $user_nav['username'] === 'backup')) {
            $stmt_pending = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE approved = 0");
            $stmt_pending->execute();
            $pending_count = $stmt_pending->fetch()['count'];
            
            if ($pending_count > 0) {
                $stmt_pending = $pdo->prepare("SELECT username, email, created_at FROM users WHERE approved = 0 ORDER BY created_at DESC LIMIT 3");
                $stmt_pending->execute();
                $pending_approvals = $stmt_pending->fetchAll();
            }
        }
        ?>
        
        <?php if ($pending_approvals): ?>
        <div class="post" style="background: #fff3e0; border-left: 4px solid #ff9800;">
            <h3>🔔 Pending User Approvals (<?= $pending_count ?>)</h3>
            <?php foreach ($pending_approvals as $pending): ?>
            <div style="padding: 8px; border-bottom: 1px solid #ddd; font-size: 14px;">
                <strong><?= htmlspecialchars($pending['username']) ?></strong> (<?= htmlspecialchars($pending['email']) ?>)
                <small style="color: #666; float: right;"><?= date('M j, H:i', strtotime($pending['created_at'])) ?></small>
            </div>
            <?php endforeach; ?>
            <div style="text-align: center; margin-top: 10px;">
                <a href="admin" style="background: #ff9800; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; font-weight: bold;">Review in Admin Panel</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($friend_requests): ?>
        <div class="post" style="background: #e3f2fd; border-left: 4px solid #1877f2;">
            <h3>Friend Requests</h3>
            <?php foreach ($friend_requests as $request): ?>
            <div class="friend-request-item" style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #ddd;">
                <span><strong><?= htmlspecialchars($request['username']) ?></strong> wants to be your friend</span>
                <div class="friend-request-buttons">
                    <a href="?accept=<?= $request['id'] ?>" style="background: #42a5f5; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;">Accept</a>
                    <a href="?decline=<?= $request['id'] ?>" style="background: #f44336; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">Decline</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['post_error'])): ?>
        <div class="post" style="background: #ffebee; border-left: 4px solid #f44336;">
            <div style="color: #c62828; padding: 10px;">
                ❌ <?= htmlspecialchars($_SESSION['post_error']) ?>
            </div>
        </div>
        <?php unset($_SESSION['post_error']); endif; ?>
        
        <div class="post">
            <h3>Share something...</h3>
            <form method="POST" enctype="multipart/form-data" id="postForm">
                <div class="form-group">
                    <div id="editor" style="border: 1px solid #ddd; border-radius: 5px; min-height: 100px; padding: 10px; background: white;"></div>
                    <textarea name="content" id="content" style="display: none;"></textarea>
                </div>
                <div class="form-group">
                    <input type="file" name="file" id="fileInput" accept=".mp4,.mp3,.png,.jpg,.jpeg" style="margin-bottom: 10px;">
                    <small style="color: #666;">Upload: MP4, MP3, PNG, JPG (max 10MB)</small>
                </div>
                <button type="submit">Post</button>
            </form>
        </div>

        <?php if ($posts): ?>
            <?php foreach ($posts as $post): ?>
            <div class="post">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <?php if ($post['avatar']): ?>
                            <?php if (strpos($post['avatar'], 'avatars/') === 0): ?>
                                <img src="/<?= htmlspecialchars($post['avatar']) ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <span style="font-size: 30px;"><?= htmlspecialchars($post['avatar']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="font-size: 30px;">👤</span>
                        <?php endif; ?>
                        <div>
                            <strong><?= htmlspecialchars($post['username']) ?></strong>
                            <?php if (($post['post_type'] ?? 'post') === 'reel'): ?>
                                <span style="background: linear-gradient(45deg, #ff6b6b, #4ecdc4); color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-left: 8px;">🎬 REEL</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    // Check if current user owns this post
                    $stmt_owner = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
                    $stmt_owner->execute([$post['id']]);
                    $post_owner = $stmt_owner->fetch();
                    if ($post_owner && $post_owner['user_id'] == $_SESSION['user_id']):
                    ?>
                    <div style="display: flex; gap: 5px;">
                        <button onclick="editPost(<?= $post['id'] ?>)" style="background: #1877f2; color: white; padding: 6px 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px;">Edit</button>
                        <a href="?delete_post=<?= $post['id'] ?>" onclick="return confirm('Are you sure you want to delete this post?')" style="background: #f44336; color: white; padding: 6px 12px; text-decoration: none; border-radius: 5px; font-size: 12px;">Delete</a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
                $processed = process_content_with_links($post['content']);
                ?>
                <div id="content-<?= $post['id'] ?>"><?= $processed['content'] ?></div>
                
                <?php if (!empty($processed['previews'])): ?>
                    <?php foreach ($processed['previews'] as $preview): ?>
                    <div style="border: 1px solid #e1e5e9; border-radius: 8px; margin: 10px 0; overflow: hidden; max-width: 400px;">
                        <?php if ($preview['image']): ?>
                        <img src="<?= htmlspecialchars($preview['image']) ?>" alt="Preview" style="width: 100%; height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        <div style="padding: 12px;">
                            <div style="font-weight: bold; margin-bottom: 5px; color: #1d2129;"><?= htmlspecialchars($preview['title']) ?></div>
                            <?php if ($preview['description']): ?>
                            <div style="color: #606770; font-size: 14px; margin-bottom: 8px;"><?= htmlspecialchars(substr($preview['description'], 0, 100)) ?>...</div>
                            <?php endif; ?>
                            <div style="color: #8a8d91; font-size: 12px; text-transform: uppercase;"><?= parse_url($preview['url'], PHP_URL_HOST) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($post['file_path']): ?>
                <div style="margin: 10px 0;">
                    <?php if ($post['file_type'] == 'mp4'): ?>
                        <video controls style="width: 100%; max-width: 100%; display: block;">
                            <source src="<?= $post['file_path'] ?>" type="video/mp4">
                        </video>
                    <?php elseif ($post['file_type'] == 'mp3'): ?>
                        <audio controls preload="metadata" style="width: 100%; display: block;">
                            <source src="<?= $post['file_path'] ?>" type="audio/mpeg">
                            <source src="<?= $post['file_path'] ?>" type="audio/mp3">
                            Your browser does not support the audio element.
                        </audio>
                    <?php elseif (in_array($post['file_type'], ['png', 'jpg', 'jpeg'])): ?>
                        <img src="<?= htmlspecialchars($post['file_path']) ?>" alt="Uploaded image" style="width: 100%; max-width: 100%; display: block; border-radius: 8px;">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Reactions -->
                <div style="margin: 15px 0; padding: 10px 0; border-top: 1px solid #eee;">
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <button type="submit" name="reaction" value="<?= $post['user_reaction'] === 'like' ? 'remove' : 'like' ?>" 
                                    style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 10px; touch-action: manipulation; <?= $post['user_reaction'] === 'like' ? 'color: #1877f2;' : '' ?>">
                                👍 <?= $post['like_count'] > 0 ? $post['like_count'] : '' ?>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <button type="submit" name="reaction" value="<?= $post['user_reaction'] === 'love' ? 'remove' : 'love' ?>" 
                                    style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 10px; touch-action: manipulation; <?= $post['user_reaction'] === 'love' ? 'color: #e91e63;' : '' ?>">
                                ❤️ <?= $post['love_count'] > 0 ? $post['love_count'] : '' ?>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <button type="submit" name="reaction" value="<?= $post['user_reaction'] === 'laugh' ? 'remove' : 'laugh' ?>" 
                                    style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 10px; touch-action: manipulation; <?= $post['user_reaction'] === 'laugh' ? 'color: #ff9800;' : '' ?>">
                                😂 <?= $post['laugh_count'] > 0 ? $post['laugh_count'] : '' ?>
                            </button>
                        </form>
                        <span style="color: #666; font-size: 14px;">💬 <?= $post['comment_count'] ?></span>
                    </div>
                    
                    <!-- Comments -->
                    <?php
                    $stmt_comments = $pdo->prepare("SELECT c.content, c.created_at, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
                    $stmt_comments->execute([$post['id']]);
                    $comments = $stmt_comments->fetchAll();
                    ?>
                    
                    <?php if ($comments): ?>
                        <?php foreach ($comments as $comment): ?>
                        <div style="background: #f8f9fa; padding: 8px; margin: 5px 0; border-radius: 5px; font-size: 14px;">
                            <strong><?= htmlspecialchars($comment['username']) ?>:</strong> 
                            <?= htmlspecialchars($comment['content']) ?>
                            <small style="color: #666; margin-left: 10px;"><?= date('M j, H:i', strtotime($comment['created_at'])) ?></small>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Add Comment -->
                    <form method="POST" style="margin-top: 10px;" onsubmit="sessionStorage.setItem('scrollPos', window.pageYOffset);">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="comment" placeholder="Write a comment..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 20px; font-size: 14px;" required>
                            <button type="submit" style="padding: 8px 15px; font-size: 12px;">Post</button>
                        </div>
                    </form>
                </div>
                
                <div style="clear: both; margin-top: 10px;">
                    <small><?php
                    // Get user's timezone
                    $stmt_tz = $pdo->prepare("SELECT timezone FROM users WHERE id = ?");
                    $stmt_tz->execute([$_SESSION['user_id']]);
                    $user_tz = $stmt_tz->fetch();
                    $timezone = $user_tz['timezone'] ?? 'Europe/London';
                    
                    // Convert to user's timezone
                    $date = new DateTime($post['created_at'], new DateTimeZone('UTC'));
                    $date->setTimezone(new DateTimeZone($timezone));
                    echo $date->format('M j, H:i');
                    ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="post">
                <p><strong>Welcome!</strong></p>
                <p>Start by creating your first post above!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>