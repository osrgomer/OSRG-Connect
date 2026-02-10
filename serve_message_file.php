<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(403);
    exit('Access denied');
}

$pdo = get_db();
$message_id = $_GET['id'];

// Get message and verify user has access to it
$stmt = $pdo->prepare("
    SELECT file_content, file_type, file_path
    FROM messages 
    WHERE id = ? AND (sender_id = ? OR receiver_id = ?)
");
$stmt->execute([$message_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$message = $stmt->fetch();

if (!$message || !$message['file_content']) {
    http_response_code(404);
    exit('File not found');
}

// Set appropriate headers
header('Content-Type: ' . $message['file_type']);
header('Content-Length: ' . strlen($message['file_content']));

// For downloads (non-media files)
$file_ext = strtolower(pathinfo($message['file_path'], PATHINFO_EXTENSION));
if (in_array($file_ext, ['txt', 'md'])) {
    header('Content-Disposition: attachment; filename="' . basename($message['file_path']) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($message['file_path']) . '"');
}

echo $message['file_content'];
?>
