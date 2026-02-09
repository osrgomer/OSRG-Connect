<?php
header('Content-Type: application/json');

try {
    require_once 'config.php';
    init_db();
    
    $pdo = get_db();
    
    // Get post count for notifications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM posts WHERE post_type IS NULL OR post_type = 'post'");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    
    // Get latest post for notification
    $stmt = $pdo->prepare("SELECT p.content, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE (p.post_type IS NULL OR p.post_type = 'post') ORDER BY p.created_at DESC LIMIT 1");
    $stmt->execute();
    $latest = $stmt->fetch();
    
    $latest_post = $latest ? $latest['username'] . ': ' . substr($latest['content'], 0, 50) . '...' : 'New post available';
    
    echo json_encode([
        'count' => $count,
        'latest_post' => $latest_post
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'count' => 0,
        'latest_post' => 'Error loading posts'
    ]);
}
?>