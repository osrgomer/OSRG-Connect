<?php
require_once 'config.php';
init_db();
$pdo = get_db();

echo "<h2>Fixing Database Columns</h2>";

try {
    // Add post_type column
    $pdo->exec("ALTER TABLE posts ADD COLUMN post_type TEXT DEFAULT 'post'");
    echo "<p>✅ Added post_type column</p>";
} catch (Exception $e) {
    echo "<p>⚠️ post_type column: " . $e->getMessage() . "</p>";
}

try {
    // Add reel_serial column
    $pdo->exec("ALTER TABLE posts ADD COLUMN reel_serial INTEGER");
    echo "<p>✅ Added reel_serial column</p>";
} catch (Exception $e) {
    echo "<p>⚠️ reel_serial column: " . $e->getMessage() . "</p>";
}

// Convert existing video posts to reels
try {
    $stmt = $pdo->prepare("UPDATE posts SET post_type = 'reel' WHERE file_type IN ('mp4', 'mov', 'avi') AND post_type IS NULL");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "<p>✅ Converted $updated video posts to reels</p>";
} catch (Exception $e) {
    echo "<p>❌ Error converting posts: " . $e->getMessage() . "</p>";
}

// Add serial numbers to reels
try {
    $stmt = $pdo->query("SELECT id FROM posts WHERE post_type = 'reel' ORDER BY created_at ASC");
    $reels = $stmt->fetchAll();
    
    $serial = 1;
    foreach ($reels as $reel) {
        $update_stmt = $pdo->prepare("UPDATE posts SET reel_serial = ? WHERE id = ?");
        $update_stmt->execute([$serial, $reel['id']]);
        $serial++;
    }
    echo "<p>✅ Added serial numbers to " . count($reels) . " reels</p>";
} catch (Exception $e) {
    echo "<p>❌ Error adding serials: " . $e->getMessage() . "</p>";
}

echo "<h3>Final Check:</h3>";
$stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE post_type = 'reel'");
$reel_count = $stmt->fetchColumn();
echo "<p>Total reels now: $reel_count</p>";

echo "<p><strong>✅ Database fix complete! Reels should work now.</strong></p>";
?>