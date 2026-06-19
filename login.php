<?php
declare(strict_types=1);

require_once __DIR__ . './dashboard/config/init.php';
require_once __DIR__ . './dashboard/core/Auth.php';
require_once __DIR__ . './dashboard/core/RateLimiter.php';
require_once __DIR__ . './dashboard/core/Csrf.php';

$auth = new Auth($db);
$rateLimiter = new RateLimiter($db);

// Already logged in?
if ($auth->check()) {
    header('Location: ' . BASE_URL . 'dashboard/');
    exit;
}

$error = '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid. Please refresh the page.';
    } else {
        // Rate limit
        $check = $rateLimiter->check($ip, 'login', MAX_LOGIN_ATTEMPTS, LOCKOUT_DURATION);
        if (!$check['allowed']) {
            $error = 'Too many attempts. Account locked for 15 minutes.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if ($username === '' || $password === '') {
                $error = 'Please enter username and password.';
            } else {
                $result = $auth->login($username, $password, $ip, $ua);
                if ($result['success']) {
                    header('Location: ' . BASE_URL . 'dashboard/');
                    exit;
                }
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — HomeCare Dashboard</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
             background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
             min-height:100vh;display:flex;align-items:center;justify-content:center}
        .box{background:#fff;padding:40px;border-radius:12px;
             box-shadow:0 20px 60px rgba(0,0,0,0.3);width:100%;max-width:400px}
        h1{color:#333;margin-bottom:6px;font-size:24px}
        .sub{color:#666;margin-bottom:24px;font-size:14px}
        .err{background:#fee;color:#c33;padding:12px;border-radius:6px;
             margin-bottom:20px;font-size:14px;border-left:4px solid #c33}
        .grp{margin-bottom:20px}
        label{display:block;margin-bottom:6px;color:#555;font-size:14px;font-weight:500}
        input{width:100%;padding:12px;border:2px solid #e1e1e1;border-radius:6px;font-size:15px}
        input:focus{outline:none;border-color:#667eea}
        button{width:100%;padding:14px;background:#667eea;color:#fff;border:none;
               border-radius:6px;font-size:16px;font-weight:600;cursor:pointer}
        button:hover{background:#5568d3}
        .info{margin-top:20px;text-align:center;color:#888;font-size:13px}
    </style>
</head>
<body>
    <div class="box">
        <h1>🔐 Dashboard</h1>
        <p class="sub">HomeCare Organization</p>
        
        <?php if ($error): ?>
            <div class="err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= Csrf::field() ?>
            
            <div class="grp">
                <label for="u">Username</label>
                <input type="text" id="u" name="username" required autocomplete="username" autofocus>
            </div>
            
            <div class="grp">
                <label for="p">Password</label>
                <input type="password" id="p" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit">Sign In</button>
        </form>
        
        <p class="info">Default: admin / password</p>
    </div>
</body>
</html>