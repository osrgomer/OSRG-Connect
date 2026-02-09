<?php
session_start();

// Admin authentication
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_email']) || $_SESSION['user_email'] !== 'omersr12@gmail.com') {
    header('Location: index.php');
    exit;
}

require_once 'db.php';
if (isset($_SESSION['user_id'])) {
    updateLastActive($_SESSION['user_id']);
}

$current_page = 'admin';
$page_title = 'Command Centre - SeriesList';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #0a0a0f;
            background-image: 
                radial-gradient(at 0% 0%, rgba(0, 255, 255, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(0, 255, 136, 0.1) 0px, transparent 50%);
            color: #e0e0e0;
            font-family: 'Courier New', monospace;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }
        
        .status-pulse {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        
        .status-online { background: #00ff88; box-shadow: 0 0 10px #00ff88; }
        .status-idle { background: #ffaa00; box-shadow: 0 0 10px #ffaa00; }
        .status-offline { background: #666; box-shadow: 0 0 5px #666; }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .activity-ticker {
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #00ffff #1a1a2e;
        }
        
        .activity-ticker::-webkit-scrollbar {
            width: 6px;
        }
        
        .activity-ticker::-webkit-scrollbar-track {
            background: #1a1a2e;
        }
        
        .activity-ticker::-webkit-scrollbar-thumb {
            background: #00ffff;
            border-radius: 3px;
        }
        
        .activity-item {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .neon-text {
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.8);
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #00ff88 0%, #00ffff 100%);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .impersonate-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(90deg, #ff0000, #ff6600);
            color: white;
            padding: 10px;
            text-align: center;
            z-index: 9999;
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.5);
            display: none;
        }
        
        /* BUBBLE MAP STYLES */
        .user-bubble {
            position: absolute;
            transition: all 0.3s ease-in-out;
            animation: float 6s infinite ease-in-out;
            cursor: pointer;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            50% { transform: translateY(-20px) translateX(10px); }
        }
        
        .status-ring {
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            opacity: 0.3;
            animation: pulse-ring 2s infinite;
        }
        
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.5); opacity: 0.1; }
            100% { transform: scale(1); opacity: 0.3; }
        }
        
        .user-bubble:hover {
            transform: scale(1.2);
            z-index: 10;
        }
        
        .user-bubble:hover .status-ring {
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <!-- Impersonation Bar -->
    <div id="impersonateBar" class="impersonate-bar">
        ⚠️ Impersonating <span id="impersonateUser"></span> - 
        <button onclick="exitImpersonate()" class="underline font-bold">Exit God Mode</button>
    </div>

    <div class="container mx-auto px-4 py-8 <?php echo isset($_SESSION['admin_origin']) ? 'mt-12' : ''; ?>">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-5xl font-bold neon-text mb-2">
                <i class="fas fa-satellite-dish"></i> COMMAND CENTRE
            </h1>
            <p class="text-cyan-400">System Status: <span class="text-green-400">OPERATIONAL</span></p>
            <a href="index.php" class="text-sm text-slate-400 hover:text-cyan-400">
                <i class="fas fa-arrow-left"></i> Back to Main
            </a>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Server Load -->
            <div class="glass-card rounded-lg p-6">
                <div class="mb-4">
                    <h3 class="text-xs font-bold tracking-widest text-slate-400 uppercase mb-2">
                        <i class="fas fa-server"></i> SERVER LOAD
                    </h3>
                    <div id="serverLoad" class="text-2xl font-bold text-cyan-400">--</div>
                </div>
                <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                    <div id="serverLoadBar" class="h-full bg-gradient-to-r from-cyan-500 to-green-500 transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
            
            <!-- Total Users -->
            <div class="glass-card rounded-lg p-6">
                <h3 class="text-xs font-bold tracking-widest text-slate-400 uppercase mb-2">
                    <i class="fas fa-users"></i> TOTAL USERS
                </h3>
                <div id="totalUsers" class="text-3xl font-bold text-green-400">0</div>
            </div>
            
            <!-- Total Shows -->
            <div class="glass-card rounded-lg p-6">
                <h3 class="text-xs font-bold tracking-widest text-slate-400 uppercase mb-2">
                    <i class="fas fa-tv"></i> TOTAL SHOWS
                </h3>
                <div id="totalShows" class="text-3xl font-bold text-purple-400">0</div>
            </div>
            
            <!-- Total Friendships -->
            <div class="glass-card rounded-lg p-6">
                <h3 class="text-xs font-bold tracking-widest text-slate-400 uppercase mb-2">
                    <i class="fas fa-heart"></i> FRIENDSHIPS
                </h3>
                <div id="totalFriendships" class="text-3xl font-bold text-pink-400">0</div>
            </div>
        </div>

        <!-- Online/Idle/Offline Users -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Online Users -->
            <div class="glass-card rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-green-400">
                        <i class="fas fa-circle status-pulse status-online"></i> ONLINE
                    </h3>
                    <span id="onlineCount" class="text-3xl font-bold text-green-400">0</span>
                </div>
                <div id="onlineUsers" class="space-y-2"></div>
            </div>

            <!-- Idle Users -->
            <div class="glass-card rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-amber-400">
                        <i class="fas fa-circle status-pulse status-idle"></i> IDLE
                    </h3>
                    <span id="idleCount" class="text-3xl font-bold text-amber-400">0</span>
                </div>
                <div id="idleUsers" class="space-y-2"></div>
            </div>

            <!-- Offline Users -->
            <div class="glass-card rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-slate-400">
                        <i class="fas fa-circle status-pulse status-offline"></i> OFFLINE
                    </h3>
                    <span id="offlineCount" class="text-3xl font-bold text-slate-400">0</span>
                </div>
                <div id="offlineUsers" class="space-y-2"></div>
            </div>
        </div>

        <!-- Live Activity Ticker & Trending Shows -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Activity Feed -->
            <div class="glass-card rounded-lg p-6">
                <h3 class="text-2xl font-bold neon-text mb-4">
                    <i class="fas fa-terminal"></i> LIVE ACTIVITY STREAM
                </h3>
                <div id="activityFeed" class="activity-ticker space-y-2">
                    <p class="text-slate-500 text-center">Initializing feed...</p>
                </div>
            </div>

            <!-- Trending Shows -->
            <div class="glass-card rounded-lg p-6">
                <h3 class="text-2xl font-bold neon-text mb-4">
                    <i class="fas fa-fire"></i> TRENDING SHOWS
                </h3>
                <div id="trendingShows" class="space-y-4">
                    <p class="text-slate-500 text-center">Loading data...</p>
                </div>
            </div>
        </div>
        
        <!-- BUBBLE MAP - God View -->
        <div class="glass-card rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-2xl font-bold neon-text">
                    <i class="fas fa-globe"></i> LIVE USER MAP
                </h3>
                <span class="text-xs tracking-widest text-cyan-500 uppercase bg-cyan-500/10 px-3 py-1 rounded">SYSTEM LIVE VIEW</span>
            </div>
            <div id="userStage" class="relative w-full h-96 bg-slate-950 rounded-2xl border border-white/5 overflow-hidden">
                <!-- Bubbles will spawn here -->
            </div>
        </div>
        
        <!-- FILE UPLOAD MANAGER -->
        <div class="glass-card rounded-lg p-6 mt-6">
            <h3 class="text-2xl font-bold neon-text mb-4">
                <i class="fas fa-upload"></i> FILE UPLOAD MANAGER
            </h3>
            
            <form id="uploadForm" class="mb-6 flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Title</label>
                    <input type="text" id="uploadTitle" placeholder="Enter file title" required
                           class="w-full px-4 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white focus:border-cyan-500 focus:outline-none">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-300 mb-2">File (Image or Text)</label>
                    <input type="file" id="uploadImage" accept="image/*,.txt,.md,.csv,.json,.xml,.log" required
                           class="w-full px-4 py-2 bg-slate-800 border border-slate-600 rounded-lg text-white focus:border-cyan-500 focus:outline-none">
                </div>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-cyan-500 to-indigo-500 text-white font-bold rounded-lg hover:shadow-lg hover:shadow-cyan-500/50 transition-all">
                    <i class="fas fa-upload mr-2"></i>Upload
                </button>
            </form>
            
            <div id="uploadsList" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <p class="text-slate-500 text-center col-span-full">Loading uploads...</p>
            </div>
        </div>
    </div>

    <script>
        let lastActivityId = 0;
        let impersonating = <?php echo isset($_SESSION['admin_origin']) ? 'true' : 'false'; ?>;

        // Load ALL stats in one call
        async function loadStats() {
            try {
                const response = await fetch('api_admin.php?action=get_stats');
                const data = await response.json();
                
                console.log('Admin stats loaded:', data);
                
                if (!data.success) {
                    console.error('Stats failed:', data);
                    return;
                }
                
                // Update server load
                const load = data.server_load || 0;
                document.getElementById('serverLoad').textContent = load.toFixed(2);
                const loadPercent = Math.min((load / 2) * 100, 100); // Cap at 2.0 = 100%
                document.getElementById('serverLoadBar').style.width = loadPercent + '%';
                if (load > 1.5) {
                    document.getElementById('serverLoadBar').className = 'h-full bg-gradient-to-r from-red-500 to-orange-500 transition-all duration-500';
                } else if (load > 0.8) {
                    document.getElementById('serverLoadBar').className = 'h-full bg-gradient-to-r from-amber-500 to-yellow-500 transition-all duration-500';
                } else {
                    document.getElementById('serverLoadBar').className = 'h-full bg-gradient-to-r from-cyan-500 to-green-500 transition-all duration-500';
                }
                
                // Update totals
                document.getElementById('totalUsers').textContent = data.stats.total_users;
                document.getElementById('totalShows').textContent = data.stats.total_shows;
                document.getElementById('totalFriendships').textContent = data.stats.total_friendships;
                
                // Update users (categorize by status)
                const online = [], idle = [], offline = [];
                console.log('Total users from API:', data.users.length);
                
                data.users.forEach(user => {
                    const secondsAgo = parseInt(user.seconds_ago);
                    console.log(`${user.username}: ${secondsAgo}s ago, manual=${user.manual_status}`);
                    
                    if (secondsAgo < 120 || user.manual_status === 'online') {
                        online.push(user);
                    } else if (secondsAgo < 300 && user.manual_status !== 'offline') {
                        idle.push(user);
                    } else {
                        offline.push(user);
                    }
                });
                
                console.log(`Online: ${online.length}, Idle: ${idle.length}, Offline: ${offline.length}`);
                
                document.getElementById('onlineCount').textContent = online.length;
                document.getElementById('idleCount').textContent = idle.length;
                document.getElementById('offlineCount').textContent = offline.length;
                
                renderUsers('onlineUsers', online, 'green');
                renderUsers('idleUsers', idle, 'amber');
                renderUsers('offlineUsers', offline, 'slate');
                
                // Update activity feed
                if (data.activities.length > 0) {
                    renderActivity(data.activities);
                }
                
                // Update trending
                if (data.trending.length > 0) {
                    renderTrending(data.trending);
                }
                
                // Update bubble map
                if (data.bubbles) {
                    renderBubbleMap(data.bubbles);
                }
                
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }
        
        // Render the BUBBLE MAP
        function renderBubbleMap(bubbles) {
            const stage = document.getElementById('userStage');
            
            bubbles.forEach((user, index) => {
                let bubble = document.getElementById(`bubble-${user.id}`);
                
                // Create bubble if it doesn't exist
                if (!bubble) {
                    bubble = document.createElement('div');
                    bubble.id = `bubble-${user.id}`;
                    bubble.className = 'user-bubble';
                    
                    // Spread bubbles across the stage (not random, grid-like but offset)
                    const col = index % 5;
                    const row = Math.floor(index / 5);
                    bubble.style.left = `${col * 18 + 10}%`;
                    bubble.style.top = `${row * 30 + 10}%`;
                    
                    bubble.onclick = () => {
                        if (confirm(`Jump into ${user.username}'s account?`)) {
                            impersonate(user.id, user.username);
                        }
                    };
                    
                    stage.appendChild(bubble);
                }
                
                // Update bubble content and color
                const ringColor = user.color === 'green' ? 'bg-green-500' : 
                                 user.color === 'amber' ? 'bg-amber-500' : 'bg-slate-600';
                
                const pulseClass = user.last_action ? 'animate-pulse' : '';
                
                bubble.innerHTML = `
                    <div class="relative">
                        <div class="status-ring ${ringColor} ${pulseClass}"></div>
                        <img src="${user.avatar_url}" 
                             class="w-14 h-14 rounded-full border-2 border-white/40 relative z-10 transition-transform hover:scale-110" 
                             alt="${user.username}">
                        <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-[10px] text-white opacity-0 hover:opacity-100 whitespace-nowrap bg-black/80 px-2 py-1 rounded transition-opacity pointer-events-none">
                            ${user.username}
                            ${user.last_action ? `<br><span class="text-cyan-400">${user.last_action}</span>` : ''}
                        </div>
                    </div>
                `;
            });
        }
        
        // Kept for backwards compatibility
        async function loadUsers() {
            await loadStats();
        }

        function renderUsers(containerId, users, color) {
            const container = document.getElementById(containerId);
            if (users.length === 0) {
                container.innerHTML = '<p class="text-sm text-slate-500">None</p>';
                return;
            }
            
            container.innerHTML = users.map(user => `
                <div class="flex items-center justify-between p-2 rounded hover:bg-${color}-500/10">
                    <div class="flex items-center gap-2">
                        <img src="${user.avatar_url}" class="w-8 h-8 rounded-full">
                        <span class="text-sm">${user.username}</span>
                    </div>
                    <button onclick="impersonate(${user.id}, '${user.username.replace(/'/g, "\\'")}')" 
                            class="text-xs text-cyan-400 hover:text-cyan-300">
                        <i class="fas fa-user-secret"></i>
                    </button>
                </div>
            `).join('');
        }

        // Load activity feed
        function renderActivity(activities) {
            const feed = document.getElementById('activityFeed');
            feed.innerHTML = activities.map(activity => `
                <div class="activity-item p-3 rounded bg-slate-900/50 border border-slate-700">
                    <div class="flex items-center gap-2 mb-1">
                        <img src="${activity.avatar_url}" class="w-6 h-6 rounded-full">
                        <span class="text-sm text-cyan-400">${activity.username}</span>
                        <span class="text-xs text-slate-500">${timeAgo(activity.created_at)}</span>
                    </div>
                    <p class="text-sm text-slate-300">${activity.action} <span class="text-white">${activity.show_title}</span></p>
                </div>
            `).join('');
        }

        // Load trending shows
        function renderTrending(trending) {
            const container = document.getElementById('trendingShows');
            container.innerHTML = trending.map((show, index) => {
                const completedPercent = (show.completed / show.total_fans) * 100;
                const watchingPercent = (show.watching / show.total_fans) * 100;
                
                return `
                    <div class="p-3 rounded bg-slate-900/50 border border-slate-700">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-bold ${index === 0 ? 'text-yellow-400' : 'text-white'}">
                                ${index + 1}. ${show.title}
                            </span>
                            <span class="text-sm text-cyan-400">${show.total_fans} fans</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-green-400 w-20">Completed:</span>
                                <div class="flex-1 bg-slate-800 rounded h-2">
                                    <div class="progress-bar rounded h-full" style="width: ${completedPercent}%"></div>
                                </div>
                                <span class="text-xs text-slate-400">${show.completed}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-amber-400 w-20">Watching:</span>
                                <div class="flex-1 bg-slate-800 rounded h-2">
                                    <div class="progress-bar rounded h-full" style="width: ${watchingPercent}%"></div>
                                </div>
                                <span class="text-xs text-slate-400">${show.watching}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Impersonate user
        async function impersonate(userId, username) {
            if (!confirm(`Enter God Mode as ${username}?`)) return;
            
            try {
                const response = await fetch('api_admin.php?action=impersonate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'index.php';
                }
            } catch (error) {
                console.error('Impersonation failed:', error);
                alert('Impersonation failed!');
            }
        }

        async function exitImpersonate() {
            try {
                await fetch('api_admin.php?action=exit_impersonate');
                window.location.href = 'admin.php';
            } catch (error) {
                console.error('Exit failed:', error);
            }
        }

        function timeAgo(timestamp) {
            const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
            if (seconds < 60) return 'just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
            return Math.floor(seconds / 86400) + 'd ago';
        }

        // Auto-refresh - ONE call for everything
        loadStats();
        setInterval(loadStats, 5000); // Every 5 seconds
        
        // Image Upload Manager
        document.getElementById('uploadForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('title', document.getElementById('uploadTitle').value);
            formData.append('image', document.getElementById('uploadImage').files[0]);
            
            try {
                const response = await fetch('api_admin_uploads.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    alert('✓ Image uploaded successfully!');
                    document.getElementById('uploadForm').reset();
                    loadUploads();
                } else {
                    alert('✗ Upload failed: ' + result.error);
                }
            } catch (error) {
                alert('✗ Upload error: ' + error.message);
            }
        };
        
        async function loadUploads() {
            try {
                const response = await fetch('api_admin_uploads.php');
                const data = await response.json();
                
                if (data.success && data.uploads.length > 0) {
                    document.getElementById('uploadsList').innerHTML = data.uploads.map(upload => {
                        const isImage = upload.file_type && upload.file_type.startsWith('image/');
                        return `
                        <div class="bg-slate-900/50 border border-slate-700 rounded-lg overflow-hidden">
                            ${isImage ? 
                                `<img src="${upload.image_path}" class="w-full h-48 object-cover" alt="${upload.title}">` :
                                `<div class="w-full h-48 bg-slate-800 flex items-center justify-center">
                                    <i class="fas fa-file-alt text-6xl text-cyan-400"></i>
                                </div>`
                            }
                            <div class="p-3">
                                <h4 class="text-sm font-bold text-white">${upload.title}</h4>
                                <p class="text-xs text-slate-400">${upload.username} • ${timeAgo(upload.uploaded_at)}</p>
                                <a href="${upload.image_path}" target="_blank" class="text-xs text-cyan-400 hover:text-cyan-300">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                        `;
                    }).join('');
                } else {
                    document.getElementById('uploadsList').innerHTML = '<p class="text-slate-500 text-center col-span-full">No uploads yet</p>';
                }
            } catch (error) {
                console.error('Failed to load uploads:', error);
            }
        }
        
        loadUploads();
        
        // Check if impersonating
        if (impersonating) {
            document.getElementById('impersonateBar').style.display = 'block';
            <?php
            if (isset($_SESSION['admin_origin'])) {
                $stmt = getDB()->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $impersonatedUser = $stmt->fetchColumn();
                echo "document.getElementById('impersonateUser').textContent = '" . htmlspecialchars($impersonatedUser) . "';";
            }
            ?>
        }
    </script>
</body>
</html>
