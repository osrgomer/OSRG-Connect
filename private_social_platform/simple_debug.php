<?php
echo "<h2>Simple Debug</h2>";

try {
    require_once 'config.php';
    echo "<p>✅ Config loaded</p>";
    
    init_db();
    echo "<p>✅ Database initialized</p>";
    
    $pdo = get_db();
    echo "<p>✅ Database connection established</p>";
    
    // Check if posts table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='posts'");
    $table_exists = $stmt->fetch();
    echo "<p>Posts table exists: " . ($table_exists ? "YES" : "NO") . "</p>";
    
    if ($table_exists) {
        // Count all posts
        $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
        $total_posts = $stmt->fetchColumn();
        echo "<p>Total posts: $total_posts</p>";
        
        // Check for video files
        $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE file_type IN ('mp4', 'mov', 'avi')");
        $video_posts = $stmt->fetchColumn();
        echo "<p>Video posts: $video_posts</p>";
        
        // Check for reel type
        $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE post_type = 'reel'");
        $reel_posts = $stmt->fetchColumn();
        echo "<p>Reel posts: $reel_posts</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>