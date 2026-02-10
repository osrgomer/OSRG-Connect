<?php
require_once 'config.php';
require_once 'series_db.php';

echo "<h1>Header Debug</h1>";
echo "Session ID: " . ($_SESSION['user_id'] ?? 'Not Set') . "<br>";

$pdo = get_db();
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>DB User Data:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    echo "<h3>Comparison:</h3>";
    echo "Username from DB: '" . $user['username'] . "'<br>";
    echo "Length: " . strlen($user['username']) . "<br>";
    echo "Is 'OSRG'? " . ($user['username'] === 'OSRG' ? 'YES' : 'NO') . "<br>";
    echo "Is 'backup'? " . ($user['username'] === 'backup' ? 'YES' : 'NO') . "<br>";
} else {
    echo "<h3>Not Logged In</h3>";
}
?>
