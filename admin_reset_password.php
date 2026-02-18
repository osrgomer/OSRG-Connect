<?php
// admin_reset_password.php - Minimal admin-only password reset page
require_once __DIR__ . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(403);
    echo "Access denied. Please log in as admin.";
    exit;
}
$pdo = get_db();
try {
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $me = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Database error.";
    exit;
}
if (!($me && (isset($me['username']) && $me['username'] === 'admin'))) {
    http_response_code(403);
    echo "Access denied. Admin required.";
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target = trim($_POST['target'] ?? '');
    $newpw = $_POST['new_password'] ?? '';
    if ($target === '' || $newpw === '') {
        $msg = "Target and new password required.";
    } else {
        try {
            if (ctype_digit($target)) {
                $tstmt = $pdo->prepare("SELECT id, email, username FROM users WHERE id = ?");
                $tstmt->execute([$target]);
            } else {
                $tstmt = $pdo->prepare("SELECT id, email, username FROM users WHERE email = ? OR username = ?");
                $tstmt->execute([$target, $target]);
            }
            $target_user = $tstmt->fetch(PDO::FETCH_ASSOC);
            if (!$target_user) {
                $msg = "User not found.";
            } else {
                $newhash = password_hash($newpw, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password_hash = ?, password = NULL WHERE id = ?");
                $upd->execute([$newhash, $target_user['id']]);
                $display = htmlspecialchars($target_user['email'] ?? $target_user['username']);
                $msg = "Password updated for user id " . $target_user['id'] . " (" . $display . ")";
            }
        } catch (Exception $e) {
            $msg = "Error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Password Reset</title>
    <style>body{font-family:Arial,sans-serif;padding:20px;}label{display:block;margin:8px 0;}input{padding:8px;width:100%;max-width:480px;}button{padding:8px 12px;margin-top:8px;}</style>
</head>
<body>
    <h1>Admin Password Reset</h1>
    <p>Logged in as admin: <?php echo htmlspecialchars($me['email'] ?? $me['username'] ?? 'admin'); ?></p>
    <form method="post">
        <label>Target (email, username or id):<br><input name="target" required></label>
        <label>New password:<br><input name="new_password" type="password" required></label>
        <button type="submit">Set Password</button>
    </form>
    <?php if ($msg): ?><p><?php echo htmlspecialchars($msg); ?></p><?php endif; ?>
    <p style="color:#666;font-size:12px;margin-top:20px;">Notes: This endpoint requires an active admin session (username 'admin'). Use responsibly and remove when done.</p>
</body>
</html>
