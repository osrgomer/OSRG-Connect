<?php
session_start();
require_once 'db.php';

// Force login as omer for testing
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'omersr12@gmail.com';

echo "<h1>🔍 Testing User Search API</h1>";

// Test 1: Search for "testy"
echo "<h2>Test 1: Searching for 'testy'</h2>";
$_GET['action'] = 'search_users';
$_GET['q'] = 'testy';

ob_start();
include 'api_users.php';
$response = ob_get_clean();

echo "<pre>";
echo "Response:\n";
echo $response;
echo "\n\nDecoded:\n";
print_r(json_decode($response, true));
echo "</pre>";

// Test 2: Check if users exist in database
echo "<h2>Test 2: Direct Database Query</h2>";
$db = getDB();
$stmt = $db->query("SELECT id, username, email FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($users);
echo "</pre>";

// Test 3: Test search query manually
echo "<h2>Test 3: Manual Search Query</h2>";
$searchTerm = "%testy%";
$stmt = $db->prepare("SELECT id, username, email, avatar FROM users WHERE (username LIKE ? OR email LIKE ?) AND id != ? LIMIT 20");
$stmt->execute([$searchTerm, $searchTerm, 1]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($results);
echo "</pre>";
?>
