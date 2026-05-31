<?php
// File: /var/www/html/anime/403.php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 – Forbidden | Anime Vault</title>
  <link rel="stylesheet" href="/anime/styles/main.css">
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:2rem;background:var(--gradient-hero);">
  <div>
    <div style="font-size:5rem;margin-bottom:1rem;">🚫</div>
    <h1 style="font-size:clamp(3rem,8vw,7rem);font-family:var(--font-display);color:var(--neon-pink);line-height:1;margin-bottom:0.5rem;">403</h1>
    <h2 style="margin-bottom:1rem;color:var(--text-secondary);">Access Denied</h2>
    <p style="color:var(--text-secondary);margin-bottom:2rem;">You need admin privileges to view this page.</p>
    <a href="/anime/" class="btn-primary">← Go Home</a>
  </div>
</div>
</body>
</html>
