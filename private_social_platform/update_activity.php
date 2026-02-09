<?php
require_once 'config.php';
init_db();

if (isset($_SESSION['user_id'])) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("UPDATE users SET last_seen = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // Silently fail
    }
}
?>