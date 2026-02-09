<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();
$message = '';

// Get current user settings with error handling
try {
    $stmt = $pdo->prepare("SELECT username, email, timezone, email_notifications, avatar, bio FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    // Fallback for missing columns
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $user['timezone'] = null;
    $user['email_notifications'] = null;
    $user['avatar'] = null;
    $user['bio'] = null;
}
$current_timezone = $user['timezone'] ?? 'Europe/London';
$email_notifications = $user['email_notifications'] ?? 0;
$current_username = $user['username'] ?? '';
$current_email = $user['email'] ?? '';
$current_avatar = $user['avatar'] ?? '';
$current_bio = $user['bio'] ?? '';

// Handle profile update
if ($_POST['update_profile'] ?? false) {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_bio = $_POST['bio'] ?? '';
    $avatar = $current_avatar; // Keep current avatar by default
    
    // Handle avatar upload (takes priority over preset)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['png', 'jpg', 'jpeg'];
        $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['avatar']['size'];
        $max_size = 2 * 1024 * 1024; // 2MB limit
        
        if ($file_size <= $max_size && in_array($file_ext, $allowed)) {
            if (!is_dir('avatars')) {
                mkdir('avatars', 0755, true);
            }
            $avatar_filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $avatar_path = 'avatars/' . $avatar_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                $avatar = $avatar_path;
            }
        }
    } elseif (!empty($_POST['preset_avatar'])) {
        // Only use preset if no file upload and preset is selected
        $avatar = $_POST['preset_avatar'];
    }
    
    // Add missing columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar TEXT");
    } catch (Exception $e) {}
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$new_username, $new_email, $new_bio, $avatar, $_SESSION['user_id']]);
        $message = 'Profile updated successfully!';
    } catch (Exception $e) {
        // Fallback for missing columns
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$new_username, $new_email, $_SESSION['user_id']]);
            $message = 'Basic profile updated successfully!';
        } catch (PDOException $e) {
            $message = 'Error: Username or email already exists.';
        }
    }
    // Refresh user data
    try {
        $stmt = $pdo->prepare("SELECT username, email, timezone, email_notifications, avatar, bio FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    $current_username = $user['username'];
    $current_email = $user['email'];
    $current_avatar = $user['avatar'] ?? '';
    $current_bio = $user['bio'] ?? '';
}

// Handle password change
if ($_POST['change_password'] ?? false) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password hash
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    
    // Check both possible password field names for backward compatibility
    $password_field = isset($user_data['password_hash']) ? $user_data['password_hash'] : $user_data['password'];
    
    if (password_verify($current_password, $password_field)) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$new_hash, $_SESSION['user_id']]);
                $message = 'Password changed successfully!';
            } else {
                $message = 'New password must be at least 6 characters long.';
            }
        } else {
            $message = 'New passwords do not match.';
        }
    } else {
        $message = 'Current password is incorrect.';
    }
}

// Handle settings update (only if not profile update)
if (($_POST['timezone'] ?? false) && !($_POST['update_profile'] ?? false)) {
    $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
    // Add missing columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN timezone TEXT DEFAULT 'Europe/London'");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_notifications INTEGER DEFAULT 0");
    } catch (Exception $e) {}
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET timezone = ?, email_notifications = ? WHERE id = ?");
        $stmt->execute([$_POST['timezone'], $email_notif, $_SESSION['user_id']]);
        $message = 'Settings updated successfully!';
    } catch (Exception $e) {
        $message = 'Settings update failed: ' . $e->getMessage();
    }
    // Refresh user data
    try {
        $stmt = $pdo->prepare("SELECT username, email, timezone, email_notifications, avatar, bio FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $current_timezone = $user['timezone'] ?? 'Europe/London';
        $email_notifications = $user['email_notifications'] ?? 0;
        $current_avatar = $user['avatar'] ?? '';
        $current_bio = $user['bio'] ?? '';
    } catch (Exception $e) {
        // Keep current values if refresh fails
    }
}

// Common timezones
$timezones = [
    'Europe/London' => 'London (GMT/BST)',
    'Europe/Paris' => 'Paris (CET/CEST)',
    'Europe/Berlin' => 'Berlin (CET/CEST)',
    'Europe/Amsterdam' => 'Amsterdam (CET/CEST)',
    'Europe/Rome' => 'Rome (CET/CEST)',
    'Europe/Madrid' => 'Madrid (CET/CEST)',
    'America/New_York' => 'New York (EST/EDT)',
    'America/Los_Angeles' => 'Los Angeles (PST/PDT)',
    'America/Chicago' => 'Chicago (CST/CDT)',
    'America/Toronto' => 'Toronto (EST/EDT)',
    'Asia/Tokyo' => 'Tokyo (JST)',
    'Asia/Shanghai' => 'Shanghai (CST)',
    'Asia/Dubai' => 'Dubai (GST)',
    'Australia/Sydney' => 'Sydney (AEST/AEDT)',
    'Pacific/Auckland' => 'Auckland (NZST/NZDT)'
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Private Social</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1877f2, #42a5f5); color: white; padding: 25px; text-align: center; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .header h1 { font-size: 2.2em; margin-bottom: 8px; }
        .nav { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 15px; margin-bottom: 25px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .nav-links { display: flex; align-items: center; }
        .nav-links a { color: #1877f2; text-decoration: none; margin-right: 20px; font-weight: 500; transition: color 0.3s; }
        .nav-links a:hover { color: #0d47a1; }
        .hamburger { display: none; flex-direction: column; cursor: pointer; }
        .hamburger span { width: 25px; height: 3px; background: #1877f2; margin: 3px 0; transition: 0.3s; }
        
        @media (max-width: 768px) {
            .hamburger { display: flex !important; }
            .nav-links { display: none; position: absolute; top: 60px; left: 0; right: 0; background: white; flex-direction: column; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 1000; }
            .nav-links.active { display: flex !important; }
            .nav-links a { margin: 10px 0; padding: 10px; border-bottom: 1px solid #f0f0f0; }
            .nav { position: relative; }
        }
        .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .post { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 25px; margin: 15px 0; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.2); }
        .post h3 { color: #1877f2; margin-bottom: 20px; font-size: 1.4em; display: flex; align-items: center; gap: 10px; }
        .form-group { margin: 20px 0; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        input, select, button { width: 100%; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 10px; font-size: 16px; transition: all 0.3s; }
        input:focus, select:focus { outline: none; border-color: #1877f2; box-shadow: 0 0 0 3px rgba(24,119,242,0.1); }
        button { background: linear-gradient(135deg, #1877f2, #42a5f5); color: white; border: none; cursor: pointer; font-weight: 600; margin-top: 10px; }
        button:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(24,119,242,0.3); }
        .message { color: #2e7d32; padding: 15px; background: linear-gradient(135deg, #e8f5e8, #c8e6c9); border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #4caf50; }
        .current-time { background: linear-gradient(135deg, #e3f2fd, #bbdefb); padding: 15px; border-radius: 10px; margin: 15px 0; border-left: 4px solid #2196f3; }
        .avatar-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 15px; }
        .avatar-option { display: flex; align-items: center; justify-content: center; padding: 10px; border: 2px solid #e1e5e9; border-radius: 10px; cursor: pointer; transition: all 0.3s; }
        .avatar-option:hover { border-color: #1877f2; background: #f8f9fa; }
        .avatar-option input[type="radio"] { display: none; }
        .avatar-option input[type="radio"]:checked + span { transform: scale(1.2); }
        .current-avatar { text-align: center; margin: 15px 0; }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .settings-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 1.8em; }
            .avatar-grid { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>
<?php require_once 'header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1>⚙️ Settings</h1>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="settings-grid">
            <!-- Profile Settings -->
            <div class="post" id="profile">
                <h3>👤 Edit Profile</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label><strong>Username:</strong></label>
                    <input type="text" name="username" value="<?= htmlspecialchars($current_username) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div class="form-group">
                    <label><strong>Email:</strong></label>
                    <input type="email" name="email" value="<?= htmlspecialchars($current_email) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div class="form-group">
                    <label><strong>Bio:</strong></label>
                    <textarea name="bio" placeholder="Tell us about yourself..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; min-height: 80px; resize: vertical;"><?= htmlspecialchars($current_bio) ?></textarea>
                    <small style="color: #666; display: block; margin-top: 5px;">Optional - appears on your profile</small>
                </div>
                <div class="form-group">
                    <label><strong>Avatar:</strong></label>
                    <?php if ($current_avatar): ?>
                        <div style="margin: 10px 0;">
                            <?php if (strpos($current_avatar, 'avatars/') === 0): ?>
                                <img src="<?= htmlspecialchars($current_avatar) ?>" alt="Current Avatar" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <span style="font-size: 60px;"><?= htmlspecialchars($current_avatar) ?></span>
                            <?php endif; ?>
                            <small style="display: block; color: #666;">Current Avatar</small>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="avatar" accept=".png,.jpg,.jpeg" style="margin-bottom: 10px;">
                    <small style="color: #666; display: block;">Upload custom avatar (PNG, JPG - max 2MB)</small>
                    
                    <div style="margin: 15px 0;">
                        <label><strong>Or choose preset avatar:</strong></label>
                        <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                            <label style="cursor: pointer;">
                                <input type="radio" name="preset_avatar" value="👤" style="margin-right: 5px;">
                                <span style="font-size: 40px;">👤</span>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="preset_avatar" value="👨" style="margin-right: 5px;">
                                <span style="font-size: 40px;">👨</span>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="preset_avatar" value="👩" style="margin-right: 5px;">
                                <span style="font-size: 40px;">👩</span>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="preset_avatar" value="🧑" style="margin-right: 5px;">
                                <span style="font-size: 40px;">🧑</span>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="preset_avatar" value="👶" style="margin-right: 5px;">
                                <span style="font-size: 40px;">👶</span>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="preset_avatar" value="🐱" style="margin-right: 5px;">
                                <span style="font-size: 40px;">🐱</span>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="preset_avatar" value="🐶" style="margin-right: 5px;">
                                <span style="font-size: 40px;">🐶</span>
                            </label>
                            <label style="cursor: pointer;">
                                <input type="radio" name="preset_avatar" value="🦊" style="margin-right: 5px;">
                                <span style="font-size: 40px;">🦊</span>
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" name="update_profile" value="1">Update Profile</button>
            </form>
        </div>

            <!-- Timezone & Notification Settings -->
            <div class="post">
                <h3>⚙️ Preferences</h3>
            
            <div class="current-time">
                <strong>Current time in your timezone:</strong><br>
                <?php
                date_default_timezone_set($current_timezone);
                echo date('l, F j, Y - H:i:s T');
                ?>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label><strong>Select your timezone:</strong></label>
                    <select name="timezone" required>
                        <?php foreach ($timezones as $tz => $label): ?>
                            <option value="<?= $tz ?>" <?= $tz === $current_timezone ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="email_notifications" <?= $email_notifications ? 'checked' : '' ?>>
                        <strong>Send me notifications by email</strong>
                    </label>
                    <small style="color: #666; margin-left: 30px;">Get email alerts when you receive new messages</small>
                </div>
                <button type="submit">Update Settings</button>
            </form>
            
            <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e1e5e9;">
                <h4 style="color: #1877f2; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                    🌍 About Timezones
                </h4>
                <p style="color: #606770; font-size: 14px; line-height: 1.5;">
                    Setting your timezone ensures that all timestamps (posts, messages, etc.) are displayed in your local time. The default timezone is London (GMT/BST).
                </p>
            </div>
            </div>
        </div>
        
        <!-- Account Statistics -->
        <div class="post">
            <h3>📊 Account Statistics</h3>
            <?php
            // Get user stats
            // Get user stats with correct table structure
            $stmt = $pdo->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $post_count = $stmt->fetch()['post_count'] ?? 0;
            
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT CASE WHEN user_id = ? THEN friend_id WHEN friend_id = ? THEN user_id END) as friend_count FROM friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'");
            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
            $friend_count = $stmt->fetch()['friend_count'] ?? 0;
            
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as reaction_count FROM reactions r JOIN posts p ON r.post_id = p.id WHERE p.user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $reactions_received = $stmt->fetch()['reaction_count'] ?? 0;
            } catch (Exception $e) {
                $reactions_received = 0;
            }
            
            $stmt = $pdo->prepare("SELECT created_at FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_data = $stmt->fetch();
            $join_date = $user_data['created_at'] ?? date('Y-m-d H:i:s');
            ?>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                <div style="text-align: center; padding: 15px; background: rgba(24,119,242,0.1); border-radius: 10px;">
                    <div style="font-size: 28px; font-weight: bold; color: #1877f2;"><?= $post_count ?></div>
                    <div style="color: #666; font-size: 14px; font-weight: 500;">Posts Created</div>
                </div>
                <div style="text-align: center; padding: 15px; background: rgba(66,165,245,0.1); border-radius: 10px;">
                    <div style="font-size: 28px; font-weight: bold; color: #42a5f5;"><?= $friend_count ?></div>
                    <div style="color: #666; font-size: 14px; font-weight: 500;">Friends</div>
                </div>
                <div style="text-align: center; padding: 15px; background: rgba(76,175,80,0.1); border-radius: 10px;">
                    <div style="font-size: 28px; font-weight: bold; color: #4caf50;"><?= $reactions_received ?></div>
                    <div style="color: #666; font-size: 14px; font-weight: 500;">Reactions Received</div>
                </div>
                <div style="text-align: center; padding: 15px; background: rgba(255,152,0,0.1); border-radius: 10px;">
                    <div style="font-size: 28px; font-weight: bold; color: #ff9800;"><?= date('M Y', strtotime($join_date)) ?></div>
                    <div style="color: #666; font-size: 14px; font-weight: 500;">Member Since</div>
                </div>
            </div>
        </div>
        
        <!-- Password Change -->
        <div class="post">
            <h3>🔒 Change Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label><strong>Current Password:</strong></label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label><strong>New Password:</strong></label>
                    <input type="password" name="new_password" required minlength="6">
                    <small style="color: #666; display: block; margin-top: 5px;">Minimum 6 characters</small>
                </div>
                <div class="form-group">
                    <label><strong>Confirm New Password:</strong></label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" name="change_password" value="1">Change Password</button>
            </form>
        </div>
        
        <!-- Quick Actions -->
        <div class="post">
            <h3>⚡ Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                <a href="users.php" style="display: flex; align-items: center; gap: 12px; padding: 18px; background: linear-gradient(135deg, rgba(227,242,253,0.8), rgba(187,222,251,0.8)); border-radius: 12px; text-decoration: none; color: #1565c0; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <span style="font-size: 24px;">👥</span>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 2px;">Find Friends</div>
                        <div style="font-size: 12px; opacity: 0.8;">Discover new connections</div>
                    </div>
                </a>
                <a href="messages.php" style="display: flex; align-items: center; gap: 12px; padding: 18px; background: linear-gradient(135deg, rgba(232,245,232,0.8), rgba(200,230,201,0.8)); border-radius: 12px; text-decoration: none; color: #2e7d32; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <span style="font-size: 24px;">💬</span>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 2px;">Messages</div>
                        <div style="font-size: 12px; opacity: 0.8;">Chat with friends</div>
                    </div>
                </a>
                <a href="index.php" style="display: flex; align-items: center; gap: 12px; padding: 18px; background: linear-gradient(135deg, rgba(255,243,224,0.8), rgba(255,224,178,0.8)); border-radius: 12px; text-decoration: none; color: #ef6c00; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <span style="font-size: 24px;">🏠</span>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 2px;">Public Feed</div>
                        <div style="font-size: 12px; opacity: 0.8;">See what's happening</div>
                    </div>
                </a>
                <a href="friends.php" style="display: flex; align-items: center; gap: 12px; padding: 18px; background: linear-gradient(135deg, rgba(252,228,236,0.8), rgba(248,187,208,0.8)); border-radius: 12px; text-decoration: none; color: #c2185b; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <span style="font-size: 24px;">❤️</span>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 2px;">Friends Feed</div>
                        <div style="font-size: 12px; opacity: 0.8;">Friends-only content</div>
                    </div>
                </a>
            </div>
        </div>

    </div>
</body>
</html>