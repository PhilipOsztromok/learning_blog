<?php
// ============================================================
// ANIME VAULT - Register Page
// File: /var/www/html/anime/register.php
// ============================================================

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: /anime/'); exit; }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $result = registerUser(
        trim($_POST['username'] ?? ''),
        trim($_POST['email'] ?? ''),
        $_POST['password'] ?? ''
    );
    if ($result['ok']) {
        header('Location: /anime/');
        exit;
    }
    $error = $result['error'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register – Anime Vault</title>
  <link rel="stylesheet" href="/anime/styles/main.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <h1>Anime<span class="text-gradient">Vault</span></h1>
      <p>Create your free account</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" required autofocus
               maxlength="50" pattern="[a-zA-Z0-9_]+"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="Letters, numbers, underscores">
      </div>
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" required minlength="8">
        <span class="form-hint">At least 8 characters</span>
      </div>
      <button type="submit" class="btn-primary w-full" style="margin-top:0.5rem;">Create Account</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="/anime/login.php">Sign in</a>
    </div>
  </div>
</div>
</body>
</html>
