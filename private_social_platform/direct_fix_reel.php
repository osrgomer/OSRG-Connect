<?php
require_once 'config.php';
$pdo = get_db();

echo "<h2>Direct Fix for Post ID 12</h2>";

// Add missing columns if they don't exist
try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN post_type TEXT DEFAULT 'post'");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN reel_serial INTEGER");
} catch (Exception $e) {}

// Convert Post ID 12 to reel
$stmt = $pdo->prepare("UPDATE posts SET post_type = 'reel', reel_serial = 1 WHERE id = 12");
$result = $stmt->execute();

if ($result) {
    echo "<p style='color: green;'>✅ Post ID 12 converted to Reel #1</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to convert Post ID 12</p>";
}

// Show the updated post
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = 12");
$stmt->execute();
$post = $stmt->fetch();

if ($post) {
    echo "<h3>Updated Post:</h3>";
    echo "<p><strong>ID:</strong> {$post['id']}</p>";
    echo "<p><strong>Content:</strong> " . htmlspecialchars($post['content']) . "</p>";
    echo "<p><strong>File Path:</strong> {$post['file_path']}</p>";
    echo "<p><strong>File Type:</strong> {$post['file_type']}</p>";
    echo "<p><strong>Post Type:</strong> {$post['post_type']}</p>";
    echo "<p><strong>Reel Serial:</strong> {$post['reel_serial']}</p>";
} else {
    echo "<p>Post ID 12 not found</p>";
}
?>