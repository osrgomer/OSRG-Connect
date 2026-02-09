<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting friends debug...<br>";

try {
    require_once 'config.php';
    echo "Config loaded<br>";
    
    init_db();
    echo "DB initialized<br>";
    
    if (!isset($_SESSION['user_id'])) {
        echo "No session user_id<br>";
        exit;
    }
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    
    $pdo = get_db();
    echo "PDO connection established<br>";
    
    // Test simple query
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $count = $stmt->fetch();
    echo "Users count: " . $count['count'] . "<br>";
    
    echo "All checks passed - friends.php should work<br>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>