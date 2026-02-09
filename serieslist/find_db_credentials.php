<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find MySQL Credentials - SeriesList</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-3xl font-bold text-slate-800 mb-6">🔍 Find Your MySQL Credentials</h1>
        
        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-6 py-4 rounded-lg mb-6">
            <h2 class="font-bold text-lg mb-2">For Hostinger:</h2>
            <ol class="list-decimal list-inside space-y-2">
                <li>Login to <strong>Hostinger hPanel</strong></li>
                <li>Go to <strong>Databases → MySQL Databases</strong></li>
                <li>Find your database or <strong>Create New Database</strong></li>
                <li>Copy the credentials:</li>
                <ul class="ml-8 mt-2 space-y-1">
                    <li>• Database name (usually starts with u_)</li>
                    <li>• Username (usually same as database name)</li>
                    <li>• Password (you set this when creating)</li>
                    <li>• Host (usually localhost)</li>
                </ul>
            </ol>
        </div>

        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-6 py-4 rounded-lg mb-6">
            <strong>⚠️ Important:</strong> If you don't have a database yet, create one in hPanel first!
        </div>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Database Host:</label>
                <input type="text" name="db_host" value="<?php echo $_POST['db_host'] ?? 'localhost'; ?>" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Database Name:</label>
                <input type="text" name="db_name" value="<?php echo $_POST['db_name'] ?? 'serieslist'; ?>" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Database Username:</label>
                <input type="text" name="db_user" value="<?php echo $_POST['db_user'] ?? 'root'; ?>" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Database Password:</label>
                <input type="text" name="db_pass" value="<?php echo $_POST['db_pass'] ?? ''; ?>" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                    placeholder="Leave empty if no password">
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-indigo-700 transition">
                Test Connection
            </button>
        </form>

        <?php
        if ($_POST) {
            $host = $_POST['db_host'] ?? 'localhost';
            $dbname = $_POST['db_name'] ?? 'serieslist';
            $username = $_POST['db_user'] ?? 'root';
            $password = $_POST['db_pass'] ?? '';

            try {
                $pdo = new PDO("mysql:host=$host", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                echo '<div class="mt-6 bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-lg">';
                echo '<h3 class="font-bold text-lg mb-2">✅ Connection Successful!</h3>';
                echo '<p class="mb-4">These credentials work! Click below to use them:</p>';
                echo '<div class="bg-white p-4 rounded font-mono text-sm mb-4">';
                echo "Host: $host<br>";
                echo "Database: $dbname<br>";
                echo "Username: $username<br>";
                echo "Password: " . ($password ? $password : "(empty)");
                echo '</div>';
                
                // Update db.php with correct credentials
                $dbContent = "<?php
// Database configuration
define('DB_HOST', '$host');
define('DB_NAME', '$dbname');
define('DB_USER', '$username');
define('DB_PASS', '$password');

// Create database connection
function getDB() {
    static \$pdo = null;
    
    if (\$pdo === null) {
        try {
            \$dsn = \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\";
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
        } catch (PDOException \$e) {
            die(\"Database connection failed: \" . \$e->getMessage());
        }
    }
    
    return \$pdo;
}

// Helper function to get user by email
function getUserByEmail(\$email) {
    \$pdo = getDB();
    \$stmt = \$pdo->prepare(\"SELECT * FROM users WHERE email = ?\");
    \$stmt->execute([\$email]);
    return \$stmt->fetch();
}

// Helper function to get user by ID
function getUserById(\$id) {
    \$pdo = getDB();
    \$stmt = \$pdo->prepare(\"SELECT * FROM users WHERE id = ?\");
    \$stmt->execute([\$id]);
    return \$stmt->fetch();
}

// Helper function to check if user is online
function isUserOnline(\$userId) {
    \$pdo = getDB();
    \$stmt = \$pdo->prepare(\"SELECT manual_status, last_active FROM users WHERE id = ?\");
    \$stmt->execute([\$userId]);
    \$user = \$stmt->fetch();
    
    if (!\$user) return false;
    
    // Check manual status override first
    if (\$user['manual_status'] === 'offline') return false;
    if (\$user['manual_status'] === 'online') return true;
    
    // Auto mode: check last activity within 2 minutes
    \$lastActive = strtotime(\$user['last_active']);
    return (time() - \$lastActive) < 120;
}

// Update user's last active timestamp
function updateLastActive(\$userId) {
    \$pdo = getDB();
    \$stmt = \$pdo->prepare(\"UPDATE users SET last_active = NOW() WHERE id = ?\");
    \$stmt->execute([\$userId]);
}
?>";
                
                file_put_contents('db.php', $dbContent);
                
                echo '<a href="setup_database.php" class="inline-block bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700">Continue to Database Setup →</a>';
                echo '</div>';
                
            } catch (PDOException $e) {
                echo '<div class="mt-6 bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg">';
                echo '<strong>❌ Connection Failed:</strong> ' . htmlspecialchars($e->getMessage());
                echo '<p class="mt-2 text-sm">Try different credentials above.</p>';
                echo '</div>';
            }
        }
        ?>

        <div class="mt-8 bg-slate-100 p-6 rounded-lg">
            <h3 class="font-bold text-slate-800 mb-2">💡 Hostinger Example:</h3>
            <div class="text-sm text-slate-700 space-y-2">
                <p><strong>Database Name:</strong> <code class="bg-white px-2 py-1 rounded">u123456789_serieslist</code></p>
                <p><strong>Username:</strong> <code class="bg-white px-2 py-1 rounded">u123456789_serieslist</code></p>
                <p><strong>Password:</strong> <code class="bg-white px-2 py-1 rounded">YourPasswordHere</code></p>
                <p><strong>Host:</strong> <code class="bg-white px-2 py-1 rounded">localhost</code></p>
                <p class="text-xs text-amber-600 mt-3">⚠️ Your actual values will be different - get them from hPanel!</p>
            </div>
        </div>
    </div>
</body>
</html>
