<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file was uploaded
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }

    $file = $_FILES['avatar'];
    $userId = $_SESSION['user_email'] ?? 'user';
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
        exit;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . md5($userId . time()) . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $avatarUrl = 'uploads/avatars/' . $filename;
        
        // Save to session
        $_SESSION['user_avatar'] = $avatarUrl;
        
        // Update global users if exists
        if (isset($_SESSION['global_users'][$userId])) {
            $_SESSION['global_users'][$userId]['avatar'] = $avatarUrl;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Avatar uploaded successfully',
            'avatar_url' => $avatarUrl
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    }
} elseif ($action === 'set' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set avatar from URL or preset
    $data = json_decode(file_get_contents('php://input'), true);
    $avatarUrl = $data['avatar_url'] ?? '';
    
    if (empty($avatarUrl)) {
        echo json_encode(['success' => false, 'message' => 'No avatar URL provided']);
        exit;
    }
    
    $userId = $_SESSION['user_email'] ?? 'user';
    
    // Save to session
    $_SESSION['user_avatar'] = $avatarUrl;
    
    // Update global users if exists
    if (isset($_SESSION['global_users'][$userId])) {
        $_SESSION['global_users'][$userId]['avatar'] = $avatarUrl;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Avatar updated successfully',
        'avatar_url' => $avatarUrl
    ]);
} elseif ($action === 'get') {
    // Get current avatar
    $userId = $_SESSION['user_email'] ?? 'user';
    $avatar = $_SESSION['user_avatar'] ?? null;
    
    // If no avatar set, generate default
    if (!$avatar) {
        $userName = $_SESSION['user_name'] ?? 'User';
        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=4f46e5&color=fff&size=200';
    }
    
    echo json_encode([
        'success' => true,
        'avatar_url' => $avatar
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
