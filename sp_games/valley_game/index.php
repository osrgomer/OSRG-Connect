<?php
// Fallback PHP index to serve index.html in case static files are blocked by the server config.
$index = __DIR__ . '/index.html';
if (file_exists($index)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($index);
    exit;
}
http_response_code(404);
echo 'Not found';
