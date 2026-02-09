<?php
require_once 'config.php';
init_db();

$pdo = get_db();

try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN post_type TEXT DEFAULT 'post'");
    echo "Added post_type column successfully";
} catch (Exception $e) {
    echo "Column already exists or error: " . $e->getMessage();
}
?>