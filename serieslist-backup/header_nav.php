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
    </script>
