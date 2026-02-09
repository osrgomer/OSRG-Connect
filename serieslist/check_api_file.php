<?php
$file = 'api_users.php';
$contents = file_get_contents($file);

$hasMySQL = strpos($contents, 'Search in MySQL database') !== false;
$hasSessionCode = strpos($contents, 'global_users') !== false;

echo "<h1>API Users File Check</h1>";
echo "<pre>";
echo "Has MySQL search code: " . ($hasMySQL ? "YES ✅" : "NO ❌") . "\n";
echo "Has old session code: " . ($hasSessionCode ? "YES ❌ (BAD!)" : "NO ✅ (GOOD!)") . "\n";
echo "\n";

if ($hasMySQL && !$hasSessionCode) {
    echo "✅ File is UPDATED correctly!\n";
} else {
    echo "❌ File is STILL OLD! Re-upload api_users.php from GitHub!\n";
}

// Show first 50 lines of search_users case
$lines = explode("\n", $contents);
$inSearchUsers = false;
$lineNum = 0;
echo "\nFirst part of search_users code:\n";
foreach ($lines as $i => $line) {
    if (strpos($line, "case 'search_users'") !== false) {
        $inSearchUsers = true;
        $lineNum = 0;
    }
    if ($inSearchUsers) {
        echo ($i + 1) . ": " . htmlspecialchars($line) . "\n";
        $lineNum++;
        if ($lineNum > 20) break;
    }
}
echo "</pre>";
