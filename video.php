<?php
require_once 'config.php';

// Get video path from URL parameter
$video_path = $_GET['v'] ?? '';

// Validate video path
if (empty($video_path) || !file_exists($video_path)) {
    http_response_code(404);
    die('Video not found');
}

// Get file info
$file_info = pathinfo($video_path);
$mime_type = 'video/mp4'; // Default to mp4

// Set proper MIME type based on extension
switch (strtolower($file_info['extension'])) {
    case 'mp4':
        $mime_type = 'video/mp4';
        break;
    case 'mov':
        $mime_type = 'video/quicktime';
        break;
    case 'avi':
        $mime_type = 'video/x-msvideo';
        break;
}

// If requesting raw video file, serve it with proper headers
if (isset($_GET['raw'])) {
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($video_path));
    header('Accept-Ranges: bytes');
    readfile($video_path);
    exit;
}

$page_title = 'Video Viewer - OSRG Connect';
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #000; font-family: Arial, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .video-container { max-width: 90vw; max-height: 90vh; }
        video { width: 100%; height: auto; max-height: 90vh; border-radius: 8px; }
        .controls { text-align: center; margin-top: 20px; }
        .controls a { color: #1877f2; text-decoration: none; padding: 10px 20px; background: white; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="video-container">
        <video controls autoplay>
            <source src="video.php?v=<?= urlencode($video_path) ?>&raw=1" type="<?= $mime_type ?>">
            Your browser does not support the video tag.
        </video>
        <div class="controls">
            <a href="reels.php">← Back to Reels</a>
        </div>
    </div>
</body>
</html>
