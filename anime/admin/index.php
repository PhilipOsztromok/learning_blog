<?php
// ============================================================
// ANIME VAULT - Admin Dashboard
// File: /var/www/html/anime/admin/index.php
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDB();

$stats = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM anime)    AS total_anime,
        (SELECT COUNT(*) FROM users)    AS total_users,
        (SELECT COUNT(*) FROM reviews)  AS total_reviews,
        (SELECT COUNT(*) FROM studios)  AS total_studios,
        (SELECT COUNT(*) FROM people)   AS total_people,
        (SELECT AVG(rating) FROM anime WHERE rating IS NOT NULL) AS avg_rating"
)->fetch();

$recentAnime = $pdo->query(
    "SELECT a.id, a.title, a.status, a.rating, a.created_at, u.username
     FROM anime a LEFT JOIN users u ON a.created_by = u.id
     ORDER BY a.created_at DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <div class="admin-content">
    <div class="admin-header">
      <h2>Dashboard</h2>
      <a href="/anime/admin/edit_anime.php" class="btn-primary">+ Add Anime</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon pink">⛩</div>
        <div><div class="stat-card-num"><?= number_format($stats['total_anime']) ?></div><div class="stat-card-label">Anime</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon blue">👥</div>
        <div><div class="stat-card-num"><?= number_format($stats['total_users']) ?></div><div class="stat-card-label">Users</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">✍</div>
        <div><div class="stat-card-num"><?= number_format($stats['total_reviews']) ?></div><div class="stat-card-label">Reviews</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon gold">🎬</div>
        <div><div class="stat-card-num"><?= number_format($stats['total_studios']) ?></div><div class="stat-card-label">Studios</div></div>
      </div>
    </div>

    <!-- Recent Anime -->
    <div class="card">
      <div class="card-header">
        <h3>Recently Added</h3>
        <a href="/anime/admin/anime.php" class="btn-ghost btn-sm">View All</a>
      </div>
      <div class="data-table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Status</th>
              <th>Rating</th>
              <th>Added By</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recentAnime): ?>
              <?php foreach ($recentAnime as $a): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
                  <td><span class="status-badge status-<?= strtolower($a['status']) ?>"><?= $a['status'] ?></span></td>
                  <td><?= $a['rating'] ?? '—' ?></td>
                  <td><?= htmlspecialchars($a['username'] ?? 'System') ?></td>
                  <td class="text-muted text-sm"><?= date('M j, Y', strtotime($a['created_at'])) ?></td>
                  <td>
                    <div class="td-actions">
                      <a href="/anime/admin/edit_anime.php?id=<?= $a['id'] ?>" class="btn-ghost btn-sm">Edit</a>
                      <a href="/anime/show.php?slug=<?= urlencode($a['id']) ?>" class="btn-ghost btn-sm">View</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center text-muted" style="padding:2rem;">No anime yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
