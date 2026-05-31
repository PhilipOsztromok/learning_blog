<?php
// ============================================================
// ANIME VAULT - Admin: Reviews
// File: /var/www/html/anime/admin/reviews.php
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (isset($_POST['delete_review'])) {
        $rid = (int)$_POST['review_id'];
        // Get anime_id first to recalculate rating
        $row = $pdo->prepare("SELECT anime_id FROM reviews WHERE id=?");
        $row->execute([$rid]);
        $row = $row->fetch();
        $pdo->prepare("DELETE FROM reviews WHERE id=?")->execute([$rid]);
        if ($row) {
            $pdo->prepare("UPDATE anime SET rating = (SELECT AVG(rating) FROM reviews WHERE anime_id=?) WHERE id=?")
                ->execute([$row['anime_id'], $row['anime_id']]);
        }
        $msg = 'success:Review deleted.';
    }
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$total = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$reviews = $pdo->query(
    "SELECT r.*, u.username, a.title AS anime_title, a.slug AS anime_slug
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     JOIN anime a ON r.anime_id = a.id
     ORDER BY r.created_at DESC
     LIMIT $perPage OFFSET $offset"
)->fetchAll();

$pageTitle = 'Manage Reviews';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-header">
      <h2>Reviews (<?= number_format($total) ?>)</h2>
    </div>

    <?php if ($msg): [$t,$m] = explode(':',$msg,2); ?>
      <div class="alert alert-<?=$t?>"><?=htmlspecialchars($m)?></div>
    <?php endif; ?>

    <div class="card">
      <div class="data-table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>#</th><th>Anime</th><th>User</th><th>Rating</th><th>Title</th><th>Excerpt</th><th>Date</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if ($reviews): foreach ($reviews as $r): ?>
              <tr>
                <td class="text-muted"><?= $r['id'] ?></td>
                <td>
                  <a href="/anime/show.php?slug=<?= htmlspecialchars($r['anime_slug']) ?>" target="_blank" style="color:var(--neon-blue);font-size:0.85rem;">
                    <?= htmlspecialchars($r['anime_title']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($r['username']) ?></td>
                <td>
                  <span style="color:var(--neon-gold);font-weight:700;"><?= $r['rating'] ?>/10</span>
                </td>
                <td><?= htmlspecialchars($r['title'] ?? '—') ?></td>
                <td class="text-muted" style="font-size:0.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  <?= htmlspecialchars(substr($r['body'], 0, 80)) ?>…
                </td>
                <td class="text-muted text-sm"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                <td>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Delete this review?')">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="delete_review" value="1">
                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                    <button class="btn-danger btn-sm">Del</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">No reviews yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <a href="?page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
