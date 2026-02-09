<?php
session_start();
header('Content-Type: application/json');

// Test 1: Can we even reach this point?
$tests = ['reached_script' => true];

// Test 2: Can we require db.php?
try {
    require_once 'db.php';
    $tests['db_required'] = true;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'db.php failed', 'message' => $e->getMessage()]);
    exit;
}

// Test 3: Does updateUserStatus function exist?
$tests['function_exists'] = function_exists('updateUserStatus');

// Test 4: Can we call it?
if (isset($_SESSION['user_id'])) {
    try {
        $result = updateUserStatus($_SESSION['user_id'], 'online');
        $tests['update_result'] = $result;
        $tests['user_id'] = $_SESSION['user_id'];
    } catch (Exception $e) {
        $tests['update_error'] = $e->getMessage();
    }
} else {
    $tests['session_issue'] = 'No user_id in session';
}

echo json_encode(['success' => true, 'tests' => $tests]);
exit;
