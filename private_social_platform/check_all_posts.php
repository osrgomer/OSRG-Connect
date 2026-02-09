<?php
require_once 'config.php';
$pdo = get_db();

echo "<h2>All Posts in Database</h2>";

$stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
$posts = $stmt->fetchAll();

echo "<p>Total posts: " . count($posts) . "</p>";

foreach ($posts as $post) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Post ID:</strong> {$post['id']}<br>";
    echo "<strong>User ID:</strong> {$post['user_id']}<br>";
    echo "<strong>Content:</strong> " . htmlspecialchars($post['content']) . "<br>";
    echo "<strong>File Path:</strong> " . htmlspecialchars($post['file_path'] ?? 'none') . "<br>";
    echo "<strong>File Type:</strong> " . htmlspecialchars($post['file_type'] ?? 'none') . "<br>";
    echo "<strong>Post Type:</strong> " . htmlspecialchars($post['post_type'] ?? 'none') . "<br>";
    echo "<strong>Reel Serial:</strong> " . htmlspecialchars($post['reel_serial'] ?? 'none') . "<br>";
    echo "<strong>Created:</strong> {$post['created_at']}<br>";
    echo "</div>";
}
?>