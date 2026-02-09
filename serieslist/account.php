<?php
session_start();

// Handle OAuth status messages
$status_message = '';
$status_type = '';

if (isset($_GET['connected'])) {
    $status_message = ucfirst($_GET['connected']) . ' connected successfully!';
    $status_type = 'success';
} elseif (isset($_GET['disconnected'])) {
    $status_message = ucfirst($_GET['disconnected']) . ' disconnected successfully!';
    $status_type = 'success';
} elseif (isset($_GET['error'])) {
    $status_message = 'Connection failed. Please try again.';
    $status_type = 'error';
}

// Get connection status
$connections = $_SESSION['connections'] ?? [];

// Ensure username is set
if (!isset($_SESSION['username']) && isset($_SESSION['user_email'])) {
    $_SESSION['username'] = $_SESSION['user_email'];
}

// Get current user info from DATABASE
echo "<!-- DEBUG: user_id in session: " . ($_SESSION['user_id'] ?? 'NOT SET') . " -->";
echo "<!-- DEBUG: admin_origin: " . ($_SESSION['admin_origin'] ?? 'NOT SET') . " -->";

if (isset($_SESSION['user_id'])) {
    require_once 'db.php';
    updateLastActive($_SESSION['user_id']);
    
    $db = getDB();
    $stmt = $db->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Database returned: " . print_r($userData, true) . " -->";
    
    if ($userData) {
        $current_user = $userData['username'];
        $user_email = $userData['email'];
        $user_name = $userData['username'];
        $user_avatar = $userData['avatar'];
    } else {
        // Fallback
        $current_user = $_SESSION['username'] ?? 'guest';
        $user_email = $_SESSION['user_email'] ?? 'user@example.com';
        $user_name = $_SESSION['user_name'] ?? 'User';
        $user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name);
    }
} else {
    $current_user = $_SESSION['username'] ?? 'guest';
    $user_email = $_SESSION['user_email'] ?? 'user@example.com';
    $user_name = $_SESSION['user_name'] ?? 'User';
    $user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user_name);
}

echo "<!-- DEBUG: Final values - user: $current_user, email: $user_email, avatar: $user_avatar -->";

$current_page = 'account';
$page_title = 'Account - SeriesList';
$extra_head = '';
include 'header.php';
?>

    <main class="max-w-5xl mx-auto px-4 pt-8 pb-20">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Left Sidebar -->
            <aside class="w-full lg:w-64 space-y-6">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center shadow-sm">
                    <div class="relative inline-block group cursor-pointer" onclick="openAvatarModal()">
                        <img id="profileAvatar" src="<?php echo htmlspecialchars($user_avatar ?? 'https://ui-avatars.com/api/?name=User'); ?>" class="w-24 h-24 rounded-full border-4 border-indigo-500 group-hover:border-indigo-600 shadow-lg mx-auto object-cover transition-all" />
                        <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <i class="fas fa-camera text-white"></i>
                        </div>
                    </div>
                    <h2 id="profileUsername" class="text-slate-800 font-bold text-lg mt-4">User</h2>

                </div>

                <nav class="flex flex-col text-sm font-medium">
                    <a href="#" onclick="showSection('profile')" class="bg-indigo-50 text-indigo-600 px-4 py-3 rounded-t-lg border-b border-slate-100" id="profileTab">Profile Settings</a>
                    <a href="#" onclick="showSection('avatar')" class="bg-white hover:bg-slate-50 text-slate-600 px-4 py-3 border-b border-slate-100 transition-colors" id="avatarTab">Avatar & Cover</a>
                    <a href="#" onclick="showSection('security')" class="bg-white hover:bg-slate-50 text-slate-600 px-4 py-3 border-b border-slate-100 transition-colors" id="securityTab">Security</a>
                    <a href="#" onclick="showSection('connections')" class="bg-white hover:bg-slate-50 text-slate-600 px-4 py-3 rounded-b-lg transition-colors" id="connectionsTab">Connections</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <section class="flex-1">
                <div class="flex justify-between items-center mb-8">
                    <h1 id="sectionTitle" class="text-slate-800 text-2xl font-bold">Profile Settings</h1>
                    <div id="statusMessage" class="text-green-600 text-sm font-medium hidden"></div>
                    <?php if ($status_message): ?>
                    <div class="<?php echo $status_type === 'success' ? 'text-green-600' : 'text-red-600'; ?> text-sm font-medium">
                        <?php echo htmlspecialchars($status_message); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Profile Settings Section -->
                <div id="profileSection" class="section-content">
                    <form onsubmit="handleSave(event)" class="space-y-6 bg-white border border-slate-200 p-6 sm:p-8 rounded-2xl shadow-sm">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Username</label>
                                <input 
                                    type="text" 
                                    id="usernameInput"
                                    class="w-full p-3 rounded-lg border border-slate-200 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                    value="User"
                                />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Email</label>
                                <input 
                                    type="email" 
                                    id="emailInput"
                                    class="w-full p-3 rounded-lg border border-slate-200 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                    value="user@example.com"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Bio</label>
                            <textarea 
                                id="bioInput"
                                class="w-full p-3 rounded-lg border border-slate-200 text-slate-800 text-sm min-h-[120px] focus:ring-2 focus:ring-indigo-500 outline-none"
                            ></textarea>
                            <p class="text-xs text-slate-500">Markdown is supported for formatting your biography.</p>
                        </div>

                        <div class="pt-6 border-t border-slate-200 flex justify-end">
                            <button 
                                type="submit"
                                id="saveBtn"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm px-8 py-3 rounded-lg transition-all disabled:opacity-50"
                            >
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Avatar & Cover Section -->
                <div id="avatarSection" class="section-content hidden">
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 sm:p-8 rounded-2xl shadow-sm">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-6">Profile Picture</h3>
                        <div class="flex flex-col sm:flex-row items-center gap-6 mb-6">
                            <div class="relative group">
                                <img id="avatarPreview" src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200&h=200&fit=crop" class="w-32 h-32 rounded-full border-4 border-slate-200 dark:border-slate-700 object-cover shadow-lg" />
                                <div class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer" onclick="openAvatarModal()">
                                    <i class="fas fa-camera text-white text-2xl"></i>
                                </div>
                            </div>
                            <div class="flex-1 text-center sm:text-left">
                                <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-2">Choose Your Avatar</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Upload a custom photo or select from our presets</p>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <button onclick="openAvatarModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition-colors shadow-md">
                                        <i class="fas fa-images mr-2"></i>Change Avatar
                                    </button>
                                    <button onclick="removeAvatar()" class="bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 px-6 py-2.5 rounded-lg text-sm font-medium transition-colors">
                                        <i class="fas fa-trash mr-2"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="pt-6 border-t border-slate-200 dark:border-slate-700">
                            <h4 class="font-bold text-slate-800 dark:text-slate-100 mb-3 text-sm">Avatar Options</h4>
                            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <div>
                                        <p class="font-medium text-slate-700 dark:text-slate-200">Upload Custom</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">JPG, PNG, GIF up to 5MB</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <div>
                                        <p class="font-medium text-slate-700 dark:text-slate-200">12 Preset Avatars</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Ready-to-use profile pictures</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <div>
                                        <p class="font-medium text-slate-700 dark:text-slate-200">Generated Avatars</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Based on your initials</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <div>
                                        <p class="font-medium text-slate-700 dark:text-slate-200">Auto-sync</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">Updates across all pages</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Section -->
                <div id="securitySection" class="section-content hidden">
                    <div class="bg-white border border-slate-200 p-6 sm:p-8 rounded-2xl shadow-sm">
                        <h3 class="text-lg font-bold text-slate-800 mb-4">Password & Security</h3>
                        <form onsubmit="handlePasswordChange(event)" class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Current Password</label>
                                <input type="password" id="currentPassword" class="w-full p-3 rounded-lg border border-slate-200 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Enter current password" required />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-600">New Password</label>
                                <input type="password" id="newPassword" class="w-full p-3 rounded-lg border border-slate-200 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Enter new password" required minlength="6" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-slate-600">Confirm Password</label>
                                <input type="password" id="confirmPassword" class="w-full p-3 rounded-lg border border-slate-200 text-slate-800 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Confirm new password" required />
                            </div>
                            <div id="passwordMessage" class="text-sm font-medium hidden"></div>
                            <div class="pt-4">
                                <button type="submit" id="passwordBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm px-8 py-3 rounded-lg transition-all disabled:opacity-50">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Connections Section -->
                <div id="connectionsSection" class="section-content hidden">
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 sm:p-8 rounded-2xl shadow-sm">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2">Connected Services</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">Link your accounts to enhance your experience</p>
                        <div class="space-y-4">
                            <!-- Google -->
                            <div class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center shadow-md">
                                        <i class="fab fa-google text-white text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 dark:text-slate-100">Google Account</p>
                                        <?php if (isset($connections['google']['user_info'])): ?>
                                            <p class="text-xs text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($connections['google']['user_info']['email'] ?? $connections['google']['user_info']['name'] ?? 'Connected'); ?></p>
                                        <?php else: ?>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Sync your watchlist across devices</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (isset($connections['google'])): ?>
                                <div class="flex items-center gap-3">
                                    <?php if (isset($connections['google']['user_info']['picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($connections['google']['user_info']['picture']); ?>" class="w-8 h-8 rounded-full border-2 border-slate-200 dark:border-slate-600" />
                                    <?php endif; ?>
                                    <span class="flex items-center gap-1 text-green-600 dark:text-green-400 text-sm font-medium">
                                        <i class="fas fa-check-circle"></i> Connected
                                    </span>
                                    <a href="oauth.php?provider=google&action=disconnect" class="text-slate-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 text-sm font-medium transition-colors">
                                        <i class="fas fa-unlink"></i>
                                    </a>
                                </div>
                                <?php else: ?>
                                <a href="oauth.php?provider=google&action=connect" class="bg-slate-100 dark:bg-slate-700 hover:bg-indigo-600 dark:hover:bg-indigo-600 text-slate-700 dark:text-slate-300 hover:text-white px-6 py-2 rounded-lg text-sm font-medium transition-all">
                                    Connect
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- GitHub -->
                            <div class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gray-900 dark:bg-slate-700 rounded-xl flex items-center justify-center shadow-md">
                                        <i class="fab fa-github text-white text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 dark:text-slate-100">GitHub</p>
                                        <?php if (isset($connections['github']['user_info'])): ?>
                                            <p class="text-xs text-slate-600 dark:text-slate-400">@<?php echo htmlspecialchars($connections['github']['user_info']['username'] ?? $connections['github']['user_info']['name'] ?? 'Connected'); ?></p>
                                        <?php else: ?>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Connect your developer profile</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (isset($connections['github'])): ?>
                                <div class="flex items-center gap-3">
                                    <?php if (isset($connections['github']['user_info']['picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($connections['github']['user_info']['picture']); ?>" class="w-8 h-8 rounded-full border-2 border-slate-200 dark:border-slate-600" />
                                    <?php endif; ?>
                                    <span class="flex items-center gap-1 text-green-600 dark:text-green-400 text-sm font-medium">
                                        <i class="fas fa-check-circle"></i> Connected
                                    </span>
                                    <a href="oauth.php?provider=github&action=disconnect" class="text-slate-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 text-sm font-medium transition-colors">
                                        <i class="fas fa-unlink"></i>
                                    </a>
                                </div>
                                <?php else: ?>
                                <a href="oauth.php?provider=github&action=connect" class="bg-slate-100 dark:bg-slate-700 hover:bg-indigo-600 dark:hover:bg-indigo-600 text-slate-700 dark:text-slate-300 hover:text-white px-6 py-2 rounded-lg text-sm font-medium transition-all">
                                    Connect
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Spotify (Hidden until Feb 11, 2026 due to Spotify API restrictions) -->
                            <?php if (strtotime('2026-02-11') <= time()): ?>
                            <div class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center shadow-md">
                                        <i class="fab fa-spotify text-white text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 dark:text-slate-100">Spotify</p>
                                        <?php if (isset($connections['spotify']['user_info'])): ?>
                                            <p class="text-xs text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($connections['spotify']['user_info']['name'] ?? 'Connected'); ?></p>
                                        <?php else: ?>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">Track your music tastes</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (isset($connections['spotify'])): ?>
                                <div class="flex items-center gap-3">
                                    <?php if (isset($connections['spotify']['user_info']['picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($connections['spotify']['user_info']['picture']); ?>" class="w-8 h-8 rounded-full border-2 border-slate-200 dark:border-slate-600" />
                                    <?php endif; ?>
                                    <span class="flex items-center gap-1 text-green-600 dark:text-green-400 text-sm font-medium">
                                        <i class="fas fa-check-circle"></i> Connected
                                    </span>
                                    <a href="oauth.php?provider=spotify&action=disconnect" class="text-slate-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 text-sm font-medium transition-colors">
                                        <i class="fas fa-unlink"></i>
                                    </a>
                                </div>
                                <?php else: ?>
                                <a href="oauth.php?provider=spotify&action=connect" class="bg-slate-100 dark:bg-slate-700 hover:bg-indigo-600 dark:hover:bg-indigo-600 text-slate-700 dark:text-slate-300 hover:text-white px-6 py-2 rounded-lg text-sm font-medium transition-all">
                                    Connect
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex gap-3">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                                <div class="text-sm">
                                    <p class="font-medium text-blue-900 dark:text-blue-200 mb-1">Why Connect?</p>
                                    <p class="text-blue-700 dark:text-blue-300 text-xs">Connecting your accounts enables cross-platform syncing, social features, and personalized recommendations based on your preferences.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Avatar Modal -->
    <div id="avatarModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between mb-6">
                <h3 class="text-slate-800 dark:text-slate-100 font-bold text-lg">Choose Your Avatar</h3>
                <button onclick="closeAvatarModal()" class="text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 text-2xl leading-none">&times;</button>
            </div>
            
            <!-- Upload Section -->
            <div class="mb-6 p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600">
                <div class="text-center">
                    <i class="fas fa-cloud-upload-alt text-4xl text-slate-400 dark:text-slate-500 mb-3"></i>
                    <h4 class="font-bold text-slate-700 dark:text-slate-200 mb-2">Upload Custom Avatar</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">JPG, PNG, GIF or WebP. Max size 5MB.</p>
                    <input type="file" id="avatarUpload" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" onchange="handleAvatarUpload(event)" />
                    <button onclick="document.getElementById('avatarUpload').click()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-upload mr-2"></i>Choose File
                    </button>
                </div>
                <div id="uploadProgress" class="hidden mt-4">
                    <div class="w-full bg-slate-200 dark:bg-slate-600 rounded-full h-2">
                        <div id="uploadBar" class="bg-indigo-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                    <p id="uploadStatus" class="text-xs text-slate-600 dark:text-slate-400 mt-2 text-center"></p>
                </div>
            </div>
            
            <!-- Preset Avatars -->
            <div class="mb-6">
                <h4 class="font-bold text-slate-700 dark:text-slate-200 mb-3 text-sm uppercase tracking-wide">Preset Avatars</h4>
                <div class="grid grid-cols-6 gap-3">
                    <img src="https://i.pravatar.cc/150?img=11" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=12" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=13" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=14" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=15" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=16" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=17" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=18" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=19" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=20" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=21" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                    <img src="https://i.pravatar.cc/150?img=22" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar(this.src)" />
                </div>
            </div>
            
            <!-- Generated Avatars -->
            <div>
                <h4 class="font-bold text-slate-700 dark:text-slate-200 mb-3 text-sm uppercase tracking-wide">Generated Avatars</h4>
                <div class="grid grid-cols-6 gap-3" id="generatedAvatars">
                    <!-- Will be populated with initials-based avatars -->
                </div>
            </div>
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

        function openAvatarModal() {
            document.getElementById('avatarModal').classList.remove('hidden');
            generateInitialsAvatars();
        }

        function closeAvatarModal() {
            document.getElementById('avatarModal').classList.add('hidden');
        }
        
        // Generate initials-based avatars
        function generateInitialsAvatars() {
            const userName = document.getElementById('usernameInput').value || 'User';
            const colors = ['4f46e5', 'ef4444', '10b981', 'f59e0b', '8b5cf6', 'ec4899', '06b6d4', 'f97316'];
            const container = document.getElementById('generatedAvatars');
            
            container.innerHTML = colors.map(color => {
                const url = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=${color}&color=fff&size=200`;
                return `<img src="${url}" class="rounded-full cursor-pointer hover:ring-4 ring-indigo-500 transition-all aspect-square object-cover" onclick="selectAvatar('${url}')" />`;
            }).join('');
        }
        
        // Handle avatar upload
        async function handleAvatarUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file size
            if (file.size > 5 * 1024 * 1024) {
                alert('File too large. Maximum size is 5MB.');
                return;
            }
            
            // Show progress
            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('uploadBar');
            const statusText = document.getElementById('uploadStatus');
            
            progressDiv.classList.remove('hidden');
            statusText.textContent = 'Uploading...';
            progressBar.style.width = '0%';
            
            // Create form data
            const formData = new FormData();
            formData.append('avatar', file);
            
            try {
                // Simulate progress
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += 10;
                    if (progress >= 90) {
                        clearInterval(progressInterval);
                    }
                    progressBar.style.width = progress + '%';
                }, 100);
                
                const response = await fetch('api_avatar.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                
                const data = await response.json();
                
                if (data.success) {
                    statusText.textContent = 'Upload successful!';
                    statusText.className = 'text-xs text-green-600 dark:text-green-400 mt-2 text-center font-bold';
                    
                    // Update avatar display
                    document.getElementById('profileAvatar').src = data.avatar_url;
                    document.getElementById('avatarPreview').src = data.avatar_url;
                    
                    // Save to profile
                    const profileData = JSON.parse(localStorage.getItem(userProfileKey) || '{}');
                    profileData.avatar = data.avatar_url;
                    localStorage.setItem(userProfileKey, JSON.stringify(profileData));
                    
                    setTimeout(() => {
                        closeAvatarModal();
                        progressDiv.classList.add('hidden');
                    }, 1500);
                } else {
                    statusText.textContent = 'Error: ' + data.message;
                    statusText.className = 'text-xs text-red-600 dark:text-red-400 mt-2 text-center font-bold';
                }
            } catch (error) {
                console.error('Upload error:', error);
                statusText.textContent = 'Upload failed. Please try again.';
                statusText.className = 'text-xs text-red-600 dark:text-red-400 mt-2 text-center font-bold';
            }
            
            // Reset file input
            event.target.value = '';
        }
        
        // Select avatar from presets
        async function selectAvatar(avatarUrl) {
            try {
                const response = await fetch('api_avatar.php?action=set', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ avatar_url: avatarUrl })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update avatar display
                    document.getElementById('profileAvatar').src = avatarUrl;
                    document.getElementById('avatarPreview').src = avatarUrl;
                    
                    // Save to profile
                    const profileData = JSON.parse(localStorage.getItem(userProfileKey) || '{}');
                    profileData.avatar = avatarUrl;
                    localStorage.setItem(userProfileKey, JSON.stringify(profileData));
                    
                    closeAvatarModal();
                } else {
                    alert('Failed to set avatar: ' + data.message);
                }
            } catch (error) {
                console.error('Error setting avatar:', error);
                alert('Failed to set avatar. Please try again.');
            }
        }

        
        // Remove avatar (reset to default)
        async function removeAvatar() {
            if (!confirm('Reset to default avatar?')) return;
            
            const userName = document.getElementById('usernameInput').value || 'User';
            const defaultAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=4f46e5&color=fff&size=200`;
            
            try {
                const response = await fetch('api_avatar.php?action=set', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ avatar_url: defaultAvatar })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('profileAvatar').src = defaultAvatar;
                    document.getElementById('avatarPreview').src = defaultAvatar;
                    
                    const profileData = JSON.parse(localStorage.getItem(userProfileKey) || '{}');
                    profileData.avatar = defaultAvatar;
                    localStorage.setItem(userProfileKey, JSON.stringify(profileData));
                    
                    showStatusMessage('Avatar reset to default', 'success');
                } else {
                    showStatusMessage('Failed to reset avatar', 'error');
                }
            } catch (error) {
                console.error('Error resetting avatar:', error);
                showStatusMessage('Failed to reset avatar', 'error');
            }
        }
        
        // Show status message helper
        function showStatusMessage(message, type = 'success') {
            const statusEl = document.getElementById('statusMessage');
            statusEl.textContent = message;
            statusEl.className = `text-sm font-medium ${type === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}`;
            statusEl.classList.remove('hidden');
            setTimeout(() => statusEl.classList.add('hidden'), 3000);
        }

        function handleSave(e) {
            e.preventDefault();
            const btn = document.getElementById('saveBtn');
            const status = document.getElementById('statusMessage');
            
            // Get form values
            const username = document.getElementById('usernameInput').value;
            const email = document.getElementById('emailInput').value;
            const bio = document.getElementById('bioInput').value;
            const avatar = document.getElementById('profileAvatar').src;
            
            // Save to localStorage
            const profileData = { username, email, bio, avatar };
            localStorage.setItem(userProfileKey, JSON.stringify(profileData));
            
            btn.disabled = true;
            btn.textContent = 'Saving...';
            
            // Simulate save
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = 'Save Changes';
                status.textContent = 'Profile updated successfully!';
                status.classList.remove('hidden');
                
                // Update display
                document.getElementById('profileUsername').textContent = username;
                
                setTimeout(() => status.classList.add('hidden'), 3000);
            }, 800);
        }

        function selectAvatar(src) {
            document.getElementById('profileAvatar').src = src;
            document.getElementById('avatarPreview').src = src;
            closeAvatarModal();
        }
        
        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.section-content').forEach(s => s.classList.add('hidden'));
            
            // Remove active state from all tabs
            document.querySelectorAll('nav a').forEach(tab => {
                tab.classList.remove('bg-indigo-50', 'text-indigo-600');
                tab.classList.add('bg-white', 'text-slate-600');
            });
            
            // Show selected section and activate tab
            const sections = {
                'profile': { element: 'profileSection', title: 'Profile Settings', tab: 'profileTab' },
                'avatar': { element: 'avatarSection', title: 'Avatar & Cover', tab: 'avatarTab' },
                'security': { element: 'securitySection', title: 'Password & Security', tab: 'securityTab' },
                'connections': { element: 'connectionsSection', title: 'Connected Services', tab: 'connectionsTab' }
            };
            
            const current = sections[section];
            if (current) {
                document.getElementById(current.element).classList.remove('hidden');
                document.getElementById('sectionTitle').textContent = current.title;
                const activeTab = document.getElementById(current.tab);
                activeTab.classList.remove('bg-white', 'text-slate-600');
                activeTab.classList.add('bg-indigo-50', 'text-indigo-600');
            }
        }
        
        function handlePasswordChange(e) {
            e.preventDefault();
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const btn = document.getElementById('passwordBtn');
            const msg = document.getElementById('passwordMessage');
            
            if (newPass !== confirm) {
                msg.textContent = 'Passwords do not match';
                msg.className = 'text-red-600 text-sm font-medium';
                msg.classList.remove('hidden');
                return;
            }
            
            if (newPass.length < 6) {
                msg.textContent = 'Password must be at least 6 characters';
                msg.className = 'text-red-600 text-sm font-medium';
                msg.classList.remove('hidden');
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Updating...';
            
            // Simulate password change
            setTimeout(() => {
                localStorage.setItem('userPassword', newPass);
                msg.textContent = 'Password updated successfully!';
                msg.className = 'text-green-600 text-sm font-medium';
                msg.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = 'Update Password';
                e.target.reset();
                setTimeout(() => msg.classList.add('hidden'), 3000);
            }, 1000);
        }
        
        // Auto-hide status messages after 5 seconds
        <?php if ($status_message): ?>
        setTimeout(() => {
            const statusEl = document.querySelector('.text-green-600, .text-red-600');
            if (statusEl && statusEl.textContent.includes('<?php echo addslashes($status_message); ?>')) {
                statusEl.style.opacity = '0';
                setTimeout(() => statusEl.remove(), 300);
            }
        }, 5000);
        <?php endif; ?>
        
        // Load saved profile data on page load
        const currentUser = '<?php echo addslashes($current_user); ?>';
        const userProfileKey = 'userProfile_' + currentUser;
        
        window.addEventListener('load', () => {
            // Migration: Check for old shared profile data
            if (currentUser !== 'guest') {
                const oldProfile = localStorage.getItem('userProfile');
                if (oldProfile && !localStorage.getItem(userProfileKey)) {
                    // Only migrate if this is the first user (assume old data belongs to first user)
                    console.log('Migrating old profile data to user-specific key');
                    localStorage.setItem(userProfileKey, oldProfile);
                }
            }
            
            const saved = localStorage.getItem(userProfileKey);
            if (saved) {
                const profile = JSON.parse(saved);
                document.getElementById('usernameInput').value = profile.username || '<?php echo addslashes($user_name); ?>';
                document.getElementById('emailInput').value = profile.email || '<?php echo addslashes($user_email); ?>';
                document.getElementById('bioInput').value = profile.bio || '';
                document.getElementById('profileAvatar').src = profile.avatar || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?q=80&w=200&h=200&fit=crop';
                document.getElementById('profileUsername').textContent = profile.username || '<?php echo addslashes($user_name); ?>';
            } else {
                // Set defaults from session for new user
                document.getElementById('usernameInput').value = '<?php echo addslashes($user_name); ?>';
                document.getElementById('emailInput').value = '<?php echo addslashes($user_email); ?>';
                document.getElementById('bioInput').value = '';
                document.getElementById('profileUsername').textContent = '<?php echo addslashes($user_name); ?>';
            }
        });
    </script>
</body>
</html>