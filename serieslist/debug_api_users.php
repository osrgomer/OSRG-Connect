<?php
// Enable ALL error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>API Users Debug Test</h1>";

// Test 1: Check if logged in
echo "<h2>Test 1: Session Check</h2>";
echo "<pre>";
echo "Logged in: " . (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true ? 'YES' : 'NO') . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Email: " . ($_SESSION['user_email'] ?? 'NOT SET') . "\n";
echo "</pre>";

// Test 2: Check if db.php loads
echo "<h2>Test 2: Database Connection</h2>";
try {
    require_once 'db.php';
    echo "<p style='color:green'>✅ db.php loaded successfully</p>";
    
    $db = getDB();
    echo "<p style='color:green'>✅ Database connection works</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ ERROR: " . $e->getMessage() . "</p>";
    exit;
}

// Test 3: Test updateLastActive
echo "<h2>Test 3: updateLastActive Function</h2>";
try {
    if (isset($_SESSION['user_id'])) {
        updateLastActive($_SESSION['user_id']);
        echo "<p style='color:green'>✅ updateLastActive works</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No user_id in session</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ ERROR: " . $e->getMessage() . "</p>";
}

// Test 4: Test getUserStatus
echo "<h2>Test 4: getUserStatus Function</h2>";
try {
    if (isset($_SESSION['user_id'])) {
        $status = getUserStatus($_SESSION['user_id']);
        echo "<p style='color:green'>✅ getUserStatus works: $status</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No user_id in session</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ ERROR: " . $e->getMessage() . "</p>";
}

// Test 5: Simulate search_users
echo "<h2>Test 5: Search Users Query</h2>";
try {
    if (isset($_SESSION['user_id'])) {
        $query = 'om';
        $searchTerm = "%$query%";
        $stmt = $db->prepare("
            SELECT id, username, email, avatar 
            FROM users 
            WHERE (username LIKE ? OR email LIKE ?) 
            AND id != ?
            LIMIT 20
        ");
        $stmt->execute([$searchTerm, $searchTerm, $_SESSION['user_id']]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color:green'>✅ Query executed successfully</p>";
        echo "<pre>";
        print_r($users);
        echo "</pre>";
    } else {
        echo "<p style='color:orange'>⚠️ No user_id in session</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 6: Full API call simulation
echo "<h2>Test 6: Full get_friends API Call</h2>";
$_GET['action'] = 'get_friends';
ob_start();
include 'api_users.php';
$response = ob_get_clean();
echo "<pre>Response: " . htmlspecialchars($response) . "</pre>";

echo "<h2>Test 7: Full search_users API Call</h2>";
$_GET['action'] = 'search_users';
$_GET['q'] = 'om';
ob_start();
include 'api_users.php';
$response = ob_get_clean();
echo "<pre>Response: " . htmlspecialchars($response) . "</pre>";
?>
