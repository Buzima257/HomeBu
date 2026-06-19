<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 — Forbidden</title>
    <style>
        body{font-family:sans-serif;text-align:center;padding:80px 20px}
        h1{color:#e74c3c;font-size:72px;margin:0}
        p{color:#555;font-size:18px;margin:20px 0}
        a{color:#3498db;text-decoration:none}
    </style>
</head>
<body>
    <h1>403</h1>
    <p>Access Forbidden. You don't have permission to view this resource.</p>
    <p><a href="<?= BASE_URL ?>login.php">← Return to Login</a></p>
</body>
</html>