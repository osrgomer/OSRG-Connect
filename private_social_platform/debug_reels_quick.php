<?php
require_once 'config.php';
init_db();
$pdo = get_db();

echo "<h2>Quick Reels Debug</h2>";

// Check all posts
$stmt = $pdo->query("SELECT id, user_id, content, file_path, file_type, post_type, reel_serial, created_at FROM posts ORDER BY created_at DESC LIMIT 10");
$posts = $stmt->fetchAll();

echo "<h3>Recent Posts:</h3>";
foreach ($posts as $post) {
    echo "<p>ID: {$post['id']}, Type: {$post['post_type']}, File: {$post['file_path']}, Serial: {$post['reel_serial']}</p>";
}

// Check specifically for reels
$stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE post_type = 'reel' OR file_type IN ('mp4', 'mov', 'avi')");
$reel_count = $stmt->fetchColumn();
echo "<h3>Total Reels: $reel_count</h3>";

// Check the exact query from reels.php
$stmt = $pdo->prepare("
    SELECT p.id, p.content, p.created_at, u.username, u.avatar, p.file_path, p.file_type, p.reel_serial,
           COUNT(DISTINCT CASE WHEN r.reaction_type = 'like' THEN r.id END) as like_count,
           COUNT(DISTINCT CASE WHEN r.reaction_type = 'love' THEN r.id END) as love_count,
           COUNT(DISTINCT CASE WHEN r.reaction_type = 'laugh' THEN r.id END) as laugh_count,
           COUNT(DISTINCT c.id) as comment_count,
           ur.reaction_type as user_reaction
    FROM posts p 
    JOIN users u ON p.user_id = u.id
    LEFT JOIN reactions r ON p.id = r.post_id
    LEFT JOIN comments c ON p.id = c.post_id
    LEFT JOIN reactions ur ON p.id = ur.post_id AND ur.user_id = ?
    WHERE u.approved = 1 AND (p.file_type IN ('mp4', 'mov', 'avi') OR p.post_type = 'reel')
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([1]); // Using user_id 1 for test
$reels = $stmt->fetchAll();

echo "<h3>Reels Query Result: " . count($reels) . " found</h3>";
foreach ($reels as $reel) {
    echo "<p>Reel ID: {$reel['id']}, User: {$reel['username']}, File: {$reel['file_path']}</p>";
}
?>