<?php
// Set cookie domain before starting session
if (strpos($_SERVER['HTTP_HOST'], 'connect.osrg.lol') !== false) {
    ini_set('session.cookie_domain', '.osrg.lol');
}
session_start();
date_default_timezone_set('Europe/London');

// Include sensitive keys
if (file_exists('config_keys.php')) {
    require_once 'config_keys.php';
}

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

require_once 'series_db.php';

function get_db() {
    return getDB();
}

function init_db() {
    $pdo = get_db();
    if (!$pdo) return null;
    
    // 1. Update users table (SeriesList already has some of these)
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255)");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar TEXT");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_content LONGBLOB");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN timezone VARCHAR(100) DEFAULT 'Europe/London'");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_notifications TINYINT DEFAULT 0");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN approved TINYINT DEFAULT 1");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN timezone VARCHAR(100) DEFAULT 'Europe/London'");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN email_notifications TINYINT DEFAULT 0");
    } catch (Exception $e) {}
    
    // 2. Create posts table with reel support and BLOB storage
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN file_content LONGBLOB");
    } catch (Exception $e) {}
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL, 
        content TEXT, 
        file_path VARCHAR(500),
        file_type VARCHAR(50),
        file_content LONGBLOB,
        post_type VARCHAR(50) DEFAULT 'post',
        reel_serial INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 3. Create friends table
    $pdo->exec("CREATE TABLE IF NOT EXISTS friends (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL, 
        friend_id INT NOT NULL,
        status VARCHAR(50), 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(friend_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 4. Create messages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        sender_id INT NOT NULL, 
        receiver_id INT NOT NULL,
        content TEXT, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 5. Create comments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 6. Create reactions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction_type VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_reaction (post_id, user_id, reaction_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // 7. Create remember_tokens table
    $pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
