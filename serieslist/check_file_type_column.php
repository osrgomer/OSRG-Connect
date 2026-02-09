<?php
require_once __DIR__ . '/db.php';
$db = getDB();

echo "<h1>Check file_type Column</h1>";

try {
    // Show table structure
    $stmt = $db->query("DESCRIBE admin_uploads");
    echo "<h2>Current Table Structure:</h2>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if file_type column exists
    $stmt = $db->query("SHOW COLUMNS FROM admin_uploads LIKE 'file_type'");
    $hasColumn = $stmt->fetch();
    
    if (!$hasColumn) {
        echo "<h2 style='color:orange'>⚠ file_type column does NOT exist - Adding it now...</h2>";
        $db->exec("ALTER TABLE admin_uploads ADD COLUMN file_type VARCHAR(100) DEFAULT 'image/jpeg' AFTER image_path");
        echo "<p style='color:green'>✓ Column added successfully!</p>";
        
        // Show updated structure
        $stmt = $db->query("DESCRIBE admin_uploads");
        echo "<h2>Updated Table Structure:</h2>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $val) {
                echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<h2 style='color:green'>✓ file_type column already exists!</h2>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>ERROR: " . $e->getMessage() . "</p>";
}
?>
