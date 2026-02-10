<?php
header('Content-Type: application/json');

// CORS Headers (Full Support)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';
require_once 'config_keys.php';

$raw_input = file_get_contents('php://input');

// Handle empty input
if (empty($raw_input)) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received. Please try again.']);
    exit;
}

$data = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid JSON input: ' . json_last_error_msg(),
        'raw_input' => substr($raw_input, 0, 200) // First 200 chars for debugging
    ]);
    exit;
}

$topic = $data['topic'] ?? '';

if (empty(trim($topic))) {
    http_response_code(400);
    echo json_encode(['error' => 'Topic cannot be empty']);
    exit;
}

// Gemini API key from config
$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';

// Gemini API endpoint and payload (adjust as needed)
$geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey);

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => "Create a JSON array of 10 multiple choice questions about $topic. Each array element should be an object with: 'question' (string), 'options' (array of 4 strings), and 'answer' (string, the correct option). Only return the JSON array, nothing else."]
            ]
        ]
    ]
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($payload),
        'timeout' => 20,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($geminiUrl, false, $context);

if ($result === FALSE) {
    http_response_code(500);
    $error = error_get_last();
    echo json_encode([
        'error' => 'Failed to contact Gemini API: ' . ($error['message'] ?? 'Unknown error'),
        'debug' => $error
    ]);
    exit;
}

// Parse Gemini response and extract quiz JSON
$response = json_decode($result, true);
$quizJson = null;
if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
    $text = $response['candidates'][0]['content']['parts'][0]['text'];
    
    // Attempt to extract JSON array
    if (preg_match('/\[.*\]/s', $text, $matches)) {
        $quizJson = json_decode($matches[0], true);
    } else {
        // Fallback: try decoding the whole text cleaning markdown
        $cleaned_text = preg_replace('/^```json\s*|```$/m', '', trim($text));
        $quizJson = json_decode($cleaned_text, true);
    }
}

if (!$quizJson) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid response from Gemini',
        'gemini_raw_response' => $result,
        'gemini_parsed_response' => $response
    ]);
    exit;
}

echo json_encode(['quiz' => $quizJson]);
?>
