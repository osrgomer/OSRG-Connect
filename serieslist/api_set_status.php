<?php
session_start();
header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Check if POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

// Load database
require_once 'db.php';

// Get status from POST data
$data = json_decode(file_get_contents('php://input'), true);
$status = $data['status'] ?? 'auto';

// Validate status
if (!in_array($status, ['online', 'offline', 'auto'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'No user ID']);
    exit;
}

// Update database
try {
    $result = updateUserStatus($userId, $status);
    
    if ($result) {
        $_SESSION['manual_status'] = $status;
        echo json_encode(['success' => true, 'status' => $status]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
