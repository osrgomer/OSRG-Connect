<?php
require_once 'config.php';

$error = '';

if ($_POST['username'] ?? false) {
    $identifier = $_POST['username'];
    $password = $_POST['password'];

    // Debug logging for authentication issues
    @file_put_contents(__DIR__ . '/login_debug.log', "[" . date('c') . "] POST login attempt - host: " . ($_SERVER['HTTP_HOST'] ?? '') . " | cookie_consent_request: " . ($_REQUEST['cookie_consent'] ?? 'NULL') . " | cookie_consent_cookie: " . ($_COOKIE['cookie_consent'] ?? 'NULL') . " | session_status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : session_status()) . PHP_EOL, FILE_APPEND);
    
    // Check if we're on local environment
    $is_local = strpos($_SERVER['HTTP_HOST'], 'osrg.local') !== false || strpos($_SERVER['HTTP_HOST'], 'connect.osrg.lol') !== false;
    
    // Verify reCAPTCHA only on production
    if (!$is_local && (!isset($_POST['g-recaptcha-response']) || !verify_recaptcha($_POST['g-recaptcha-response']))) {
        $error = 'Security verification failed. Please try again.';
    } else {
        // 1. Try Connect Platform Login (SQLite)
        init_db();
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        
        @file_put_contents(__DIR__ . '/login_debug.log', "[" . date('c') . "] DB lookup result: " . ($user ? "FOUND user_id=" . $user['id'] . ", username=" . $user['username'] . ", email=" . $user['email'] . ", approved=" . $user['approved'] : "NO USER") . PHP_EOL, FILE_APPEND);
        
        $password_hash_field = $user['password_hash'] ?? null;
        $password_plain_field = $user['password'] ?? null;
        $login_ok = false;
        
        if ($user) {
            if ($password_hash_field && password_verify($password, $password_hash_field)) {
                $login_ok = true;
            } elseif ($password_plain_field && $password === $password_plain_field) {
                // Migrate legacy plaintext password to password_hash
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $stmt_upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt_upd->execute([$new_hash, $user['id']]);
                } catch (Exception $e) {}
                $login_ok = true;
            }
        }
        
        if ($login_ok) {
            if ($user['approved']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_email'] = $user['email'] ?? '';
                $_SESSION['username'] = $user['username'] ?? '';
                $_SESSION['user_name'] = $user['username'] ?? '';
                
                // Handle Remember Me
                if (!defined('NO_COOKIES') || !NO_COOKIES) {
                    if (isset($_POST['remember_me'])) {
                        try {
                            $token = bin2hex(random_bytes(32));
                            $expires = time() + (30 * 24 * 60 * 60);
                            $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)");
                            $stmt->execute([$user['id'], $token, $expires]);
                            setcookie('remember_token', $token, $expires, '/', '', true, true);
                        } catch (Exception $e) {
                            // Ignore remember me failure
                        }
                    }
                }
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Your account is pending approval.';
            }
        } 
        
        // 1.5 Try Auto-Recovery from SQLite (The "Missing Account" Fix)
        elseif (file_exists('private_social.db')) {
            try {
                $sqlite = new PDO('sqlite:private_social.db');
                $stmt = $sqlite->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$identifier, $identifier]);
                $sqlite_user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($sqlite_user) {
                    $pwd_check = $sqlite_user['password_hash'] ?? $sqlite_user['password'] ?? '';
                    
                    if (password_verify($password, $pwd_check)) {
                        // Found in old DB! Automatic Migration.
                        $mysql = get_db();
                        
                        // Prepare avatar
                        $avatar_blob = null;
                        if (!empty($sqlite_user['avatar']) && file_exists($sqlite_user['avatar'])) {
                            $avatar_blob = file_get_contents($sqlite_user['avatar']);
                        }
                        
                        // Insert into MySQL
                        $ins = $mysql->prepare("INSERT INTO users (username, email, password_hash, approved, timezone, email_notifications, avatar, avatar_content, bio, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $ins->execute([
                            $sqlite_user['username'], 
                            $sqlite_user['email'], 
                            $pwd_check, 
                            $sqlite_user['approved'] ?? 1,
                            $sqlite_user['timezone'] ?? 'Europe/London',
                            $sqlite_user['email_notifications'] ?? 0,
                            $sqlite_user['avatar'],
                            $avatar_blob,
                            $sqlite_user['bio'] ?? null,
                            $sqlite_user['created_at']
                        ]);
                        
                        $new_user_id = $mysql->lastInsertId();
                        $_SESSION['user_id'] = $new_user_id;
                        // Ensure migrated user has the same session flags as normal login
                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['user_email'] = $sqlite_user['email'] ?? '';
                        $_SESSION['username'] = $sqlite_user['username'] ?? '';
                        $_SESSION['user_name'] = $sqlite_user['username'] ?? '';

                        // Migrate Posts (Instant Recovery)
                        $p_stmt = $sqlite->prepare("SELECT * FROM posts WHERE user_id = ?");
                        $p_stmt->execute([$sqlite_user['id']]);
                        $old_posts = $p_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if ($old_posts) {
                            $p_ins = $mysql->prepare("INSERT INTO posts (user_id, content, file_path, file_type, file_content, post_type, reel_serial, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            foreach ($old_posts as $post) {
                                $file_blob = null;
                                if (!empty($post['file_path']) && file_exists($post['file_path'])) {
                                    $file_blob = file_get_contents($post['file_path']);
                                }
                                $p_ins->execute([
                                    $new_user_id,
                                    $post['content'],
                                    $post['file_path'],
                                    $post['file_type'],
                                    $file_blob,
                                    $post['post_type'] ?? 'post',
                                    $post['reel_serial'] ?? null,
                                    $post['created_at']
                                ]);
                            }
                        }

                        header('Location: index.php');
                        exit;
                    }
                }
            } catch (Exception $e) {
                // Silent fail/continue to next fallback
            }
        } 
        elseif (file_exists('series_db.php')) {
            require_once 'series_db.php';
            $user_series = getUserByEmail($identifier);
            if ($user_series && password_verify($password, $user_series['password'])) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_email'] = $user_series['email'];
                $_SESSION['user_id'] = $user_series['id'];
                $_SESSION['username'] = $user_series['email'];
                $_SESSION['user_name'] = $user_series['username'];
                updateLastActive($user_series['id']);
                header('Location: series_index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
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
    <title>Login - OSRG Connect</title>
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1Y8S6WHNH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-Y1Y8S6WHNH');
    </script>
    
    <!-- reCAPTCHA v3 (only on production) -->
    <?php if (strpos($_SERVER['HTTP_HOST'], 'osrg.local') === false && strpos($_SERVER['HTTP_HOST'], 'connect.osrg.lol') === false): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
    <?php endif; ?>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1877f2; color: white; padding: 15px; text-align: center; }
        .form-group { margin: 15px 0; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { background: #1877f2; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .error { color: red; padding: 10px; }
        
        .password-container { position: relative; }
        .show-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #666; font-size: 14px; }
        
        @media (max-width: 768px) {
            .container { padding: 15px; margin: 10px; }
            .header { padding: 20px 15px; }
            input, button { padding: 12px; font-size: 16px; }
        }
    </style>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const showButton = document.querySelector('.show-password');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                showButton.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                showButton.textContent = '👁️';
            }
        }
        
        // reCAPTCHA v3 integration (only on production)
        <?php if (strpos($_SERVER['HTTP_HOST'], 'osrg.local') === false && strpos($_SERVER['HTTP_HOST'], 'connect.osrg.lol') === false): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Verifying...';
                
                grecaptcha.ready(function() {
                    grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'login'}).then(function(token) {
                        document.getElementById('g-recaptcha-response').value = token;
                        form.submit();
                    });
                });
            });
        });
        <?php endif; ?>
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>OSRG Connect</h1>
            <p>Private Social Network</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username or Email" required>
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <button type="button" class="show-password" onclick="togglePassword()">👁️</button>
                </div>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                    <input type="checkbox" name="remember_me" style="width: auto;">
                    <span style="font-size: 14px; color: #666;">Remember me for 30 days</span>
                </label>
            </div>
            <div class="form-group">
                <button type="submit" id="submitBtn">Login</button>
            </div>
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
            <input type="hidden" name="cookie_consent" id="cookie_consent_input">
        </form>

        <p style="text-align: center; margin-top: 20px;">
            Don't have an account? <a href="register.php">Register here</a><br>
            <a href="forgot_password.php" style="color: #666; font-size: 14px;">Forgot Password?</a>
        </p>
    </div>

<!-- Cookie consent banner -->
<div id="cookieBanner" style="position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid #ddd;padding:12px;display:none;justify-content:space-between;align-items:center;gap:12px;z-index:9999;">
  <div style="font-size:14px;color:#333;">This site uses cookies to improve your experience. Do you accept cookies?</div>
  <div>
    <button id="cookieAcceptBtn" style="background:#1877f2;color:#fff;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;margin-right:8px;">Accept</button>
    <button id="cookieDeclineBtn" style="background:#ddd;color:#333;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;">Decline</button>
  </div>
</div>

<script>
(function(){
  function setHiddenConsent(val){ var el = document.getElementById('cookie_consent_input'); if(el) el.value = val; }
  var consent = localStorage.getItem('cookie_consent') || (document.cookie.match(/(^|; )cookie_consent=([^;]+)/)? RegExp.$2 : null);
  if(consent === 'accepted'){ document.cookie = 'cookie_consent=accepted; path=/'; setHiddenConsent('accepted'); var b=document.getElementById('cookieBanner'); if(b) b.remove(); }
  else if(consent === 'declined'){ setHiddenConsent('declined'); var b=document.getElementById('cookieBanner'); if(b) b.remove(); }
  else {
    var b=document.getElementById('cookieBanner'); if(b) b.style.display = 'flex';
  }
  var acceptBtn = document.getElementById('cookieAcceptBtn');
  if (acceptBtn) acceptBtn.addEventListener('click', function(){ localStorage.setItem('cookie_consent','accepted'); document.cookie='cookie_consent=accepted; path=/'; setHiddenConsent('accepted'); var b=document.getElementById('cookieBanner'); if(b) b.remove(); });
  var declineBtn = document.getElementById('cookieDeclineBtn');
  if (declineBtn) declineBtn.addEventListener('click', function(){ localStorage.setItem('cookie_consent','declined'); setHiddenConsent('declined'); var b=document.getElementById('cookieBanner'); if(b) b.remove(); });
  var forms = document.querySelectorAll('form');
  forms.forEach(function(f){ f.addEventListener('submit', function(){ setHiddenConsent(localStorage.getItem('cookie_consent') || 'accepted'); }); });
})();
</script>

</body>
</html>
