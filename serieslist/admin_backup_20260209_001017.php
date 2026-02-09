<?php
session_start();

// Admin authentication - only omersr12@gmail.com
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_email']) || $_SESSION['user_email'] !== 'omersr12@gmail.com') {
    header('Location: index.php');
    exit;
}

// Update last active
if (isset($_SESSION['user_id'])) {
    require_once 'db.php';
    updateLastActive($_SESSION['user_id']);
}

$current_page = 'admin';
$page_title = 'Admin Panel - SeriesList';
$extra_head = '
<style>
    @keyframes pulse-green {
        0% { transform: scale(1); text-shadow: 0 0 0px rgba(34, 197, 94, 0); }
        50% { transform: scale(1.15); text-shadow: 0 0 15px rgba(34, 197, 94, 0.8); }
        100% { transform: scale(1); text-shadow: 0 0 0px rgba(34, 197, 94, 0); }
    }
    
    @keyframes pulse-amber {
        0% { transform: scale(1); text-shadow: 0 0 0px rgba(245, 158, 11, 0); }
        50% { transform: scale(1.15); text-shadow: 0 0 15px rgba(245, 158, 11, 0.8); }
        100% { transform: scale(1); text-shadow: 0 0 0px rgba(245, 158, 11, 0); }
    }
    
    @keyframes pulse-blue {
        0% { transform: scale(1); text-shadow: 0 0 0px rgba(59, 130, 246, 0); }
        50% { transform: scale(1.15); text-shadow: 0 0 15px rgba(59, 130, 246, 0.8); }
        100% { transform: scale(1); text-shadow: 0 0 0px rgba(59, 130, 246, 0); }
    }
    
    .pulse-update-green {
        display: inline-block;
        animation: pulse-green 0.6s ease-in-out;
    }
    
    .pulse-update-amber {
        display: inline-block;
        animation: pulse-amber 0.6s ease-in-out;
    }
    
    .pulse-update-blue {
        display: inline-block;
        animation: pulse-blue 0.6s ease-in-out;
    }
</style>
';
include 'header.php';

// Get database stats
require_once 'db.php';
$pdo = getDB();

// Count users
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Count friendships
$friendshipCount = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM friendships")->fetchColumn();

// Count activities
$activityCount = $pdo->query("SELECT COUNT(*) FROM user_activity")->fetchColumn();

// Get online/idle/offline users with time-based queries
$onlineUsers = $pdo->query("
    SELECT id, email, username, last_active, manual_status 
    FROM users 
    WHERE last_active >= NOW() - INTERVAL 2 MINUTE 
    OR manual_status = 'online'
    ORDER BY last_active DESC
")->fetchAll();

$idleUsers = $pdo->query("
    SELECT id, email, username, last_active, manual_status 
    FROM users 
    WHERE last_active < NOW() - INTERVAL 2 MINUTE 
    AND last_active >= NOW() - INTERVAL 5 MINUTE
    AND manual_status != 'online'
    AND manual_status != 'offline'
    ORDER BY last_active DESC
")->fetchAll();

$offlineUsers = $pdo->query("
    SELECT id, email, username, last_active, manual_status 
    FROM users 
    WHERE (last_active < NOW() - INTERVAL 5 MINUTE OR manual_status = 'offline')
    AND manual_status != 'online'
    ORDER BY last_active DESC
")->fetchAll();

// Get all users
$users = $pdo->query("SELECT id, email, username, manual_status, last_active, created_at FROM users ORDER BY created_at DESC")->fetchAll();

// Get recent activities
$recentActivities = $pdo->query("
    SELECT ua.*, u.username, u.email 
    FROM user_activity ua 
    JOIN users u ON ua.user_id = u.id 
    ORDER BY ua.created_at DESC 
    LIMIT 20
")->fetchAll();
?>

    <main class="max-w-7xl mx-auto px-4 pt-8 pb-20">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center text-white">
                <i class="fas fa-shield-alt text-xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Admin Panel</h1>
                <p class="text-slate-600 dark:text-slate-400">System overview and management</p>
            </div>
            <button id="muteToggle" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors flex items-center gap-2">
                <i id="muteIcon" class="fas fa-volume-up"></i>
                <span id="muteText">Sound On</span>
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Total Users</p>
                        <p class="text-3xl font-bold text-slate-900 dark:text-slate-100"><?php echo $userCount; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-indigo-600 dark:text-indigo-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Online Now</p>
                        <p id="onlineCount" class="text-3xl font-bold text-green-600 dark:text-green-400">
                            <?php echo count($onlineUsers); ?>
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            <i class="fas fa-sync-alt text-[10px]"></i> Live updating
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-circle text-green-600 dark:text-green-400 text-xl animate-pulse"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Friendships</p>
                        <p id="friendshipCount" class="text-3xl font-bold text-slate-900 dark:text-slate-100">
                            <?php echo $friendshipCount; ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-friends text-amber-600 dark:text-amber-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Activities</p>
                        <p id="activityCount" class="text-3xl font-bold text-slate-900 dark:text-slate-100">
                            <?php echo $activityCount; ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live User Status Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Online Users (Last 2 minutes) -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 bg-green-50 dark:bg-green-900/20">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-circle text-green-500 text-xs"></i>
                        <h3 class="font-bold text-slate-900 dark:text-slate-100">Online</h3>
                        <span class="text-xs text-slate-600 dark:text-slate-400">(last 2 min)</span>
                    </div>
                </div>
                <div class="p-4 space-y-3 max-h-64 overflow-y-auto">
                    <?php if (empty($onlineUsers)): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">No users online</p>
                    <?php else: ?>
                        <?php foreach ($onlineUsers as $user): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <i class="fas fa-user-circle text-2xl text-green-500"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    <?php 
                                        $diff = time() - strtotime($user['last_active']);
                                        if ($diff < 60) echo 'Active now';
                                        else echo floor($diff / 60) . ' min ago';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Idle Users (2-5 minutes) -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 bg-amber-50 dark:bg-amber-900/20">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-circle text-amber-500 text-xs"></i>
                        <h3 class="font-bold text-slate-900 dark:text-slate-100">Idle</h3>
                        <span class="text-xs text-slate-600 dark:text-slate-400">(2-5 min)</span>
                    </div>
                </div>
                <div class="p-4 space-y-3 max-h-64 overflow-y-auto">
                    <?php if (empty($idleUsers)): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">No idle users</p>
                    <?php else: ?>
                        <?php foreach ($idleUsers as $user): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <i class="fas fa-user-circle text-2xl text-amber-500"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    <?php 
                                        $diff = time() - strtotime($user['last_active']);
                                        echo floor($diff / 60) . ' min ago';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Offline Users (>5 minutes) -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/30">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-circle text-slate-400 text-xs"></i>
                        <h3 class="font-bold text-slate-900 dark:text-slate-100">Offline</h3>
                        <span class="text-xs text-slate-600 dark:text-slate-400">(>5 min)</span>
                    </div>
                </div>
                <div class="p-4 space-y-3 max-h-64 overflow-y-auto">
                    <?php if (empty($offlineUsers)): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">All users active!</p>
                    <?php else: ?>
                        <?php foreach ($offlineUsers as $user): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <i class="fas fa-user-circle text-2xl text-slate-400"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    <?php 
                                        $diff = time() - strtotime($user['last_active']);
                                        if ($diff < 3600) echo floor($diff / 60) . ' min ago';
                                        elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                                        else echo floor($diff / 86400) . ' days ago';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 mb-8">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">All Users</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Last Active</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($users as $user): ?>
                        <?php 
                            $isOnline = isUserOnline($user['id']);
                            $lastActive = strtotime($user['last_active']);
                            $timeDiff = time() - $lastActive;
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <i class="fas fa-user-circle text-3xl text-slate-400 dark:text-slate-500"></i>
                                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 <?php echo $isOnline ? 'bg-green-500' : 'bg-slate-400'; ?> border-2 border-white dark:border-slate-800 rounded-full"></div>
                                    </div>
                                    <span class="font-medium text-slate-900 dark:text-slate-100"><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    <?php if ($user['manual_status'] === 'online'): ?>
                                        bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                                    <?php elseif ($user['manual_status'] === 'offline'): ?>
                                        bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400
                                    <?php else: ?>
                                        bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400
                                    <?php endif; ?>">
                                    <?php echo ucfirst($user['manual_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                <?php 
                                    if ($timeDiff < 60) echo 'Just now';
                                    elseif ($timeDiff < 3600) echo floor($timeDiff / 60) . ' min ago';
                                    elseif ($timeDiff < 86400) echo floor($timeDiff / 3600) . ' hours ago';
                                    else echo floor($timeDiff / 86400) . ' days ago';
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">Recent Activity</h2>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php foreach ($recentActivities as $activity): ?>
                <div class="p-6 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-start gap-4">
                        <i class="fas fa-user-circle text-2xl text-slate-400 dark:text-slate-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-slate-900 dark:text-slate-100">
                                <span class="font-semibold"><?php echo htmlspecialchars($activity['username']); ?></span>
                                <span class="text-slate-600 dark:text-slate-400"> <?php echo htmlspecialchars($activity['action']); ?></span>
                                <span class="font-semibold text-indigo-600 dark:text-indigo-400"><?php echo htmlspecialchars($activity['show_name']); ?></span>
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        // Notification sound - subtle ping
        const joinSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2354/2354.wav');
        joinSound.volume = 0.3; // Keep it subtle
        
        // Mute toggle state
        let isMuted = localStorage.getItem('adminSoundMuted') === 'true';
        
        // Set initial mute state
        function updateMuteButton() {
            const muteIcon = document.getElementById('muteIcon');
            const muteText = document.getElementById('muteText');
            if (isMuted) {
                muteIcon.className = 'fas fa-volume-mute';
                muteText.textContent = 'Sound Off';
            } else {
                muteIcon.className = 'fas fa-volume-up';
                muteText.textContent = 'Sound On';
            }
        }
        
        // Mute toggle handler
        document.getElementById('muteToggle').addEventListener('click', function() {
            isMuted = !isMuted;
            localStorage.setItem('adminSoundMuted', isMuted);
            updateMuteButton();
        });
        
        updateMuteButton();
        
        // Live updating counters with pulse animation and sound
        let currentCounts = {
            online: <?php echo count($onlineUsers); ?>,
            friendships: <?php echo $friendshipCount; ?>,
            activities: <?php echo $activityCount; ?>
        };
        
        function animateCounter(element, className) {
            element.classList.remove(className);
            void element.offsetWidth; // Force reflow to restart animation
            element.classList.add(className);
        }
        
        function updateCounters() {
            fetch('/serieslist/api_online_count.php')
                .then(response => response.json())
                .then(data => {
                    // Update online count
                    const onlineElement = document.getElementById('onlineCount');
                    
                    // Check if someone joined (count increased)
                    if (data.online > currentCounts.online) {
                        // Play sound if not muted
                        if (!isMuted) {
                            joinSound.play().catch(e => {
                                console.log("Audio autoplay blocked. User must interact with page first.");
                            });
                        }
                        
                        // Animate the counter
                        animateCounter(onlineElement, 'pulse-update-green');
                    } else if (data.online !== currentCounts.online) {
                        // Just animate without sound (someone left or went idle)
                        animateCounter(onlineElement, 'pulse-update-green');
                    }
                    
                    // Update the displayed count
                    if (data.online !== currentCounts.online) {
                        currentCounts.online = data.online;
                        onlineElement.textContent = data.online;
                    }
                })
                .catch(error => console.error('Error updating counters:', error));
        }
        
        // Update every 5 seconds
        setInterval(updateCounters, 5000);
        
        // Run once on page load
        updateCounters();
    </script>

<?php include 'footer.php'; ?>
