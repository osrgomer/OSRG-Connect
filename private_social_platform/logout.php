<?php
require_once 'config.php';

// Clear remember me token if exists
if (isset($_COOKIE['remember_token'])) {
    $pdo = get_db();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token = ?");
            $stmt->execute([$_COOKIE['remember_token']]);
        } catch (Exception $e) {}
    }
    // Clear cookie
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

session_destroy();
header('Location: login.php');
exit;
?>