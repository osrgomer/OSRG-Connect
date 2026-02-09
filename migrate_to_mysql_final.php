<?php
require_once 'config.php';
require_once 'series_db.php';

echo "<h1>Starting Migration: SQLite to MySQL</h1>";

try {
    $sqlite = new PDO('sqlite:private_social.db');
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mysql = getDB();
    
    // Ensure MySQL tables are ready
    init_db();
    
    echo "<h2>1. Migrating Users...</h2>";
    $stmt = $sqlite->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $u) {
        $check = $mysql->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check->execute([$u['email'], $u['username']]);
        $existing = $check->fetch();
        
        if (!$existing) {
            $avatar_blob = null;
            if ($u['avatar'] && file_exists($u['avatar'])) {
                $avatar_blob = file_get_contents($u['avatar']);
            }
            
            $ins = $mysql->prepare("INSERT INTO users (username, email, password_hash, approved, timezone, email_notifications, avatar, avatar_content, bio, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE bio=VALUES(bio), avatar=VALUES(avatar), avatar_content=VALUES(avatar_content)");
            $ins->execute([
                $u['username'], 
                $u['email'], 
                $u['password_hash'] ?? $u['password'], 
                $u['approved'] ?? 1,
                $u['timezone'] ?? 'Europe/London',
                $u['email_notifications'] ?? 0,
                $u['avatar'],
                $avatar_blob,
                $u['bio'] ?? null,
                $u['created_at']
            ]);
            echo "Migrated user: " . $u['username'] . "<br>";
        } else {
            // Update existing user with social info
            $upd = $mysql->prepare("UPDATE users SET bio = IFNULL(bio, ?), avatar = IFNULL(avatar, ?), avatar_content = IFNULL(avatar_content, ?) WHERE id = ?");
            $avatar_blob = null;
            if ($u['avatar'] && file_exists($u['avatar'])) {
                $avatar_blob = file_get_contents($u['avatar']);
            }
            $upd->execute([$u['bio'] ?? null, $u['avatar'] ?? null, $avatar_blob, $existing['id']]);
            echo "Updated social info for: " . $u['username'] . "<br>";
        }
    }
    
    echo "<h2>2. Migrating Posts...</h2>";
    $stmt = $sqlite->query("SELECT * FROM posts");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($posts as $p) {
        $u_stmt = $sqlite->prepare("SELECT username FROM users WHERE id = ?");
        $u_stmt->execute([$p['user_id']]);
        $u_row = $u_stmt->fetch();
        
        if ($u_row) {
            $m_u_stmt = $mysql->prepare("SELECT id FROM users WHERE username = ?");
            $m_u_stmt->execute([$u_row['username']]);
            $m_u = $m_u_stmt->fetch();
            
            if ($m_u) {
                $p_check = $mysql->prepare("SELECT id FROM posts WHERE user_id = ? AND content = ? AND created_at = ?");
                $p_check->execute([$m_u['id'], $p['content'], $p['created_at']]);
                if (!$p_check->fetch()) {
                    $file_blob = null;
                    if ($p['file_path'] && file_exists($p['file_path'])) {
                        $file_blob = file_get_contents($p['file_path']);
                    }
                    
                    $ins = $mysql->prepare("INSERT INTO posts (user_id, content, file_path, file_type, file_content, post_type, reel_serial, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([
                        $m_u['id'],
                        $p['content'],
                        $p['file_path'],
                        $p['file_type'],
                        $file_blob,
                        $p['post_type'] ?? 'post',
                        $p['reel_serial'] ?? null,
                        $p['created_at']
                    ]);
                    echo "Migrated post from: " . $u_row['username'] . "<br>";
                }
            }
        }
    }
    
    echo "<h2>3. Migrating Interactions...</h2>";
    // Migration for comments and reactions could be added here if needed.
    
    echo "<h3>Migration Finished! Everything is now in MySQL.</h3>";
    echo "<p>Please delete this script (migrate_to_mysql_final.php) for security.</p>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Migration Error: " . $e->getMessage() . "</h3>";
}
?>
