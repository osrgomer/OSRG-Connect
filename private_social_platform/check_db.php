<?php
require_once 'config.php';
init_db();

$pdo = get_db();

echo "<h2>Database Diagnostic</h2>";

// Check all posts
echo "<h3>All Posts:</h3>";
$stmt = $pdo->query("SELECT id, user_id, content, file_path, file_type, created_at FROM posts ORDER BY id DESC LIMIT 10");
$posts = $stmt->fetchAll();
foreach ($posts as $post) {
    echo "ID: {$post['id']}, User: {$post['user_id']}, File: {$post['file_path']}, Time: {$post['created_at']}<br>";
}

// Check video posts specifically
echo "<h3>Video Posts:</h3>";
$stmt = $pdo->query("SELECT id, user_id, content, file_path, file_type, created_at FROM posts WHERE file_type IN ('mp4', 'mov', 'avi') ORDER BY id DESC");
$videos = $stmt->fetchAll();
foreach ($videos as $video) {
    echo "ID: {$video['id']}, User: {$video['user_id']}, File: {$video['file_path']}, Time: {$video['created_at']}<br>";
}

// Check database file permissions
echo "<h3>Database Info:</h3>";
$db_file = 'social_network.db';
if (file_exists($db_file)) {
    echo "DB file exists: YES<br>";
    echo "DB file size: " . filesize($db_file) . " bytes<br>";
    echo "DB file writable: " . (is_writable($db_file) ? 'YES' : 'NO') . "<br>";
    echo "DB file permissions: " . substr(sprintf('%o', fileperms($db_file)), -4) . "<br>";
} else {
    echo "DB file exists: NO<br>";
}

// Test insert
echo "<h3>Test Insert:</h3>";
try {
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, file_path, file_type) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([1, 'Test post', 'test.mp4', 'mp4']);
    $test_id = $pdo->lastInsertId();
    echo "Test insert: SUCCESS (ID: $test_id)<br>";
    
    // Check if it exists
    $check = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $check->execute([$test_id]);
    if ($check->fetch()) {
        echo "Test post still exists: YES<br>";
        // Clean up
        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$test_id]);
        echo "Test post cleaned up<br>";
    } else {
        echo "Test post still exists: NO (disappeared!)<br>";
    }
} catch (Exception $e) {
    echo "Test insert failed: " . $e->getMessage() . "<br>";
}
?>