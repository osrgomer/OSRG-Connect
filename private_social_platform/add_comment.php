<?php
require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id']) || !isset($_POST['comment'])) {
    exit;
}

$pdo = get_db();
$stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
$stmt->execute([$_POST['post_id'], $_SESSION['user_id'], $_POST['comment']]);

echo 'success';
?>