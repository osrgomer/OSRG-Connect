<?php
require_once 'config.php';
$pdo = get_db();

echo "<h2>Convert Video Post to Reel</h2>";

if ($_POST['convert'] ?? false) {
    $post_id = $_POST['convert'];
    
    // Update the post to be a reel with serial number 1
    $stmt = $pdo->prepare("UPDATE posts SET post_type = 'reel', reel_serial = 1 WHERE id = ?");
    $stmt->execute([$post_id]);
    
    echo "<p style='color: green;'>✅ Converted Post ID $post_id to Reel #1</p>";
}

// Show video posts that can be converted
$stmt = $pdo->query("SELECT id, content, file_path, file_type FROM posts WHERE file_type = 'mp4' AND (post_type IS NULL OR post_type != 'reel')");
$video_posts = $stmt->fetchAll();

foreach ($video_posts as $post) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Post ID:</strong> {$post['id']}<br>";
    echo "<strong>Content:</strong> " . htmlspecialchars($post['content']) . "<br>";
    echo "<strong>File:</strong> {$post['file_path']}<br>";
    echo "<form method='POST' style='margin-top: 10px;'>";
    echo "<button type='submit' name='convert' value='{$post['id']}' style='background: green; color: white; padding: 5px 10px; border: none; border-radius: 3px;'>Convert to Reel</button>";
    echo "</form>";
    echo "</div>";
}
?>