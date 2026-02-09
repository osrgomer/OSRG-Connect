<?php
session_start();

echo "<h2>Friendship Debug</h2>";

echo "<h3>Omer's Friends:</h3>";
echo "<pre>";
print_r($_SESSION['friends_data']['friends'] ?? 'No friends data');
echo "</pre>";

echo "<h3>Global Friends Data:</h3>";
echo "<pre>";
print_r($_SESSION['friends_data_all'] ?? 'No global friends data');
echo "</pre>";

echo "<h3>User Activity:</h3>";
echo "<pre>";
print_r($_SESSION['user_activity'] ?? 'No activity');
echo "</pre>";

// Clear friendships button
if (isset($_GET['clear'])) {
    unset($_SESSION['friends_data']);
    unset($_SESSION['friends_data_all']);
    echo "<div style='background:green;color:white;padding:10px;margin:10px 0;'>✅ All friendships cleared!</div>";
}

echo "<br><a href='?clear=1' style='padding:10px 20px;background:red;color:white;text-decoration:none;'>Clear All Friendships</a>";
echo " | <a href='index.php'>Go to Library</a>";
?>
