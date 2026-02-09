<?php
require_once 'config.php';

// Clear remember me token if exists (Social Platform)
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

// Set user to offline in database (Series List)
if (isset($_SESSION['user_id'])) {
    if (file_exists('series_db.php')) {
        try {
            require_once 'series_db.php';
            $pdo_series = getDB();
            // Set last_active to old date to immediately show as offline
            $stmt_series = $pdo_series->prepare("UPDATE users SET last_active = '1970-01-01 00:00:00' WHERE id = ?");
            $stmt_series->execute([$_SESSION['user_id']]);
        } catch (Exception $e) {}
    }
}

session_destroy();
header('Location: login.php');
exit;
?>
