<?php
require_once 'config.php';

$error = '';

if ($_POST['username'] ?? false) {
    $identifier = $_POST['username'];
    $password = $_POST['password'];
    
    // Check if we're on local environment
    $is_local = strpos($_SERVER['HTTP_HOST'], 'osrg.local') !== false;
    
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
        
        $password_field = isset($user['password_hash']) ? $user['password_hash'] : ($user['password'] ?? '');
        
        if ($user && password_verify($password, $password_field)) {
            if ($user['approved']) {
                $_SESSION['user_id'] = $user['id'];
                
                // Handle Remember Me
                if (isset($_POST['remember_me'])) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60);
                    $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $token, $expires]);
                    setcookie('remember_token', $token, $expires, '/', '', true, true);
                }
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Your account is pending approval.';
            }
        } 
        // 2. Try SeriesList Login Fallback (MySQL)
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
    <?php if (strpos($_SERVER['HTTP_HOST'], 'osrg.local') === false): ?>
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
        <?php if (strpos($_SERVER['HTTP_HOST'], 'osrg.local') === false): ?>
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
        </form>

        <p style="text-align: center; margin-top: 20px;">
            Don't have an account? <a href="register.php">Register here</a><br>
            <a href="forgot-password" style="color: #666; font-size: 14px;">Forgot Password?</a>
        </p>
    </div>
</body>
</html>
