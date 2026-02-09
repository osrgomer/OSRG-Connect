<?php
session_start();

// OAuth Configuration Template
// Copy this file to oauth.php and add your real credentials
$oauth_config = [
    'google' => [
        'client_id' => 'YOUR_GOOGLE_CLIENT_ID',
        'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost/serieslist/oauth.php?provider=google',
        'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'scope' => 'openid email profile'
    ],
    'twitter' => [
        'client_id' => 'YOUR_TWITTER_CLIENT_ID',
        'client_secret' => 'YOUR_TWITTER_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost/serieslist/oauth.php?provider=twitter',
        'auth_url' => 'https://twitter.com/i/oauth2/authorize',
        'token_url' => 'https://api.twitter.com/2/oauth2/token',
        'scope' => 'tweet.read users.read'
    ],
    'spotify' => [
        'client_id' => 'YOUR_SPOTIFY_CLIENT_ID',
        'client_secret' => 'YOUR_SPOTIFY_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost/serieslist/oauth.php?provider=spotify',
        'auth_url' => 'https://accounts.spotify.com/authorize',
        'token_url' => 'https://accounts.spotify.com/api/token',
        'scope' => 'user-read-private user-read-email'
    ]
];

$provider = $_GET['provider'] ?? '';
$action = $_GET['action'] ?? '';

if (!isset($oauth_config[$provider])) {
    die('Invalid provider');
}

$config = $oauth_config[$provider];

// Handle OAuth flow
if ($action === 'connect') {
    // Step 1: Redirect to OAuth provider
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_provider'] = $provider;
    
    $params = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'scope' => $config['scope'],
        'response_type' => 'code',
        'state' => $state
    ];
    
    if ($provider === 'twitter') {
        $params['code_challenge'] = 'challenge';
        $params['code_challenge_method'] = 'plain';
    }
    
    $auth_url = $config['auth_url'] . '?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;
    
} elseif (isset($_GET['code'])) {
    // Step 2: Handle callback and exchange code for token
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';
    
    if (!isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
        die('Invalid state parameter');
    }
    
    // Exchange code for access token
    $token_data = [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $config['redirect_uri']
    ];
    
    if ($provider === 'twitter') {
        $token_data['code_verifier'] = 'challenge';
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['token_url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $token_info = json_decode($response, true);
        
        if (isset($token_info['access_token'])) {
            // Store connection info
            if (!isset($_SESSION['connections'])) {
                $_SESSION['connections'] = [];
            }
            
            $_SESSION['connections'][$provider] = [
                'access_token' => $token_info['access_token'],
                'connected_at' => time(),
                'expires_in' => $token_info['expires_in'] ?? null
            ];
            
            // Redirect back to account page with success
            header('Location: account.php?connected=' . $provider);
            exit;
        }
    }
    
    // Error handling
    header('Location: account.php?error=connection_failed');
    exit;
    
} elseif ($action === 'disconnect') {
    // Handle disconnection
    if (isset($_SESSION['connections'][$provider])) {
        unset($_SESSION['connections'][$provider]);
    }
    header('Location: account.php?disconnected=' . $provider);
    exit;
}

// Default redirect
header('Location: account.php');
exit;
?>