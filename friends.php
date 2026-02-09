<?php
require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$pdo = get_db();

// Handle reactions
if ($_POST['reaction'] ?? false) {
    $post_id = $_POST['post_id'];
    $reaction = $_POST['reaction'];
    
    $stmt = $pdo->prepare("DELETE FROM reactions WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    
    if ($reaction !== 'remove') {
        $stmt = $pdo->prepare("INSERT INTO reactions (post_id, user_id, reaction_type) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id'], $reaction]);
    }
    
    header('Location: /friends');
    exit;
}

// Handle comments
if ($_POST['comment'] ?? false) {
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['post_id'], $_SESSION['user_id'], $_POST['comment']]);
    header('Location: /friends');
    exit;
}

// Get friends
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, MIN(f.created_at) as created_at
        FROM users u
        JOIN friends f ON (
            (f.user_id = ? AND f.friend_id = u.id AND f.status = 'accepted') OR
            (f.friend_id = ? AND f.user_id = u.id AND f.status = 'accepted')
        )
        GROUP BY u.id, u.username
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $friends = $stmt->fetchAll();
} catch (Exception $e) {
    $friends = [];
}

// Get posts from friends and yourself
$friend_ids = array_column($friends, 'id');
$friend_ids[] = $_SESSION['user_id'];

try {
    if (!empty($friend_ids)) {
        $placeholders = implode(',', array_fill(0, count($friend_ids), '?'));
        $stmt = $pdo->prepare("
            SELECT p.id, p.content, p.created_at, u.username, u.avatar, p.file_path, p.file_type,
                   0 as like_count, 0 as love_count, 0 as laugh_count, 0 as comment_count,
                   NULL as user_reaction
            FROM posts p 
            JOIN users u ON p.user_id = u.id
            WHERE u.approved = 1 AND p.user_id IN ($placeholders)
            ORDER BY p.created_at DESC
            LIMIT 20
        ");
        $stmt->execute($friend_ids);
        $friend_posts = $stmt->fetchAll();
    } else {
        $friend_posts = [];
    }
} catch (Exception $e) {
    $friend_posts = [];
}

$page_title = 'My Friends - OSRG Connect';
require_once 'header.php';
?>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <div style="display: flex; gap: 20px;">
        <!-- Friends List -->
        <div style="width: 300px;">
            <div style="background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3>Friends List</h3>
                <?php if ($friends): ?>
                    <?php foreach ($friends as $friend): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee;">
                        <div>
                            <div style="font-weight: bold;"><?= htmlspecialchars($friend['username']) ?></div>
                            <div style="color: #666; font-size: 12px;">Friends since <?= date('M j, Y', strtotime($friend['created_at'])) ?></div>
                        </div>
                        <span style="color: #4caf50; font-size: 18px;">✓</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #666; padding: 20px;">
                        No friends yet.<br>
                        <a href="users.php" style="color: #1877f2;">Find some friends!</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Friends Feed -->
        <div style="flex: 1;">
            <div style="background: #1877f2; color: white; padding: 15px; text-align: center; margin-bottom: 20px; border-radius: 8px;">
                <h1>Friends Feed</h1>
            </div>

            <?php if ($friend_posts): ?>
                <?php foreach ($friend_posts as $post): ?>
                <div style="background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <?php if ($post['avatar']): ?>
                            <?php 
                            $is_local_img = (strpos($post['avatar'], 'sp_avatars/') === 0 || strpos($post['avatar'], 'avatars/') === 0 || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $post['avatar']));
                            $avatar_url = $is_local_img ? 'serve_asset.php?file=' . basename($post['avatar']) : $post['avatar'];
                            ?>
                            <?php if ($is_local_img || strpos($post['avatar'], 'http') === 0): ?>
                                <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <span style="font-size: 30px;"><?= htmlspecialchars($post['avatar']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="font-size: 30px;">👤</span>
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($post['username']) ?></strong>
                    </div>
                    
                    <?php
                    try {
                        $processed = process_content_with_links($post['content']);
                        echo '<div>' . $processed['content'] . '</div>';
                        
                        if (!empty($processed['previews'])): 
                            foreach ($processed['previews'] as $preview): ?>
                            <div style="border: 1px solid #e1e5e9; border-radius: 8px; margin: 10px 0; overflow: hidden; max-width: 400px;">
                                <?php if ($preview['image']): ?>
                                <img src="<?= htmlspecialchars($preview['image']) ?>" alt="Preview" style="width: 100%; height: 200px; object-fit: cover;">
                                <?php endif; ?>
                                <div style="padding: 12px;">
                                    <div style="font-weight: bold; margin-bottom: 5px; color: #1d2129;"><?= htmlspecialchars($preview['title']) ?></div>
                                    <?php if ($preview['description']): ?>
                                    <div style="color: #606770; font-size: 14px; margin-bottom: 8px;"><?= htmlspecialchars(substr($preview['description'], 0, 100)) ?>...</div>
                                    <?php endif; ?>
                                    <div style="color: #8a8d91; font-size: 12px; text-transform: uppercase;"><?= parse_url($preview['url'], PHP_URL_HOST) ?></div>
                                </div>
                            </div>
                            <?php endforeach;
                        endif;
                    } catch (Exception $e) {
                        echo '<div>' . nl2br(htmlspecialchars($post['content'])) . '</div>';
                    }
                    ?>
                    
                    <?php if ($post['file_path']): ?>
                    <div style="margin: 10px 0;">
                        <?php if ($post['file_type'] == 'mp4'): ?>
                            <video controls style="width: 100%; max-width: 100%;">
                                <source src="serve_asset.php?file=<?= htmlspecialchars(basename($post['file_path'])) ?>" type="video/mp4">
                            </video>
                        <?php elseif ($post['file_type'] == 'mp3'): ?>
                            <audio controls preload="metadata" style="width: 100%; display: block;">
                                <source src="serve_asset.php?file=<?= htmlspecialchars(basename($post['file_path'])) ?>" type="audio/mpeg">
                            </audio>
                        <?php elseif (in_array($post['file_type'], ['png', 'jpg', 'jpeg'])): ?>
                            <img src="serve_asset.php?file=<?= htmlspecialchars(basename($post['file_path'])) ?>" alt="Image" style="max-width: 100%; border-radius: 8px;">
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <small style="color: #666;"><?= date('M j, H:i', strtotime($post['created_at'])) ?></small>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <p style="text-align: center; color: #666; padding: 20px;">
                        No posts from friends yet.<br>
                        <a href="users.php" style="color: #1877f2;">Add some friends to see their posts!</a><br><br>

                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
