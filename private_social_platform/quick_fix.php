<?php
require_once 'config.php';

try {
    $pdo = get_db();
    
    // Add columns if missing
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN post_type TEXT DEFAULT 'post'");
    } catch (Exception $e) {}
    
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN reel_serial INTEGER");
    } catch (Exception $e) {}
    
    // Fix Post ID 12 again
    $stmt = $pdo->prepare("UPDATE posts SET post_type = 'reel', reel_serial = 1 WHERE id = 12");
    $stmt->execute();
    
    echo "✅ Fixed Post ID 12 as Reel #1";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>