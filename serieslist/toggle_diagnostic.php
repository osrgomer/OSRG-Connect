<!DOCTYPE html>
<html>
<head>
    <title>Toggle Diagnostic</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e293b; color: #e2e8f0; }
        .success { background: #10b981; color: white; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { background: #ef4444; color: white; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .info { background: #3b82f6; color: white; padding: 10px; margin: 10px 0; border-radius: 5px; }
        pre { background: #0f172a; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { background: #8b5cf6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #7c3aed; }
    </style>
</head>
<body>
    <?php
    session_start();
    require_once 'db.php';
    
    echo "<h1>🔬 Toggle Diagnostic Tool</h1>";
    
    // Check session
    echo "<div class='info'>";
    echo "<strong>Session Check:</strong><br>";
    echo "user_id: " . ($_SESSION['user_id'] ?? '❌ NOT SET') . "<br>";
    echo "user_email: " . ($_SESSION['user_email'] ?? '❌ NOT SET');
    echo "</div>";
    
    // Check database
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $db = getDB();
        $stmt = $db->prepare("SELECT id, email, username, manual_status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div class='success'>";
            echo "<strong>Database User Found:</strong><br>";
            echo "ID: {$user['id']}<br>";
            echo "Email: {$user['email']}<br>";
            echo "Current Status: <strong>{$user['manual_status']}</strong>";
            echo "</div>";
        } else {
            echo "<div class='error'>❌ User ID {$userId} not found in database!</div>";
        }
    }
    ?>
    
    <h2>Test Buttons:</h2>
    <button onclick="testSetOnline()">Set ONLINE</button>
    <button onclick="testSetAuto()">Set AUTO</button>
    <button onclick="checkStatus()">Check Current Status</button>
    
    <div id="result"></div>
    
    <script>
    async function testSetOnline() {
        await testToggle('online');
    }
    
    async function testSetAuto() {
        await testToggle('auto');
    }
    
    async function testToggle(status) {
        const result = document.getElementById('result');
        result.innerHTML = '<div class="info">Sending request to api_set_status.php...</div>';
        
        try {
            const response = await fetch('/serieslist/api_set_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: status })
            });
            
            const text = await response.text();
            
            result.innerHTML = `
                <div class="${response.ok ? 'success' : 'error'}">
                    <strong>Response Status:</strong> ${response.status}<br>
                    <strong>Response Body:</strong><br>
                    <pre>${text}</pre>
                </div>
            `;
            
            if (response.ok) {
                setTimeout(() => location.reload(), 1000);
            }
        } catch (err) {
            result.innerHTML = `<div class="error"><strong>Error:</strong> ${err.message}</div>`;
        }
    }
    
    async function checkStatus() {
        location.reload();
    }
    </script>
</body>
</html>
