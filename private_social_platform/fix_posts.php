<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = get_db();

// Check if user is admin (OSRG)
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['username'] !== 'OSRG') {
    header('Location: index.php');
    exit;
}

$message = '';

// Fix corrupted posts
if ($_POST['fix_posts'] ?? false) {
    $stmt = $pdo->query("SELECT id, content FROM posts");
    $posts = $stmt->fetchAll();
    
    $fixed_count = 0;
    foreach ($posts as $post) {
        $original_content = $post['content'];
        
        // Check if content contains HTML tags (from WYSIWYG editor)
        if (strpos($original_content, '<p>') !== false || strpos($original_content, '</p>') !== false) {
            // Remove HTML tags and decode entities to get clean text
            $clean_content = html_entity_decode(strip_tags($original_content));
            // Remove extra whitespace and line breaks
            $clean_content = preg_replace('/\s+/', ' ', trim($clean_content));
            
            if (strlen($clean_content) > 0) {
                $stmt = $pdo->prepare("UPDATE posts SET content = ? WHERE id = ?");
                $stmt->execute([$clean_content, $post['id']]);
                $fixed_count++;
            }
        }
    }
    
    $message = "Fixed $fixed_count corrupted posts!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Posts - OSRG Connect</title>
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1Y8S6WHNH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-Y1Y8S6WHNH');
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .post { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0; }
        button { background: #1877f2; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .message { color: green; padding: 15px; background: #e8f5e8; border-radius: 5px; margin: 20px 0; }
        .warning { color: #d32f2f; padding: 15px; background: #ffebee; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="post">
            <h2>🔧 Fix Corrupted Posts</h2>
            <p>This tool will clean up posts that have corrupted HTML content from the WYSIWYG editor.</p>
            
            <?php if ($message): ?>
                <div class="message"><?= $message ?></div>
            <?php endif; ?>
            
            <div class="warning">
                <strong>Warning:</strong> This will remove HTML formatting from all posts and convert them to plain text. URLs will still work with link previews.
            </div>
            
            <form method="POST">
                <button type="submit" name="fix_posts" value="1" onclick="return confirm('Are you sure you want to fix all corrupted posts?')">
                    Fix All Corrupted Posts
                </button>
            </form>
            
            <p style="margin-top: 20px;">
                <a href="admin.php" style="color: #1877f2;">← Back to Admin Panel</a>
            </p>
        </div>
    </div>
</body>
</html>