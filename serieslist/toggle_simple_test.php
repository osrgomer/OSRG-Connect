<!DOCTYPE html>
<html>
<head>
    <title>Toggle Test</title>
</head>
<body>
    <h1>Direct Toggle Test</h1>
    
    <?php
    session_start();
    require_once 'db.php';
    
    $isOnline = false;
    if (isset($_SESSION['user_id'])) {
        $currentStatus = getUserStatus($_SESSION['user_id']);
        $isOnline = ($currentStatus === 'online');
        echo "<p>Logged in as user ID: " . $_SESSION['user_id'] . "</p>";
        echo "<p>Current status from DB: <strong>" . $currentStatus . "</strong></p>";
    } else {
        echo "<p>NOT LOGGED IN</p>";
    }
    ?>
    
    <label>
        <input type="checkbox" 
               id="statusToggle" 
               onchange="toggleStatus(this)"
               <?php echo $isOnline ? 'checked' : ''; ?>>
        Status: <span id="statusText"><?php echo $isOnline ? 'Online' : 'Auto'; ?></span>
    </label>
    
    <div id="result" style="margin-top: 20px; padding: 10px; background: #f0f0f0;"></div>
    
    <script>
    function toggleStatus(checkbox) {
        const status = checkbox.checked ? 'online' : 'auto';
        const statusText = document.getElementById('statusText');
        const resultDiv = document.getElementById('result');
        
        statusText.textContent = checkbox.checked ? 'Online' : 'Auto';
        resultDiv.innerHTML = 'Sending request...';
        
        console.log('Toggling to:', status);
        
        fetch('/serieslist/api_set_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: status })
        })
        .then(res => {
            console.log('Response status:', res.status);
            console.log('Response headers:', res.headers);
            return res.text(); // Get raw text first
        })
        .then(text => {
            console.log('Raw response:', text);
            resultDiv.innerHTML = '<h3>Raw Response:</h3><pre style="background:#f0f0f0;padding:10px;overflow:auto;">' + text + '</pre>';
            
            // Try to parse as JSON
            try {
                const data = JSON.parse(text);
                resultDiv.innerHTML += '<h3>Parsed JSON:</h3><pre style="background:#d4edda;padding:10px;overflow:auto;">' + JSON.stringify(data, null, 2) + '</pre>';
                if (!data.success) {
                    checkbox.checked = !checkbox.checked;
                    statusText.textContent = !checkbox.checked ? 'Online' : 'Auto';
                }
            } catch(e) {
                resultDiv.innerHTML += '<h3 style="color:red;">JSON Parse Error:</h3><pre style="background:#f8d7da;padding:10px;overflow:auto;">' + e.message + '</pre>';
                checkbox.checked = !checkbox.checked;
                statusText.textContent = !checkbox.checked ? 'Online' : 'Auto';
            }
        })
        .catch(err => {
            console.error('Fetch Error:', err);
            resultDiv.innerHTML = '<h3 style="color:red;">Network Error:</h3><pre style="background:#f8d7da;padding:10px;overflow:auto;">' + err.message + '</pre>';
            checkbox.checked = !checkbox.checked;
            statusText.textContent = !checkbox.checked ? 'Online' : 'Auto';
        });
    }
    </script>
    
    <hr>
    <a href="/serieslist/">Back to app</a>
</body>
</html>
