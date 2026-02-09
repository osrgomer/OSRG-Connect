<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Update last active timestamp in database
if (isset($_SESSION['user_id'])) {
    require_once 'db.php';
    updateLastActive($_SESSION['user_id']);
}

$current_page = 'friends';
$page_title = 'Friends - SeriesList';
$extra_head = '';
include 'header.php';
?>

    <main class="max-w-5xl mx-auto px-4 pt-8 pb-20">
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
    </main>

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
                    renderActivity(activities);
                }
            } catch (error) {
                console.error('Error loading activity:', error);
                const feed = document.getElementById('activityFeed');
                if (feed) {
                    feed.innerHTML = '<p class="text-slate-500 dark:text-slate-400 italic">Error loading activity feed</p>';
                }
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
                    
                    // Log response for debugging
                    if (!response.ok) {
                        console.error('API responded with status:', response.status);
                        const text = await response.text();
                        console.error('Response body:', text);
                        results.innerHTML = '<p class="text-sm text-red-500">Error searching users (check console)</p>';
                        return;
                    }
                    
                    const data = await response.json();
                    console.log('Search results:', data);
                    
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
                    <button onclick="removeFriend('${friend.id}')" class="text-slate-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 p-2">
                        <i class="fas fa-user-times"></i>
                    </button>
                </div>
            `).join('');
        }

        // Render activity feed
        function renderActivity(activities) {
            const feed = document.getElementById('activityFeed');
            
            if (!activities || activities.length === 0) {
                feed.innerHTML = '<p class="text-slate-500 dark:text-slate-400 italic">No activity yet. Add friends to see their updates!</p>';
                return;
            }
            
            feed.innerHTML = activities.map(activity => {
                const timeAgo = getTimeAgo(activity.timestamp);
                let activityText = '';
                
                if (activity.action === 'completed') {
                    activityText = `completed <strong>${activity.showTitle}</strong>`;
                    if (activity.rating) {
                        activityText += ` and rated it ${activity.rating}/10`;
                    }
                } else if (activity.action === 'added to watchlist') {
                    activityText = `added <strong>${activity.showTitle}</strong> to their watchlist`;
                } else if (activity.action === 'updated progress on') {
                    activityText = `updated progress on <strong>${activity.showTitle}</strong>`;
                } else {
                    activityText = `${activity.action} <strong>${activity.showTitle}</strong>`;
                }
                
                return `
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                        <img src="${activity.avatar}" class="w-10 h-10 rounded-full">
                        <div class="flex-1">
                            <p class="text-sm text-slate-700 dark:text-slate-200">
                                <span class="font-medium">${activity.username}</span> ${activityText}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">${timeAgo}</p>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function getTimeAgo(timestamp) {
            const seconds = Math.floor((Date.now() - timestamp) / 1000);
            if (seconds < 60) return 'just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
            return Math.floor(seconds / 86400) + 'd ago';
        }

        // Update stats
        function updateStats() {
            document.getElementById('friendsCount').textContent = friends.length;
            document.getElementById('activeCount').textContent = friends.filter(f => f.online).length;
            document.getElementById('sharedCount').textContent = 0; // TODO: Calculate real shared shows
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
            const seconds = Math.floor(Date.now() / 1000 - timestamp);
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
