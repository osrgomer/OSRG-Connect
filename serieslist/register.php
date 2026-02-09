<?php
session_start();
require_once 'db.php';

// Handle registration form submission
if ($_POST) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    
    // Check if user already exists
    if (getUserByEmail($email)) {
        $error = "An account with this email already exists.";
    } else {
        try {
            $pdo = getDB();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=4f46e5&color=fff';
            
            $stmt = $pdo->prepare("INSERT INTO users (email, username, password, avatar) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $name, $hashedPassword, $avatar]);
            
            $userId = $pdo->lastInsertId();
            
            // Log them in
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $email;
            $_SESSION['user_name'] = $name;
            
            header('Location: ./');
            exit;
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeriesList - Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-4">
    
    <div class="mb-8 flex items-center gap-2">
        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold shadow-indigo-200 shadow-lg">L</div>
        <span class="text-2xl font-black text-slate-800 tracking-tight">Series<span class="text-indigo-600">List</span></span>
    </div>

    <div class="w-full max-w-md bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
        <h2 class="text-slate-800 text-2xl font-bold mb-1">Join the Club</h2>
        <p class="text-slate-600 text-sm mb-6">Track every show you watch</p>
        
        <?php if (isset($error)): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-2">Full Name</label>
                <input name="name" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-2">Email</label>
                <input name="email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs uppercase tracking-wider font-bold text-slate-600 mb-2">Password</label>
                <input name="password" type="password" required class="w-full bg-slate-50 border border-slate-200 rounded-lg p-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition-all transform active:scale-[0.98]">
                Create Account
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <span class="text-slate-600">Already a member?</span>
            <a href="login.php" class="text-indigo-600 hover:text-indigo-700 font-bold ml-1">Sign in</a>
        </div>
    </div>

    <a href="login.php" class="mt-8 text-slate-600 hover:text-slate-800 text-sm transition-colors flex items-center gap-2">
        <i class="fas fa-arrow-left"></i>
        Back to Login
    </a>
</body>
</html>