<?php
// ============================================================
// ANIME VAULT - User Watchlist
// File: /var/www/html/anime/watchlist.php
// ============================================================

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo  = getDB();
$user = currentUser();

// Handle remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    verifyCsrf();
    $pdo->prepare("DELETE FROM watchlist WHERE user_id=? AND anime_id=?")->execute([$user['id'], (int)$_POST['anime_id']]);
    header('Location: /anime/watchlist.php');
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$where  = ['w.user_id = ?'];
$params = [$user['id']];
if ($statusFilter) { $where[] = 'w.status = ?'; $params[] = $statusFilter; }
$wSQL = implode(' AND ', $where);

$entries = $pdo->prepare(
    "SELECT w.*, a.title, a.slug, a.poster_url, a.episodes, a.type, a.status AS anime_status, a.rating AS site_rating
     FROM watchlist w JOIN anime a ON w.anime_id = a.id
     WHERE $wSQL ORDER BY w.updated_at DESC"
);
$entries->execute($params);
$entries = $entries->fetchAll();

// Group by status
$grouped = [];
foreach ($entries as $e) $grouped[$e['status']][] = $e;

$statusLabels = [
    'watching'      => '▶ Watching',
    'completed'     => '✓ Completed',
    'plan_to_watch' => '📋 Plan to Watch',
    'on_hold'       => '⏸ On Hold',
    'dropped'       => '✕ Dropped',
];

$pageTitle = 'My Watchlist';
include __DIR__ . '/includes/header.php';
?>

<section class="section-sm" style="background:var(--bg-secondary);border-bottom:1px solid var(--border);">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
      <h1>My Watchlist</h1>
      <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <a href="/anime/watchlist.php" class="filter-chip <?= !$statusFilter ? 'active' : '' ?>">All (<?= count($entries) ?>)</a>
        <?php foreach ($statusLabels as $k => $l):
          $cnt = count($grouped[$k] ?? []);
          if ($cnt): ?>
          <a href="?status=<?= $k ?>" class="filter-chip <?= $statusFilter === $k ? 'active' : '' ?>">
            <?= $l ?> (<?= $cnt ?>)
          </a>
        <?php endif; endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if (!$entries): ?>
      <div class="card text-center" style="padding:4rem;">
        <div style="font-size:3rem;margin-bottom:1rem;">📋</div>
        <h3>Your watchlist is empty</h3>
        <p class="text-muted mt-1">Start adding anime you want to watch!</p>
        <a href="/anime/browse.php" class="btn-primary mt-2">Browse Anime</a>
      </div>
    <?php elseif ($statusFilter && isset($grouped[$statusFilter])): ?>
      <?= watchlistTable($grouped[$statusFilter], $statusLabels[$statusFilter]) ?>
    <?php else: ?>
      <?php foreach ($statusLabels as $k => $l): if (!empty($grouped[$k])): ?>
        <?= watchlistTable($grouped[$k], $l) ?>
      <?php endif; endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php
function watchlistTable(array $items, string $label): string {
    ob_start();
    ?>
    <div class="card mb-3">
      <div class="card-header">
        <h3><?= htmlspecialchars($label) ?> <span class="text-muted text-sm">(<?= count($items) ?>)</span></h3>
      </div>
      <div class="data-table-wrap">
        <table class="data-table">
          <thead><tr><th>Poster</th><th>Title</th><th>Type</th><th>Progress</th><th>Your Score</th><th>Site Rating</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($items as $e): ?>
              <tr>
                <td style="width:50px;">
                  <?php if ($e['poster_url']): ?>
                    <img src="<?= htmlspecialchars($e['poster_url']) ?>" style="width:40px;height:55px;object-fit:cover;border-radius:4px;" loading="lazy">
                  <?php else: ?>
                    <div style="width:40px;height:55px;background:var(--bg-secondary);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">⛩</div>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="/anime/show.php?slug=<?= htmlspecialchars($e['slug']) ?>" style="font-weight:700;color:var(--text-primary);">
                    <?= htmlspecialchars($e['title']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($e['type']) ?></td>
                <td>
                  <?= $e['progress'] ?><?= $e['episodes'] ? ' / '.$e['episodes'] : '' ?> ep
                </td>
                <td><?= $e['score'] ? '<span style="color:var(--neon-gold);font-weight:700;">'.$e['score'].'</span>/10' : '—' ?></td>
                <td><?= $e['site_rating'] ?? '—' ?></td>
                <td>
                  <div class="td-actions">
                    <a href="/anime/show.php?slug=<?= htmlspecialchars($e['slug']) ?>" class="btn-ghost btn-sm">View</a>
                    <form method="post" style="display:inline;">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="remove" value="1">
                      <input type="hidden" name="anime_id" value="<?= $e['anime_id'] ?>">
                      <button class="btn-danger btn-sm" onclick="return confirm('Remove from list?')">✕</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

include __DIR__ . '/includes/footer.php';
?>
