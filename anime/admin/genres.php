<?php
// ============================================================
// ANIME VAULT - Admin: Genres
// File: /var/www/html/anime/admin/genres.php
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['save_genre'])) {
        $id   = (int)($_POST['genre_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (!$slug) $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
        $slug = strtolower(trim($slug, '-'));

        if (!$name) {
            $msg = 'error:Name is required.';
        } elseif ($id) {
            $pdo->prepare("UPDATE genres SET name=?,slug=? WHERE id=?")->execute([$name, $slug, $id]);
            $msg = 'success:Genre updated.';
        } else {
            try {
                $pdo->prepare("INSERT INTO genres (name,slug) VALUES (?,?)")->execute([$name, $slug]);
                $msg = 'success:Genre added.';
            } catch (PDOException $e) {
                $msg = 'error:Genre name or slug already exists.';
            }
        }
    }

    if (isset($_POST['delete_genre'])) {
        $pdo->prepare("DELETE FROM genres WHERE id=?")->execute([(int)$_POST['genre_id']]);
        $msg = 'success:Genre deleted.';
    }
}

$genres = $pdo->query(
    "SELECT g.*, COUNT(ag.anime_id) AS anime_count
     FROM genres g LEFT JOIN anime_genres ag ON g.id = ag.genre_id
     GROUP BY g.id ORDER BY g.name"
)->fetchAll();

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM genres WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$pageTitle = 'Manage Genres';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-header">
      <h2>Genres</h2>
      <button class="btn-primary" onclick="document.getElementById('genre-form').classList.toggle('hidden')">+ Add Genre</button>
    </div>

    <?php if ($msg): [$t,$m] = explode(':',$msg,2); ?>
      <div class="alert alert-<?=$t?>"><?=htmlspecialchars($m)?></div>
    <?php endif; ?>

    <!-- Form -->
    <div id="genre-form" class="card mb-3 <?= $editing ? '' : 'hidden' ?>">
      <div class="card-header">
        <h3><?= $editing ? 'Edit Genre' : 'Add Genre' ?></h3>
        <button class="btn-ghost btn-sm" onclick="document.getElementById('genre-form').classList.add('hidden')">✕</button>
      </div>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="save_genre" value="1">
        <input type="hidden" name="genre_id" value="<?= $editing['id'] ?? 0 ?>">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Name *</label>
            <input type="text" name="name" id="genre-name" class="form-control" required
                   value="<?= htmlspecialchars($editing['name'] ?? '') ?>"
                   oninput="autoSlug(this.value)">
          </div>
          <div class="form-group">
            <label class="form-label">Slug (URL-safe)</label>
            <input type="text" name="slug" id="genre-slug" class="form-control"
                   value="<?= htmlspecialchars($editing['slug'] ?? '') ?>"
                   placeholder="auto-generated">
            <span class="form-hint">Used in URLs: /browse?genre=this-value</span>
          </div>
        </div>
        <button type="submit" class="btn-primary">Save Genre</button>
      </form>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="data-table-wrap">
        <table class="data-table">
          <thead><tr><th>#</th><th>Name</th><th>Slug</th><th>Anime</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if ($genres): foreach ($genres as $g): ?>
              <tr>
                <td class="text-muted"><?= $g['id'] ?></td>
                <td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
                <td><code style="font-size:0.8rem;color:var(--neon-blue)"><?= htmlspecialchars($g['slug']) ?></code></td>
                <td><a href="/anime/browse.php?genre=<?= htmlspecialchars($g['slug']) ?>" class="anime-card-genre" target="_blank"><?= $g['anime_count'] ?></a></td>
                <td>
                  <div class="td-actions">
                    <a href="?edit=<?= $g['id'] ?>" class="btn-ghost btn-sm">Edit</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this genre? It will be removed from all anime.')">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="delete_genre" value="1">
                      <input type="hidden" name="genre_id" value="<?= $g['id'] ?>">
                      <button type="submit" class="btn-danger btn-sm">Del</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="5" class="text-center text-muted" style="padding:2rem;">No genres found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function autoSlug(val) {
  const slugField = document.getElementById('genre-slug');
  if (!slugField.dataset.manual) {
    slugField.value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  }
}
document.getElementById('genre-slug').addEventListener('input', function() {
  this.dataset.manual = 'true';
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
