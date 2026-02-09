<?php
require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id']) || !isset($_GET['post_id'])) {
    echo json_encode([]);
    exit;
}

$pdo = get_db();
$stmt = $pdo->prepare("SELECT c.content, c.created_at, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
$stmt->execute([$_GET['post_id']]);
$comments = $stmt->fetchAll();

foreach ($comments as &$comment) {
    $comment['created_at'] = date('M j, H:i', strtotime($comment['created_at']));
}

header('Content-Type: application/json');
echo json_encode($comments);
?>