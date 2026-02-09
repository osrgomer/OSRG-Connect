<!DOCTYPE html>
<html>
<head>
    <title>Check LocalStorage</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e293b; color: #e2e8f0; }
        pre { background: #0f172a; padding: 15px; border-radius: 5px; overflow-x: auto; }
        button { background: #8b5cf6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
    </style>
</head>
<body>
    <h1>📦 LocalStorage Inspector</h1>
    <button onclick="checkStorage()">Check Storage</button>
    <button onclick="listAllKeys()">List All Keys</button>
    <div id="result"></div>
    
    <script>
    const userKey = 'user_<?php echo $_SESSION['username'] ?? 'omersr12@gmail.com'; ?>';
    
    function checkStorage() {
        const result = document.getElementById('result');
        const seriesData = localStorage.getItem('series_v2_' + userKey);
        
        result.innerHTML = '<h2>Results for: ' + userKey + '</h2>';
        result.innerHTML += '<pre>Key: series_v2_' + userKey + '</pre>';
        
        if (seriesData) {
            const parsed = JSON.parse(seriesData);
            result.innerHTML += '<pre>Found ' + parsed.length + ' entries:\n' + JSON.stringify(parsed, null, 2) + '</pre>';
        } else {
            result.innerHTML += '<pre style="background: #dc2626; color: white; padding: 10px;">❌ NO DATA FOUND for this user!</pre>';
        }
    }
    
    function listAllKeys() {
        const result = document.getElementById('result');
        result.innerHTML = '<h2>All LocalStorage Keys:</h2><pre>';
        
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key.startsWith('series_v2_')) {
                const data = JSON.parse(localStorage.getItem(key));
                result.innerHTML += '\n' + key + ' → ' + data.length + ' entries';
            }
        }
        result.innerHTML += '</pre>';
    }
    </script>
</body>
</html>
