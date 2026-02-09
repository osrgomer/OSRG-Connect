<?php
require_once 'config.php';

$message = '';
$error = '';

if ($_POST['email'] ?? false) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token in database
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INTEGER PRIMARY KEY,
                user_id INTEGER,
                token TEXT,
                expires DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (Exception $e) {
            // Table already exists
        }
        
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $reset_token, $expires]);
        
        // Send email
        $reset_link = "https://connect.osrg.lol/reset_password.php?token=" . $reset_token;
        $subject = "Password Reset - OSRG Connect";
        $body = "Hi " . $user['username'] . ",\n\n";
        $body .= "You requested a password reset for your OSRG Connect account.\n\n";
        $body .= "Click the link below to reset your password:\n";
        $body .= $reset_link . "\n\n";
        $body .= "This link will expire in 1 hour.\n\n";
        $body .= "If you didn't request this, please ignore this email.\n\n";
        $body .= "Best regards,\nOSRG Connect Team";
        
        $headers = "From: OSRG Connect <omer@osrg.lol>\r\n";
        $headers .= "Reply-To: omer@osrg.lol\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Configure SMTP
        ini_set('SMTP', 'smtp.hostinger.com');
        ini_set('smtp_port', '465');
        ini_set('sendmail_from', 'omer@osrg.lol');
        
        if (mail($user['email'], $subject, $body, $headers)) {
            $message = 'Password reset link sent to your email address.';
        } else {
            $error = 'Failed to send email. Please try again later.';
        }
    } else {
        $error = 'No account found with that email address.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <title>Forgot Password - OSRG Connect</title>
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1Y8S6WHNH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-Y1Y8S6WHNH');
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        .container { 
            max-width: 450px; 
            width: 90%; 
            background: rgba(255,255,255,0.95); 
            backdrop-filter: blur(10px); 
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            overflow: hidden; 
        }
        .header { 
            background: linear-gradient(135deg, #1877f2, #42a5f5); 
            color: white; 
            padding: 40px 30px; 
            text-align: center; 
        }
        .header h1 { 
            font-size: 2em; 
            margin-bottom: 8px; 
            font-weight: 600; 
        }
        .header p { 
            opacity: 0.9; 
            font-size: 1.1em; 
        }
        .form-container { 
            padding: 40px 30px; 
        }
        .form-group { 
            margin: 25px 0; 
        }
        input { 
            width: 100%; 
            padding: 15px 20px; 
            border: 2px solid #e1e5e9; 
            border-radius: 12px; 
            font-size: 16px; 
            transition: all 0.3s ease; 
            background: #f8f9fa; 
        }
        input:focus { 
            outline: none; 
            border-color: #1877f2; 
            box-shadow: 0 0 0 3px rgba(24,119,242,0.1); 
            background: white; 
        }
        button { 
            width: 100%; 
            background: linear-gradient(135deg, #1877f2, #42a5f5); 
            color: white; 
            padding: 15px 20px; 
            border: none; 
            border-radius: 12px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 600; 
            transition: all 0.3s ease; 
        }
        button:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 25px rgba(24,119,242,0.3); 
        }
        .message { 
            color: #2e7d32; 
            padding: 15px 20px; 
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9); 
            border-radius: 12px; 
            margin-bottom: 20px; 
            border-left: 4px solid #4caf50; 
            font-weight: 500; 
        }
        .error { 
            color: #d32f2f; 
            padding: 15px 20px; 
            background: linear-gradient(135deg, #ffebee, #ffcdd2); 
            border-radius: 12px; 
            margin-bottom: 20px; 
            border-left: 4px solid #f44336; 
            font-weight: 500; 
        }
        .back-link { 
            text-align: center; 
            margin-top: 25px; 
            padding-top: 20px; 
            border-top: 1px solid #e1e5e9; 
        }
        .back-link a { 
            color: #1877f2; 
            text-decoration: none; 
            font-weight: 500; 
            transition: color 0.3s; 
        }
        .back-link a:hover { 
            color: #0d47a1; 
        }
        
        @media (max-width: 768px) {
            .container { 
                width: 95%; 
                margin: 20px; 
            }
            .header { 
                padding: 30px 20px; 
            }
            .header h1 { 
                font-size: 1.7em; 
            }
            .form-container { 
                padding: 30px 20px; 
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Forgot Password</h1>
            <p>Reset your OSRG Connect password</p>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="message">✅ <?= $message ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">❌ <?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Enter your email address" required>
                </div>
                <div class="form-group">
                    <button type="submit">📧 Send Reset Link</button>
                </div>
            </form>

            <div class="back-link">
                Remember your password? <a href="login">🔙 Login here</a>
            </div>
        </div>
    </div>
</body>
</html>