<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

require_once 'db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

switch ($action) {
    case 'get_all':
        // Get all TV shows for current user
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM series WHERE user_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$userId]);
        $series = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'series' => $series]);
        exit;
        
    case 'save':
        // Save/Update a TV show
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $db = getDB();
        
        if (isset($data['id']) && $data['id']) {
            // Update existing
            $stmt = $db->prepare("
                UPDATE series 
                SET title = ?, poster = ?, rating = ?, status = ?, notes = ?, tmdb_id = ?, progress = ?, total = ?
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([
                $data['title'] ?? '',
                $data['poster'] ?? null,
                $data['rating'] ?? null,
                $data['status'] ?? 'watching',
                $data['notes'] ?? null,
                $data['tmdb_id'] ?? null,
                $data['progress'] ?? 0,
                $data['total'] ?? 0,
                $data['id'],
                $userId
            ]);
            
            echo json_encode(['success' => $result, 'id' => $data['id']]);
        } else {
            // Insert new
            $stmt = $db->prepare("
                INSERT INTO series (user_id, title, poster, rating, status, notes, tmdb_id, progress, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $userId,
                $data['title'] ?? '',
                $data['poster'] ?? null,
                $data['rating'] ?? null,
                $data['status'] ?? 'watching',
                $data['notes'] ?? null,
                $data['tmdb_id'] ?? null,
                $data['progress'] ?? 0,
                $data['total'] ?? 0
            ]);
            
            echo json_encode(['success' => $result, 'id' => $db->lastInsertId()]);
        }
        exit;
        
    case 'delete':
        // Delete a TV show
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $seriesId = $data['id'] ?? null;
        
        if (!$seriesId) {
            echo json_encode(['success' => false, 'error' => 'No ID provided']);
            exit;
        }
        
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM series WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$seriesId, $userId]);
        
        echo json_encode(['success' => $result]);
        exit;
        
    case 'import_from_localstorage':
        // Import TV shows from localStorage backup
        if ($method !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $series = $data['series'] ?? [];
        
        $db = getDB();
        $imported = 0;
        
        foreach ($series as $show) {
            $stmt = $db->prepare("
                INSERT INTO series (user_id, title, poster, rating, status, notes, tmdb_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $userId,
                $show['title'] ?? '',
                $show['poster'] ?? null,
                $show['rating'] ?? null,
                $show['status'] ?? 'watching',
                $show['notes'] ?? null,
                $show['id'] ?? null // tmdb_id
            ]);
            if ($result) $imported++;
        }
        
        echo json_encode(['success' => true, 'imported' => $imported]);
        exit;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;
}
