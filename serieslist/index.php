<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Ensure username is set (backward compatibility)
if (!isset($_SESSION['username']) && isset($_SESSION['user_email'])) {
    $_SESSION['username'] = $_SESSION['user_email'];
}

// Update last active timestamp in database
if (isset($_SESSION['user_id'])) {
    require_once 'db.php';
    updateLastActive($_SESSION['user_id']);
}

$current_page = 'library';
$page_title = 'SeriesList Tracker';
$extra_head = '
    <style>
        .modal-backdrop { background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); }
        .animate-in { animation: fadeIn 0.2s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .spinner { border: 3px solid rgba(79, 70, 229, 0.1); border-top: 3px solid #4f46e5; border-radius: 50%; width: 24px; height: 24px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Custom scrollbar for a cleaner look */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        /* Better focus states for accessibility */
        button:focus, input:focus, select:focus {
            outline: 2px solid #4f46e5;
            outline-offset: 2px;
        }
        
        /* Smooth transitions */
        * { transition: all 0.2s ease; }
        
        /* Mobile optimizations */
        @media (max-width: 640px) {
            .mobile-stack { flex-direction: column; gap: 0.5rem; }
            .mobile-full { width: 100%; }
            .mobile-text-sm { font-size: 0.75rem; }
        }
    </style>
';
$extra_buttons = '
    <button id="settingsBtn" class="p-2 text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700" aria-label="Settings">
        <i class="fas fa-cog"></i>
    </button>
    <a href="logout.php" class="hidden md:block p-2 text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition-colors rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700" aria-label="Logout">
        <i class="fas fa-sign-out-alt"></i>
    </a>
    <button id="addBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-semibold transition-all flex items-center gap-2 shadow-md active:scale-95">
        <i class="fas fa-plus"></i> <span class="hidden sm:inline">Add Series</span><span class="sm:hidden">Add</span>
    </button>
';
include 'header.php';
?>

    <main class="max-w-5xl mx-auto p-4 space-y-6">
        <!-- Connection Banner -->
        <div id="connectionBanner" class="hidden bg-amber-50 border border-amber-200 p-3 rounded-xl text-amber-800 text-xs font-medium flex items-center gap-2">
            <i class="fas fa-wifi-slash"></i> Offline Mode: Saving locally until connection restored.
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-800 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                <p class="text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">Your Library</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span id="statCount" class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-slate-100">0</span>
                    <span class="text-slate-400 dark:text-slate-500 text-sm">Entries</span>
                </div>
            </div>
            <div class="lg:col-span-2 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-2xl p-5 sm:p-6 text-white shadow-xl relative overflow-hidden group">
                <div class="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -left-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
                <div class="relative z-10">
                    <h3 class="font-bold mb-2 flex items-center gap-2 text-base sm:text-lg">
                        <i class="fas fa-sparkles text-yellow-300"></i> 
                        <span>AI Insights</span>
                    </h3>
                    <p id="aiStatus" class="text-white/90 text-xs sm:text-sm mb-4 min-h-[1.25rem] font-medium">Initializing storage...</p>
                    <button id="recBtn" class="bg-white text-indigo-600 px-5 sm:px-6 py-2.5 rounded-full text-xs sm:text-sm font-bold hover:bg-indigo-50 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl">
                        <i class="fas fa-magic mr-1.5"></i>Get Recommendation
                    </button>
                </div>
            </div>
        </div>

        <div id="listGrid" class="grid grid-cols-1 gap-3">
            <div id="statusMessage" class="py-20 text-center text-slate-400">
                <div class="spinner mx-auto mb-4"></div>
                <p id="loadingText">Connecting to cloud storage...</p>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div id="addModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 modal-backdrop" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md relative z-10 shadow-2xl p-4 sm:p-6 animate-in max-h-[90vh] overflow-y-auto">
            <form id="seriesForm" class="space-y-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-lg dark:text-slate-100">New Entry</h3>
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 p-1 rounded" aria-label="Close modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="space-y-3">
                    <input type="text" id="formTitle" required placeholder="Series Title" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 text-sm" aria-label="Series title">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <select id="formStatus" class="px-4 py-3 border rounded-lg bg-white dark:bg-slate-700 dark:text-slate-100 border-slate-200 dark:border-slate-600 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" aria-label="Status">
                            <option>Watching</option>
                            <option>Completed</option>
                            <option>Planned</option>
                            <option>Dropped</option>
                        </select>
                        <input type="number" id="formRating" min="0" max="10" step="0.1" placeholder="Rating / 10" class="px-4 py-3 border rounded-lg border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" aria-label="Rating out of 10">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="relative">
                            <span class="absolute right-3 top-3 text-[10px] text-slate-400 dark:text-slate-500 font-bold pointer-events-none">EP</span>
                            <input type="number" id="formProgress" placeholder="Watched" class="w-full px-4 py-3 pr-8 border rounded-lg border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" aria-label="Episodes watched">
                        </div>
                        <div class="relative">
                            <span class="absolute right-3 top-3 text-[10px] text-slate-400 dark:text-slate-500 font-bold pointer-events-none">TOTAL</span>
                            <input type="number" id="formTotal" placeholder="Total" class="w-full px-4 py-3 pr-12 border rounded-lg border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" aria-label="Total episodes">
                        </div>
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-100 dark:shadow-indigo-900/20 mt-4 disabled:opacity-50 disabled:cursor-not-allowed">Add to Library</button>
            </form>
        </div>
    </div>

    <div id="settingsModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 modal-backdrop" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md relative z-10 shadow-2xl p-6 animate-in">
            <h3 class="font-bold text-lg mb-4 dark:text-slate-100">AI Settings</h3>
            <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase block mb-1">AI Provider</label>
                    <select id="aiProviderSelect" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                        <option value="gemini">Google Gemini</option>
                        <option value="openai">OpenAI ChatGPT</option>
                        <option value="claude">Anthropic Claude</option>
                        <option value="grok">xAI Grok</option>
                        <option value="perplexity">Perplexity</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase block mb-1">API Key</label>
                    <input type="password" id="apiKeyInput" placeholder="Enter your API key..." class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 italic">Key is stored securely in your profile.</p>
                </div>
                <button id="saveSettingsBtn" class="w-full bg-slate-800 dark:bg-slate-700 text-white font-bold py-2 rounded-lg hover:bg-slate-900 dark:hover:bg-slate-600 transition-colors">Save Configuration</button>
            </div>
        </div>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
        import { getAuth, signInAnonymously, signInWithCustomToken, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
        import { getFirestore, collection, addDoc, onSnapshot, doc, updateDoc, deleteDoc, setDoc } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";

        let db, auth, user, currentAppId;
        let aiSettings = { provider: 'gemini', apiKey: '' };
        let currentSeries = [];
        let isCloudMode = false;

        const banner = document.getElementById('connectionBanner');

        const init = async () => {
            const loadingText = document.getElementById('loadingText');
            let attempts = 0;

            const checkEnvironment = () => {
                const hasConfig = typeof __firebase_config !== 'undefined' && __firebase_config;
                if (hasConfig) {
                    setupFirebase();
                } else if (attempts < 10) {
                    attempts++;
                    setTimeout(checkEnvironment, 1000);
                } else {
                    setupOfflineMode();
                }
            };
            checkEnvironment();
        };

        const setupFirebase = async () => {
            try {
                const firebaseConfig = JSON.parse(__firebase_config);
                currentAppId = typeof __app_id !== 'undefined' ? __app_id : 'series-tracker-v1';
                const app = initializeApp(firebaseConfig);
                auth = getAuth(app);
                db = getFirestore(app);

                onAuthStateChanged(auth, (u) => {
                    if (u) {
                        user = u;
                        isCloudMode = true;
                        banner.classList.add('hidden');
                        startDataListeners();
                    } else {
                        banner.classList.remove('hidden');
                    }
                });

                if (typeof __initial_auth_token !== 'undefined' && __initial_auth_token) {
                    await signInWithCustomToken(auth, __initial_auth_token).catch(() => signInAnonymously(auth));
                } else {
                    await signInAnonymously(auth);
                }
            } catch (e) {
                console.error("Firebase Initialization Failed:", e);
                setupOfflineMode();
            }
        };

        const setupOfflineMode = () => {
            isCloudMode = false;
            const userKey = 'user_<?php echo $_SESSION['username'] ?? 'guest'; ?>';
            
            // Migration: Check for old data under legacy keys and move it (ONE TIME ONLY)
            if (userKey !== 'user_guest') {
                const migrationFlag = localStorage.getItem('migration_completed');
                
                if (!migrationFlag) {
                    // First time migration - check for old shared data
                    const oldBackupData = localStorage.getItem('series_v2_backup');
                    if (oldBackupData && !localStorage.getItem('series_v2_' + userKey)) {
                        console.log('Migrating data from series_v2_backup to ' + userKey);
                        localStorage.setItem('series_v2_' + userKey, oldBackupData);
                    }
                    
                    const oldData = localStorage.getItem('series_v2_user_guest');
                    if (oldData && !localStorage.getItem('series_v2_' + userKey)) {
                        console.log('Migrating data from user_guest to ' + userKey);
                        localStorage.setItem('series_v2_' + userKey, oldData);
                    }
                    
                    // Migrate AI settings
                    const oldSettingsBackup = localStorage.getItem('ai_settings');
                    if (oldSettingsBackup && !localStorage.getItem('ai_settings_' + userKey)) {
                        localStorage.setItem('ai_settings_' + userKey, oldSettingsBackup);
                    }
                    
                    const oldSettings = localStorage.getItem('ai_settings_user_guest');
                    if (oldSettings && !localStorage.getItem('ai_settings_' + userKey)) {
                        localStorage.setItem('ai_settings_' + userKey, oldSettings);
                    }
                    
                    // Mark migration as complete and clean up old keys
                    localStorage.setItem('migration_completed', 'true');
                    localStorage.removeItem('series_v2_backup');
                    localStorage.removeItem('series_v2_user_guest');
                    localStorage.removeItem('ai_settings');
                    localStorage.removeItem('ai_settings_user_guest');
                }
            }
            
            const savedSettings = localStorage.getItem('ai_settings_' + userKey);
            if (savedSettings) {
                aiSettings = JSON.parse(savedSettings);
            }
            document.getElementById('aiStatus').innerText = aiSettings.apiKey ? "AI ready for suggestions." : "Add API Key to unlock recommendations.";
            
            // Load series from MySQL database instead of localStorage
            loadSeriesFromDatabase();
        };
        
        // Load series from MySQL
        async function loadSeriesFromDatabase() {
            try {
                const response = await fetch('/serieslist/api_series.php?action=get_all');
                const data = await response.json();
                
                if (data.success) {
                    currentSeries = data.series || [];
                    console.log('Loaded ' + currentSeries.length + ' entries from MySQL');
                    renderList(currentSeries);
                } else {
                    console.error('Failed to load series:', data.error);
                    currentSeries = [];
                    renderList(currentSeries);
                }
            } catch (error) {
                console.error('Error loading series:', error);
                currentSeries = [];
                renderList(currentSeries);
            }
        }
        
        // Save series to MySQL
        async function saveSeriesToDatabase(series) {
            try {
                const response = await fetch('/serieslist/api_series.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(series)
                });
                const data = await response.json();
                
                if (data.success) {
                    // Reload to get updated data
                    await loadSeriesFromDatabase();
                }
                return data;
            } catch (error) {
                console.error('Error saving series:', error);
                return { success: false, error: error.message };
            }
        }
        
        // Delete series from MySQL
        async function deleteSeriesFromDatabase(id) {
            try {
                const response = await fetch('/serieslist/api_series.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                
                if (data.success) {
                    await loadSeriesFromDatabase();
                }
                return data;
            } catch (error) {
                console.error('Error deleting series:', error);
                return { success: false, error: error.message };
            }
        }

        const startDataListeners = () => {
            if (!user || !db) return;
            const seriesRef = collection(db, 'artifacts', currentAppId, 'public', 'data', 'series_' + user.uid);
            const userKey = 'user_<?php echo $_SESSION['username'] ?? 'guest'; ?>';
            onSnapshot(seriesRef, (snapshot) => {
                currentSeries = snapshot.docs.map(d => ({ id: d.id, ...d.data() }));
                localStorage.setItem('series_v2_' + userKey, JSON.stringify(currentSeries));
                renderList(currentSeries);
            }, (err) => {
                if (err.code === 'permission-denied') setupOfflineMode();
            });

            const settingsRef = doc(db, 'artifacts', currentAppId, 'public', 'data', 'profiles', user.uid);
            onSnapshot(settingsRef, (snap) => {
                if (snap.exists()) {
                    const data = snap.data();
                    aiSettings = {
                        provider: data.aiProvider || 'gemini',
                        apiKey: data.apiKey || ''
                    };
                }
                document.getElementById('aiStatus').innerText = aiSettings.apiKey ? "AI ready for suggestions." : "Add API Key to unlock recommendations.";
            });
        };

        const renderList = (list) => {
            const container = document.getElementById('listGrid');
            document.getElementById('statCount').textContent = list.length;
            
            if (list.length === 0) {
                container.innerHTML = `<div class="py-20 text-center text-slate-400 border-2 border-dashed border-slate-200 rounded-2xl">Your library is empty. Click 'Add Series' to start.</div>`;
                return;
            }

            // Sort by most recently updated
            const sortedList = [...list].sort((a, b) => (b.updatedAt || 0) - (a.updatedAt || 0));

            container.innerHTML = sortedList.map(item => {
                const isOver = item.total > 0 && item.progress >= item.total;
                const progressColor = isOver ? 'text-green-600' : 'text-indigo-600';
                
                return `
                <div class="bg-white p-3 sm:p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 shadow-sm hover:border-indigo-100 hover:shadow-md transition-all animate-in">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-slate-800 truncate text-sm sm:text-base" title="${item.title}">${item.title}</h4>
                        <div class="flex gap-2 sm:gap-3 items-center mt-1 text-xs text-slate-400 flex-wrap">
                            <span class="px-2 py-0.5 ${isOver ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'} rounded uppercase font-bold text-[9px] tracking-tight whitespace-nowrap">
                                ${isOver ? 'Completed' : item.status}
                            </span>
                            <span class="flex items-center gap-1 whitespace-nowrap">
                                <i class="fas fa-star text-yellow-400"></i> 
                                <span class="font-semibold text-slate-600">${item.rating || '—'}</span>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4 flex-shrink-0">
                        <div class="text-left sm:text-right">
                            <div class="flex items-baseline gap-1">
                                <span class="text-lg sm:text-base font-black ${progressColor}">${item.progress || 0}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase">/ ${item.total || '?'} ep</span>
                            </div>
                            ${item.total > 0 && item.progress < item.total ? `<div class="text-[9px] text-slate-400 font-medium">${item.total - item.progress} left</div>` : ''}
                        </div>
                        <div class="flex gap-1">
                            <button onclick="window.incrementValue('${item.id}', ${item.progress || 0}, ${item.total || 0})" class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-colors active:scale-90" aria-label="Increment progress">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                            <button onclick="window.removeEntry('${item.id}')" class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-slate-50 text-slate-300 flex items-center justify-center hover:bg-red-500 hover:text-white transition-colors" aria-label="Remove series">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `}).join('');
        };

        window.incrementValue = async (id, cur, total) => {
            // Prevent 31/30 logic - only increment if below total or total is undefined
            if (total > 0 && cur >= total) return;
            
            const newVal = cur + 1;
            const newStatus = (total > 0 && newVal >= total) ? 'Completed' : 'Watching';
            
            // Get show title for activity logging
            const show = currentSeries.find(s => s.id === id);
            const showTitle = show ? show.title : 'Unknown';

            if (isCloudMode && user && !id.startsWith('local_')) {
                await updateDoc(doc(db, 'artifacts', currentAppId, 'public', 'data', 'series_' + user.uid, id), { 
                    progress: newVal,
                    status: newStatus,
                    updatedAt: Date.now()
                });
            } else {
                const idx = currentSeries.findIndex(s => s.id === id);
                if (idx !== -1) {
                    currentSeries[idx].progress = newVal;
                    currentSeries[idx].status = newStatus;
                    currentSeries[idx].updatedAt = Date.now();
                    
                    // Save to MySQL database
                    await saveSeriesToDatabase(currentSeries[idx]);
                }
                renderList(currentSeries);
            }
            
            // Log activity
            const activityType = newStatus === 'Completed' ? 'completed' : 'updated progress on';
            await fetch('api_activity.php?action=log_activity', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: activityType,
                    show: showTitle,
                    progress: newVal
                })
            }).catch(err => console.log('Activity logging failed:', err));
        };

        window.removeEntry = async (id) => {
            if (isCloudMode && user && !id.startsWith('local_')) {
                await deleteDoc(doc(db, 'artifacts', currentAppId, 'public', 'data', 'series_' + user.uid, id));
            } else {
                // Delete from MySQL database
                await deleteSeriesFromDatabase(id);
            }
        };

        document.getElementById('addBtn').onclick = () => document.getElementById('addModal').classList.remove('hidden');
        document.getElementById('settingsBtn').onclick = () => {
            document.getElementById('aiProviderSelect').value = aiSettings.provider;
            document.getElementById('apiKeyInput').value = aiSettings.apiKey;
            document.getElementById('settingsModal').classList.remove('hidden');
        };

        document.getElementById('saveSettingsBtn').onclick = async () => {
            const provider = document.getElementById('aiProviderSelect').value;
            const key = document.getElementById('apiKeyInput').value.trim();
            
            aiSettings = { provider, apiKey: key };
            
            if (isCloudMode && user) {
                const profileRef = doc(db, 'artifacts', currentAppId, 'public', 'data', 'profiles', user.uid);
                await setDoc(profileRef, { aiProvider: provider, apiKey: key }, { merge: true });
            } else {
                localStorage.setItem('ai_settings_' + getUserKey(), JSON.stringify(aiSettings));
            }
            document.getElementById('settingsModal').classList.add('hidden');
        };

        document.getElementById('seriesForm').onsubmit = async (e) => {
            e.preventDefault();
            const total = Number(document.getElementById('formTotal').value) || 0;
            const progress = Number(document.getElementById('formProgress').value) || 0;
            let status = document.getElementById('formStatus').value;

            // Simple validation: If progress matches total, force Completed status
            if (total > 0 && progress >= total) status = 'Completed';

            const data = {
                title: document.getElementById('formTitle').value,
                status: status,
                rating: Number(document.getElementById('formRating').value) || 0,
                progress: progress,
                total: total,
                updatedAt: Date.now()
            };

            if (isCloudMode && user) {
                await addDoc(collection(db, 'artifacts', currentAppId, 'public', 'data', 'series_' + user.uid), data);
            } else {
                // Save to MySQL database
                await saveSeriesToDatabase(data);
            }
            
            // Log activity
            await fetch('api_activity.php?action=log_activity', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'added to watchlist',
                    show: data.title,
                    rating: data.rating > 0 ? data.rating : null
                })
            }).catch(err => console.log('Activity logging failed:', err));
            
            document.getElementById('addModal').classList.add('hidden');
            e.target.reset();
        };

        document.getElementById('recBtn').onclick = async () => {
            if (!aiSettings.apiKey) return alert("Please add your AI API key in settings.");
            if (currentSeries.length === 0) return alert("Add some series first to get recommendations.");
            
            const aiStatus = document.getElementById('aiStatus');
            const recBtn = document.getElementById('recBtn');
            const originalText = aiStatus.innerText;
            
            recBtn.disabled = true;
            aiStatus.innerText = "Consulting AI...";
            
            try {
                const userList = currentSeries.map(s => `${s.title} (${s.rating}/10)`).join(', ');
                const randomPrompts = [
                    `Based on these shows I liked: ${userList}. Recommend ONE different show I should watch next. Give ONLY the title.`,
                    `I enjoyed watching: ${userList}. Suggest ONE new series similar to these. Just the title please.`,
                    `My favorite shows are: ${userList}. What's ONE show you'd recommend that I haven't seen? Title only.`,
                    `Given my taste in: ${userList}. Pick ONE show that would fit my preferences. Only respond with the title.`,
                    `Shows I've rated highly: ${userList}. Recommend ONE series that matches this taste. Title only please.`
                ];
                const prompt = randomPrompts[Math.floor(Math.random() * randomPrompts.length)];

                const { provider, apiKey } = aiSettings;
                let suggestion = "No suggestion found.";
                
                if (provider === 'gemini') {
                    const res = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=${apiKey}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            contents: [{ parts: [{ text: prompt }] }],
                            generationConfig: { temperature: 0.9, topP: 0.8, maxOutputTokens: 50 }
                        })
                    });
                    const data = await res.json();
                    suggestion = data.candidates?.[0]?.content?.parts?.[0]?.text || "No suggestion found.";
                } else if (provider === 'openai') {
                    const res = await fetch('https://api.openai.com/v1/chat/completions', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${apiKey}`
                        },
                        body: JSON.stringify({
                            model: 'gpt-4o-mini',
                            messages: [{ role: 'user', content: prompt }],
                            max_tokens: 50,
                            temperature: 0.9
                        })
                    });
                    const data = await res.json();
                    suggestion = data.choices?.[0]?.message?.content || "No suggestion found.";
                } else if (provider === 'claude') {
                    const res = await fetch('https://api.anthropic.com/v1/messages', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'x-api-key': apiKey,
                            'anthropic-version': '2023-06-01'
                        },
                        body: JSON.stringify({
                            model: 'claude-3-haiku-20240307',
                            max_tokens: 50,
                            messages: [{ role: 'user', content: prompt }]
                        })
                    });
                    const data = await res.json();
                    suggestion = data.content?.[0]?.text || "No suggestion found.";
                } else if (provider === 'grok') {
                    const res = await fetch('https://api.x.ai/v1/chat/completions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${apiKey}`
                        },
                        body: JSON.stringify({
                            model: 'grok-beta',
                            messages: [{ role: 'user', content: prompt }],
                            max_tokens: 50,
                            temperature: 0.9
                        })
                    });
                    const data = await res.json();
                    suggestion = data.choices?.[0]?.message?.content || "No suggestion found.";
                } else if (provider === 'perplexity') {
                    const res = await fetch('https://api.perplexity.ai/chat/completions', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${apiKey}`
                        },
                        body: JSON.stringify({
                            model: 'llama-3.1-sonar-small-128k-online',
                            messages: [{ role: 'user', content: prompt }],
                            max_tokens: 50,
                            temperature: 0.9
                        })
                    });
                    const data = await res.json();
                    suggestion = data.choices?.[0]?.message?.content || "No suggestion found.";
                }
                
                aiStatus.innerHTML = `Recommended: <span class="text-indigo-400 font-bold">${suggestion}</span>`;
            } catch (err) {
                aiStatus.innerText = "Connection failed. Try again.";
                setTimeout(() => aiStatus.innerText = originalText, 3000);
            } finally {
                recBtn.disabled = false;
            }
        };

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

        init();
    </script>
</body>
</html>