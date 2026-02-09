<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['friend'])) {
    http_response_code(401);
    exit;
}

$friend_id = $_GET['friend'];
$pdo = get_db();

// Get message count between users
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
");
$stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']]);
$count = $stmt->fetch()['count'];

// Get latest message
$stmt = $pdo->prepare("
    SELECT m.content, u.username 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id'], $friend_id, $friend_id, $_SESSION['user_id']]);
$latest = $stmt->fetch();

$response = [
    'count' => (int)$count,
    'latest_message' => $latest ? $latest['username'] . ': ' . substr($latest['content'], 0, 50) . '...' : ''
];

header('Content-Type: application/json');
echo json_encode($response);
?>