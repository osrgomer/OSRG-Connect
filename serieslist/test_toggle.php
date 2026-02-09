<?php
// Quick toggle test page - shows exactly what's happening
session_start();
require_once 'db.php';

echo "<h1>Toggle Debug Test</h1>";
echo "<pre>";

// Check session
echo "=== SESSION DATA ===\n";
echo "User logged in: " . (isset($_SESSION['user_logged_in']) ? 'YES' : 'NO') . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "User Email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "\n\n";

// Check current status
if (isset($_SESSION['user_id'])) {
    echo "=== CURRENT STATUS ===\n";
    $currentStatus = getUserStatus($_SESSION['user_id']);
    echo "Current manual_status: " . $currentStatus . "\n\n";
    
    // Test update
    echo "=== TESTING UPDATE ===\n";
    echo "Trying to set status to 'online'...\n";
    $result = updateUserStatus($_SESSION['user_id'], 'online');
    echo "Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n\n";
    
    // Check again
    $newStatus = getUserStatus($_SESSION['user_id']);
    echo "New manual_status: " . $newStatus . "\n";
    
    if ($newStatus === 'online') {
        echo "✅ DATABASE UPDATE WORKS!\n";
    } else {
        echo "❌ DATABASE UPDATE FAILED!\n";
    }
} else {
    echo "❌ NOT LOGGED IN - can't test\n";
}

echo "</pre>";

echo "<hr>";
echo "<a href='/serieslist/'>Back to Library</a>";
?>
