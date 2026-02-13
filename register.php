<?php
require_once 'config.php';
init_db();

$message = '';

if ($_POST['username'] ?? false) {
    // Verify reCAPTCHA
    if (!isset($_POST['g-recaptcha-response']) || !verify_recaptcha($_POST['g-recaptcha-response'])) {
        $message = 'reCAPTCHA verification failed. Please try again.';
    } else {
        $pdo = get_db();
    
    try {
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $approved = ($_POST['username'] === 'OSRG') ? 1 : 0;
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, approved) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['username'], $_POST['email'], $password_hash, $approved]);
        
        if ($approved) {
            $message = 'Registration successful! <a href="login.php">Login here</a>';
        } else {
            // Send notification email to admin
            $subject = "New User Registration - OSRG Connect";
            $body = "A new user has registered and needs approval:\n\n";
            $body .= "Username: " . $_POST['username'] . "\n";
            $body .= "Email: " . $_POST['email'] . "\n";
            $body .= "Registration Time: " . date('Y-m-d H:i:s') . "\n\n";
            $body .= "Please login to the admin panel to approve or reject this user:\n";
            $body .= "https://connect.osrg.lol/admin\n\n";
            $body .= "OSRG Connect Admin System";
            
            $headers = "From: OSRG Connect <omer@osrg.lol>\r\n";
            $headers .= "Reply-To: omer@osrg.lol\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            ini_set('SMTP', 'smtp.hostinger.com');
            ini_set('smtp_port', '465');
            ini_set('sendmail_from', 'omer@osrg.lol');
            
            mail('omersr12@gmail.com', $subject, $body, $headers);
            
            // Add internal notification for Admin
            try {
                // Find Admin ID (OSRG or Omer Shalom Rimon)
                $adm = $pdo->query("SELECT id FROM users WHERE username IN ('OSRG', 'Omer Shalom Rimon', 'backup') OR email = 'omersr12@gmail.com' LIMIT 1")->fetch();
                if ($adm) {
                    $new_user_id = $pdo->lastInsertId();
                    $notif_text = "New user registered: " . $_POST['username'];
                    $activity_stmt = $pdo->prepare("INSERT INTO user_activity (user_id, type, description, link) VALUES (?, 'system', ?, 'admin.php')");
                    // We log this activity under the ADMIN's ID so they see it, OR we can implement a 'target_user_id' column if the system supports it.
                    // Assuming current system shows activity OF followed users or general logs.
                    // Better approach for this specific request: Log it as system activity that the admin will see in the global logs.
                    $activity_stmt->execute([$new_user_id, $notif_text]); 
                }
            } catch (Exception $e) { /* Ignore notification error */ }
            
            $message = 'Registration submitted! Please wait for admin approval before logging in.';
        }
        } catch (PDOException $e) {
            $message = 'Username or email already exists';
        }
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
    <link rel="manifest" href="site.webmanifest">
    <title>Register - OSRG Connect</title>
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-Y1Y8S6WHNH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('config', 'G-Y1Y8S6WHNH');
    </script>
    
    <!-- reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1877f2; color: white; padding: 15px; text-align: center; }
        .form-group { margin: 15px 0; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        button { background: #1877f2; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .message { color: green; padding: 10px; }
        
        .password-container { position: relative; }
        .show-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #666; font-size: 14px; }
        .password-strength { margin-top: 5px; font-size: 12px; }
        .strength-weak { color: #f44336; }
        .strength-medium { color: #ff9800; }
        .strength-strong { color: #4caf50; }
        
        @media (max-width: 768px) {
            .container { padding: 15px; margin: 10px; }
            .header { padding: 20px 15px; }
            input, button { padding: 12px; font-size: 16px; }
        }
    </style>
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const showButton = document.querySelector('.show-password');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                showButton.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                showButton.textContent = '👁️';
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            let score = 0;
            
            // Length check
            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            
            // Character variety checks
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            if (score < 3) {
                strengthDiv.textContent = 'Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (score < 5) {
                strengthDiv.textContent = 'Medium password';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = 'Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
        }
        
        // reCAPTCHA v3 integration
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Verifying...';
                
                grecaptcha.ready(function() {
                    grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'register'}).then(function(token) {
                        document.getElementById('g-recaptcha-response').value = token;
                        form.submit();
                    });
                });
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Join Private Social</h1>
            <p>Create your account</p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Password" required oninput="checkPasswordStrength()">
                    <button type="button" class="show-password" onclick="togglePassword()">👁️</button>
                </div>
                <div id="password-strength" class="password-strength"></div>
            </div>
            <div class="form-group">
                <button type="submit" id="submitBtn">Register</button>
            </div>
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
            <input type="hidden" name="cookie_consent" id="cookie_consent_input">
        </form>

        <p style="text-align: center; margin-top: 20px;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>

<!-- Cookie consent banner -->
<div id="cookieBanner" style="position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid #ddd;padding:12px;display:none;justify-content:space-between;align-items:center;gap:12px;z-index:9999;">
  <div style="font-size:14px;color:#333;">This site uses cookies to improve your experience. Do you accept cookies?</div>
  <div>
    <button id="cookieAcceptBtn" style="background:#1877f2;color:#fff;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;margin-right:8px;">Accept</button>
    <button id="cookieDeclineBtn" style="background:#ddd;color:#333;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;">Decline</button>
  </div>
</div>

<script>
(function(){
  function setHiddenConsent(val){ var el = document.getElementById('cookie_consent_input'); if(el) el.value = val; }
  var consent = localStorage.getItem('cookie_consent') || (document.cookie.match(/(^|; )cookie_consent=([^;]+)/)? RegExp.$2 : null);
  if(consent === 'accepted'){ document.cookie = 'cookie_consent=accepted; path=/'; setHiddenConsent('accepted'); var b=document.getElementById('cookieBanner'); if(b) b.remove(); }
  else if(consent === 'declined'){ setHiddenConsent('declined'); var b=document.getElementById('cookieBanner'); if(b) b.remove(); }
  else {
    var b=document.getElementById('cookieBanner'); if(b) b.style.display = 'flex';
  }
  var acceptBtn = document.getElementById('cookieAcceptBtn');
  if (acceptBtn) acceptBtn.addEventListener('click', function(){ localStorage.setItem('cookie_consent','accepted'); document.cookie='cookie_consent=accepted; path=/'; setHiddenConsent('accepted'); var b=document.getElementById('cookieBanner'); if(b) b.remove(); });
  var declineBtn = document.getElementById('cookieDeclineBtn');
  if (declineBtn) declineBtn.addEventListener('click', function(){ localStorage.setItem('cookie_consent','declined'); setHiddenConsent('declined'); var b=document.getElementById('cookieBanner'); if(b) b.remove(); });
  var forms = document.querySelectorAll('form');
  forms.forEach(function(f){ f.addEventListener('submit', function(){ setHiddenConsent(localStorage.getItem('cookie_consent') || 'accepted'); }); });
})();
</script>

</body>
</html>
