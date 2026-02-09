<?php
// Set cookie domain before starting session
if (strpos($_SERVER['HTTP_HOST'], 'connect.osrg.lol') !== false) {
    ini_set('session.cookie_domain', '.osrg.lol');
}
session_start();
date_default_timezone_set('Europe/London');

// reCAPTCHA v3 Configuration - Temporary direct keys
define('RECAPTCHA_SITE_KEY', '6LdfnuErAAAAAGYSK_kNF-Z02QjDxGTVkzswyofS');
define('RECAPTCHA_SECRET_KEY', '6LdfnuErAAAAAOuWEvsDZGWj-a5ruWmz9JWio8PH');

// Check for remember me token if not logged in
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $pdo = get_db();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT rt.user_id FROM remember_tokens rt WHERE rt.token = ? AND rt.expires > ?");
            $stmt->execute([$_COOKIE['remember_token'], time()]);
            $token_data = $stmt->fetch();
            
            if ($token_data) {
                $_SESSION['user_id'] = $token_data['user_id'];
            } else {
                // Invalid or expired token, remove cookie
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }
        } catch (Exception $e) {
            // Table doesn't exist yet, ignore
        }
    }
}

function get_db() {
    try {
        $pdo = new PDO('sqlite:private_social.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

function init_db() {
    $pdo = get_db();
    if (!$pdo) return null;
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        username TEXT UNIQUE, 
        email TEXT UNIQUE, 
        password TEXT, 
        approved INTEGER DEFAULT 0,
        timezone TEXT DEFAULT 'Europe/London',
        email_notifications INTEGER DEFAULT 0,
        avatar TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        user_id INTEGER, 
        content TEXT, 
        file_path TEXT,
        file_type TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS friends (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        user_id INTEGER, 
        friend_id INTEGER,
        status TEXT, 
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(friend_id) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        sender_id INTEGER, 
        receiver_id INTEGER,
        content TEXT, 
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(sender_id) REFERENCES users(id),
        FOREIGN KEY(receiver_id) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER,
        user_id INTEGER,
        content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(post_id) REFERENCES posts(id),
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS reactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER,
        user_id INTEGER,
        reaction_type TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(post_id) REFERENCES posts(id),
        FOREIGN KEY(user_id) REFERENCES users(id),
        UNIQUE(post_id, user_id)
    )");
    
    return $pdo;
}

function get_link_preview($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; OSRG-Bot)');
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$html || $http_code >= 400) return null;
    
    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    
    $title = $xpath->query('//meta[@property="og:title"]/@content');
    $description = $xpath->query('//meta[@property="og:description"]/@content');
    $image = $xpath->query('//meta[@property="og:image"]/@content');
    
    if ($title->length == 0) {
        $title = $xpath->query('//title');
        $title = $title->length > 0 ? $title->item(0)->textContent : null;
    } else {
        $title = $title->item(0)->nodeValue;
    }
    
    // Don't show preview if we can't get a proper title
    if (!$title || trim($title) === '' || strpos(strtolower($title), '404') !== false || strpos(strtolower($title), 'not found') !== false) {
        return null;
    }
    
    $image_url = '';
    if ($image->length > 0) {
        $image_url = $image->item(0)->nodeValue;
        if ($image_url && !filter_var($image_url, FILTER_VALIDATE_URL)) {
            $parsed_url = parse_url($url);
            $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
            if (isset($parsed_url['port'])) {
                $base_url .= ':' . $parsed_url['port'];
            }
            
            if (strpos($image_url, '/') === 0) {
                $image_url = $base_url . $image_url;
            } else {
                $path = dirname($parsed_url['path'] ?? '/');
                $image_url = $base_url . rtrim($path, '/') . '/' . $image_url;
            }
        }
    }
    
    return [
        'title' => $title,
        'description' => $description->length > 0 ? $description->item(0)->nodeValue : '',
        'image' => $image_url,
        'url' => $url
    ];
}

function process_content_with_links($content) {
    $text_content = strip_tags($content);
    $url_pattern = '/https?:\/\/[^\s<>"]+/i';
    preg_match_all($url_pattern, $text_content, $matches);
    
    $processed_content = $content;
    $link_previews = [];
    
    foreach ($matches[0] as $url) {
        $clean_url = html_entity_decode($url);
        if (strpos($processed_content, 'href="' . $clean_url . '"') === false) {
            $processed_content = str_replace($clean_url, '<a href="' . $clean_url . '" target="_blank" style="color: #1877f2; text-decoration: none;">' . $clean_url . '</a>', $processed_content);
            $preview = get_link_preview($clean_url);
            if ($preview) {
                $link_previews[] = $preview;
            }
        }
    }
    
    return [
        'content' => $processed_content,
        'previews' => $link_previews
    ];
}

function verify_recaptcha($token) {
    $secret = RECAPTCHA_SECRET_KEY;
    // Skip verification if using placeholder keys
    if ($secret === 'YOUR_SECRET_KEY_HERE') {
        return true; // Allow login when keys are not configured
    }
    
    try {
        $response = @file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$token}");
        if ($response === false) {
            error_log('Failed to verify reCAPTCHA: Unable to contact verification service');
            return false;
        }
        
        $result = json_decode($response, true);
        if (!is_array($result)) {
            error_log('Failed to verify reCAPTCHA: Invalid response format');
            return false;
        }
        
        return isset($result['success']) && isset($result['score']) && 
               $result['success'] === true && $result['score'] >= 0.5;
    } catch (Exception $e) {
        error_log('reCAPTCHA verification error: ' . $e->getMessage());
        return false;
    }
}
?>