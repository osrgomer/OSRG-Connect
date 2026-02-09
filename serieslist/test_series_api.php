<?php
session_start();
require_once 'db.php';

// Force login as omer
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'omersr12@gmail.com';

echo "<h1>📺 Testing Series API</h1>";

// Test 1: Check if series table exists
echo "<h2>Test 1: Check Series Table Structure</h2>";
try {
    $db = getDB();
    $stmt = $db->query("DESCRIBE series");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
}

// Test 2: Try to save a series
echo "<h2>Test 2: Save a Series</h2>";
$testSeries = [
    'title' => 'Test Show',
    'status' => 'watching',
    'rating' => 8.5,
    'progress' => 5,
    'total' => 10,
    'poster' => 'https://example.com/poster.jpg',
    'tmdb_id' => 12345,
    'notes' => 'Test note'
];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'save';

// Simulate POST data
file_put_contents('php://input', json_encode($testSeries));

ob_start();
include 'api_series.php';
$response = ob_get_clean();

echo "<pre>";
echo "Response:\n";
echo $response;
echo "\n\nDecoded:\n";
print_r(json_decode($response, true));
echo "</pre>";

// Test 3: Get all series
echo "<h2>Test 3: Get All Series</h2>";
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get_all';

ob_start();
include 'api_series.php';
$response = ob_get_clean();

echo "<pre>";
echo "Response:\n";
echo $response;
echo "\n\nDecoded:\n";
print_r(json_decode($response, true));
echo "</pre>";
?>
