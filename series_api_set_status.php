<?php
session_start();
header('Content-Type: application/json');

// Check if logged in
if (!((isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) || isset($_SESSION['user_id']))) {
    echo json_encode(['success' => false, 'message' => 'Not logged in', 'error' => 'Not logged in']);
    exit;
}

// Load database and accept POST or GET for status
require_once 'series_db.php';

// Backwards-compat: resolve user_id from session email if missing
if (empty($_SESSION['user_id']) && !empty($_SESSION['user_email'])) {
    $possibleUser = getUserByEmail($_SESSION['user_email']);
    if ($possibleUser && isset($possibleUser['id'])) {
        $_SESSION['user_id'] = $possibleUser['id'];
    }
}

// Get status from POST JSON/body or GET param
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$status = 'auto';
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = $_POST ?? [];
    if (isset($data['status'])) {
        $status = $data['status'];
    } elseif (isset($_POST['status'])) {
        $status = $_POST['status'];
    } elseif (isset($_GET['status'])) {
        $status = $_GET['status'];
    }
} else {
    $status = $_GET['status'] ?? 'auto';
}

// Validate status
if (!in_array($status, ['online', 'offline', 'auto'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'No user ID', 'error' => 'No user ID']);
    exit;
}

// Update database
try {
    $result = updateUserStatus($userId, $status);
    
    if ($result) {
        $_SESSION['manual_status'] = $status;
        echo json_encode(['success' => true, 'status' => $status, 'message' => 'Status updated']);
    } else {
        // If no rows affected, maybe status already set; check current status
        $current = getUserStatus($userId);
        if ($current === $status) {
            $_SESSION['manual_status'] = $status;
            echo json_encode(['success' => true, 'status' => $status, 'message' => 'Already set']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed', 'error' => 'Update failed']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception', 'error' => $e->getMessage()]);
}
exit;
