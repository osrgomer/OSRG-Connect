<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="transition-colors duration-200">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends - SeriesList</title>
    <script src="theme.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-900">

    <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-30 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold shadow-indigo-200 shadow-lg">L</div>
                <span class="font-bold text-xl tracking-tight hidden sm:block text-slate-900 dark:text-slate-100">Series<span class="text-indigo-600">List</span></span>
                <span class="font-bold text-lg tracking-tight sm:hidden text-slate-900 dark:text-slate-100">SL</span>
            </div>
            <div class="hidden md:flex items-center gap-1">
                <a href="./" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">Library</a>
                <a href="friends" class="px-3 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg">Friends</a>
                <a href="trivia" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">Trivia</a>
                <a href="tts" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">Voice</a>
                <a href="account" class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition-colors">Account</a>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="toggleTheme()" class="p-2 text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700" aria-label="Toggle theme">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:inline"></i>
                </button>
                <div class="md:hidden relative">
                    <button id="mobileMenuBtn" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors rounded-lg hover:bg-slate-50" aria-label="Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div id="mobileMenu" class="hidden absolute right-0 top-12 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-lg py-2 min-w-[120px]">
                        <a href="./" class="block px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Library</a>
                        <a href="friends" class="block px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30">Friends</a>
                        <a href="trivia" class="block px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Trivia</a>
                        <a href="tts" class="block px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Voice</a>
                        <a href="account" class="block px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Account</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 pt-8 pb-20">
        <div class="flex flex-col lg:flex-row gap-6">
            
            <!-- Left Sidebar -->
            <aside class="w-full lg:w-72 space-y-4">
                <!-- Find Friends -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-user-plus text-indigo-600"></i> Find Friends
                    </h2>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-3.5 text-slate-400 dark:text-slate-500"></i>
                        <input type="text" id="searchInput" placeholder="Search by username..." class="w-full pl-10 pr-4 py-3 border border-slate-200 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm dark:bg-slate-700 dark:text-slate-100">
                    </div>
                    <div id="searchResults" class="mt-3 space-y-2 max-h-48 overflow-y-auto">
                        <!-- Search results will appear here -->
                    </div>
                </div>

                <!-- Friend Requests -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-bell text-amber-600"></i> Pending Requests
                        <span id="requestCount" class="ml-auto bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-500 text-xs font-bold px-2 py-1 rounded-full">0</span>
                    </h2>
                    <div id="requestsList" class="space-y-2">
                        <p class="text-sm text-slate-500 dark:text-slate-400 italic">No pending requests</p>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 space-y-6">
                <!-- Stats -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 text-center shadow-sm">
                        <div class="text-2xl font-black text-indigo-600" id="friendsCount">0</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Friends</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 text-center shadow-sm">
                        <div class="text-2xl font-black text-green-600" id="activeCount">0</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Active Now</div>
                    </div>
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 text-center shadow-sm">
                        <div class="text-2xl font-black text-purple-600" id="sharedCount">0</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Shared Shows</div>
                    </div>
                </div>

                <!-- Activity Feed -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-rss text-indigo-600"></i> Friend Activity
                    </h2>
                    <div id="activityFeed" class="space-y-4">
                        <p class="text-slate-500 dark:text-slate-400 italic">No recent activity</p>
                    </div>
                </div>

                <!-- Friends List -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-users text-indigo-600"></i> Your Friends
                    </h2>
                    <div id="friendsList" class="space-y-3">
                        <p class="text-slate-500 dark:text-slate-400 italic">Add friends to see them here</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

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

        // Real users data
        let friends = [];
        let requests = [];
        let activities = [];
        
        // Load data from backend
        async function loadFriends() {
            try {
                const response = await fetch('api_users.php?action=get_friends');
                const data = await response.json();
                if (data.success) {
                    friends = data.friends;
                    renderFriends();
                    updateStats();
                }
            } catch (error) {
                console.error('Error loading friends:', error);
            }
        }

        async function loadRequests() {
            try {
                const response = await fetch('api_users.php?action=get_requests');
                const data = await response.json();
                if (data.success) {
                    requests = data.requests;
                    document.getElementById('requestCount').textContent = requests.length;
                }
            } catch (error) {
                console.error('Error loading requests:', error);
            }
        }

        async function loadActivity() {
            try {
                const response = await fetch('api_users.php?action=get_activity');
                const data = await response.json();
                if (data.success) {
                    activities = data.activities;
                    renderActivity();
                }
            } catch (error) {
                console.error('Error loading activity:', error);
            }
        }

        // Search functionality - search real users
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const query = e.target.value.trim();
            const results = document.getElementById('searchResults');
            
            if (query.length < 2) {
                results.innerHTML = '';
                return;
            }

            // Debounce search
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`api_users.php?action=search_users&q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    
                    if (data.success && data.users.length > 0) {
                        results.innerHTML = data.users.map(user => `
                            <div class="flex items-center justify-between p-2 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <img src="${user.avatar}" class="w-8 h-8 rounded-full">
                                    <span class="text-sm font-medium dark:text-slate-200">${user.username}</span>
                                </div>
                                <button onclick="sendRequest('${user.id}')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 text-xs font-bold">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        `).join('');
                    } else {
                        results.innerHTML = '<p class="text-sm text-slate-500 dark:text-slate-400">No users found</p>';
                    }
                } catch (error) {
                    console.error('Search error:', error);
                    results.innerHTML = '<p class="text-sm text-red-500">Error searching users</p>';
                }
            }, 300);
        });

        // Send friend request
        window.sendRequest = async (userId) => {
            try {
                const response = await fetch('api_users.php?action=add_friend', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ friend_id: userId })
                });
                
                const data = await response.json();
                if (data.success) {
                    await loadFriends();
                    await loadActivity();
                    
                    document.getElementById('searchInput').value = '';
                    document.getElementById('searchResults').innerHTML = '';
                    
                    showNotification(data.message);
                } else {
                    showNotification(data.message || 'Failed to add friend', 'error');
                }
            } catch (error) {
                console.error('Error adding friend:', error);
                showNotification('Error adding friend', 'error');
            }
        };

        // Render friends list
        function renderFriends() {
            const list = document.getElementById('friendsList');
            
            if (friends.length === 0) {
                list.innerHTML = '<p class="text-slate-500 dark:text-slate-400 italic">Add friends to see them here</p>';
                return;
            }

            list.innerHTML = friends.map(friend => `
                <div class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <img src="${friend.avatar}" class="w-12 h-12 rounded-full border-2 border-slate-200 dark:border-slate-600">
                            ${friend.online ? '<div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white dark:border-slate-800 rounded-full"></div>' : ''}
                        </div>
                        <div>
                            <p class="font-medium text-slate-800 dark:text-slate-100">${friend.username}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">${friend.online ? 'Online now' : 'Offline'}</p>
                        </div>
                    </div>
                    <button onclick="removeFriend(${friend.id})" class="text-slate-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 p-2">
                        <i class="fas fa-user-times"></i>
                    </button>
                </div>
            `).join('');
        }

        // Render activity feed
        function renderActivity() {
            const feed = document.getElementById('activityFeed');
            
            if (activities.length === 0) {
                feed.innerHTML = '<p class="text-slate-500 dark:text-slate-400 italic">No recent activity</p>';
                return;
            }

            feed.innerHTML = activities.map(activity => {
                const timeAgo = getTimeAgo(activity.time);
                return `
                    <div class="flex items-start gap-3 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                        <img src="${activity.user.avatar}" class="w-10 h-10 rounded-full">
                        <div class="flex-1">
                            <p class="text-sm text-slate-700 dark:text-slate-200">
                                <span class="font-bold">${activity.user.username}</span> ${activity.action} 
                                <span class="font-medium text-indigo-600 dark:text-indigo-400">${activity.show}</span>
                                ${activity.rating ? ` and rated it <span class="text-amber-600 font-bold">${activity.rating}/10</span>` : ''}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">${timeAgo}</p>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Update stats
        function updateStats() {
            document.getElementById('friendsCount').textContent = friends.length;
            document.getElementById('activeCount').textContent = friends.filter(f => f.online).length;
            document.getElementById('sharedCount').textContent = Math.floor(friends.length * 1.5); // Mock shared shows
        }

        // Remove friend
        window.removeFriend = async (userId) => {
            if (!confirm('Remove this friend?')) return;
            
            try {
                const response = await fetch('api_users.php?action=remove_friend', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ friend_id: userId })
                });
                
                const data = await response.json();
                if (data.success) {
                    await loadFriends();
                    await loadActivity();
                    showNotification(data.message);
                } else {
                    showNotification(data.message || 'Failed to remove friend', 'error');
                }
            } catch (error) {
                console.error('Error removing friend:', error);
                showNotification('Error removing friend', 'error');
            }
        };

        // Utility: Time ago
        function getTimeAgo(timestamp) {
            const seconds = Math.floor((Date.now() - timestamp) / 1000);
            if (seconds < 60) return 'Just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            return Math.floor(seconds / 86400) + ' days ago';
        }

        // Show notification
        function showNotification(message, type = 'success') {
            const notif = document.createElement('div');
            const bgColor = type === 'error' ? 'bg-red-600' : 'bg-indigo-600';
            notif.className = `fixed top-20 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-in`;
            notif.textContent = message;
            document.body.appendChild(notif);
            setTimeout(() => notif.remove(), 3000);
        }

        // Initialize page
        (async function init() {
            await loadFriends();
            await loadRequests();
            await loadActivity();
        })();
    </script>
</body>
</html>
