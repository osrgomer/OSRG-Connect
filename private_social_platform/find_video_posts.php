<?php
require_once 'config.php';
init_db();
$pdo = get_db();

echo "<h2>Finding Video Posts</h2>";

// Check all posts with file_path
$stmt = $pdo->query("SELECT id, user_id, content, file_path, file_type, post_type, created_at FROM posts WHERE file_path IS NOT NULL ORDER BY created_at DESC");
$posts_with_files = $stmt->fetchAll();

echo "<h3>Posts with files (" . count($posts_with_files) . "):</h3>";
foreach ($posts_with_files as $post) {
    echo "<p>ID: {$post['id']}, Type: {$post['post_type']}, File: {$post['file_path']}, FileType: {$post['file_type']}</p>";
}

// Check specifically for video file types
$stmt = $pdo->query("SELECT id, user_id, content, file_path, file_type, post_type, created_at FROM posts WHERE file_type IN ('mp4', 'mov', 'avi') ORDER BY created_at DESC");
$video_posts = $stmt->fetchAll();

echo "<h3>Video Posts (" . count($video_posts) . "):</h3>";
foreach ($video_posts as $post) {
    echo "<p>ID: {$post['id']}, Type: {$post['post_type']}, File: {$post['file_path']}, FileType: {$post['file_type']}</p>";
    
    // Try to convert this to a reel
    if ($post['post_type'] !== 'reel') {
        try {
            $update_stmt = $pdo->prepare("UPDATE posts SET post_type = 'reel', reel_serial = 1 WHERE id = ?");
            $update_stmt->execute([$post['id']]);
            echo "<p>✅ Converted post {$post['id']} to reel</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error converting: " . $e->getMessage() . "</p>";
        }
    }
}

// Final check
$stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE post_type = 'reel'");
$reel_count = $stmt->fetchColumn();
echo "<h3>Total reels now: $reel_count</h3>";
?>