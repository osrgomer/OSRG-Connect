<?php
require_once 'config.php';
require_once 'series_db.php';

echo "<h1>Database User Check</h1>";

try {
    $pdo = get_db();
    
    // Check MySQL Users
    $stmt = $pdo->query("SELECT id, username, email, created_at, bio, avatar FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>MySQL Users (" . count($users) . ")</h2>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Username</th><th>Email</th><th>Bio (Preview)</th><th>Avatar</th><th>Created At</th></tr>";
    
    foreach ($users as $u) {
        $bio_preview = $u['bio'] ? substr($u['bio'], 0, 50) . (strlen($u['bio']) > 50 ? '...' : '') : '<em>None</em>';
        $avatar_display = $u['avatar'] ? htmlspecialchars($u['avatar']) : '<em>None</em>';
        
        echo "<tr>";
        echo "<td>{$u['id']}</td>";
        echo "<td><strong>" . htmlspecialchars($u['username']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($u['email']) . "</td>";
        echo "<td>{$bio_preview}</td>";
        echo "<td>{$avatar_display}</td>";
        echo "<td>{$u['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Check Auto-Recovery Source (SQLite)
    if (file_exists('private_social.db')) {
        $sqlite = new PDO('sqlite:private_social.db');
        $s_stmt = $sqlite->query("SELECT COUNT(*) FROM users");
        $sqlite_count = $s_stmt->fetchColumn();
        echo "<h2>SQLite Backup Users (Old DB): $sqlite_count</h2>";
    } else {
        echo "<h2>SQLite Backup: Not Found</h2>";
    }

} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>
