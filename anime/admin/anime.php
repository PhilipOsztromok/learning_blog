<?php
// ============================================================
// ANIME VAULT - Admin: Anime List
// File: /var/www/html/anime/admin/anime.php
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDB();

$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($q) { $where[] = "a.title LIKE ?"; $params[] = '%' . $q . '%'; }
$whereSQL = implode(' AND ', $where);

$total = (int) $pdo->prepare("SELECT COUNT(*) FROM anime a WHERE $whereSQL")->execute($params) ? null : 0;
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM anime a WHERE $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT a.id, a.title, a.slug, a.type, a.status, a.rating, a.episodes,
            a.premiered_year, a.created_at, s.name AS studio_name
     FROM anime a LEFT JOIN studios s ON a.studio_id = s.id
     WHERE $whereSQL ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$shows = $stmt->fetchAll();

$pageTitle = 'Manage Anime';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-header">
      <h2>Anime (<?= number_format($total) ?>)</h2>
      <a href="/anime/admin/edit_anime.php" class="btn-primary">+ Add Anime</a>
    </div>

    <form method="get" style="display:flex;gap:0.5rem;margin-bottom:1.5rem;">
      <input type="search" name="q" class="form-control" placeholder="Search by title…" value="<?= htmlspecialchars($q) ?>" style="max-width:320px;">
      <button type="submit" class="btn-primary btn-sm">Search</button>
      <?php if ($q): ?><a href="/anime/admin/anime.php" class="btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>

    <div class="card">
      <div class="data-table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th><th>Title</th><th>Studio</th><th>Type</th><th>Status</th>
              <th>Rating</th><th>Eps</th><th>Year</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($shows): ?>
              <?php foreach ($shows as $a): ?>
                <tr>
                  <td class="text-muted"><?= $a['id'] ?></td>
                  <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
                  <td class="text-muted"><?= htmlspecialchars($a['studio_name'] ?? '—') ?></td>
                  <td><?= $a['type'] ?></td>
                  <td><span class="status-badge status-<?= strtolower($a['status']) ?>"><?= $a['status'] ?></span></td>
                  <td><?= $a['rating'] ?? '—' ?></td>
                  <td><?= $a['episodes'] ?? '—' ?></td>
                  <td><?= $a['premiered_year'] ?? '—' ?></td>
                  <td>
                    <div class="td-actions">
                      <a href="/anime/admin/edit_anime.php?id=<?= $a['id'] ?>" class="btn-ghost btn-sm">Edit</a>
                      <a href="/anime/show.php?slug=<?= htmlspecialchars($a['slug']) ?>" class="btn-ghost btn-sm" target="_blank">View</a>
                      <a href="/anime/admin/delete_anime.php?id=<?= $a['id'] ?>&csrf=<?= csrfToken() ?>"
                         class="btn-danger btn-sm"
                         onclick="return confirm('Delete <?= addslashes($a['title']) ?>?')">Del</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="9" class="text-center text-muted" style="padding:2rem;">No anime found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++):
          $qs = $q ? 'q='.urlencode($q).'&' : '';
        ?>
          <a href="?<?= $qs ?>page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
