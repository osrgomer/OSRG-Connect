    <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-30 shadow-sm">
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
            </nav>
            <div class="flex items-center gap-2">
                <!-- Online Status Toggle -->
                <div class="relative">
                    <button id="statusBtn" class="p-2 text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 relative z-10" aria-label="Online Status">
                        <i id="statusIcon" class="fas fa-circle text-amber-500 text-xs"></i>
                    </button>
                    <div id="statusDropdown" class="hidden absolute right-0 top-12 w-52 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-xl py-2 z-[100]">
                        <button onclick="setStatus('online')" class="w-full px-4 py-2.5 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                            <i class="fas fa-circle text-green-500 text-[10px]"></i>
                            <span class="text-slate-700 dark:text-slate-200 font-medium">Online</span>
                        </button>
                        <button onclick="setStatus('offline')" class="w-full px-4 py-2.5 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                            <i class="fas fa-circle text-slate-400 text-[10px]"></i>
                            <span class="text-slate-700 dark:text-slate-200 font-medium">Appear Offline</span>
                        </button>
                        <button onclick="setStatus('auto')" class="w-full px-4 py-2.5 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-3 transition-colors">
                            <i class="fas fa-circle text-amber-500 text-[10px]"></i>
                            <span class="text-slate-700 dark:text-slate-200 font-medium">Auto</span>
                        </button>
                    </div>
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
                    <button id="mobileMenuBtn" class="p-2 text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700" aria-label="Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div id="mobileMenu" class="hidden absolute right-0 top-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg py-2 min-w-[120px]">
                        <a href="/serieslist/" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'library' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Library</a>
                        <a href="/serieslist/friends" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'friends' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Friends</a>
                        <a href="/serieslist/trivia" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'trivia' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Trivia</a>
                        <a href="/serieslist/voice" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'voice' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Voice</a>
                        <a href="/serieslist/account" class="block px-4 py-2 text-sm font-medium <?php echo ($current_page ?? '') === 'account' ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'; ?>">Account</a>
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
        if (document.getElementById('mobileMenuBtn')) {
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
        }
        
        // Notification system
        let lastCheckedTime = Date.now();
        const seenActivities = new Set(JSON.parse(localStorage.getItem('seenActivities') || '[]'));
        
        // Toggle notification dropdown
        if (document.getElementById('notifBtn')) {
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
        }
        
        // Load notifications
        async function loadNotifications() {
            try {
                const response = await fetch('/serieslist/api_users.php?action=get_activity');
                const data = await response.json();
                
                if (data.success && data.activities.length > 0) {
                    const notifList = document.getElementById('notifList');
                    const recentActivities = data.activities.slice(0, 10);
                    
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
                    
                    recentActivities.forEach(activity => {
                        const activityId = activity.user.id + '_' + activity.show + '_' + activity.time;
                        seenActivities.add(activityId);
                    });
                    localStorage.setItem('seenActivities', JSON.stringify([...seenActivities]));
                    updateNotificationBadge();
                } else {
                    document.getElementById('notifList').innerHTML = '<p class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">No new notifications</p>';
                }
            } catch (error) {
                console.error('Failed to load notifications:', error);
            }
        }
        
        function getTimeAgo(timestamp) {
            const seconds = Math.floor((Date.now() / 1000) - timestamp);
            if (seconds < 60) return 'Just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
            return Math.floor(seconds / 86400) + 'd ago';
        }
        
        async function updateNotificationBadge() {
            try {
                const response = await fetch('/serieslist/api_users.php?action=get_activity');
                const data = await response.json();
                
                if (data.success && data.activities.length > 0) {
                    const unseenCount = data.activities.filter(activity => {
                        const activityId = activity.user.id + '_' + activity.show + '_' + activity.time;
                        return !seenActivities.has(activityId);
                    }).length;
                    
                    const badge = document.getElementById('notifBadge');
                    if (badge && unseenCount > 0) {
                        badge.textContent = unseenCount > 9 ? '9+' : unseenCount;
                        badge.classList.remove('hidden');
                    } else if (badge) {
                        badge.classList.add('hidden');
                    }
                }
            } catch (error) {
                console.error('Failed to update badge:', error);
            }
        }
        
        function clearNotifications() {
            seenActivities.clear();
            localStorage.removeItem('seenActivities');
            loadNotifications();
        }
        
        // Online status management
        let currentStatus = localStorage.getItem('userStatus') || 'auto';
        
        if (document.getElementById('statusBtn')) {
            document.getElementById('statusBtn').onclick = (e) => {
                e.stopPropagation();
                const dropdown = document.getElementById('statusDropdown');
                dropdown.classList.toggle('hidden');
                const notifDropdown = document.getElementById('notifDropdown');
                if (notifDropdown) notifDropdown.classList.add('hidden');
            };
        }
        
        // Make setStatus globally accessible
        window.setStatus = async function(status) {
            try {
                const response = await fetch('/serieslist/api_users.php?action=set_online_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: status })
                });
                
                const data = await response.json();
                if (data.success) {
                    currentStatus = status;
                    localStorage.setItem('userStatus', status);
                    updateStatusIcon();
                    const dropdown = document.getElementById('statusDropdown');
                    if (dropdown) dropdown.classList.add('hidden');
                }
            } catch (error) {
                console.error('Failed to update status:', error);
            }
        };
        
        function updateStatusIcon() {
            const icon = document.getElementById('statusIcon');
            if (!icon) return;
            if (currentStatus === 'online') {
                icon.className = 'fas fa-circle text-green-500 text-xs';
            } else if (currentStatus === 'offline') {
                icon.className = 'fas fa-circle text-slate-400 text-xs';
            } else {
                icon.className = 'fas fa-circle text-amber-500 text-xs';
            }
        }
        
        updateStatusIcon();
        if (currentStatus !== 'auto') {
            window.setStatus(currentStatus);
        }
        
        // Check for new notifications every 30 seconds
        setInterval(updateNotificationBadge, 30000);
        updateNotificationBadge();
    </script>
