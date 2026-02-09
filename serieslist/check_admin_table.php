<?php
session_start();
$_SESSION['user_logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'omersr12@gmail.com';

require_once __DIR__ . '/db.php';
$db = getDB();

echo "<h1>Check Admin Uploads Table</h1>";

// Show current database
$stmt = $db->query("SELECT DATABASE()");
$currentDb = $stmt->fetchColumn();
echo "<p>Connected to database: <strong>$currentDb</strong></p>";

// Check if table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'admin_uploads'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<p style='color:green'>✓ Table 'admin_uploads' exists</p>";
        
        // Count rows
        $stmt = $db->query("SELECT COUNT(*) FROM admin_uploads");
        $count = $stmt->fetchColumn();
        echo "<p>Total uploads: $count</p>";
        
    } else {
        echo "<p style='color:red'>✗ Table 'admin_uploads' does NOT exist</p>";
        echo "<h2>Creating table...</h2>";
        
        $sql = "CREATE TABLE admin_uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            uploaded_by INT NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $result = $db->exec($sql);
        echo "<p style='color:green'>✓ Table created! Result: $result</p>";
        
        // Verify it exists now
        $stmt = $db->query("SHOW TABLES LIKE 'admin_uploads'");
        $nowExists = $stmt->fetch();
        
        if ($nowExists) {
            echo "<p style='color:green'>✓✓ VERIFIED: Table now exists!</p>";
        } else {
            echo "<p style='color:red'>✗✗ ERROR: Table still doesn't exist after creation!</p>";
        }
    }
    
    // Show all tables
    echo "<h2>All Tables in Database:</h2><ul>";
    $stmt = $db->query("SHOW TABLES");
    while ($table = $stmt->fetchColumn()) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
