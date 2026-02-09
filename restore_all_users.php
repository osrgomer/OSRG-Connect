<?php
require_once 'config.php';
require_once 'series_db.php';

echo "<h1>Restoring All Old Users</h1>";

if (!file_exists('private_social.db')) {
    die("<h3>❌ Error: private_social.db not found. Cannot restore.</h3>");
}

try {
    $sqlite = new PDO('sqlite:private_social.db');
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo = get_db();

    // Get all users from old DB
    $stmt = $sqlite->query("SELECT * FROM users");
    $old_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Found " . count($old_users) . " users in backup. Processing...</p>";

    foreach ($old_users as $u) {
        // 1. Check if user exists in new DB
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check->execute([$u['email'], $u['username']]);
        $existing = $check->fetch();

        $user_id = null;
        $is_new = false;

        if (!$existing) {
            // RESTORE USER
            $avatar_blob = null;
            if (!empty($u['avatar']) && file_exists($u['avatar'])) {
                $avatar_blob = file_get_contents($u['avatar']);
            }

            $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash, approved, timezone, email_notifications, avatar, avatar_content, bio, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([
                $u['username'],
                $u['email'],
                $u['password_hash'] ?? $u['password'], // Fallback if hash missing
                $u['approved'] ?? 1,
                $u['timezone'] ?? 'Europe/London',
                $u['email_notifications'] ?? 0,
                $u['avatar'],
                $avatar_blob,
                $u['bio'] ?? null,
                $u['created_at']
            ]);
            $user_id = $pdo->lastInsertId();
            $is_new = true;
            echo "<div style='color:green'>✅ Restored user: <strong>" . htmlspecialchars($u['username']) . "</strong> (ID: $user_id)</div>";
        } else {
            $user_id = $existing['id'];
            echo "<div style='color:gray'>User exists: <strong>" . htmlspecialchars($u['username']) . "</strong> (Skipping create)</div>";
            
            // Optional: Update bio/avatar if missing in current DB
            // (You can uncomment this if you want to force-update existing users' missing data)
            /*
            $avatar_blob = null;
            if (!empty($u['avatar']) && file_exists($u['avatar'])) {
                $avatar_blob = file_get_contents($u['avatar']);
            }
            $upd = $pdo->prepare("UPDATE users SET bio = COALESCE(bio, ?), avatar_content = COALESCE(avatar_content, ?) WHERE id = ?");
            $upd->execute([$u['bio'], $avatar_blob, $user_id]);
            */
        }

        // 2. RESTORE POSTS for this user
        // We need to fetch posts from SQLite for the OLD user ID
        $p_stmt = $sqlite->prepare("SELECT * FROM posts WHERE user_id = ?");
        $p_stmt->execute([$u['id']]);
        $posts = $p_stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($posts) {
            $count = 0;
            foreach ($posts as $post) {
                // Check if post already exists to avoid duplicates
                // Matching by content and created_at and user_id
                $dup = $pdo->prepare("SELECT id FROM posts WHERE user_id = ? AND content = ? AND created_at = ?");
                $dup->execute([$user_id, $post['content'], $post['created_at']]);
                
                if (!$dup->fetch()) {
                    $file_blob = null;
                    if (!empty($post['file_path']) && file_exists($post['file_path'])) {
                        $file_blob = file_get_contents($post['file_path']);
                    }
                    
                    $p_ins = $pdo->prepare("INSERT INTO posts (user_id, content, file_path, file_type, file_content, post_type, reel_serial, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $p_ins->execute([
                        $user_id,
                        $post['content'],
                        $post['file_path'],
                        $post['file_type'],
                        $file_blob,
                        $post['post_type'] ?? 'post',
                        $post['reel_serial'] ?? null,
                        $post['created_at']
                    ]);
                    $count++;
                }
            }
            if ($count > 0) echo "<div style='margin-left:20px; color:blue'>↳ Restored $count missing posts.</div>";
        }
    }
    
    echo "<h2>🎉 Restoration Complete!</h2>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>
