<?php
// ============================================================
// ANIME VAULT - Homepage
// File: /var/www/html/anime/index.php
// ============================================================

require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Home';

$pdo = getDB();

// Featured / Top Rated
$topRated = $pdo->query(
    "SELECT a.id, a.title, a.slug, a.poster_url, a.rating, a.type, a.status, a.episodes,
            a.premiered_year, s.name AS studio_name
     FROM anime a LEFT JOIN studios s ON a.studio_id = s.id
     ORDER BY a.rating DESC, a.updated_at DESC LIMIT 8"
)->fetchAll();

// Recently Added
$recent = $pdo->query(
    "SELECT a.id, a.title, a.slug, a.poster_url, a.rating, a.type, a.status,
            a.premiered_year, s.name AS studio_name
     FROM anime a LEFT JOIN studios s ON a.studio_id = s.id
     ORDER BY a.created_at DESC LIMIT 8"
)->fetchAll();

// Currently Airing
$airing = $pdo->query(
    "SELECT a.id, a.title, a.slug, a.poster_url, a.rating, a.episodes
     FROM anime a WHERE a.status = 'Airing'
     ORDER BY a.rating DESC LIMIT 6"
)->fetchAll();

// Stats
$stats = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM anime) AS total_anime,
        (SELECT COUNT(*) FROM users) AS total_users,
        (SELECT COUNT(*) FROM reviews) AS total_reviews,
        (SELECT COUNT(*) FROM studios) AS total_studios"
)->fetch();

include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <div class="hero-badge">⛩ Your Anime Database</div>
      <h1>Discover, Track &amp;<br><span class="text-gradient">Review Anime</span></h1>
      <p class="hero-sub">From timeless classics to the latest season — browse our curated database, write reviews, and build your personal watchlist.</p>
      <div class="hero-actions">
        <a href="/anime/browse.php" class="btn-primary btn-lg">Browse All Anime</a>
        <?php if (!isLoggedIn()): ?>
          <a href="/anime/register.php" class="btn-secondary btn-lg">Join Free</a>
        <?php endif; ?>
      </div>
      <div class="hero-stats">
        <div class="stat-item">
          <span class="stat-num"><?= number_format($stats['total_anime']) ?></span>
          <span class="stat-label">Anime</span>
        </div>
        <div class="stat-item">
          <span class="stat-num"><?= number_format($stats['total_studios']) ?></span>
          <span class="stat-label">Studios</span>
        </div>
        <div class="stat-item">
          <span class="stat-num"><?= number_format($stats['total_reviews']) ?></span>
          <span class="stat-label">Reviews</span>
        </div>
        <div class="stat-item">
          <span class="stat-num"><?= number_format($stats['total_users']) ?></span>
          <span class="stat-label">Members</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Currently Airing -->
<?php if ($airing): ?>
<section class="section-sm" style="background:var(--bg-secondary); border-bottom:1px solid var(--border);">
  <div class="container">
    <div class="section-header">
      <div class="section-title">
        <h2>Currently Airing <span class="status-badge status-airing">● Live</span></h2>
      </div>
      <a href="/anime/browse.php?status=Airing" class="btn-ghost btn-sm">View All</a>
    </div>
    <div class="cards-grid">
      <?php foreach ($airing as $a): ?>
        <?= animeCardHtml($a) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Top Rated -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="section-title"><h2>Top Rated</h2></div>
      <a href="/anime/browse.php?sort=rating" class="btn-ghost btn-sm">See More</a>
    </div>
    <?php if ($topRated): ?>
      <div class="cards-grid">
        <?php foreach ($topRated as $a): ?>
          <?= animeCardHtml($a) ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="card text-center" style="padding:3rem;">
        <p class="text-muted">No anime added yet.</p>
        <?php if (isAdmin()): ?>
          <a href="/anime/admin/" class="btn-primary mt-2">Add Anime</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Recently Added -->
<?php if ($recent): ?>
<section class="section" style="background:var(--bg-secondary);">
  <div class="container">
    <div class="section-header">
      <div class="section-title"><h2>Recently Added</h2></div>
      <a href="/anime/browse.php?sort=newest" class="btn-ghost btn-sm">See More</a>
    </div>
    <div class="cards-grid">
      <?php foreach ($recent as $a): ?>
        <?= animeCardHtml($a) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
include __DIR__ . '/includes/footer.php';

// ── Helper: render a card ────────────────────────────────────
function animeCardHtml(array $a): string {
    $slug   = htmlspecialchars($a['slug']);
    $title  = htmlspecialchars($a['title']);
    $rating = $a['rating'] ? number_format((float)$a['rating'], 1) : '—';
    $year   = $a['premiered_year'] ?? '';
    $type   = htmlspecialchars($a['type'] ?? '');
    $status = $a['status'] ?? '';
    $poster = $a['poster_url']
        ? '<img src="' . htmlspecialchars($a['poster_url']) . '" alt="' . $title . '" class="anime-card-poster" loading="lazy">'
        : '<div class="anime-card-poster-placeholder">⛩</div>';

    $statusClass = match($status) {
        'Airing'    => 'status-airing',
        'Completed' => 'status-completed',
        'Upcoming'  => 'status-upcoming',
        default     => 'status-hiatus',
    };

    return <<<HTML
    <a href="/anime/show.php?slug={$slug}" class="anime-card" style="text-decoration:none;">
      <div style="position:relative;">
        {$poster}
        <span class="anime-card-badge">{$type}</span>
        <span class="anime-card-rating">★ {$rating}</span>
      </div>
      <div class="anime-card-body">
        <div class="anime-card-title">{$title}</div>
        <div class="anime-card-meta">
          <span>{$year}</span>
          <span class="status-badge {$statusClass}">{$status}</span>
        </div>
      </div>
    </a>
    HTML;
}
?>
