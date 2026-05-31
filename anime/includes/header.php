<?php
// ============================================================
// ANIME VAULT - Common Header Include
// File: /var/www/html/anime/includes/header.php
// Usage: include at top of each page after setting $pageTitle
// ============================================================

if (!isset($pageTitle)) $pageTitle = 'Anime Vault';
$user = currentUser();
$canonicalBase = 'https://osztromok.com/anime';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> – Anime Vault</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc ?? 'Your definitive anime database – browse shows, leave reviews, track your watchlist.') ?>">
  <link rel="stylesheet" href="/anime/styles/main.css">
  <link rel="icon" href="/anime/styles/favicon.svg" type="image/svg+xml">
</head>
<body>

<nav class="navbar">
  <div class="container">
    <div class="navbar-inner">

      <a href="/anime/" class="navbar-brand">
        <span class="brand-icon">⛩</span>
        Anime<span class="accent">Vault</span>
      </a>

      <nav class="navbar-nav">
        <a href="/anime/" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Home</a>
        <a href="/anime/browse.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'browse.php' ? 'active' : '' ?>">Browse</a>
        <a href="/anime/browse.php?type=Movie" class="nav-link">Movies</a>
        <a href="/anime/browse.php?sort=rating" class="nav-link">Top Rated</a>
        <?php if ($user): ?>
          <a href="/anime/watchlist.php" class="nav-link">My List</a>
        <?php endif; ?>
        <?php if ($user && $user['role'] === 'admin'): ?>
          <a href="/anime/admin/" class="nav-link text-pink">Admin</a>
        <?php endif; ?>
      </nav>

      <form class="navbar-search" action="/anime/browse.php" method="get">
        <input type="search" name="q" placeholder="Search anime…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        <button type="submit">Search</button>
      </form>

      <div class="navbar-auth">
        <?php if ($user): ?>
          <span class="text-sm text-muted">Hi, <strong><?= htmlspecialchars($user['username']) ?></strong></span>
          <a href="/anime/logout.php" class="btn-ghost btn-sm">Log Out</a>
        <?php else: ?>
          <a href="/anime/login.php" class="btn-ghost">Log In</a>
          <a href="/anime/register.php" class="btn-primary">Register</a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</nav>
