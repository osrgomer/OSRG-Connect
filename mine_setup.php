<?php
$config_file = 'mine_config.php';
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_key'])) {
    $new_key = $_POST['api_key'];
    $content = "<?php\n// Save your key here\ndefine('GEMINI_API_KEY', '" . addslashes($new_key) . "');\n?>";
    
    if (file_put_contents($config_file, $content)) {
        $message = "System Key Updated Successfully.";
    } else {
        $message = "Error: Could not write to config.php. Check file permissions.";
    }
}

// Read current key (optional, for the UI)
$current_key = "";
if (file_exists($config_file)) {
    include $config_file;
    $current_key = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Void Sage | System Initialization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&family=Inter:wght@400;600;800&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #030507; color: #d1d5db; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .glass { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(30, 41, 59, 0.5); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full glass p-8 rounded-3xl shadow-2xl border-t border-blue-500/30">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-black text-white tracking-tighter mb-2">SYSTEM <span class="text-blue-500 underline decoration-blue-500/50 underline-offset-4">INIT</span></h1>
            <p class="text-[10px] mono text-gray-500 uppercase tracking-[0.3em]">Advanced Configuration Interface</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-3 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold text-center">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 px-1">Gemini API Key</label>
                <input type="password" name="api_key" value="<?= ($current_key === 'your_actual_api_key_here') ? '' : htmlspecialchars($current_key) ?>" 
                       placeholder="Enter your key..."
                       class="w-full bg-black border border-gray-800 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-600 transition text-white">
                <p class="mt-2 text-[9px] text-gray-600 px-1 italic">This key is stored securely in config.php and never shared with users.</p>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white py-3 rounded-xl font-bold text-sm transition-all shadow-lg shadow-blue-900/20 uppercase tracking-widest">
                Authorize & Save
            </button>
            
            <a href="./" class="block text-center text-[10px] font-bold text-gray-500 hover:text-white transition uppercase tracking-widest mt-4">
                Return to Archive ◈
            </a>
        </form>
    </div>
</body>
</html>
