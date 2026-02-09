<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data = json_decode(file_get_contents('php://input'), true);
$topic = $data['topic'] ?? '';

// Gemini API key - Place your Gemini API key here
$apiKey = 'AIzaSyAAGk7e5nqt-tkDNr6kH1dkYbjd3N2a33w';

if (!$topic) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing topic']);
    exit;
}

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
        'timeout' => 20
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($geminiUrl, false, $context);

if ($result === FALSE) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to contact Gemini API',
        'debug' => error_get_last()
    ]);
    exit;
}

// Parse Gemini response and extract quiz JSON
$response = json_decode($result, true);
$quizJson = null;
if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
    $text = $response['candidates'][0]['content']['parts'][0]['text'];
    // Remove Markdown code block if present
    $text = preg_replace('/^```json\\s*|```$/m', '', trim($text));
    $quizJson = json_decode($text, true);
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
