<?php
// ============================================================
// ANIME VAULT - 404 Page
// File: /var/www/html/anime/404.php
// ============================================================

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 – Not Found | Anime Vault</title>
  <link rel="stylesheet" href="/anime/styles/main.css">
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:2rem;background:var(--gradient-hero);">
  <div>
    <div style="font-size:6rem;margin-bottom:1rem;line-height:1;">⛩</div>
    <h1 style="font-size:clamp(4rem,10vw,8rem);font-family:var(--font-display);background:linear-gradient(90deg,var(--neon-pink),var(--neon-purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;margin-bottom:0.5rem;">404</h1>
    <h2 style="margin-bottom:1rem;color:var(--text-secondary);">This anime doesn't exist (yet)</h2>
    <p style="color:var(--text-secondary);margin-bottom:2rem;max-width:400px;margin-left:auto;margin-right:auto;">
      The page you're looking for has wandered into another dimension. Perhaps it was isekai'd.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="/anime/" class="btn-primary">← Go Home</a>
      <a href="/anime/browse.php" class="btn-secondary">Browse Anime</a>
    </div>
  </div>
</div>
</body>
</html>
