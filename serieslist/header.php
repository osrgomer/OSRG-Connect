<!DOCTYPE html>
<html lang="en" class="transition-colors duration-200">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'SeriesList Tracker'; ?></title>
    <?php
    // Fix old sessions that don't have user_id
    if (isset($_SESSION['user_email']) && !isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/db.php';
        $user = getUserByEmail($_SESSION['user_email']);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
        }
    }
    ?>
    <script src="<?php echo $base_path ?? ''; ?>theme.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php echo $extra_head ?? ''; ?>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 min-h-screen">

    <?php if (isset($_SESSION['admin_origin'])): ?>
    <!-- GOD MODE WARNING BAR -->
    <div class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-red-600 to-orange-600 text-white text-center py-2 shadow-lg">
        <div class="flex items-center justify-center gap-4">
            <i class="fas fa-user-secret"></i>
            <span class="font-bold">GOD MODE: Viewing as <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
            <button onclick="exitGodMode()" class="bg-white/20 hover:bg-white/30 px-4 py-1 rounded text-sm font-bold">
                Exit & Return to Admin
            </button>
        </div>
    </div>
    <script>
        async function exitGodMode() {
            if (!confirm('Exit God Mode and return to admin panel?')) return;
            
            try {
                const response = await fetch('api_admin.php?action=exit_impersonate');
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'admin.php';
                } else {
                    alert('Failed to exit God Mode: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Exit failed:', error);
                alert('Error exiting God Mode. Check console.');
            }
        }
    </script>
    <?php endif; ?>

    <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 <?php echo isset($_SESSION['admin_origin']) ? 'mt-10' : ''; ?> sticky top-0 z-30 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold shadow-indigo-200 shadow-lg">L</div>
                <span class="font-bold text-xl tracking-tight hidden sm:block text-slate-900 dark:text-slate-100">Series<span class="text-indigo-600">List</span></span>
                <span class="font-bold text-lg tracking-tight sm:hidden text-slate-900 dark:text-slate-100">SL</span>
            </div>
            <nav class="hidden md:flex items-center gap-1">
                <a href="/serieslist/" class="px-3 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'library' ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700'; ?> rounded-lg transition-colors">Library</a>
                <a href="/serieslist/friends" class="px-3 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'friends' ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700'; ?> rounded-lg transition-colors">Friends</a>
                <a href="/serieslist/trivia" class="px-3 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'trivia' ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700'; ?> rounded-lg transition-colors">Trivia</a>
                <a href="/serieslist/voice" class="px-3 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'voice' ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700'; ?> rounded-lg transition-colors">Voice</a>
                <a href="/serieslist/account" class="px-3 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'account' ? 'text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700'; ?> rounded-lg transition-colors">Account</a>
                <?php if ((isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'omersr12@gmail.com') || isset($_SESSION['admin_origin'])): ?>
                <a href="/serieslist/admin" class="px-3 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'admin' ? 'text-red-600 bg-red-50 dark:bg-red-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-700'; ?> rounded-lg transition-colors">
                    <i class="fas fa-shield-alt mr-1"></i>Admin
                </a>
                <?php endif; ?>
            </nav>
            <div class="flex items-center gap-2">
                <!-- Online Status Toggle - Simple Switch -->
                <div class="flex items-center gap-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <?php
                        // Check database for current status
                        $isOnline = false;
                        if (isset($_SESSION['user_id'])) {
                            require_once __DIR__ . '/db.php';
                            $currentStatus = getUserStatus($_SESSION['user_id']);
                            $isOnline = ($currentStatus === 'online');
                        }
                        ?>
                        <input type="checkbox" 
                               id="statusToggle" 
                               class="sr-only peer" 
                               onchange="window.toggleOnlineStatus(this)"
                               <?php echo $isOnline ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-slate-300 dark:bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    </label>
                    <span id="statusText" class="text-sm text-slate-600 dark:text-slate-400"><?php echo $isOnline ? 'Online' : 'Auto'; ?></span>
                </div>
                
                <!-- Notifications Bell -->
                <div class="relative">
                    <button id="notifBtn" class="p-2 text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 relative" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <span id="notifBadge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center">0</span>
                    </button>
                    <div id="notifDropdown" class="hidden absolute right-0 top-12 w-80 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl py-2 max-h-96 overflow-y-auto z-50">
                        <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <h3 class="font-bold text-sm text-slate-800 dark:text-slate-100">Notifications</h3>
                            <button onclick="clearNotifications()" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Clear all</button>
                        </div>
                        <div id="notifList" class="py-2">
                            <p class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No new notifications</p>
                        </div>
                    </div>
                </div>
                
                <button onclick="toggleTheme()" class="p-2 text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700" aria-label="Toggle theme">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:inline"></i>
                </button>
                <div class="md:hidden relative">
                    <button id="mobileMenuBtn" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700" aria-label="Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div id="mobileMenu" class="hidden absolute right-0 top-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg py-2 min-w-[120px]">
                        <a href="/serieslist/" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'library' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Library</a>
                        <a href="/serieslist/friends" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'friends' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Friends</a>
                        <a href="/serieslist/trivia" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'trivia' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Trivia</a>
                        <a href="/serieslist/voice" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'voice' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Voice</a>
                        <a href="/serieslist/account" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'account' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Account</a>
                        <?php if ((isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'omersr12@gmail.com') || isset($_SESSION['admin_origin'])): ?>
                        <a href="/serieslist/admin" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'admin' ? 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">
                            <i class="fas fa-shield-alt mr-1"></i>Admin
                        </a>
                        <?php endif; ?>
                        <hr class="my-2 border-slate-200 dark:border-slate-700">
                        <a href="/serieslist/logout" class="block px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">Logout</a>
                    </div>
                </div>
                <?php echo $extra_buttons ?? ''; ?>
            </div>
        </div>
    </header>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').onclick = () => {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        };
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            const menu = document.getElementById('mobileMenu');
            const btn = document.getElementById('mobileMenuBtn');
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
        
        // Notification system
        let lastCheckedTime = Date.now();
        const seenActivities = new Set(JSON.parse(localStorage.getItem('seenActivities') || '[]'));
        
        // Toggle notification dropdown
        document.getElementById('notifBtn').onclick = (e) => {
            e.stopPropagation();
            const dropdown = document.getElementById('notifDropdown');
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) {
                loadNotifications();
            }
        };
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('notifDropdown');
            const btn = document.getElementById('notifBtn');
            if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
        
        // Load notifications
        async function loadNotifications() {
            try {
                const response = await fetch('/serieslist/api_users.php?action=get_activity');
                const data = await response.json();
                
                if (data.success && data.activities.length > 0) {
                    const notifList = document.getElementById('notifList');
                    const recentActivities = data.activities.slice(0, 10); // Last 10
                    
                    notifList.innerHTML = recentActivities.map(activity => {
                        const activityId = activity.user.id + '_' + activity.show + '_' + activity.time;
                        const isNew = !seenActivities.has(activityId);
                        
                        return `
                            <div class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700 border-b border-slate-100 dark:border-slate-700 last:border-b-0 ${isNew ? 'bg-indigo-50 dark:bg-indigo-900/20' : ''}">
                                <div class="flex items-start gap-3">
                                    <img src="${activity.user.avatar}" class="w-8 h-8 rounded-full flex-shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-slate-800 dark:text-slate-100">
                                            <span class="font-semibold">${activity.user.username}</span>
                                            <span class="text-slate-600 dark:text-slate-400"> ${activity.action}</span>
                                            <span class="font-semibold text-indigo-600 dark:text-indigo-400">${activity.show}</span>
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">${getTimeAgo(activity.time)}</p>
                                    </div>
                                    ${isNew ? '<div class="w-2 h-2 bg-indigo-600 rounded-full flex-shrink-0 mt-1.5"></div>' : ''}
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    // Mark all as seen
                    recentActivities.forEach(activity => {
                        const activityId = activity.user.id + '_' + activity.show + '_' + activity.time;
                        seenActivities.add(activityId);
                    });
                    localStorage.setItem('seenActivities', JSON.stringify([...seenActivities]));
                    updateBadge();
                } else {
                    document.getElementById('notifList').innerHTML = '<p class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No new notifications</p>';
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }
        
        // Update badge count
        async function updateBadge() {
            try {
                const response = await fetch('/serieslist/api_users.php?action=get_activity');
                const data = await response.json();
                
                if (data.success) {
                    const unseenCount = data.activities.filter(activity => {
                        const activityId = activity.user.id + '_' + activity.show + '_' + activity.time;
                        return !seenActivities.has(activityId);
                    }).length;
                    
                    const badge = document.getElementById('notifBadge');
                    if (unseenCount > 0) {
                        badge.textContent = unseenCount > 9 ? '9+' : unseenCount;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            } catch (error) {
                console.error('Error updating badge:', error);
            }
        }
        
        // Clear all notifications
        function clearNotifications() {
            localStorage.setItem('seenActivities', '[]');
            seenActivities.clear();
            updateBadge();
            document.getElementById('notifList').innerHTML = '<p class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No new notifications</p>';
        }
        
        // Online status toggle - Simple on/off
        let currentStatus = '<?php echo isset($currentStatus) ? $currentStatus : 'auto'; ?>';
        
        // Function is already defined above by PHP, just make sure it exists
        window.toggleOnlineStatus = async function(checkbox) {
            const statusTextEl = document.getElementById('statusText');
            const newStatus = checkbox.checked ? 'online' : 'auto';
            if (statusTextEl) {
                statusTextEl.textContent = checkbox.checked ? 'Online' : 'Auto';
            }
            
            console.log('Setting status to:', newStatus);
            
            try {
                const response = await fetch('/serieslist/api_set_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: newStatus })
                });
                
                const data = await response.json();
                console.log('Status response:', data);
                
                if (data.success) {
                    currentStatus = newStatus;
                } else {
                    // Revert on failure
                    checkbox.checked = !checkbox.checked;
                    if (statusTextEl) {
                        statusTextEl.textContent = currentStatus === 'online' ? 'Online' : 'Auto';
                    }
                    alert('Failed to update status: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Failed to update status:', error);
                // Revert on error
                checkbox.checked = !checkbox.checked;
                if (statusTextEl) {
                    statusTextEl.textContent = currentStatus === 'online' ? 'Online' : 'Auto';
                }
                alert('Error updating status');
            }
        };
        
        // Time ago helper
        function getTimeAgo(timestamp) {
            const seconds = Math.floor((Date.now() - timestamp) / 1000);
            if (seconds < 60) return 'Just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
            return Math.floor(seconds / 86400) + 'd ago';
        }
        
        // Check for new notifications every 30 seconds
        setInterval(updateBadge, 30000);
        updateBadge(); // Initial check
    </script>
