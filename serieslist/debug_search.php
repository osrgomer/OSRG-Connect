<?php
session_start();
header('Content-Type: application/json');

// Debug search functionality
$currentUserId = $_SESSION['user_email'] ?? 'unknown';
$query = $_GET['q'] ?? '';

echo json_encode([
    'current_user' => $currentUserId,
    'search_query' => $query,
    'global_users' => $_SESSION['global_users'] ?? [],
    'friends_data' => $_SESSION['friends_data'] ?? [],
    'friends_data_all' => $_SESSION['friends_data_all'] ?? []
], JSON_PRETTY_PRINT);
?>
