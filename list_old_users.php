<?php
require_once 'config.php';
require_once 'series_db.php';

echo "<h1>List All Users in Old Database</h1>";

if (file_exists('private_social.db')) {
    try {
        $sqlite = new PDO('sqlite:private_social.db');
        $stmt = $sqlite->query("SELECT id, username, email FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Username</th><th>Email</th></tr>";
        
        foreach ($users as $u) {
            echo "<tr>";
            echo "<td>" . $u['id'] . "</td>";
            echo "<td>" . htmlspecialchars($u['username']) . "</td>";
            echo "<td>" . htmlspecialchars($u['email']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Debug: Raw Emails Found</h3>";
        foreach ($users as $u) {
            echo "'" . $u['email'] . "' (Length: " . strlen($u['email']) . ")<br>";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "<h3>Error: private_social.db file not found!</h3>";
}
?>
