<?php
echo "PHP is working<br>";

try {
    $pdo = new PDO('sqlite:private_social.db');
    echo "SQLite connection works<br>";
    
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll();
    echo "Tables: ";
    foreach($tables as $table) {
        echo $table['name'] . " ";
    }
    echo "<br>";
    
    // Check users table structure
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll();
    // Reset OSRG password
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'OSRG'");
    $stmt->execute([$password]);
    echo "OSRG password reset to: admin123<br>";
    echo "You can now login with OSRG/admin123<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>