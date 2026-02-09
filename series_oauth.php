<?php
session_start();

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// OAuth Configuration
$oauth_config = [
    'google' => [
        'client_id' => '20841640756-s62spmurqarg2oraa176j7jevcu9fe51.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect_uri' => 'https://osrg.lol/serieslist/series_oauth.php',
        'auth_url' => 'https://accounts.google.com/o/oauth2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'scope' => 'openid email profile'
    ],
    'github' => [
        'client_id' => 'Ov23li6aJeSYhmgYls38',
        'client_secret' => '8d3ea8762b4e402332298da484a79d17e3ebc491',
        'redirect_uri' => 'https://osrg.lol/serieslist/series_oauth.php',
        'auth_url' => 'https://github.com/login/oauth/authorize',
        'token_url' => 'https://github.com/login/oauth/access_token',
        'scope' => 'user:email'
    ],
    'spotify' => [
        'client_id' => 'YOUR_SPOTIFY_CLIENT_ID',
        'client_secret' => 'YOUR_SPOTIFY_CLIENT_SECRET',
        'redirect_uri' => 'https://osrg.lol/serieslist/series_oauth.php',
        'auth_url' => 'https://accounts.spotify.com/authorize',
        'token_url' => 'https://accounts.spotify.com/api/token',
        'scope' => 'user-read-private user-read-email'
    ]
];

$provider = $_GET['provider'] ?? '';
$action = $_GET['action'] ?? '';

// If provider not in URL, try to get from session (for callbacks)
if (empty($provider) && isset($_GET['code']) && isset($_SESSION['oauth_provider'])) {
    $provider = $_SESSION['oauth_provider'];
}

if (!isset($oauth_config[$provider])) {
    header('Location: series_account.php?error=invalid_provider');
    exit;
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
    
    $auth_url = $config['auth_url'] . '?' . http_build_query($params);
    header('Location: ' . $auth_url);
    exit;
    
} elseif (isset($_GET['code'])) {
    // Step 2: Handle callback and exchange code for token
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';
    
    // Debug logging
    error_log("OAuth Callback - Provider from session: " . ($_SESSION['oauth_provider'] ?? 'NOT SET'));
    error_log("OAuth Callback - Code received: " . substr($code, 0, 10) . "...");
    error_log("OAuth Callback - State: " . $state);
    
    if (!isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
        error_log("OAuth Error - State mismatch or not set");
        header('Location: series_account.php?error=invalid_state');
        exit;
    }
    
    // Exchange code for access token
    $token_data = [
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $config['redirect_uri']
    ];
    
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
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code === 200) {
        $token_info = json_decode($response, true);
        
        if (isset($token_info['access_token'])) {
            $access_token = $token_info['access_token'];
            
            // Fetch user info from provider
            $user_info = null;
            
            switch ($provider) {
                case 'google':
                    // Get Google user info
                    $user_ch = curl_init();
                    curl_setopt($user_ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
                    curl_setopt($user_ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($user_ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $access_token
                    ]);
                    $user_response = curl_exec($user_ch);
                    curl_close($user_ch);
                    
                    $user_data = json_decode($user_response, true);
                    if ($user_data) {
                        $user_info = [
                            'id' => $user_data['id'] ?? null,
                            'name' => $user_data['name'] ?? null,
                            'email' => $user_data['email'] ?? null,
                            'picture' => $user_data['picture'] ?? null,
                            'verified_email' => $user_data['verified_email'] ?? false
                        ];
                    }
                    break;
                    
                case 'github':
                    // Get GitHub user info
                    $user_ch = curl_init();
                    curl_setopt($user_ch, CURLOPT_URL, 'https://api.github.com/user');
                    curl_setopt($user_ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($user_ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $access_token,
                        'User-Agent: SeriesList-App'
                    ]);
                    $user_response = curl_exec($user_ch);
                    curl_close($user_ch);
                    
                    $user_data = json_decode($user_response, true);
                    
                    // Also get email (requires separate endpoint)
                    $email_ch = curl_init();
                    curl_setopt($email_ch, CURLOPT_URL, 'https://api.github.com/user/emails');
                    curl_setopt($email_ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($email_ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $access_token,
                        'User-Agent: SeriesList-App'
                    ]);
                    $email_response = curl_exec($email_ch);
                    curl_close($email_ch);
                    
                    $emails = json_decode($email_response, true);
                    $primary_email = null;
                    if (is_array($emails)) {
                        foreach ($emails as $email) {
                            if ($email['primary'] ?? false) {
                                $primary_email = $email['email'];
                                break;
                            }
                        }
                    }
                    
                    if ($user_data) {
                        $user_info = [
                            'id' => $user_data['id'] ?? null,
                            'name' => $user_data['name'] ?? $user_data['login'] ?? null,
                            'username' => $user_data['login'] ?? null,
                            'email' => $primary_email ?? $user_data['email'] ?? null,
                            'picture' => $user_data['avatar_url'] ?? null,
                            'bio' => $user_data['bio'] ?? null,
                            'location' => $user_data['location'] ?? null,
                            'company' => $user_data['company'] ?? null,
                            'blog' => $user_data['blog'] ?? null,
                            'public_repos' => $user_data['public_repos'] ?? 0
                        ];
                    }
                    break;
                    
                case 'spotify':
                    // Get Spotify user info
                    $user_ch = curl_init();
                    curl_setopt($user_ch, CURLOPT_URL, 'https://api.spotify.com/v1/me');
                    curl_setopt($user_ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($user_ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $access_token
                    ]);
                    $user_response = curl_exec($user_ch);
                    curl_close($user_ch);
                    
                    $user_data = json_decode($user_response, true);
                    if ($user_data) {
                        $user_info = [
                            'id' => $user_data['id'] ?? null,
                            'name' => $user_data['display_name'] ?? null,
                            'email' => $user_data['email'] ?? null,
                            'picture' => $user_data['images'][0]['url'] ?? null,
                            'country' => $user_data['country'] ?? null,
                            'followers' => $user_data['followers']['total'] ?? 0,
                            'product' => $user_data['product'] ?? null
                        ];
                    }
                    break;
            }
            
            // Store connection info
            if (!isset($_SESSION['connections'])) {
                $_SESSION['connections'] = [];
            }
            
            $_SESSION['connections'][$provider] = [
                'access_token' => $access_token,
                'connected_at' => time(),
                'expires_in' => $token_info['expires_in'] ?? null,
                'user_info' => $user_info,
                'token_type' => $token_info['token_type'] ?? 'Bearer'
            ];
            
            // Update user profile with connected account data if not already set
            if ($user_info) {
                // Update session user data if empty
                if (empty($_SESSION['user_name']) && !empty($user_info['name'])) {
                    $_SESSION['user_name'] = $user_info['name'];
                }
                if (empty($_SESSION['user_email']) && !empty($user_info['email'])) {
                    $_SESSION['user_email'] = $user_info['email'];
                }
                if (empty($_SESSION['user_avatar']) && !empty($user_info['picture'])) {
                    $_SESSION['user_avatar'] = $user_info['picture'];
                }
                
                // Update global users if exists
                $userId = $_SESSION['user_email'] ?? 'user@example.com';
                if (isset($_SESSION['global_users'][$userId])) {
                    if (!empty($user_info['picture'])) {
                        $_SESSION['global_users'][$userId]['avatar'] = $user_info['picture'];
                    }
                    if (!empty($user_info['name'])) {
                        $_SESSION['global_users'][$userId]['username'] = $user_info['name'];
                    }
                }
            }
            
            // Clear OAuth session data
            unset($_SESSION['oauth_state']);
            unset($_SESSION['oauth_provider']);
            
            // Redirect back to account page with success
            header('Location: series_account.php?connected=' . $provider);
            exit;
        } else {
            // Token response didn't contain access_token
            error_log("OAuth Error - No access_token in response for $provider: " . print_r($token_info, true));
            header('Location: series_account.php?error=no_token&provider=' . $provider);
            exit;
        }
    } else {
        // HTTP error
        error_log("OAuth Error - HTTP $http_code for $provider. Response: $response. cURL Error: $curl_error");
        header('Location: series_account.php?error=connection_failed&code=' . $http_code . '&provider=' . $provider);
        exit;
    }
    
} elseif ($action === 'disconnect') {
    // Handle disconnection
    if (isset($_SESSION['connections'][$provider])) {
        unset($_SESSION['connections'][$provider]);
    }
    header('Location: series_account.php?disconnected=' . $provider);
    exit;
}

// Default redirect
header('Location: series_account.php');
exit;
?>
