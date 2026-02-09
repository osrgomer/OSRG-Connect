<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id']) || !isset($_POST['content'])) {
    echo 'error';
    exit;
}

$pdo = get_db();

// Verify user owns the post
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->execute([$_POST['post_id']]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    echo 'error';
    exit;
}

// Update post content and set edited timestamp
$stmt = $pdo->prepare("UPDATE posts SET content = ?, edited_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
$stmt->execute([$_POST['content'], $_POST['post_id'], $_SESSION['user_id']]);

echo 'success';
?>