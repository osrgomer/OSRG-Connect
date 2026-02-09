<?php
session_start();

// Set user to offline in database before logging out
if (isset($_SESSION['user_id'])) {
    require_once 'db.php';
    $pdo = getDB();
    // Set last_active to old date to immediately show as offline
    $stmt = $pdo->prepare("UPDATE users SET last_active = '1970-01-01 00:00:00' WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Clear all session data
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>