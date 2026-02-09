<?php
require_once 'config.php';
require_once 'series_db.php';

echo "<h1>Restoring 'nomi@osrg.lol'</h1>";

try {
    $pdo = get_db();
    
    // 1. Check if Nomi is already in MySQL
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'nomi@osrg.lol'");
    $stmt->execute();
    $nomi_mysql = $stmt->fetch();
    
    if ($nomi_mysql) {
        echo "<h3 style='color:green'>✅ Nomi is ALREADY in the new database!</h3>";
        echo "ID: " . $nomi_mysql['id'] . "<br>";
        echo "Username: " . htmlspecialchars($nomi_mysql['username']) . "<br>";
        echo "Email: " . htmlspecialchars($nomi_mysql['email']) . "<br>";
    } else {
        echo "<p style='color:orange'>❌ Nomi is NOT in the new database yet.</p>";
        
        // 2. Check Old SQLite DB
        if (file_exists('private_social.db')) {
            $sqlite = new PDO('sqlite:private_social.db');
            $s_stmt = $sqlite->prepare("SELECT * FROM users WHERE email = 'nomi@osrg.lol'");
            $s_stmt->execute();
            $nomi_sqlite = $s_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($nomi_sqlite) {
                echo "<p style='color:blue'>found Nomi in old backup! Restoring now...</p>";
                
                // RESTORE USER
                $avatar_blob = null;
                if (!empty($nomi_sqlite['avatar']) && file_exists($nomi_sqlite['avatar'])) {
                    $avatar_blob = file_get_contents($nomi_sqlite['avatar']);
                }
                
                $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash, approved, timezone, email_notifications, avatar, avatar_content, bio, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([
                    $nomi_sqlite['username'], 
                    $nomi_sqlite['email'], 
                    $nomi_sqlite['password_hash'] ?? $nomi_sqlite['password'], 
                    $nomi_sqlite['approved'] ?? 1,
                    $nomi_sqlite['timezone'] ?? 'Europe/London',
                    $nomi_sqlite['email_notifications'] ?? 0,
                    $nomi_sqlite['avatar'],
                    $avatar_blob,
                    $nomi_sqlite['bio'] ?? null,
                    $nomi_sqlite['created_at']
                ]);
                $new_id = $pdo->lastInsertId();
                echo "<h3 style='color:green'>✅ Successfully restored user Nomi! (New ID: $new_id)</h3>";
                
                // RESTORE POSTS
                $p_stmt = $sqlite->prepare("SELECT * FROM posts WHERE user_id = ?");
                $p_stmt->execute([$nomi_sqlite['id']]);
                $posts = $p_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($posts) {
                    echo "<p>Restoring " . count($posts) . " posts...</p>";
                    $p_ins = $pdo->prepare("INSERT INTO posts (user_id, content, file_path, file_type, file_content, post_type, reel_serial, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($posts as $post) {
                        $file_blob = null;
                        if (!empty($post['file_path']) && file_exists($post['file_path'])) {
                            $file_blob = file_get_contents($post['file_path']);
                        }
                        $p_ins->execute([
                            $new_id,
                            $post['content'],
                            $post['file_path'],
                            $post['file_type'],
                            $file_blob,
                            $post['post_type'] ?? 'post',
                            $post['reel_serial'] ?? null,
                            $post['created_at']
                        ]);
                    }
                    echo "<p style='color:green'>✅ Restored posts successfully!</p>";
                } else {
                    echo "<p>No posts found for this user.</p>";
                }
                
            } else {
                echo "<h3 style='color:red'>❌ Could not find Nomi in the old backup either!</h3>";
            }
        } else {
            echo "<h3 style='color:red'>❌ Old backup file (private_social.db) is missing!</h3>";
        }
    }

} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>
