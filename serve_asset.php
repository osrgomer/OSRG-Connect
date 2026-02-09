<?php
// Get the video file path from URL
$video_file = $_GET['file'] ?? '';

// Security check - only allow files from uploads directory
if (empty($video_file) || strpos($video_file, '..') !== false) {
    http_response_code(404);
    exit('File not found');
}

require_once 'config.php';
$pdo = get_db();

$full_path = __DIR__ . '/uploads/' . basename($video_file);
if (!file_exists($full_path)) {
    $full_path = __DIR__ . '/sp_uploads/' . basename($video_file);
}

// 1. Try serving from filesystem
if (file_exists($full_path)) {
    $file_size = filesize($full_path);
    $file_ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
    
    $mime_types = [
        'mp4' => 'video/mp4', 'mov' => 'video/quicktime', 'avi' => 'video/x-msvideo',
        'mp3' => 'audio/mpeg', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'
    ];
    $mime_type = $mime_types[$file_ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . $file_size);
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=3600');
    readfile($full_path);
    exit;
}

// 2. Try serving from Database (BLOB backup)
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT file_content, file_type FROM posts WHERE file_path LIKE ? LIMIT 1");
        $stmt->execute(['%' . basename($video_file)]);
        $file = $stmt->fetch();
        
        if ($file && $file['file_content']) {
            $file_ext = strtolower($file['file_type']);
            $mime_types = [
                'mp4' => 'video/mp4', 'mov' => 'video/quicktime', 'avi' => 'video/x-msvideo',
                'mp3' => 'audio/mpeg', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'
            ];
            $mime_type = $mime_types[$file_ext] ?? 'application/octet-stream';
            
            header('Content-Type: ' . $mime_type);
            header('Content-Length: ' . strlen($file['file_content']));
            header('Cache-Control: public, max-age=3600');
            echo $file['file_content'];
            exit;
        }
    } catch (Exception $e) {}
}

http_response_code(404);
echo "File not found.";
?>
