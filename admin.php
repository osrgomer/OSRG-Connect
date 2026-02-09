<?php
require_once 'config.php';
require_once 'series_db.php';

// Unified Admin Authentication
$isAdmin = false;

// 1. Check Social Platform Admin (SQLite)
$pdo_social = get_db();
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo_social->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_social = $stmt->fetch();
    if ($user_social && ($user_social['username'] === 'OSRG' || $user_social['username'] === 'backup')) {
        $isAdmin = true;
    }
}

// 2. Check SeriesList Admin (MySQL) - using the same session
if (!$isAdmin && isset($_SESSION['user_email'])) {
    if ($_SESSION['user_email'] === 'omersr12@gmail.com') {
        $isAdmin = true;
    }
}

if (!$isAdmin) {
    header('Location: index.php');
    exit;
}

$message = '';

// --- SOCIAL PLATFORM ACTIONS (SQLite) ---

// Handle user deletion
if ($_GET['delete_social'] ?? false) {
    $delete_id = $_GET['delete_social'];
    $stmt_check = $pdo_social->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_check->execute([$delete_id]);
    $user_to_delete = $stmt_check->fetch();
    if ($delete_id != $_SESSION['user_id'] && $user_to_delete && !($user_to_delete['username'] === 'OSRG' || $user_to_delete['username'] === 'backup')) {
        $pdo_social->prepare("DELETE FROM users WHERE id = ?")->execute([$delete_id]);
        $pdo_social->prepare("DELETE FROM posts WHERE user_id = ?")->execute([$delete_id]);
        $pdo_social->prepare("DELETE FROM friends WHERE user_id = ? OR friend_id = ?")->execute([$delete_id, $delete_id]);
        $pdo_social->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$delete_id, $delete_id]);
        $message = 'Social user deleted successfully!';
    }
}

// Handle post deletion
if ($_GET['delete_post'] ?? false) {
    $pdo_social->prepare("DELETE FROM posts WHERE id = ?")->execute([$_GET['delete_post']]);
    $message = 'Post deleted successfully!';
}

// Handle user approval
if ($_GET['approve'] ?? false) {
    $user_id = $_GET['approve'];
    $stmt = $pdo_social->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    $pdo_social->prepare("UPDATE users SET approved = 1 WHERE id = ?")->execute([$user_id]);
    if ($user_data) {
        $subject = "Account Approved - OSRG Connect";
        $body = "Hi " . $user_data['username'] . ",\n\nGreat news! Your OSRG Connect account has been approved.\n\nhttps://connect.osrg.lol/login\n\nWelcome!\n\nBest regards,\nOSRG Connect Team";
        $headers = "From: OSRG Connect <omer@osrg.lol>\r\nReply-To: omer@osrg.lol\r\nX-Mailer: PHP/" . phpversion();
        @mail($user_data['email'], $subject, $body, $headers);
    }
    $message = 'User approved successfully!';
}

// Handle database backup
if ($_GET['backup_db'] ?? false) {
    if (!is_dir('backups')) mkdir('backups', 0755, true);
    $backup_file = 'backups/backup_' . date('Y-m-d_H-i-s') . '.db';
    if (copy('private_social.db', $backup_file)) {
        $message = 'Database backup created!';
    } else {
        $message = 'Backup failed!';
    }
}

// Handle website backup
if ($_GET['website_backup'] ?? false) {
    $backup_name = 'website_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($backup_name, ZipArchive::CREATE) === TRUE) {
        $files = glob('*.php');
        foreach ($files as $file) $zip->addFile($file);
        if (file_exists('private_social.db')) $zip->addFile('private_social.db');
        foreach (['sp_uploads', 'sp_avatars', 'series_uploads'] as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
                foreach ($iterator as $file) if ($file->isFile()) $zip->addFile($file->getPathname(), $file->getPathname());
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $backup_name . '"');
        header('Content-Length: ' . filesize($backup_name));
        readfile($backup_name);
        unlink($backup_name);
        exit;
    }
}

// --- DATA FETCHING ---

// Fetch Social Platform Data
$pending_users = $pdo_social->query("SELECT id, username, email, created_at FROM users WHERE approved = 0 ORDER BY created_at DESC")->fetchAll();
$all_social_users = $pdo_social->query("SELECT id, username, email, created_at, approved FROM users ORDER BY created_at DESC")->fetchAll();
$all_posts = $pdo_social->query("SELECT p.id, p.content, p.created_at, u.username FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC")->fetchAll();

$social_stats = [
    'users' => $pdo_social->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'posts' => $pdo_social->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'messages' => $pdo_social->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
    'db_size' => @filesize('private_social.db') ?: 0
];

// Fetch SeriesList Data (MySQL)
$pdo_series = getDB();
$series_stats = [
    'users' => $pdo_series->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'shows' => $pdo_series->query("SELECT COUNT(*) FROM series")->fetchColumn(),
    'friendships' => $pdo_series->query("SELECT COUNT(*) FROM friendships")->fetchColumn() / 2
];

$page_title = 'Unified Command Centre';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #0a0a0f;
            background-image: 
                radial-gradient(at 0% 0%, rgba(0, 255, 255, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(139, 92, 246, 0.05) 0px, transparent 50%);
            color: #e0e0e0;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            border-color: rgba(0, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
        }
        .neon-text { text-shadow: 0 0 10px rgba(0, 255, 255, 0.5); }
        .tab-btn { transition: all 0.2s; border-bottom: 2px solid transparent; }
        .tab-btn.active { border-bottom-color: #06b6d4; color: #22d3ee; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .status-pulse {
            width: 8px; height: 8px; border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="min-h-screen">
    <header class="border-b border-white/5 bg-black/20 backdrop-blur-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-purple-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-cyan-500/20">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1 class="text-xl font-black tracking-tighter neon-text">ADMIN<span class="text-cyan-400">CONNECT</span></h1>
            </div>
            <nav class="flex gap-6">
                <button onclick="showTab('dashboard')" class="tab-btn active px-2 py-1 text-sm font-bold uppercase tracking-widest" id="btn-dashboard">Dashboard</button>
                <button onclick="showTab('social')" class="tab-btn px-2 py-1 text-sm font-bold uppercase tracking-widest" id="btn-social">Social Connect</button>
                <button onclick="showTab('series')" class="tab-btn px-2 py-1 text-sm font-bold uppercase tracking-widest" id="btn-series">SeriesList</button>
                <button onclick="showTab('tools')" class="tab-btn px-2 py-1 text-sm font-bold uppercase tracking-widest" id="btn-tools">System Tools</button>
            </nav>
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-xs text-slate-400 hover:text-white transition-colors">BACK TO SITE</a>
                <a href="logout.php" class="text-xs text-red-400 hover:text-red-300 transition-colors">LOGOUT</a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 rounded-xl flex items-center gap-3">
                <i class="fas fa-info-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD TAB -->
        <div id="dashboard" class="tab-content active space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="glass-card p-6 rounded-2xl">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Network Users</p>
                    <h2 class="text-4xl font-black text-white"><?= $social_stats['users'] + $series_stats['users'] ?></h2>
                    <div class="mt-4 flex gap-2 text-[10px]">
                        <span class="px-2 py-0.5 bg-cyan-500/10 text-cyan-400 rounded"><?= $social_stats['users'] ?> Social</span>
                        <span class="px-2 py-0.5 bg-purple-500/10 text-purple-400 rounded"><?= $series_stats['users'] ?> Series</span>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-2xl">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Social Feed Activity</p>
                    <h2 class="text-4xl font-black text-cyan-400"><?= $social_stats['posts'] ?></h2>
                    <p class="text-[10px] text-slate-400 mt-4 uppercase">Total Posts shared</p>
                </div>
                <div class="glass-card p-6 rounded-2xl">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Series Database</p>
                    <h2 class="text-4xl font-black text-purple-400"><?= $series_stats['shows'] ?></h2>
                    <p class="text-[10px] text-slate-400 mt-4 uppercase">Unique shows tracked</p>
                </div>
                <div class="glass-card p-6 rounded-2xl">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Server Health</p>
                    <h2 class="text-4xl font-black text-green-400" id="server-load-val">--</h2>
                    <p class="text-[10px] text-slate-400 mt-4 uppercase">System Load (CPU)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 glass-card rounded-2xl overflow-hidden">
                    <div class="p-6 border-b border-white/5 flex justify-between items-center">
                        <h3 class="font-bold flex items-center gap-2 italic"><i class="fas fa-bolt text-yellow-400"></i> LIVE NETWORK STATUS</h3>
                        <span class="text-[10px] bg-white/5 px-2 py-1 rounded">REAL-TIME SYNC</span>
                    </div>
                    <div class="p-6 h-[400px] relative overflow-hidden bg-slate-950/50" id="userStage">
                        <!-- Bubble map spawns here -->
                    </div>
                </div>
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="p-6 border-b border-white/5">
                        <h3 class="font-bold flex items-center gap-2 italic"><i class="fas fa-terminal text-cyan-400"></i> SYSTEM LOGS</h3>
                    </div>
                    <div class="p-4 space-y-3 h-[400px] overflow-y-auto" id="activityFeed">
                        <p class="text-xs text-slate-500 text-center py-10">Listening for data...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SOCIAL TAB -->
        <div id="social" class="tab-content space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Pending Approvals -->
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="p-6 border-b border-white/5 flex justify-between items-center">
                        <h3 class="font-bold">Pending Approvals (<?= count($pending_users) ?>)</h3>
                        <i class="fas fa-user-clock text-yellow-400"></i>
                    </div>
                    <div class="divide-y divide-white/5 max-h-[500px] overflow-y-auto">
                        <?php if ($pending_users): foreach ($pending_users as $user): ?>
                            <div class="p-4 flex justify-between items-center hover:bg-white/5 transition-colors">
                                <div>
                                    <p class="font-bold text-white text-sm"><?= htmlspecialchars($user['username']) ?></p>
                                    <p class="text-xs text-slate-500"><?= htmlspecialchars($user['email']) ?></p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="?approve=<?= $user['id'] ?>" class="px-3 py-1 bg-green-500/20 text-green-400 text-[10px] font-bold rounded uppercase hover:bg-green-500 hover:text-white transition-all">APPROVE</a>
                                    <a href="?reject=<?= $user['id'] ?>" class="px-3 py-1 bg-red-500/20 text-red-400 text-[10px] font-bold rounded uppercase hover:bg-red-500 hover:text-white transition-all">REJECT</a>
                                </div>
                            </div>
                        <?php endforeach; else: ?>
                            <div class="p-10 text-center text-slate-600 text-sm italic">No registrations pending</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Latest Posts -->
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="p-6 border-b border-white/5 flex justify-between items-center">
                        <h3 class="font-bold">Recent Posts</h3>
                        <i class="fas fa-newspaper text-cyan-400"></i>
                    </div>
                    <div class="divide-y divide-white/5 max-h-[500px] overflow-y-auto">
                        <?php foreach (array_slice($all_posts, 0, 10) as $post): ?>
                            <div class="p-4 flex justify-between items-start gap-4 hover:bg-white/5 transition-colors">
                                <div class="flex-1">
                                    <p class="text-xs font-black text-cyan-400 mb-1"><?= htmlspecialchars($post['username']) ?></p>
                                    <p class="text-xs text-slate-300"><?= htmlspecialchars(substr(strip_tags($post['content']), 0, 80)) ?>...</p>
                                </div>
                                <a href="?delete_post=<?= $post['id'] ?>" onclick="return confirm('Delete post?')" class="text-slate-600 hover:text-red-400 p-2"><i class="fas fa-trash-alt text-xs"></i></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- All Users Table -->
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="p-6 border-b border-white/5">
                    <h3 class="font-bold italic">SOCIAL USER DIRECTORY</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-white/5 text-[10px] uppercase tracking-widest text-slate-500">
                                <th class="p-4">Username</th>
                                <th class="p-4">Email</th>
                                <th class="p-4 text-center">Approved</th>
                                <th class="p-4">Joined</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($all_social_users as $user): ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="p-4 font-bold"><?= htmlspecialchars($user['username']) ?></td>
                                    <td class="p-4 text-slate-400"><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="p-4 text-center">
                                        <i class="fas <?= $user['approved'] ? 'fa-check-circle text-green-500' : 'fa-times-circle text-yellow-500' ?>"></i>
                                    </td>
                                    <td class="p-4 text-xs text-slate-500"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td class="p-4 text-right">
                                        <?php if ($user['username'] !== 'OSRG' && $user['username'] !== 'backup'): ?>
                                            <a href="?delete_social=<?= $user['id'] ?>" onclick="return confirm('Erase this user?')" class="text-red-400 hover:text-red-300 text-xs">ERASE</a>
                                        <?php else: ?>
                                            <span class="text-[10px] bg-cyan-500 text-black px-2 py-0.5 rounded font-black">ADMIN</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SERIES TAB -->
        <div id="series" class="tab-content space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Online List -->
                <div class="glass-card rounded-2xl p-6">
                    <h4 class="font-bold text-green-400 flex items-center gap-2 mb-4"><i class="fas fa-signal"></i> ONLINE USERS</h4>
                    <div id="onlineList" class="space-y-2"></div>
                </div>
                <!-- Trending Shows -->
                <div class="glass-card rounded-2xl p-6">
                    <h4 class="font-bold text-purple-400 flex items-center gap-2 mb-4"><i class="fas fa-fire"></i> TRENDING SHOWS</h4>
                    <div id="trendingList" class="space-y-3"></div>
                </div>
                <!-- Admin Uploads -->
                <div class="glass-card rounded-2xl p-6">
                    <h4 class="font-bold text-cyan-400 flex items-center gap-2 mb-4"><i class="fas fa-cloud-upload-alt"></i> QUICK UPLOAD</h4>
                    <form id="uploadForm" class="space-y-4">
                        <input type="text" id="uploadTitle" placeholder="Title" class="w-full bg-black/40 border border-white/10 rounded-lg p-3 text-sm focus:border-cyan-500 outline-none" required>
                        <input type="file" id="uploadImage" class="w-full text-xs text-slate-500" required>
                        <button type="submit" class="w-full py-2 bg-gradient-to-r from-cyan-500 to-indigo-600 rounded-lg font-bold text-sm uppercase italic tracking-tighter hover:opacity-90 active:scale-[0.98] transition-all">Transmit File</button>
                    </form>
                </div>
            </div>

            <div class="glass-card rounded-2xl overflow-hidden p-6">
                <h4 class="font-bold text-slate-400 mb-6 italic uppercase tracking-widest text-xs">Recent Uploads Depot</h4>
                <div id="uploadsList" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4"></div>
            </div>
        </div>

        <!-- TOOLS TAB -->
        <div id="tools" class="tab-content space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="glass-card rounded-2xl p-8 space-y-6">
                    <h2 class="text-2xl font-black italic"><i class="fas fa-tools text-red-500 mr-2"></i> SYSTEM MAINTENANCE</h2>
                    <div class="space-y-4">
                        <a href="fix_posts.php" class="block p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-colors border-l-4 border-yellow-500">
                            <h4 class="font-bold text-sm">Fix Post Corruption</h4>
                            <p class="text-xs text-slate-500">Repair HTML tags broken by editor across all posts.</p>
                        </a>
                        <a href="fix_ids.php" class="block p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-colors border-l-4 border-orange-500">
                            <h4 class="font-bold text-sm">Synchronize User IDs</h4>
                            <p class="text-xs text-slate-500">Sequential re-indexing of user records (DANGEROUS).</p>
                        </a>
                        <a href="?backup_db=1" class="block p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-colors border-l-4 border-green-500">
                            <h4 class="font-bold text-sm">Core Database Backup</h4>
                            <p class="text-xs text-slate-500">snapshot of private_social.db into backups/ folder.</p>
                        </a>
                    </div>
                </div>
                <div class="glass-card rounded-2xl p-8 space-y-6">
                    <h2 class="text-2xl font-black italic"><i class="fas fa-archive text-purple-500 mr-2"></i> EXPORT & BACKUP</h2>
                    <div class="space-y-4">
                        <a href="?website_backup=1" class="block p-6 bg-gradient-to-br from-purple-900/40 to-indigo-900/40 border border-white/10 rounded-2xl text-center group transition-all hover:scale-[1.02]">
                            <i class="fas fa-file-zipper text-5xl mb-4 text-purple-400 group-hover:animate-bounce"></i>
                            <h4 class="text-xl font-bold mb-2">FULL SITE ZIP</h4>
                            <p class="text-xs text-slate-400 max-w-[200px] mx-auto">Download all code, databases, and uploads in one archive.</p>
                        </a>
                        <div class="p-6 bg-black/40 rounded-2xl border border-white/5">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-4">Storage Metrics</h4>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-400">SQLite DB:</span>
                                <span class="text-cyan-400 font-mono"><?= round($social_stats['db_size'] / 1024 / 1024, 2) ?> MB</span>
                            </div>
                            <div class="flex justify-between items-center text-sm mt-2">
                                <span class="text-slate-400">MySQL DB:</span>
                                <span class="text-purple-400 font-mono">REMOTE</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            document.getElementById('btn-' + tabId).classList.add('active');
            localStorage.setItem('admin_tab', tabId);
        }

        // Restore last tab
        const lastTab = localStorage.getItem('admin_tab');
        if (lastTab) showTab(lastTab);

        // --- REAL-TIME DATA (SeriesList API) ---
        async function fetchRealTimeData() {
            try {
                const res = await fetch('series_api_admin.php?action=get_stats');
                const data = await res.json();
                if (!data.success) return;

                // Update server load
                document.getElementById('server-load-val').textContent = (data.server_load || 0).toFixed(2);
                
                // Update Bubbles
                renderBubbleMap(data.bubbles);
                
                // Update Logs
                renderLogs(data.activities);
                
                // Update Online List
                renderOnline(data.users.filter(u => u.seconds_ago < 120 || u.manual_status === 'online'));
                
                // Update Trending
                renderTrending(data.trending);
                
            } catch (err) { console.error('Fetch error:', err); }
        }

        function renderBubbleMap(bubbles) {
            const stage = document.getElementById('userStage');
            bubbles.forEach((user, idx) => {
                let b = document.getElementById(`b-${user.id}`);
                if (!b) {
                    b = document.createElement('div');
                    b.id = `b-${user.id}`;
                    b.className = 'absolute transition-all duration-700 cursor-pointer group';
                    const col = idx % 6; const row = Math.floor(idx / 6);
                    b.style.left = `${10 + col * 15}%`;
                    b.style.top = `${15 + row * 25}%`;
                    b.onclick = () => { if(confirm(`God Mode into ${user.username}?`)) impersonate(user.id); };
                    stage.appendChild(b);
                }
                const color = user.color === 'green' ? 'cyan' : (user.color === 'amber' ? 'yellow' : 'slate');
                b.innerHTML = `
                    <div class="relative">
                        <div class="status-pulse bg-${color}-500 absolute -inset-1 opacity-20"></div>
                        <img src="${user.avatar_url}" class="w-12 h-12 rounded-full border-2 border-${color}-500/50 shadow-lg relative z-10 hover:scale-110 transition-transform">
                        <div class="absolute -bottom-6 left-1/2 -translate-x-1/2 whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity bg-black/80 px-2 py-0.5 rounded text-[10px] pointer-events-none">
                            ${user.username} ${user.last_action ? `• <span class="text-cyan-400">${user.last_action}</span>` : ''}
                        </div>
                    </div>
                `;
            });
        }

        function renderLogs(logs) {
            const feed = document.getElementById('activityFeed');
            feed.innerHTML = logs.map(l => `
                <div class="flex items-center gap-2 p-2 rounded bg-white/5 border border-white/5 text-[10px]">
                    <span class="text-cyan-400 font-bold shrink-0">${l.username}</span>
                    <span class="text-slate-400 truncate">${l.action} ${l.show_title || ''}</span>
                    <span class="ml-auto text-slate-600 shrink-0 italic">${timeAgo(l.created_at)}</span>
                </div>
            `).join('');
        }

        function renderOnline(users) {
            const list = document.getElementById('onlineList');
            if (users.length === 0) { list.innerHTML = '<p class="text-xs text-slate-600 italic">No one online</p>'; return; }
            list.innerHTML = users.map(u => `
                <div class="flex items-center justify-between group p-2 rounded hover:bg-white/5">
                    <div class="flex items-center gap-2">
                        <img src="${u.avatar_url}" class="w-6 h-6 rounded-full">
                        <span class="text-xs">${u.username}</span>
                    </div>
                    <button onclick="impersonate(${u.id})" class="opacity-0 group-hover:opacity-100 text-cyan-400 text-xs"><i class="fas fa-ghost"></i></button>
                </div>
            `).join('');
        }

        function renderTrending(trending) {
            const list = document.getElementById('trendingList');
            list.innerHTML = trending.map((s, i) => `
                <div class="space-y-1">
                    <div class="flex justify-between text-xs">
                        <span class="font-bold">${i+1}. ${s.title}</span>
                        <span class="text-slate-500">${s.total_fans} fans</span>
                    </div>
                    <div class="w-full bg-slate-800 h-1 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500" style="width: ${(s.completed/s.total_fans)*100}%"></div>
                    </div>
                </div>
            `).join('');
        }

        async function impersonate(userId) {
            try {
                const res = await fetch('series_api_admin.php?action=impersonate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                if ((await res.json()).success) window.location.href = 'series_index.php';
            } catch (err) { alert('Access Denied'); }
        }

        function timeAgo(date) {
            const seconds = Math.floor((new Date() - new Date(date)) / 1000);
            if (seconds < 60) return 'now';
            if (seconds < 3600) return Math.floor(seconds/60) + 'm';
            if (seconds < 86400) return Math.floor(seconds/3600) + 'h';
            return Math.floor(seconds/86400) + 'd';
        }

        setInterval(fetchRealTimeData, 5000);
        fetchRealTimeData();

        // --- UPLOADS ---
        async function loadUploads() {
            try {
                const res = await fetch('series_api_admin_uploads.php');
                const data = await res.json();
                if (data.success) {
                    document.getElementById('uploadsList').innerHTML = data.uploads.map(u => `
                        <div class="glass-card rounded-lg overflow-hidden group">
                            <div class="h-24 bg-slate-800 flex items-center justify-center relative">
                                ${u.file_type.startsWith('image/') ? `<img src="${u.image_path}" class="w-full h-full object-cover">` : `<i class="fas fa-file-alt text-2xl"></i>`}
                                <a href="${u.image_path}" target="_blank" class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"><i class="fas fa-external-link-alt"></i></a>
                            </div>
                            <p class="text-[9px] p-2 truncate text-slate-400">${u.title}</p>
                        </div>
                    `).join('');
                }
            } catch (err) {}
        }
        loadUploads();

        document.getElementById('uploadForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('title', document.getElementById('uploadTitle').value);
            formData.append('image', document.getElementById('uploadImage').files[0]);
            try {
                const res = await fetch('series_api_admin_uploads.php', { method: 'POST', body: formData });
                if ((await res.json()).success) { loadUploads(); e.target.reset(); }
            } catch (err) {}
        };
    </script>
</body>
</html>
