<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u542077544_serieslist');
define('DB_USER', 'u542077544_serieslist');
define('DB_PASS', 'SeriesList2026!Secure');

// Create database connection
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Helper function to get user by email
function getUserByEmail($email) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

// Helper function to get user by ID
function getUserById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Helper function to check if user is online
function isUserOnline($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT manual_status, last_active FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) return false;
    
    // Check manual status override first
    if ($user['manual_status'] === 'offline') return false;
    if ($user['manual_status'] === 'online') return true;
    
    // Auto mode: check last activity within 2 minutes
    $lastActive = strtotime($user['last_active']);
    return (time() - $lastActive) < 120;
}

// Update user's last active timestamp
function updateLastActive($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
}

// Update user's manual status
function updateUserStatus($userId, $status) {
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE users SET manual_status = ? WHERE id = ?");
    $result = $stmt->execute([$status, $userId]);
    return $result && $stmt->rowCount() > 0;
}

// Get user's current status
function getUserStatus($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT manual_status FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ? $user['manual_status'] : 'auto';
}
?>
