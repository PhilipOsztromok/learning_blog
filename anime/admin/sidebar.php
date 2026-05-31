<?php
// File: /var/www/html/anime/admin/sidebar.php
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
  <div class="admin-sidebar-title">Main</div>
  <a href="/anime/admin/" class="admin-nav-link <?= $current === 'index.php' ? 'active' : '' ?>">
    <span class="icon">📊</span> Dashboard
  </a>

  <div class="admin-sidebar-title" style="margin-top:1rem;">Content</div>
  <a href="/anime/admin/anime.php" class="admin-nav-link <?= $current === 'anime.php' ? 'active' : '' ?>">
    <span class="icon">⛩</span> Anime
  </a>
  <a href="/anime/admin/edit_anime.php" class="admin-nav-link <?= $current === 'edit_anime.php' && !isset($_GET['id']) ? 'active' : '' ?>">
    <span class="icon">＋</span> Add Anime
  </a>
  <a href="/anime/admin/studios.php" class="admin-nav-link <?= $current === 'studios.php' ? 'active' : '' ?>">
    <span class="icon">🎬</span> Studios
  </a>
  <a href="/anime/admin/people.php" class="admin-nav-link <?= $current === 'people.php' ? 'active' : '' ?>">
    <span class="icon">👤</span> People
  </a>
  <a href="/anime/admin/genres.php" class="admin-nav-link <?= $current === 'genres.php' ? 'active' : '' ?>">
    <span class="icon">🏷</span> Genres
  </a>

  <div class="admin-sidebar-title" style="margin-top:1rem;">Users</div>
  <a href="/anime/admin/users.php" class="admin-nav-link <?= $current === 'users.php' ? 'active' : '' ?>">
    <span class="icon">👥</span> Users
  </a>
  <a href="/anime/admin/reviews.php" class="admin-nav-link <?= $current === 'reviews.php' ? 'active' : '' ?>">
    <span class="icon">✍</span> Reviews
  </a>

  <div class="admin-sidebar-title" style="margin-top:1rem;">Site</div>
  <a href="/anime/" class="admin-nav-link">
    <span class="icon">🏠</span> View Site
  </a>
</aside>
