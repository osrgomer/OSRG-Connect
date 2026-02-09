<?php
// Enable ALL errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Exit God Mode Debug</h1>";

echo "<h2>Session State:</h2>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "admin_origin: " . ($_SESSION['admin_origin'] ?? 'NOT SET') . "\n";
echo "user_email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "\n";
echo "username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "</pre>";

if (isset($_SESSION['admin_origin'])) {
    echo "<h2>Attempting Exit...</h2>";
    
    try {
        require_once 'db.php';
        
        echo "✅ db.php loaded<br>";
        
        $_SESSION['user_id'] = $_SESSION['admin_origin'];
        echo "✅ Restored user_id to: {$_SESSION['user_id']}<br>";
        
        unset($_SESSION['admin_origin']);
        echo "✅ Removed admin_origin<br>";
        
        $db = getDB();
        echo "✅ Got database connection<br>";
        
        $stmt = $db->prepare("SELECT email, username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Query result:<br>";
        echo "<pre>";
        print_r($admin);
        echo "</pre>";
        
        if ($admin) {
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['username'] = $admin['username'];
            
            echo "<h3 style='color:green'>✅ SUCCESS! Session restored:</h3>";
            echo "<pre>";
            echo "user_id: {$_SESSION['user_id']}\n";
            echo "user_email: {$_SESSION['user_email']}\n";
            echo "username: {$_SESSION['username']}\n";
            echo "</pre>";
            
            echo "<p><a href='admin.php'>→ Go to Admin Panel</a></p>";
        } else {
            echo "<p style='color:red'>❌ No user found with ID {$_SESSION['user_id']}</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ ERROR: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p>Not in God Mode - nothing to exit!</p>";
}
?>
