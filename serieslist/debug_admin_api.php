<?php
session_start();
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'omersr12@gmail.com';

echo "<h1>Admin API Debug</h1>";

// Include API directly to test
$_GET['action'] = 'get_stats';

ob_start();
try {
    include 'api_admin.php';
    $response = ob_get_clean();
    
    echo "<h2>Raw API Output:</h2>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    echo "<h2>Parsed Data:</h2>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    if (isset($data['bubbles'])) {
        echo "<h2 style='color:green'>✓ Bubbles found: " . count($data['bubbles']) . "</h2>";
        foreach ($data['bubbles'] as $bubble) {
            echo "<p>{$bubble['username']}: avatar_url = " . htmlspecialchars($bubble['avatar_url']) . "</p>";
        }
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<h2 style='color:red'>ERROR: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
