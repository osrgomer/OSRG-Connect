<?php
// Get the video file path from URL
$video_file = $_GET['file'] ?? '';

// Security check - only allow files from uploads directory
if (empty($video_file) || strpos($video_file, '..') !== false) {
    http_response_code(404);
    exit('File not found');
}

$full_path = __DIR__ . '/uploads/' . basename($video_file);

// Check if file exists
if (!file_exists($full_path)) {
    http_response_code(404);
    exit('File not found');
}

// Get file info
$file_size = filesize($full_path);
$file_ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));

// Set MIME type
$mime_types = [
    'mp4' => 'video/mp4',
    'mov' => 'video/quicktime',
    'avi' => 'video/x-msvideo'
];

$mime_type = $mime_types[$file_ext] ?? 'video/mp4';

// Set basic headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . $file_size);
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

// Simply output the file
readfile($full_path);
?>