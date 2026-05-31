<?php
// ============================================================
// ANIME VAULT - Browse Page
// File: /var/www/html/anime/browse.php
// ============================================================

require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Browse Anime';

$pdo = getDB();

// ── Inputs ───────────────────────────────────────────────────
$q       = trim($_GET['q'] ?? '');
$genre   = $_GET['genre'] ?? '';
$type    = $_GET['type'] ?? '';
$status  = $_GET['status'] ?? '';
$sort    = $_GET['sort'] ?? 'rating';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset  = ($page - 1) * $perPage;

// ── Build query ───────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($q) {
    $where[]  = "(MATCH(a.title, a.synopsis) AGAINST(? IN BOOLEAN MODE) OR a.title LIKE ?)";
    $params[] = $q . '*';
    $params[] = '%' . $q . '%';
}

if ($genre) {
    $where[]  = "EXISTS (SELECT 1 FROM anime_genres ag JOIN genres g ON ag.genre_id = g.id WHERE ag.anime_id = a.id AND g.slug = ?)";
    $params[] = $genre;
}

if ($type)   { $where[] = "a.type = ?";   $params[] = $type; }
if ($status) { $where[] = "a.status = ?"; $params[] = $status; }

$orderBy = match($sort) {
    'newest' => 'a.created_at DESC',
    'oldest' => 'a.created_at ASC',
    'title'  => 'a.title ASC',
    'year'   => 'a.premiered_year DESC',
    default  => 'a.rating DESC, a.title ASC',
};

$whereSQL = implode(' AND ', $where);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM anime a WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));

// Fetch
$stmt = $pdo->prepare(
    "SELECT a.id, a.title, a.slug, a.poster_url, a.rating, a.type, a.status, a.episodes, a.premiered_year,
            s.name AS studio_name
     FROM anime a LEFT JOIN studios s ON a.studio_id = s.id
     WHERE $whereSQL
     ORDER BY $orderBy
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$shows = $stmt->fetchAll();

// Genres for filter dropdown
$genres = $pdo->query("SELECT name, slug FROM genres ORDER BY name")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="section-sm" style="background:var(--bg-secondary);border-bottom:1px solid var(--border);">
  <div class="container">
    <h1 style="margin-bottom:1.25rem;">
      <?= $q ? 'Results for "<span class="text-gradient">' . htmlspecialchars($q) . '</span>"' : 'Browse Anime' ?>
    </h1>

    <form method="get" class="filter-bar">
      <?php if ($q): ?>
        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
      <?php endif; ?>

      <select name="genre" class="filter-select" onchange="this.form.submit()">
        <option value="">All Genres</option>
        <?php foreach ($genres as $g): ?>
          <option value="<?= htmlspecialchars($g['slug']) ?>" <?= $genre === $g['slug'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($g['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <select name="type" class="filter-select" onchange="this.form.submit()">
        <option value="">All Types</option>
        <?php foreach (['TV','Movie','OVA','ONA','Special'] as $t): ?>
          <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>

      <select name="status" class="filter-select" onchange="this.form.submit()">
        <option value="">All Status</option>
        <?php foreach (['Airing','Completed','Upcoming','Hiatus'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>

      <select name="sort" class="filter-select" onchange="this.form.submit()">
        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top Rated</option>
        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Added</option>
        <option value="year"   <?= $sort === 'year'   ? 'selected' : '' ?>>By Year</option>
        <option value="title"  <?= $sort === 'title'  ? 'selected' : '' ?>>A–Z</option>
      </select>

      <?php if ($genre || $type || $status || $q): ?>
        <a href="/anime/browse.php" class="btn-ghost btn-sm">✕ Clear</a>
      <?php endif; ?>

      <span class="text-muted text-sm" style="margin-left:auto;"><?= number_format($total) ?> result<?= $total !== 1 ? 's' : '' ?></span>
    </form>
  </div>
</div>

<section class="section">
  <div class="container">
    <?php if ($shows): ?>
      <div class="cards-grid">
        <?php foreach ($shows as $a):
          $slug   = htmlspecialchars($a['slug']);
          $title  = htmlspecialchars($a['title']);
          $rating = $a['rating'] ? number_format((float)$a['rating'], 1) : '—';
          $type2  = htmlspecialchars($a['type'] ?? '');
          $status2 = $a['status'] ?? '';
          $statusClass = match($status2) {
              'Airing' => 'status-airing', 'Upcoming' => 'status-upcoming',
              'Completed' => 'status-completed', default => 'status-hiatus'
          };
          $poster = $a['poster_url']
              ? '<img src="' . htmlspecialchars($a['poster_url']) . '" alt="' . $title . '" class="anime-card-poster" loading="lazy">'
              : '<div class="anime-card-poster-placeholder">⛩</div>';
        ?>
          <a href="/anime/show.php?slug=<?= $slug ?>" class="anime-card" style="text-decoration:none;">
            <div style="position:relative;">
              <?= $poster ?>
              <span class="anime-card-badge"><?= $type2 ?></span>
              <span class="anime-card-rating">★ <?= $rating ?></span>
            </div>
            <div class="anime-card-body">
              <div class="anime-card-title"><?= $title ?></div>
              <div class="anime-card-meta">
                <span><?= $a['premiered_year'] ?? '' ?></span>
                <span class="status-badge <?= $statusClass ?>"><?= $status2 ?></span>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
        <div class="pagination">
          <?php
          $qs = http_build_query(array_filter(['q'=>$q,'genre'=>$genre,'type'=>$type,'status'=>$status,'sort'=>$sort]));
          for ($i = 1; $i <= $pages; $i++):
            $active = $i === $page ? 'active' : '';
            $pqs = $qs ? $qs . '&page=' . $i : 'page=' . $i;
          ?>
            <a href="/anime/browse.php?<?= $pqs ?>" class="page-btn <?= $active ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="card text-center" style="padding:4rem;">
        <div style="font-size:3rem;margin-bottom:1rem;">🔍</div>
        <h3>No results found</h3>
        <p class="text-muted mt-1">Try adjusting your filters or search terms.</p>
        <a href="/anime/browse.php" class="btn-primary mt-2">Clear Filters</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
