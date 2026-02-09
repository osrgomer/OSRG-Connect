<?php 
session_start();
include 'config.php'; 

if (isset($_POST['access_password'])) {
    if ($_POST['access_password'] === 'minepro123') {
        $_SESSION['authenticated'] = true;
    } else {
        $auth_error = "INVALID ACCESS KEY";
    }
}
$is_authed = isset($_SESSION['authenticated']) && $_SESSION['authenticated'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MineMod Archive - Modding Intel</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💎</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #030507;
            color: #d1d5db;
        }

        .mono { font-family: 'JetBrains Mono', monospace; }

        .mc-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .chat-container {
            height: 450px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #374151 transparent;
            scroll-behavior: smooth;
        }

        .chat-bubble-ai {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-left: 4px solid #3b82f6;
        }

        .chat-bubble-user {
            background: #1e293b;
            color: white;
            border-radius: 12px 12px 0 12px;
        }

        .mod-card {
            transition: all 0.2s ease-out;
            background: #0b121d;
            border: 1px solid #1e293b;
        }

        .mod-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }

        @keyframes pulse-blue {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .loading-pulse { animation: pulse-blue 1.5s infinite; }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">

    <div class="mc-container">
        <!-- Header -->
        <header class="flex flex-col lg:flex-row items-center justify-between mb-8 gap-6 border-b border-gray-800 pb-8">
            <div class="text-center lg:text-left">
                <h1 class="text-4xl font-black text-white flex items-center justify-center lg:justify-start gap-3 tracking-tight">
                    <span class="text-blue-500">◈</span> MINEMOD <span class="bg-blue-600 text-white px-2 py-0.5 rounded text-2xl">ARCHIVE</span>
                </h1>
                <p class="text-gray-500 text-[10px] font-bold uppercase tracking-[0.4em] mt-2 mono">Advanced Modding Intelligence Unit • 2026.v6</p>
            </div>
            <nav class="flex bg-gray-900/50 p-1.5 rounded-xl border border-gray-800 gap-1">
                <a href="../private_social_platform/" class="px-4 py-2 rounded-lg text-gray-400 font-bold text-sm hover:text-white transition-all flex items-center">
                    🏠 CONNECT
                </a>
                <button onclick="switchTab('finder')" id="btn-finder" class="px-6 py-2 rounded-lg bg-blue-600 text-white font-bold text-sm transition-all shadow-lg shadow-blue-900/20">DATABASE</button>
                <button onclick="switchTab('ai')" id="btn-ai" class="px-6 py-2 rounded-lg text-gray-400 font-bold text-sm hover:text-white transition-all">VOID SAGE AI</button>
                <div class="w-px bg-gray-800 mx-1 my-2"></div>
                <a href="setup" class="px-4 py-2 rounded-lg text-gray-500 font-bold text-[10px] hover:text-blue-400 transition-all flex items-center uppercase tracking-widest">
                    Setup ⚙️
                </a>
            </nav>
        </header>

        <!-- Mod Finder View -->
        <main id="finder-view" class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Sidebar -->
                <aside class="space-y-6 lg:col-span-1">
                    <div class="bg-gray-900/40 p-6 rounded-2xl border border-gray-800 space-y-6">
                        <div>
                            <h2 class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-3">System Platform</h2>
                            <div class="grid grid-cols-2 gap-2">
                                <button onclick="setPlatform('Java')" id="plt-java" class="py-2 text-[10px] font-bold rounded border border-blue-600 bg-blue-900/20 text-white uppercase">Java</button>
                                <button onclick="setPlatform('Bedrock')" id="plt-bedrock" class="py-2 text-[10px] font-bold rounded border border-gray-800 text-gray-500 uppercase">Bedrock</button>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-3">Category Filter</h2>
                            <div class="space-y-1.5" id="pref-grid"></div>
                        </div>

                        <div>
                            <h2 class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-3">Keyword Search</h2>
                            <input type="text" id="mod-search" oninput="getRecommendations()" placeholder="Search index..." 
                                   class="w-full bg-black border border-gray-800 rounded-lg px-4 py-2.5 text-xs focus:outline-none focus:border-blue-600 transition text-white">
                        </div>
                    </div>
                </aside>

                <!-- Results Grid -->
                <div class="lg:col-span-3">
                    <div id="results-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <!-- Cards injected here -->
                    </div>
                </div>
            </div>
        </main>

        <!-- AI View -->
        <main id="ai-view" class="hidden">
            <div class="max-w-4xl mx-auto bg-gray-900/30 border border-gray-800 rounded-3xl overflow-hidden flex flex-col shadow-2xl h-[700px]">
                <?php if (!$is_authed): ?>
                    <!-- Security Terminal -->
                    <div class="flex-grow flex flex-col items-center justify-center p-12 text-center">
                        <div class="w-16 h-16 mb-6 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center">
                            <span class="text-2xl">🔒</span>
                        </div>
                        <h2 class="text-xl font-black text-white mb-2 uppercase tracking-tighter">Security Protocol Active</h2>
                        <p class="text-xs text-gray-500 mb-8 max-w-xs leading-relaxed">Identity verification required to access the Void Sage Intelligence Unit.</p>
                        
                        <form method="POST" class="w-full max-w-xs space-y-4">
                            <input type="password" name="access_password" placeholder="System Access Key..." 
                                   class="w-full bg-black border border-gray-800 rounded-xl px-5 py-3 text-sm focus:outline-none focus:border-red-600 transition text-white text-center">
                            <?php if (isset($auth_error)): ?>
                                <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest animate-pulse"><?= $auth_error ?></p>
                            <?php endif; ?>
                            <button type="submit" class="w-full bg-gray-800 hover:bg-gray-700 text-white py-3 rounded-xl font-bold text-xs transition uppercase tracking-[0.2em]">
                                Authenticate
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Chat Interface -->
                    <div class="p-5 bg-black/40 border-b border-gray-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                            <span class="text-xs font-bold text-white tracking-widest uppercase mono">Void Sage Terminal</span>
                        </div>
                        <div id="status-badge" class="text-[9px] px-2 py-1 rounded bg-gray-800 text-gray-400 font-bold uppercase tracking-tighter">Authenticated</div>
                    </div>
                    
                    <div id="chat-window" class="chat-container p-6 space-y-6 bg-black/20">
                        <div class="chat-bubble-ai p-4 rounded-2xl max-w-[80%] text-sm leading-relaxed">
                            Secure connection verified. System utilizing <b class="text-blue-400">Gemini-2.5-Flash</b> protocols. I am the <b class="text-blue-500">Void Sage</b>. Dual-Platform knowledge (<b>Bedrock</b> & <b>Java</b>) is fully synchronized.
                        </div>
                    </div>
    
                    <div class="p-6 bg-black/40 border-t border-gray-800 flex gap-3">
                        <input type="text" id="ai-input" onkeydown="if(event.key==='Enter')sendToAI()" placeholder="Query the database..." 
                               class="flex-grow bg-black border border-gray-800 rounded-xl px-5 py-4 text-sm focus:outline-none focus:border-blue-600 transition text-white">
                        <button onclick="sendToAI()" id="send-btn" class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-xl font-bold text-sm transition uppercase tracking-widest">
                            Query
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        const apiKey = "<?php echo GEMINI_API_KEY; ?>"; 
        let currentPlatform = 'Java';
        let selectedPrefs = new Set();

        const modsDB = [
            { name: "Sodium + Iris + Lithium", type: ["perf"], platform: "Java", desc: "The definitive performance stack. Mandatory for high FPS and shaders in modern versions." },
            { name: "Distant Horizons", type: ["perf", "world"], platform: "Java", desc: "Adds LOD rendering, allowing for massive view distances with minimal performance impact." },
            { name: "Create", type: ["tech", "build"], platform: "Java", desc: "Rotational energy and kinetic engineering. The industry standard for mechanical automation." },
            { name: "Mekanism", type: ["tech"], platform: "Java", desc: "High-tier technological progression including fusion reactors and digital miners." },
            { name: "Applied Energistics 2", type: ["tech"], platform: "Java", desc: "Digital storage and automation via matter-to-energy conversion." },
            { name: "Modern Industrialization", type: ["tech"], platform: "Java", desc: "A clean, powerful industrial mod focused on multiblocks and steam/electric power." },
            { name: "YUNG's Better Series", type: ["world"], platform: "Java", desc: "Complete overhauls of vanilla structures like Dungeons, Mineshafts, and Strongholds." },
            { name: "Alex's Mobs", type: ["world"], platform: "Java", desc: "Adds dozens of highly detailed animals and monsters with unique drops and mechanics." },
            { name: "Sophisticated Backpacks", type: ["utility"], platform: "Java", desc: "The ultimate storage solution with upgrades for auto-feeding, crafting, and sorting." },
            { name: "Cave Dweller Reimagined", type: ["horror"], platform: "Java", desc: "An intelligent stalking entity that haunts the deep underground levels." },
            { name: "Naturalist", type: ["world"], platform: "Bedrock", desc: "Expansive wildlife update adding realistic behaviors to birds, mammals, and reptiles." },
            { name: "Better on Bedrock", type: ["utility", "world"], platform: "Bedrock", desc: "Large-scale vanilla+ overhaul adding new biomes, quests, and structural improvements." },
            { name: "Eternal End", type: ["world", "rpg"], platform: "Bedrock", desc: "A massive revamp of the End dimension with new flora, fauna, and bosses." },
            { name: "Mowzie's Mobs", type: ["rpg"], platform: "Bedrock", desc: "Powerful, unique boss entities with custom animations and mechanics." },
            { name: "Furniture Add-on", type: ["build"], platform: "Bedrock", desc: "Adds hundreds of functional items for home decoration and building." },
            { name: "3D Weapons", type: ["rpg"], platform: "Bedrock", desc: "Adds highly detailed weapons with custom combat animations." }
        ];

        const categories = [
            { id: 'utility', label: 'Utility', emoji: '🛠️' },
            { id: 'perf', label: 'Performance', emoji: '⚡' },
            { id: 'world', label: 'World', emoji: '🌍' },
            { id: 'tech', label: 'Tech', emoji: '⚙️' },
            { id: 'build', label: 'Building', emoji: '🧱' },
            { id: 'horror', label: 'Horror', emoji: '👁️' },
            { id: 'rpg', label: 'RPG/Combat', emoji: '⚔️' }
        ];

        function setupFilters() {
            const grid = document.getElementById('pref-grid');
            grid.innerHTML = '';
            categories.forEach(cat => {
                const btn = document.createElement('button');
                btn.className = "w-full p-2.5 rounded-lg border border-gray-800 bg-black/20 hover:bg-blue-900/10 transition flex items-center gap-3 text-left";
                btn.innerHTML = `<span class="text-sm">${cat.emoji}</span><span class="text-[10px] font-bold text-gray-400 uppercase">${cat.label}</span>`;
                btn.onclick = () => {
                    if (selectedPrefs.has(cat.id)) {
                        selectedPrefs.delete(cat.id);
                        btn.classList.remove('border-blue-600', 'bg-blue-900/10');
                    } else {
                        selectedPrefs.add(cat.id);
                        btn.classList.add('border-blue-600', 'bg-blue-900/10');
                    }
                    getRecommendations();
                };
                grid.appendChild(btn);
            });
        }

        function setPlatform(p) {
            currentPlatform = p;
            document.getElementById('plt-java').className = p === 'Java' ? "py-2 text-[10px] font-bold rounded border border-blue-600 bg-blue-900/20 text-white uppercase" : "py-2 text-[10px] font-bold rounded border border-gray-800 text-gray-500 uppercase";
            document.getElementById('plt-bedrock').className = p === 'Bedrock' ? "py-2 text-[10px] font-bold rounded border border-blue-600 bg-blue-900/20 text-white uppercase" : "py-2 text-[10px] font-bold rounded border border-gray-800 text-gray-500 uppercase";
            getRecommendations();
        }

        async function sendToAI() {
            const input = document.getElementById('ai-input');
            const chat = document.getElementById('chat-window');
            const query = input.value.trim();
            const statusBadge = document.getElementById('status-badge');
            
            if (!query) return;

            const userDiv = document.createElement('div');
            userDiv.className = "chat-bubble-user p-4 max-w-[80%] ml-auto text-sm";
            userDiv.textContent = query;
            chat.appendChild(userDiv);
            input.value = '';
            
            statusBadge.textContent = "Processing...";
            statusBadge.classList.replace('text-blue-500', 'text-yellow-500');

            const aiDiv = document.createElement('div');
            aiDiv.className = "chat-bubble-ai p-4 rounded-2xl max-w-[80%] text-sm italic text-gray-500";
            aiDiv.innerHTML = '<span class="loading-pulse">Establishing data-link...</span>';
            chat.appendChild(aiDiv);
            chat.scrollTop = chat.scrollHeight;

            try {
                const systemPrompt = `You are the Dual-Platform Minecraft Architect. You have total mastery over BOTH Java Edition (Forge, Fabric, Quilt) and Bedrock Edition (Add-ons, Behavior Packs, Resource Packs, Scripting API). NEVER say you are limited to one platform. If you are asked about Bedrock, provide the Bedrock answer. If asked about Java, provide the Java answer. You know everything about 2026 modding techniques for both. Selected Platform for context: ${currentPlatform}. Query: ${query}`;
                
                const text = await fetchWithRetry(query, systemPrompt);
                aiDiv.innerHTML = text.replace(/\n/g, '<br>');
                aiDiv.classList.remove('italic', 'text-gray-500');
                statusBadge.textContent = "Ready";
                statusBadge.classList.replace('text-yellow-500', 'text-blue-500');
            } catch (err) {
                console.error(err);
                aiDiv.innerHTML = `<span class="text-red-500 font-bold uppercase text-[10px]">Error:</span> Connection failed. API might be undergoing maintenance or requires authentication.`;
                statusBadge.textContent = "Offline";
                statusBadge.classList.replace('text-yellow-500', 'text-red-900');
            }
            chat.scrollTop = chat.scrollHeight;
        }

        async function fetchWithRetry(userQuery, systemPrompt) {
            const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=${apiKey}`;
            const delays = [1000, 2000, 4000, 8000, 16000];

            for (let i = 0; i <= delays.length; i++) {
                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            contents: [{ parts: [{ text: userQuery }] }],
                            systemInstruction: { parts: [{ text: systemPrompt }] }
                        })
                    });

                    if (response.ok) {
                        const data = await response.json();
                        return data.candidates?.[0]?.content?.parts?.[0]?.text || "No response received.";
                    }
                    
                    if (response.status !== 429 && response.status !== 500) {
                        const err = await response.text();
                        throw new Error(`HTTP ${response.status}: ${err}`);
                    }
                } catch (error) {
                    if (i === delays.length) throw error;
                }
                
                if (i < delays.length) {
                    await new Promise(resolve => setTimeout(resolve, delays[i]));
                }
            }
        }

        function getRecommendations() {
            const container = document.getElementById('results-container');
            const search = document.getElementById('mod-search').value.toLowerCase();
            container.innerHTML = '';

            let filtered = modsDB.filter(m => m.platform === currentPlatform);
            if (search) filtered = filtered.filter(m => m.name.toLowerCase().includes(search) || m.desc.toLowerCase().includes(search));
            if (selectedPrefs.size > 0) filtered = filtered.filter(m => m.type.some(t => selectedPrefs.has(t)));

            filtered.forEach(m => {
                const div = document.createElement('div');
                div.className = "mod-card p-5 rounded-2xl flex flex-col justify-between";
                div.innerHTML = `
                    <div>
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-800 text-gray-400 uppercase mono">${m.platform}</span>
                            <div class="flex gap-1">
                                ${m.type.map(() => `<span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>`).join('')}
                            </div>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1">${m.name}</h3>
                        <p class="text-xs text-gray-500 leading-relaxed mb-4">${m.desc}</p>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        ${m.type.map(t => `<span class="text-[8px] font-black uppercase text-gray-600 px-1.5 py-0.5 border border-gray-800 rounded">${t}</span>`).join('')}
                    </div>
                `;
                container.appendChild(div);
            });
        }

        function switchTab(t) {
            document.getElementById('finder-view').classList.toggle('hidden', t !== 'finder');
            document.getElementById('ai-view').classList.toggle('hidden', t !== 'ai');
            document.getElementById('btn-finder').className = t === 'finder' ? "px-6 py-2 rounded-lg bg-blue-600 text-white font-bold text-sm transition-all" : "px-6 py-2 rounded-lg text-gray-400 font-bold text-sm hover:text-white";
            document.getElementById('btn-ai').className = t === 'ai' ? "px-6 py-2 rounded-lg bg-blue-600 text-white font-bold text-sm transition-all" : "px-6 py-2 rounded-lg text-gray-400 font-bold text-sm hover:text-white";
        }

        window.onload = () => {
            setupFilters();
            getRecommendations();
        };
    </script>
</body>
</html>