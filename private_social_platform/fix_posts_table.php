<?php
require_once 'config.php';
init_db();

$pdo = get_db();

echo "<h2>Fixing Posts Table</h2>";

try {
    // Add the missing post_type column
    $pdo->exec("ALTER TABLE posts ADD COLUMN post_type TEXT DEFAULT 'post'");
    echo "<p style='color: green;'>✅ Successfully added post_type column to posts table</p>";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "<p style='color: orange;'>⚠️ post_type column already exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Error adding column: " . $e->getMessage() . "</p>";
    }
}

// Check the table structure now
echo "<h3>Updated Posts Table Structure:</h3>";
$stmt = $pdo->query("PRAGMA table_info(posts)");
$columns = $stmt->fetchAll();
foreach ($columns as $col) {
    echo $col['name'] . " (" . $col['type'] . ")<br>";
}

echo "<p style='color: blue;'>🎬 You can now create reels properly!</p>";
?>