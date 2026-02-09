<?php
require_once 'config.php';

$pdo = get_db();

// Check if file columns exist
try {
    $stmt = $pdo->query("SELECT file_path, file_type FROM posts LIMIT 1");
    echo "✓ File columns exist in posts table<br>";
} catch (Exception $e) {
    echo "✗ File columns missing: " . $e->getMessage() . "<br>";
}

// Check posts with files
$stmt = $pdo->query("SELECT id, content, file_path, file_type FROM posts ORDER BY created_at DESC LIMIT 5");
$posts = $stmt->fetchAll();

echo "<h3>Recent Posts Debug:</h3>";
foreach ($posts as $post) {
    echo "Post ID: " . $post['id'] . "<br>";
    echo "Content: " . htmlspecialchars($post['content']) . "<br>";
    echo "File Path: " . ($post['file_path'] ?? 'NULL') . "<br>";
    echo "File Type: " . ($post['file_type'] ?? 'NULL') . "<br>";
    
    if ($post['file_path']) {
        echo "File exists: " . (file_exists($post['file_path']) ? 'YES' : 'NO') . "<br>";
    }
    echo "<hr>";
}

// Check uploads directory
echo "<h3>Uploads Directory:</h3>";
if (is_dir('uploads')) {
    echo "✓ Uploads directory exists<br>";
    echo "Writable: " . (is_writable('uploads') ? 'YES' : 'NO') . "<br>";
    $files = scandir('uploads');
    echo "Files: " . implode(', ', array_filter($files, function($f) { return $f !== '.' && $f !== '..'; })) . "<br>";
} else {
    echo "✗ Uploads directory missing<br>";
}
?>