<?php
// Prevent warnings from being displayed in output
error_reporting(0);
ini_set('display_errors', 0);

// Log errors instead
ini_set('log_errors', 1);
error_log('Multiplayer API called');

require_once 'config.php';

// Allow CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    error_log('Raw input: ' . $rawInput);
    
    $input = json_decode($rawInput, true);
    if (!$input) {
        $jsonError = json_last_error_msg();
        error_log('JSON decode error: ' . $jsonError);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input: ' . $jsonError]);
        exit;
    }
    
    $action = $input['action'] ?? '';
    $room = $input['room'] ?? '';
    
    if (!$action) {
        echo json_encode(['success' => false, 'error' => 'No action specified']);
        exit;
    }

function generateDifferences() {
    // Hardcoded positions matching actual visual differences between scene1.svg and scene2.svg.
    // 1. Cloud 2 shifted right in scene2
    // 2. Beach ball shifted right in scene2
    // 3. Extra palm-tree leaf in scene2
    // 4. Extra bird in scene2
    // 5. Fish in ocean of scene2
    return [
        ['x1' => 290, 'x2' => 300, 'y' => 35,  'radius' => 25, 'found' => false],
        ['x1' => 100, 'x2' => 120, 'y' => 250, 'radius' => 25, 'found' => false],
        ['x1' => 290, 'x2' => 290, 'y' => 147, 'radius' => 25, 'found' => false],
        ['x1' => 200, 'x2' => 200, 'y' => 60,  'radius' => 25, 'found' => false],
        ['x1' => 200, 'x2' => 200, 'y' => 185, 'radius' => 25, 'found' => false],
    ];
}

    // Use a directory inside the uploads directory which should already exist and be writable
    $gamesDir = __DIR__ . '/uploads/games';
    
    // First check if uploads exists (it should since it's used for other features)
    if (!is_dir(__DIR__ . '/uploads')) {
        error_log('Uploads directory does not exist');
        echo json_encode(['success' => false, 'error' => 'Server configuration error']);
        exit;
    }
    
    // Create games directory inside uploads if it doesn't exist
    if (!is_dir($gamesDir)) {
        if (!@mkdir($gamesDir, 0777, true)) {
            error_log('Failed to create games directory in uploads: ' . error_get_last()['message']);
            // Try alternative location
            $gamesDir = __DIR__ . '/assets/games';
            if (!is_dir($gamesDir)) {
                if (!@mkdir($gamesDir, 0777, true)) {
                    error_log('Failed to create games directory in assets: ' . error_get_last()['message']);
                    echo json_encode(['success' => false, 'error' => 'Server storage error']);
                    exit;
                }
            }
        }
    }
    
    $gameFile = $gamesDir . '/' . preg_replace('/[^a-zA-Z0-9]/', '', $room) . '.json';
    
    // Create an empty index.php in the games directory for security
    $indexFile = $gamesDir . '/index.php';
    if (!file_exists($indexFile)) {
        @file_put_contents($indexFile, '<?php // Silence is golden');
    }

switch ($action) {
    case 'join':
        if (!$room) {
            echo json_encode(['success' => false, 'error' => 'Room name required']);
            exit;
        }
        
        $gameData = [];
        try {
            if (file_exists($gameFile)) {
                $content = @file_get_contents($gameFile);
                if ($content === false) {
                    throw new Exception('Cannot read game file');
                }
                $gameData = json_decode($content, true) ?: [];
            } else {
                $gameData = [
                    'differences' => generateDifferences(),
                    'players' => []
                ];
            }
        } catch (Exception $e) {
            error_log('Game file error: ' . $e->getMessage());
            $gameData = [
                'differences' => generateDifferences(),
                'players' => []
            ];
        }
        
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Player' . $_SESSION['user_id'];
        $gameData['players'][$_SESSION['user_id']] = [
            'username' => $username,
            'score' => 0,
            'lastActive' => time()
        ];
        
        try {
            $jsonData = json_encode($gameData);
            if ($jsonData === false) {
                throw new Exception('Failed to encode game data: ' . json_last_error_msg());
            }
            
            // First write to a temporary file
            $tempFile = $gameFile . '.tmp';
            if (@file_put_contents($tempFile, $jsonData) === false) {
                throw new Exception('Failed to write temporary file');
            }
            
            // Then rename it to the actual file
            if (!@rename($tempFile, $gameFile)) {
                @unlink($tempFile); // Clean up temp file
                throw new Exception('Failed to save game file');
            }
        } catch (Exception $e) {
            error_log('Save game error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Cannot save game data: ' . $e->getMessage()]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'differences' => $gameData['differences'],
            'players' => $gameData['players'],
            'message' => "Joined room: $room",
            'messageType' => 'waiting'
        ]);
        break;
        
    case 'newgame':
        $gameData = [
            'differences' => generateDifferences(),
            'players' => []
        ];
        
        if (file_exists($gameFile)) {
            $oldData = json_decode(file_get_contents($gameFile), true) ?: [];
            if (isset($oldData['players'])) {
                foreach ($oldData['players'] as $id => $player) {
                    $player['score'] = 0;
                    $gameData['players'][$id] = $player;
                }
            }
        }
        
        if (file_put_contents($gameFile, json_encode($gameData)) === false) {
            echo json_encode(['success' => false, 'error' => 'Cannot save game data']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'differences' => $gameData['differences'],
            'players' => $gameData['players'],
            'message' => 'New game started!',
            'messageType' => 'waiting'
        ]);
        break;
        
    case 'found':
        if (!file_exists($gameFile)) {
            echo json_encode(['success' => false, 'error' => 'Game not found']);
            exit;
        }
        
        $gameData = json_decode(file_get_contents($gameFile), true);
        $index = (int)$input['index'];
        
        if (isset($gameData['differences'][$index]) && !$gameData['differences'][$index]['found']) {
            $gameData['differences'][$index]['found'] = true;
            $gameData['players'][$_SESSION['user_id']]['score'] += 10;
            
            if (file_put_contents($gameFile, json_encode($gameData)) === false) {
                echo json_encode(['success' => false, 'error' => 'Cannot save game data']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'differences' => $gameData['differences'],
                'players' => $gameData['players'],
                'message' => 'You found a difference! +10 points',
                'messageType' => 'found'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Already found']);
        }
        break;
        
    case 'update':
        if (file_exists($gameFile)) {
            $gameData = json_decode(file_get_contents($gameFile), true);
            echo json_encode([
                'success' => true,
                'differences' => $gameData['differences'] ?? [],
                'players' => $gameData['players'] ?? []
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Game not found']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
