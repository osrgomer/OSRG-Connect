<?php
session_start();

// Simulate logged in user
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'omersr12@gmail.com';

echo "<h1>Avatar Upload Test</h1>";

// Check directory
$uploadDir = __DIR__ . '/uploads/avatars/';
echo "<p>Upload dir: $uploadDir</p>";
echo "<p>Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "</p>";
echo "<p>Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "</p>";

// List files
echo "<h2>Current files:</h2><ul>";
$files = scandir($uploadDir);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

// Test file creation
$testFile = $uploadDir . 'test_' . time() . '.txt';
if (file_put_contents($testFile, 'test')) {
    echo "<p style='color:green'>✓ Can write files to directory</p>";
    unlink($testFile);
} else {
    echo "<p style='color:red'>✗ Cannot write files to directory</p>";
}
?>

<h2>Upload Test</h2>
<form action="api_avatar.php?action=upload" method="POST" enctype="multipart/form-data">
    <input type="file" name="avatar" accept="image/*" required>
    <button type="submit">Upload</button>
</form>
