<?php
require_once 'config.php';
$pdo = get_db();

echo "<h2>Debug: Checking Reels Data</h2>";

// Check all posts with video files
echo "<h3>All posts with video files:</h3>";
$stmt = $pdo->query("SELECT id, user_id, file_path, file_type, post_type, reel_serial, created_at FROM posts WHERE file_type IN ('mp4', 'mov', 'avi') ORDER BY created_at DESC");
$video_posts = $stmt->fetchAll();
echo "<pre>";
print_r($video_posts);
echo "</pre>";

// Check posts marked as reels
echo "<h3>Posts marked as reels:</h3>";
$stmt = $pdo->query("SELECT id, user_id, file_path, file_type, post_type, reel_serial, created_at FROM posts WHERE post_type = 'reel' ORDER BY created_at DESC");
$reel_posts = $stmt->fetchAll();
echo "<pre>";
print_r($reel_posts);
echo "</pre>";

// Check the exact query from reels.php
echo "<h3>Exact query from reels.php:</h3>";
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
$stmt->execute([1]); // Using user ID 1 for test
$reels = $stmt->fetchAll();
echo "<pre>";
print_r($reels);
echo "</pre>";

// Check users table
echo "<h3>Users (approved status):</h3>";
$stmt = $pdo->query("SELECT id, username, approved FROM users");
$users = $stmt->fetchAll();
echo "<pre>";
print_r($users);
echo "</pre>";
?>