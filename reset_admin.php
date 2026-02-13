<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require_once 'config.php';
    init_db();
    
    $pdo = get_db();
    if (!$pdo) {
        die('Database connection failed');
    }
    
    // Reset OSRG password to admin123
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    // Prefer the password_hash column; fallback created if missing
    try {
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'OSRG'");
        $result = $stmt->execute([$password]);
    } catch (Exception $e) {
        // If password_hash column doesn't exist, try legacy column
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'OSRG'");
        $result = $stmt->execute([$password]);
    }
    
    if ($result) {
        echo "OSRG password reset to: admin123";
    } else {
        echo "Failed to update password";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
