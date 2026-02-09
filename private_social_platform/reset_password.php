<?php
require_once 'config.php';

$message = '';
$error = '';
$valid_token = false;

// Check if token is provided and valid
if ($_GET['token'] ?? false) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT pr.*, u.username FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? AND pr.expires > datetime('now')");
    $stmt->execute([$_GET['token']]);
    $reset_data = $stmt->fetch();
    
    if ($reset_data) {
        $valid_token = true;
        
        // Handle password reset
        if ($_POST['password'] ?? false) {
            if ($_POST['password'] === $_POST['confirm_password']) {
                $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$password_hash, $reset_data['user_id']]);
                
                // Delete used token
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$_GET['token']]);
                
                $message = 'Password reset successful! <a href="login.php">Login here</a>';
            } else {
                $error = 'Passwords do not match.';
            }
        }
    } else {
        $error = 'Invalid or expired reset token.';
    }
} else {
    $error = 'No reset token provided.';
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
    <title>Reset Password - OSRG Connect</title>
    
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
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1877f2; color: white; padding: 15px; text-align: center; }
        .form-group { margin: 15px 0; }
        .password-container { position: relative; }
        .show-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #666; font-size: 14px; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { background: #1877f2; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .message { color: green; padding: 10px; background: #e8f5e8; border-radius: 5px; margin-bottom: 10px; }
        .error { color: red; padding: 10px; background: #ffeaea; border-radius: 5px; margin-bottom: 10px; }
        
        @media (max-width: 768px) {
            .container { padding: 15px; margin: 10px; }
            .header { padding: 20px 15px; }
            input, button { padding: 12px; font-size: 16px; }
        }
    </style>
    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const showButton = passwordField.nextElementSibling;
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                showButton.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                showButton.textContent = '👁️';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reset Password</h1>
            <p>Enter your new password</p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($valid_token && !$message): ?>
        <form method="POST">
            <div class="form-group">
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="New Password" required>
                    <button type="button" class="show-password" onclick="togglePassword('password')">👁️</button>
                </div>
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
                    <button type="button" class="show-password" onclick="togglePassword('confirm_password')">👁️</button>
                </div>
            </div>
            <div class="form-group">
                <button type="submit">Reset Password</button>
            </div>
        </form>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 20px;">
            <a href="login.php">Back to Login</a>
        </p>
    </div>
</body>
</html>