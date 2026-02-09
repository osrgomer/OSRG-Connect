<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | SeriesList</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-50">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold shadow-indigo-200 shadow-lg">L</div>
                <span class="font-bold text-xl tracking-tight hidden sm:block">Series<span class="text-indigo-600">List</span></span>
                <span class="font-bold text-lg tracking-tight sm:hidden">SL</span>
            </div>
            <div class="hidden md:flex items-center gap-1">
                <a href="index.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition-colors">Library</a>
                <a href="trivia.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition-colors">Trivia</a>
                <a href="tts/index.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition-colors">Voice</a>
                <a href="account.php" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition-colors">Account</a>
            </div>
            <div class="flex items-center gap-2">
                <div class="md:hidden relative">
                    <button id="mobileMenuBtn" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors rounded-lg hover:bg-slate-50" aria-label="Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div id="mobileMenu" class="hidden absolute right-0 top-12 bg-white border border-slate-200 rounded-lg shadow-lg py-2 min-w-[120px]">
                        <a href="index.php" class="block px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Library</a>
                        <a href="trivia.php" class="block px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Trivia</a>
                        <a href="tts/index.php" class="block px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Voice</a>
                        <a href="account.php" class="block px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Account</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 pt-8 pb-20">
        <h1 class="text-3xl font-bold text-slate-800 mb-6">Privacy Policy</h1>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
            <p class="text-slate-600">Your privacy is important to us. This privacy policy explains how we collect, use, and protect your personal information when you use our website.</p>

            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-4">Information We Collect</h2>
                <ul class="list-disc list-inside space-y-2 text-slate-600">
                    <li>Personal Information: We may collect personal information such as your name, email address, and any other information you provide when you create an account or contact us.</li>
                    <li>Usage Data: We may collect information about how you use our website, including your IP address, browser type, and pages visited.</li>
                </ul>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-4">How We Use Your Information</h2>
                <ul class="list-disc list-inside space-y-2 text-slate-600">
                    <li>To provide and maintain our services.</li>
                    <li>To communicate with you regarding updates, promotions, or support.</li>
                    <li>To analyze website usage and improve our services.</li>
                </ul>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-4">Data Security</h2>
                <p class="text-slate-600">We implement appropriate security measures to protect your personal information from unauthorized access, alteration, disclosure, or destruction.</p>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-4">Your Rights</h2>
                <p class="text-slate-600">You have the right to access, update, or delete your personal information. You can also opt-out of receiving promotional communications from us at any time.</p>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-4">Changes to This Privacy Policy</h2>
                <p class="text-slate-600">We may update this privacy policy from time to time. We will notify you of any changes by posting the new privacy policy on this page.</p>
            </div>

            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-4">Contact Us</h2>
                <p class="text-slate-600">If you have any questions about this privacy policy, please contact us at privacy@osrg.lol.</p>
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
    </script>
</body>
</html>