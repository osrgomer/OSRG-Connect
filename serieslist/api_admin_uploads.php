<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_email'] !== 'omersr12@gmail.com') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/db.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload error']);
        exit;
    }
    
    $title = $_POST['title'] ?? 'Untitled';
    $file = $_FILES['image'];
    
    // Validate type (images and text files)
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'text/plain', 'text/csv', 'text/markdown', 'application/json',
        'application/xml', 'text/xml'
    ];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: images, txt, csv, md, json, xml']);
        exit;
    }
    
    // Validate size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large (max 5MB)']);
        exit;
    }
    
    // Create uploads directory
    $uploadDir = __DIR__ . '/uploads/admin/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $imagePath = 'uploads/admin/' . $filename;
        
        // Save to database with file type
        $stmt = $db->prepare("INSERT INTO admin_uploads (title, image_path, uploaded_by, file_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $imagePath, $_SESSION['user_id'], $fileType]);
        
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'image_path' => $imagePath,
            'file_type' => $fileType,
            'id' => $db->lastInsertId()
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all uploads
    $stmt = $db->query("SELECT u.*, usr.username FROM admin_uploads u JOIN users usr ON u.uploaded_by = usr.id ORDER BY u.uploaded_at DESC");
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'uploads' => $uploads]);
    
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
