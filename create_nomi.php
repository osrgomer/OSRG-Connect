<?php
require_once 'config.php';
require_once 'series_db.php';

echo "<h1>Recreating 'nomi@osrg.lol'</h1>";

try {
    $pdo = get_db();
    
    $email = 'nomi@osrg.lol';
    $username = 'Nomi'; 
    $password = '123456'; // Temporary password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "<h3 style='color:orange'>User already exists in the database!</h3>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, approved, timezone, email_notifications) VALUES (?, ?, ?, 1, 'Europe/London', 0)");
        $stmt->execute([$username, $email, $hash]);
        echo "<h3 style='color:green'>✅ Account Created Successfully!</h3>";
        echo "<p><strong>Email:</strong> $email<br><strong>Temporary Password:</strong> $password</p>";
        echo "<p>Please login and change the password immediately.</p>";
        echo "<p><a href='login.php'>Go to Login</a></p>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
