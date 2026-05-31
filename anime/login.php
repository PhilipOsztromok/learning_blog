<?php
// ============================================================
// ANIME VAULT - Login Page
// File: /var/www/html/anime/login.php
// ============================================================

require_once __DIR__ . '/includes/auth.php';

// Prevent the browser from caching this page, which would cause stale CSRF tokens.
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if (isLoggedIn()) {
    header('Location: /anime/');
    exit;
}

$error = '';
$next  = $_GET['next'] ?? '/anime/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        // Stale token (e.g. browser back button reusing an old form).
        // Silently regenerate and show the form again rather than hard-failing.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $error = 'Your session expired. Please try again.';
    } else {
        $result = loginUser(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
        if ($result['ok']) {
            header('Location: ' . $next);
            exit;
        }
        $error = $result['error'];
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – Anime Vault</title>
  <link rel="stylesheet" href="/anime/styles/main.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <h1>Anime<span class="text-gradient">Vault</span></h1>
      <p>Sign in to your account</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" required autofocus
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn-primary w-full" style="margin-top:0.5rem;">Sign In</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="/anime/register.php">Register free</a>
    </div>
    <div class="auth-footer" style="margin-top:0.5rem;">
      <a href="https://osztromok.com" style="color:var(--text-secondary);">← Back to osztromok.com</a>
    </div>
  </div>
</div>
</body>
</html>
