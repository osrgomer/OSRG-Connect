<?php
require_once 'config.php';
init_db();

$pdo = get_db();

echo "Adding new features to database...<br><br>";

try {
    // Add last_seen column for online status
    $pdo->exec("ALTER TABLE users ADD COLUMN last_seen DATETIME DEFAULT CURRENT_TIMESTAMP");
    echo "✅ Added last_seen column for online status<br>";
} catch (Exception $e) {
    echo "⚠️ last_seen column already exists<br>";
}

try {
    // Add bio column for user profiles
    $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT ''");
    echo "✅ Added bio column for user profiles<br>";
} catch (Exception $e) {
    echo "⚠️ bio column already exists<br>";
}

try {
    // Add edited_at column for post editing
    $pdo->exec("ALTER TABLE posts ADD COLUMN edited_at DATETIME NULL");
    echo "✅ Added edited_at column for post editing<br>";
} catch (Exception $e) {
    echo "⚠️ edited_at column already exists<br>";
}

echo "<br><strong style='color: green;'>Database updated successfully!</strong><br>";
echo "<a href='index.php'>← Back to Home</a>";
?>