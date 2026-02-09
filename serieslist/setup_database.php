<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - SeriesList</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-3xl font-bold text-slate-800 mb-6">🗄️ Database Setup</h1>
        
        <?php
        // Load database configuration
        require_once 'db.php';
        
        $host = DB_HOST;
        $dbname = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;
        
        $success = false;
        $error = null;
        
        try {
            // Connect to MySQL (without selecting a database first)
            $pdo = new PDO("mysql:host=$host", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database
            $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<div class='bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4'>✅ Database '$dbname' created</div>";
            
            // Select the database
            $pdo->exec("USE $dbname");
            
            // Create users table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    username VARCHAR(255) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    avatar VARCHAR(500),
                    manual_status ENUM('online', 'offline', 'auto') DEFAULT 'auto',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_last_active (last_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "<div class='bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4'>✅ Table 'users' created</div>";
            
            // Create friendships table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS friendships (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    friend_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_friendship (user_id, friend_id),
                    INDEX idx_user_id (user_id),
                    INDEX idx_friend_id (friend_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "<div class='bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4'>✅ Table 'friendships' created</div>";
            
            // Create activity table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS user_activity (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    show_title VARCHAR(255) NOT NULL,
                    rating TINYINT DEFAULT NULL,
                    progress INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "<div class='bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4'>✅ Table 'user_activity' created</div>";
            
            // Insert initial users (password is "1234567890" for both)
            $hashedPassword = password_hash('1234567890', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (email, username, password, avatar) VALUES
                (?, ?, ?, ?),
                (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE email=email
            ");
            $stmt->execute([
                'omersr12@gmail.com', 
                'Omer Shalom Rimon', 
                $hashedPassword, 
                'https://ui-avatars.com/api/?name=Omer+Shalom+Rimon&background=4f46e5&color=fff',
                'testy@osrg.lol', 
                'testy mesty', 
                $hashedPassword, 
                'https://ui-avatars.com/api/?name=testy+mesty&background=4f46e5&color=fff'
            ]);
            echo "<div class='bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4'>✅ Initial users created (password: 1234567890)</div>";
            
            $success = true;
            
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
        
        if ($success): ?>
            <div class="bg-green-100 border-2 border-green-500 text-green-800 px-6 py-4 rounded-lg mb-6">
                <h2 class="text-xl font-bold mb-2">🎉 Database Setup Complete!</h2>
                <p class="mb-4">Your database is ready to use. All data will now be stored permanently.</p>
                <p class="text-sm font-mono bg-white p-2 rounded">Database: serieslist<br>Users: 2 (Omer & Testy)<br>Password: 1234567890</p>
            </div>
            
            <div class="flex gap-4">
                <a href="login.php" class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold text-center hover:bg-indigo-700 transition">
                    Go to Login
                </a>
                <a href="index.php" class="flex-1 bg-slate-600 text-white px-6 py-3 rounded-lg font-bold text-center hover:bg-slate-700 transition">
                    Go to Library
                </a>
            </div>
        <?php elseif ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
            <p class="text-slate-600">Please check your database configuration in db.php</p>
        <?php endif; ?>
    </div>
</body>
</html>
