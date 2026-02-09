<?php
require_once 'config.php';
init_db();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$pdo = get_db();

// Handle new reel creation
if (isset($_POST['content'])) {
    $file_path = null;
    $file_type = null;
    $upload_error = null;
    
    // Handle file upload
    if (isset($_FILES['file'])) {
        if ($_FILES['file']['error'] !== 0) {
            $upload_error = 'Upload failed. Error code: ' . $_FILES['file']['error'];
        } else {
            $allowed = ['mp4', 'mov', 'avi'];
            $filename = $_FILES['file']['name'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $file_size = $_FILES['file']['size'];
            $max_size = 10 * 1024 * 1024; // 10MB limit
            
            if ($file_size <= $max_size && in_array($file_ext, $allowed)) {
                // Create uploads directory with proper permissions
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                    chmod('uploads', 0777);
                }
                
                $new_filename = 'reel_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = 'uploads/' . $new_filename;
                
                // Set proper permissions for the uploads directory
                if (is_dir('uploads')) {
                    chmod('uploads', 0777);
                }
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
                    $file_path = $upload_path;
                    $file_type = $file_ext;
                    // Set file permissions
                    chmod($upload_path, 0644);
                } else {
                    $upload_error = 'Failed to save uploaded file. Temp: ' . $_FILES['file']['tmp_name'] . ', Target: ' . $upload_path . ', Dir writable: ' . (is_writable('uploads') ? 'yes' : 'no');
                }
            } else {
                $upload_error = 'Invalid file type or size too large.';
            }
        }
    } else {
        $upload_error = 'No file uploaded.';
    }
    
    // Require video file for reels
    if ($file_path && in_array($file_type, ['mp4', 'mov', 'avi'])) {
        // First ensure required columns exist
        try {
            $pdo->exec("ALTER TABLE posts ADD COLUMN post_type TEXT DEFAULT 'post'");
        } catch (Exception $e) {
            // Column already exists, ignore
        }
        
        try {
            $pdo->exec("ALTER TABLE posts ADD COLUMN reel_serial INTEGER");
        } catch (Exception $e) {
            // Column already exists, ignore
        }
        
        try {
            // Get next serial number for reels
            $serial_stmt = $pdo->query("SELECT COALESCE(MAX(reel_serial), 0) + 1 as next_serial FROM posts WHERE post_type = 'reel'");
            $next_serial = $serial_stmt->fetchColumn();
            
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, file_path, file_type, post_type, reel_serial) VALUES (?, ?, ?, ?, 'reel', ?)");
            $result = $stmt->execute([$_SESSION['user_id'], $_POST['content'], $file_path, $file_type, $next_serial]);
            $post_id = $pdo->lastInsertId();
            $_SESSION['reel_success'] = "Reel #$next_serial created successfully!";
        } catch (Exception $e) {
            $_SESSION['reel_error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['reel_error'] = 'Upload failed - File: ' . ($file_path ?: 'none') . ', Type: ' . ($file_type ?: 'none') . ', Error: ' . ($upload_error ?: 'unknown');
    }
    
    header('Location: /reels');
    exit;
}

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
    
    echo json_encode(['status' => 'success']);
    exit;
}

// Handle comments
if ($_POST['comment'] ?? false) {
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['post_id'], $_SESSION['user_id'], $_POST['comment']]);
    header('Location: /reels');
    exit;
}

// Get all reels
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.content, p.created_at, u.username, u.avatar, p.file_path, p.file_type, p.reel_serial,
               COUNT(DISTINCT CASE WHEN r.reaction_type = 'like' THEN r.id END) as like_count,
               COUNT(DISTINCT CASE WHEN r.reaction_type = 'love' THEN r.id END) as love_count,
               COUNT(DISTINCT CASE WHEN r.reaction_type = 'laugh' THEN r.id END) as laugh_count,
               COUNT(DISTINCT c.id) as comment_count,
               ur.reaction_type as user_reaction
        FROM posts p 
        JOIN users u ON p.user_id = u.id
        LEFT JOIN reactions r ON p.id = r.post_id
        LEFT JOIN comments c ON p.id = c.post_id
        LEFT JOIN reactions ur ON p.id = ur.post_id AND ur.user_id = ?
        WHERE u.approved = 1 AND (p.file_type IN ('mp4', 'mov', 'avi') OR p.post_type = 'reel')
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $reels = $stmt->fetchAll();
} catch (Exception $e) {
    $reels = [];
}

$page_title = 'Reels - OSRG Connect';
$additional_css = '
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { overflow: hidden; font-family: Roboto, Arial, sans-serif; }
    .reel-container { height: 100vh; width: 100vw; position: fixed; top: 0; left: 0; overflow-y: scroll; scroll-snap-type: y mandatory; background-color: #000; }
    .reel-item { height: 100vh; width: 100vw; position: relative; overflow: hidden; background-color: #000; scroll-snap-align: start; display: flex; align-items: center; justify-content: center; }
    .reel-video { 
        width: 70vw; 
        height: 70vh; 
        max-width: 900px; 
        max-height: 600px; 
        object-fit: contain; 
        display: block; 
        margin: auto; 
        border-radius: 18px; 
        background: #222; 
        box-shadow: 0 8px 32px rgba(0,0,0,0.4); 
        position: relative; 
        z-index: 1; 
    }
    .reel-ui-overlay { position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 70vw; max-width: 900px; height: auto; display: flex; flex-direction: row; justify-content: space-between; align-items: flex-end; padding: 16px; color: #fff; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5); z-index: 2; }
    .user-info { display: flex; flex-direction: column; justify-content: flex-end; flex: 1; margin-bottom: 220px; margin-left: 40px; }
    .user-handle { font-weight: bold; font-size: 16px; margin-bottom: 4px; }
    .reel-serial { font-size: 12px; color: rgba(255,255,255,0.7); margin-bottom: 4px; }
    .caption { font-size: 14px; margin-bottom: 8px; max-height: 4.5em; overflow: hidden; text-overflow: ellipsis; }
    .action-bar { display: flex; flex-direction: column; gap: 15px; text-align: center; align-items: center; margin-right: -60px; margin-bottom: 120px; }
    .action-bar button { background: none; border: none; color: #fff; cursor: pointer; font-size: 24px; padding: 8px; }
    .action-bar button span { font-size: 12px; display: block; margin-top: 4px; }
    .action-bar button.active { color: #ff3040; }
    .editing-canvas-layer { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10; pointer-events: none; }
    .user-added-element { position: absolute; font-size: 40px; color: white; font-family: sans-serif; padding: 5px 10px; cursor: grab; pointer-events: auto; line-height: 1.2; }
    .comment-modal { position: fixed; bottom: 0; left: 0; right: 0; background: white; border-radius: 16px 16px 0 0; max-height: 70vh; transform: translateY(100%); transition: transform 0.3s; z-index: 1000; font-family: Roboto, Arial, sans-serif; }
    .comment-modal.active { transform: translateY(0); }
    .comment-header { padding: 12px; border-bottom: 1px solid #eee; text-align: center; font-weight: 600; font-size: 12px; }
    .comment-list { max-height: 50vh; overflow-y: auto; padding: 8px; }
    .comment-item { padding: 8px; border-bottom: 1px solid #f0f0f0; font-size: 11px; }
    .comment-form { padding: 12px; border-top: 1px solid #eee; display: flex; gap: 8px; }
    .comment-input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 16px; font-size: 11px; font-family: Roboto, Arial, sans-serif; }
    .comment-submit { padding: 8px 16px; background: #1877f2; color: white; border: none; border-radius: 16px; font-size: 10px; font-weight: 600; }
    .create-reel { position: fixed; top: 70px; left: 16px; right: 16px; background: linear-gradient(135deg, #ff6b6b, #4ecdc4); color: white; padding: 16px; border-radius: 12px; z-index: 1000; max-height: 280px; overflow-y: auto; font-family: Roboto, Arial, sans-serif; }
    .create-reel h2 { font-size: 14px; font-weight: 600; margin-bottom: 12px; }
    .create-reel textarea { font-family: Roboto, Arial, sans-serif; font-size: 11px; }
    .create-reel input { font-family: Roboto, Arial, sans-serif; font-size: 10px; }
    .create-reel button { font-family: Roboto, Arial, sans-serif; font-size: 11px; font-weight: 600; }
    .create-reel small { font-size: 9px; }
    .toggle-create { position: fixed; top: 64px; right: 80px; background: #ff6b6b; color: white; border: none; padding: 8px 12px; border-radius: 20px; z-index: 1001; cursor: pointer; font-size: 12px; }
    .create-reel.hidden { display: none; }
    .nav { position: fixed; top: 0; left: 0; right: 0; z-index: 200; }
';

require_once 'header.php';
?>

<button class="toggle-create" onclick="toggleCreateForm()">➕</button>

<div class="reel-container">
    <div class="create-reel hidden" id="createForm">
        <h2>🎬 Create a Reel</h2>
        <?php if (isset($_SESSION['reel_success'])): ?>
            <div style="background: rgba(0,255,0,0.2); color: white; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                ✅ <?= htmlspecialchars($_SESSION['reel_success']) ?>
            </div>
            <?php unset($_SESSION['reel_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['reel_error'])): ?>
            <div style="background: rgba(255,0,0,0.2); color: white; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                ❌ <?= htmlspecialchars($_SESSION['reel_error']) ?>
            </div>
            <?php unset($_SESSION['reel_error']); ?>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="reelForm">
            <div style="margin: 15px 0;">
                <textarea name="content" placeholder="Add a caption to your reel..." style="width: 100%; padding: 10px; border: none; border-radius: 8px; min-height: 80px; resize: vertical;"></textarea>
            </div>
            <div style="margin: 15px 0;">
                <input type="file" name="file" accept=".mp4,.mov,.avi" required style="width: 100%; padding: 10px; background: white; border-radius: 8px; color: black;" id="videoFile">
                <small style="color: rgba(255,255,255,0.8); display: block; margin-top: 5px;">Upload Video: MP4, MOV, AVI (max 10MB)</small>
            </div>
            <button type="submit" id="submitBtn" style="background: rgba(255,255,255,0.2); border: 2px solid white; color: white; padding: 12px 25px; border-radius: 25px; font-weight: bold;">Create Reel</button>
            <div id="uploadStatus" style="margin-top: 15px; padding: 10px; border-radius: 8px; display: none;"></div>
        </form>
    </div>


    
    <?php if ($reels): ?>
        <?php foreach ($reels as $reel): ?>
        <div class="reel-item">
            <video class="reel-video" controls>
                <source src="serve_video.php?file=<?= htmlspecialchars(basename($reel['file_path'])) ?>" type="video/<?= $reel['file_type'] ?>">
            </video>
            
            <div class="reel-ui-overlay">
                <div class="user-info">
                    <div class="user-handle"><?= htmlspecialchars($reel['username']) ?></div>
                    <div class="reel-serial">Reel #<?= $reel['reel_serial'] ?: $reel['id'] ?></div>
                    <?php if ($reel['content']): ?>
                        <div class="caption"><?= nl2br(htmlspecialchars($reel['content'])) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="action-bar">
                    <button onclick="toggleReaction(<?= $reel['id'] ?>, 'like')" class="<?= $reel['user_reaction'] === 'like' ? 'active' : '' ?>" id="like-<?= $reel['id'] ?>">
                        👍<span id="like-count-<?= $reel['id'] ?>"><?= $reel['like_count'] ?: '' ?></span>
                    </button>
                    <button onclick="toggleReaction(<?= $reel['id'] ?>, 'love')" class="<?= $reel['user_reaction'] === 'love' ? 'active' : '' ?>">
                        ❤️<span><?= $reel['love_count'] ?: '' ?></span>
                    </button>
                    <button onclick="toggleReaction(<?= $reel['id'] ?>, 'laugh')" class="<?= $reel['user_reaction'] === 'laugh' ? 'active' : '' ?>">
                        😂<span><?= $reel['laugh_count'] ?: '' ?></span>
                    </button>
                    <button onclick="openComments(<?= $reel['id'] ?>)">
                        💬<span><?= $reel['comment_count'] ?: '' ?></span>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="reel-item" style="display: flex; align-items: center; justify-content: center; color: white; text-align: center;">
            <div>
                <h3>No reels yet! 🎬</h3>
                <p>Be the first to create a reel and share it with everyone!</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Comment Modal -->
<div id="commentModal" class="comment-modal">
    <div class="comment-header">
        Comments
        <button onclick="closeComments()" style="position: absolute; right: 15px; top: 15px; background: none; border: none; font-size: 20px;">×</button>
    </div>
    <div id="commentList" class="comment-list"></div>
    <form class="comment-form" onsubmit="submitComment(event)">
        <input type="hidden" id="commentPostId" value="">
        <input type="text" id="commentInput" class="comment-input" placeholder="Write a comment..." required>
        <button type="submit" class="comment-submit">Post</button>
    </form>
</div>

<script>
document.getElementById('reelForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('videoFile');
    const submitBtn = document.getElementById('submitBtn');
    const status = document.getElementById('uploadStatus');
    
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        if (file.size > maxSize) {
            e.preventDefault();
            status.style.display = 'block';
            status.style.background = 'rgba(255,0,0,0.2)';
            status.style.color = 'white';
            status.innerHTML = '❌ File too large! Maximum size is 10MB.';
            return;
        }
        
        // Show uploading status
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Creating Reel...';
        status.style.display = 'block';
        status.style.background = 'rgba(255,255,255,0.2)';
        status.style.color = 'white';
        status.innerHTML = '📤 Uploading your reel... Please wait!';
    }
});

// File selection feedback
document.getElementById('videoFile').addEventListener('change', function(e) {
    const status = document.getElementById('uploadStatus');
    if (e.target.files.length > 0) {
        const file = e.target.files[0];
        const sizeMB = (file.size / (1024 * 1024)).toFixed(1);
        status.style.display = 'block';
        status.style.background = 'rgba(255,255,255,0.2)';
        status.style.color = 'white';
        status.innerHTML = `✅ Video selected: ${file.name} (${sizeMB}MB)`;
    }
});

// Comment functions
function openComments(postId) {
    document.getElementById('commentPostId').value = postId;
    document.getElementById('commentModal').classList.add('active');
    loadComments(postId);
}

function closeComments() {
    document.getElementById('commentModal').classList.remove('active');
}

function loadComments(postId) {
    fetch('get_comments.php?post_id=' + postId)
        .then(response => response.json())
        .then(comments => {
            const list = document.getElementById('commentList');
            list.innerHTML = comments.map(c => 
                `<div class="comment-item"><strong>${c.username}:</strong> ${c.content}<br><small>${c.created_at}</small></div>`
            ).join('');
        });
}

function submitComment(event) {
    event.preventDefault();
    const postId = document.getElementById('commentPostId').value;
    const content = document.getElementById('commentInput').value;
    
    fetch('add_comment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `post_id=${postId}&comment=${encodeURIComponent(content)}`
    }).then(() => {
        document.getElementById('commentInput').value = '';
        loadComments(postId);
        location.reload(); // Refresh to update comment count
    });
}

// Auto-play videos when in view (with user interaction handling)
const videos = document.querySelectorAll('.reel-video');
let userInteracted = false;

// Track user interaction
document.addEventListener('click', () => { userInteracted = true; }, { once: true });
document.addEventListener('touchstart', () => { userInteracted = true; }, { once: true });

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const video = entry.target;
            video.muted = false; // Unmute when in view
            video.play().catch(() => {});
        } else {
            entry.target.pause();
        }
    });
}, { threshold: 0.5 });

videos.forEach(video => {
    observer.observe(video);
});

// Toggle create form
function toggleCreateForm() {
    const form = document.getElementById('createForm');
    const button = document.querySelector('.toggle-create');
    
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
        button.textContent = '✖️';
    } else {
        form.classList.add('hidden');
        button.textContent = '➕';
    }
}

// AJAX reaction function
function toggleReaction(postId, reactionType) {
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('reaction', reactionType);
    
    fetch('/reels', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload(); // Simple reload for now to update all counts
        }
    });
}
</script>

</body>
</html>