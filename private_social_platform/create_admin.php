<?php
require_once 'config.php';
init_db();

$pdo = get_db();

// Check if OSRG user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'OSRG'");
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    // Create OSRG admin user
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, approved) VALUES (?, ?, ?, ?)");
    $stmt->execute(['OSRG', 'admin@osrg.lol', $password, 1]);
    echo "OSRG admin user created with password: admin123";
} else {
    echo "OSRG user already exists";
    echo "<br>Username: " . $user['username'];
    echo "<br>Email: " . $user['email'];
    echo "<br>Approved: " . $user['approved'];
}
?>