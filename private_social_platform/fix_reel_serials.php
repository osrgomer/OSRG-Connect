<?php
require_once 'config.php';
$pdo = get_db();

echo "<h2>Fix Reel Serial Numbers</h2>";

if ($_POST['fix_serials'] ?? false) {
    // Get all reels ordered by creation date
    $stmt = $pdo->query("SELECT id, reel_serial, file_path, created_at FROM posts WHERE post_type = 'reel' OR file_type IN ('mp4', 'mov', 'avi') ORDER BY created_at ASC");
    $reels = $stmt->fetchAll();
    
    $serial = 1;
    foreach ($reels as $reel) {
        $stmt = $pdo->prepare("UPDATE posts SET reel_serial = ?, post_type = 'reel' WHERE id = ?");
        $stmt->execute([$serial, $reel['id']]);
        echo "<p>Updated Reel ID {$reel['id']} to Serial #{$serial} ({$reel['file_path']})</p>";
        $serial++;
    }
    echo "<p style='color: green;'><strong>✅ All reel serials fixed!</strong></p>";
}

if ($_POST['delete_reel'] ?? false) {
    $reel_id = $_POST['delete_reel'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND (post_type = 'reel' OR file_type IN ('mp4', 'mov', 'avi'))");
    $stmt->execute([$reel_id]);
    echo "<p style='color: red;'>❌ Deleted reel ID: $reel_id</p>";
}

// Show current reels
echo "<h3>Current Reels:</h3>";
$stmt = $pdo->query("SELECT id, reel_serial, file_path, content, created_at FROM posts WHERE post_type = 'reel' OR file_type IN ('mp4', 'mov', 'avi') ORDER BY reel_serial ASC, created_at ASC");
$reels = $stmt->fetchAll();

foreach ($reels as $reel) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Reel #{$reel['reel_serial']}</strong> (ID: {$reel['id']})<br>";
    echo "File: {$reel['file_path']}<br>";
    echo "Caption: " . htmlspecialchars($reel['content']) . "<br>";
    echo "Created: {$reel['created_at']}<br>";
    echo "<form method='POST' style='display: inline;'>";
    echo "<button type='submit' name='delete_reel' value='{$reel['id']}' onclick='return confirm(\"Delete this reel?\")' style='background: red; color: white; padding: 5px 10px; border: none; border-radius: 3px;'>Delete</button>";
    echo "</form>";
    echo "</div>";
}

echo "<hr>";
echo "<form method='POST'>";
echo "<button type='submit' name='fix_serials' value='1' style='background: blue; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Fix All Serial Numbers</button>";
echo "</form>";
?>