<?php
// Show what version of friends.php is on the server
$file = 'friends.php';
$contents = file_get_contents($file);

// Check for the disabled activity code
$hasDisabledActivity = strpos($contents, 'TEMPORARILY DISABLED') !== false;
$hasVersionLog = strpos($contents, '2026-02-08-20:36') !== false;

echo "<h1>Server File Check</h1>";
echo "<pre>";
echo "File: $file\n";
echo "Size: " . strlen($contents) . " bytes\n";
echo "Has 'TEMPORARILY DISABLED': " . ($hasDisabledActivity ? 'YES ✅' : 'NO ❌') . "\n";
echo "Has version log: " . ($hasVersionLog ? 'YES ✅' : 'NO ❌') . "\n";
echo "\n";

// Find line 260-270
$lines = explode("\n", $contents);
echo "Lines 260-270:\n";
for ($i = 259; $i < 270 && $i < count($lines); $i++) {
    echo ($i + 1) . ": " . htmlspecialchars($lines[$i]) . "\n";
}

echo "\n\nSearching for 'renderActivity'...\n";
$pos = strpos($contents, 'function renderActivity');
if ($pos !== false) {
    $snippet = substr($contents, $pos, 500);
    echo htmlspecialchars($snippet) . "\n";
}

echo "</pre>";
