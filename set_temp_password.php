<?php
// CLI-only script to set a user's password in the app database using the app's get_db() function.
// Usage: php set_temp_password.php email@example.com newpassword
if (php_sapi_name() !== 'cli') {
    echo "Run this script from CLI only.\n";
    exit(1);
}
if ($argc < 3) {
    echo "Usage: php set_temp_password.php <email> <newpassword>\n";
    exit(1);
}
$email = $argv[1];
$newpw_plain = $argv[2];

require_once __DIR__ . '/config.php';
try {
    $pdo = get_db();
    if (!$pdo) { echo "No DB connection\n"; exit(1); }
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) { echo "User not found\n"; exit(1); }
    $newhash = password_hash($newpw_plain, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $upd->execute([$newhash, $user['id']]);
    echo "Password updated for user_id=" . $user['id'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
