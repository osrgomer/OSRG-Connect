<?php
session_start();

// Temporary debug page - DELETE AFTER USE
echo "<h2>Debug: Global Users</h2>";
echo "<pre>";
print_r($_SESSION['global_users'] ?? 'No global users');
echo "</pre>";

echo "<h2>Available Users:</h2>";
if (isset($_SESSION['global_users'])) {
    foreach ($_SESSION['global_users'] as $email => $user) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
        echo "<strong>Email:</strong> $email<br>";
        echo "<strong>Username:</strong> " . ($user['username'] ?? $user['name'] ?? 'N/A') . "<br>";
        echo "<strong>Has password:</strong> " . (isset($user['password']) ? 'YES' : 'NO') . "<br>";
        echo "<strong>Has id:</strong> " . (isset($user['id']) ? 'YES' : 'NO') . "<br>";
        echo "</div>";
    }
}

echo "<hr><h2>Reset Password</h2>";
if ($_POST && isset($_POST['email']) && isset($_POST['new_password'])) {
    $email = $_POST['email'];
    $newPassword = $_POST['new_password'];
    
    if (isset($_SESSION['global_users'][$email])) {
        $_SESSION['global_users'][$email]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        echo "<div style='background:green;color:white;padding:10px;margin:10px 0;'>Password updated for $email!</div>";
    } else {
        echo "<div style='background:red;color:white;padding:10px;margin:10px 0;'>User not found!</div>";
    }
}
?>

<form method="POST" style="background:#f0f0f0;padding:20px;margin:20px 0;">
    <label>Email: <input type="email" name="email" value="testy@osrg.lol" required style="padding:5px;"></label><br><br>
    <label>New Password: <input type="text" name="new_password" value="1234567890" required style="padding:5px;"></label><br><br>
    <button type="submit" style="padding:10px 20px;background:#4f46e5;color:white;border:none;cursor:pointer;">Reset Password</button>
</form>

