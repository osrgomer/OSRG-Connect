<?php
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$pdo = get_db();
try {
    $stmt = $pdo->prepare("SELECT coupon_unlocked FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $unlocked = $stmt->fetchColumn();
} catch (Exception $e) {
    $unlocked = 0;
}
if (!$unlocked) {
    header('Location: settings.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secret Reward - OSRG Connect</title>
    <style>body{font-family: Arial, sans-serif; background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:#fff;min-height:100vh;padding:20px} .container{max-width:800px;margin:40px auto;background:rgba(0,0,0,0.2);padding:30px;border-radius:12px}</style>
</head>
<body>
<?php require_once 'header.php'; ?>
<div class="container">
    <h1>🎉 Secret Area</h1>
    <p>Congratulations — you unlocked a secret reward! This link is permanently available in your Settings page.</p>
    <p style="margin-top:20px;">Enjoy your secret content.</p>
</div>
</body>
</html>