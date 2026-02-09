<?php
session_start();
header('Content-Type: application/json');

try {
    require_once 'db.php';
    echo json_encode(['db_works' => true]);
} catch (Exception $e) {
    echo json_encode(['db_error' => $e->getMessage()]);
}
exit;
