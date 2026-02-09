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
    
    echo "Database connected successfully<br>";
    echo "Database file: " . realpath('private_social.db') . "<br><br>";

// Check all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll();

echo "All users in database:<br><br>";
foreach ($users as $user) {
    echo "ID: " . $user['id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Approved: " . $user['approved'] . "<br>";
    echo "Password hash: " . substr($user['password_hash'], 0, 20) . "...<br>";
    echo "Created: " . $user['created_at'] . "<br>";
    echo "<hr>";
}

// Check for OSRG admin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'OSRG'");
$stmt->execute();
$osrg_admin = $stmt->fetch();

if (!$osrg_admin) {
    echo "<br><strong style='color: red;'>OSRG admin user not found - creating...</strong><br>";
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, approved) VALUES (?, ?, ?, ?)");
    $stmt->execute(['OSRG', 'admin@osrg.lol', $password, 1]);
    echo "<br><strong style='color: green;'>OSRG admin user created with password: admin123</strong><br>";
} else {
    echo "<br><strong style='color: green;'>OSRG admin user exists - ID: " . $osrg_admin['id'] . "</strong><br>";
}

// Check for OSRG2 user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'OSRG2'");
$stmt->execute();
$osrg2 = $stmt->fetch();

if (!$osrg2) {
    echo "<br><strong style='color: red;'>OSRG2 user not found - recreating...</strong><br>";
    $password = password_hash('test123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, approved) VALUES (?, ?, ?, ?)");
    $stmt->execute(['OSRG2', 'test@osrg.lol', $password, 1]);
    echo "<br><strong style='color: green;'>OSRG2 test user recreated with password: test123</strong><br>";
} else {
    echo "<br><strong style='color: green;'>OSRG2 user exists - ID: " . $osrg2['id'] . "</strong><br>";
}

// Reset all admin and test user passwords and ensure they're approved
$users_to_reset = [
    ['username' => 'OSRG', 'password' => 'admin123'],
    ['username' => 'OSRG2', 'password' => 'test123'],
    ['username' => 'backup', 'password' => 'backup123']
];

foreach ($users_to_reset as $user_reset) {
    // Check if user exists first
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$user_reset['username']]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        // Update password and ensure approved status
        $password_hash = password_hash($user_reset['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, approved = 1 WHERE username = ?");
        $stmt->execute([$password_hash, $user_reset['username']]);
        echo "<br><strong style='color: blue;'>" . $user_reset['username'] . " password reset to: " . $user_reset['password'] . " and approved</strong><br>";
    }
}

// Create backup admin user if not exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'backup'");
$stmt->execute();
$backup_user = $stmt->fetch();

if (!$backup_user) {
    $backup_password = password_hash('backup123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, approved) VALUES (?, ?, ?, ?)");
    $stmt->execute(['backup', 'backup@osrg.lol', $backup_password, 1]);
    echo "<br><strong style='color: orange;'>Backup admin user created - Username: backup, Password: backup123</strong><br>";
    
    // Get the newly created user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'backup'");
    $stmt->execute();
    $backup_user = $stmt->fetch();
}

echo "<br><strong style='color: orange;'>Backup admin user exists - ID: " . $backup_user['id'] . "</strong><br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>