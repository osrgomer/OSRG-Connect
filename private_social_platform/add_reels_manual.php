<?php
require_once 'config.php';
$pdo = get_db();

// Add missing columns if they don't exist
try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN post_type TEXT DEFAULT 'post'");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN reel_serial INTEGER");
} catch (Exception $e) {}

echo "<h2>Manual Reel Addition</h2>";

if ($_POST['add_reel'] ?? false) {
    $file_path = $_POST['file_path'];
    $caption = $_POST['caption'];
    $user_id = $_POST['user_id'];
    
    // Get file extension
    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    // Get next serial number
    $serial_stmt = $pdo->query("SELECT COALESCE(MAX(reel_serial), 0) + 1 as next_serial FROM posts WHERE post_type = 'reel'");
    $next_serial = $serial_stmt->fetchColumn();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, file_path, file_type, post_type, reel_serial, created_at) VALUES (?, ?, ?, ?, 'reel', ?, datetime('now'))");
        $stmt->execute([$user_id, $caption, $file_path, $file_ext, $next_serial]);
        echo "<p style='color: green;'>✅ Added reel #$next_serial: $file_path</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Get users for dropdown
$users = $pdo->query("SELECT id, username FROM users WHERE approved = 1")->fetchAll();
?>

<form method="POST">
    <div style="margin: 10px 0;">
        <label>User:</label>
        <select name="user_id" required>
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div style="margin: 10px 0;">
        <label>File Path (e.g., uploads/reel_123456.mp4):</label>
        <input type="text" name="file_path" required style="width: 300px;" placeholder="uploads/filename.mp4">
    </div>
    
    <div style="margin: 10px 0;">
        <label>Caption:</label>
        <textarea name="caption" style="width: 300px; height: 60px;" placeholder="Optional caption"></textarea>
    </div>
    
    <button type="submit" name="add_reel" value="1">Add Reel</button>
</form>

<hr>
<h3>Current Reels in Database:</h3>
<?php
$reels = $pdo->query("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.post_type = 'reel' ORDER BY p.created_at DESC")->fetchAll();
foreach ($reels as $reel) {
    echo "<p>Reel #{$reel['reel_serial']}: {$reel['file_path']} by {$reel['username']}</p>";
}
?>