<?php
// ============================================================
// ANIME VAULT - Admin: Studios
// File: /var/www/html/anime/admin/studios.php
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDB();
$msg = '';

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['save_studio'])) {
        $id          = (int)($_POST['studio_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $country     = trim($_POST['country'] ?? '');
        $founded     = $_POST['founded_year'] ? (int)$_POST['founded_year'] : null;
        $website     = trim($_POST['website'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $logo        = trim($_POST['logo_url'] ?? '');

        if (!$name) {
            $msg = 'error:Name is required.';
        } else {
            if ($id) {
                $pdo->prepare("UPDATE studios SET name=?,country=?,founded_year=?,website=?,description=?,logo_url=? WHERE id=?")
                    ->execute([$name, $country, $founded, $website, $description, $logo, $id]);
                $msg = 'success:Studio updated.';
            } else {
                $pdo->prepare("INSERT INTO studios (name,country,founded_year,website,description,logo_url) VALUES (?,?,?,?,?,?)")
                    ->execute([$name, $country, $founded, $website, $description, $logo]);
                $msg = 'success:Studio added.';
            }
        }
    }

    if (isset($_POST['delete_studio'])) {
        $id = (int)$_POST['studio_id'];
        $pdo->prepare("DELETE FROM studios WHERE id = ?")->execute([$id]);
        $msg = 'success:Studio deleted.';
    }
}

$studios = $pdo->query(
    "SELECT s.*, COUNT(a.id) AS anime_count
     FROM studios s LEFT JOIN anime a ON a.studio_id = s.id
     GROUP BY s.id ORDER BY s.name"
)->fetchAll();

// Load studio for editing
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM studios WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$pageTitle = 'Manage Studios';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-header">
      <h2>Studios</h2>
      <button class="btn-primary" onclick="document.getElementById('studio-form-wrap').classList.toggle('hidden')">+ Add Studio</button>
    </div>

    <?php if ($msg): [$t,$m] = explode(':',$msg,2); ?>
      <div class="alert alert-<?=$t?>"><?=htmlspecialchars($m)?></div>
    <?php endif; ?>

    <!-- Add / Edit Form -->
    <div id="studio-form-wrap" class="card mb-3 <?= $editing ? '' : 'hidden' ?>">
      <div class="card-header">
        <h3><?= $editing ? 'Edit Studio' : 'Add Studio' ?></h3>
        <button class="btn-ghost btn-sm" onclick="document.getElementById('studio-form-wrap').classList.add('hidden')">✕</button>
      </div>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="save_studio" value="1">
        <input type="hidden" name="studio_id" value="<?= $editing['id'] ?? 0 ?>">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Country</label>
            <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($editing['country'] ?? '') ?>" placeholder="Japan">
          </div>
          <div class="form-group">
            <label class="form-label">Founded Year</label>
            <input type="number" name="founded_year" class="form-control" min="1900" max="2030" value="<?= $editing['founded_year'] ?? '' ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($editing['website'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Logo URL</label>
            <input type="url" name="logo_url" class="form-control" value="<?= htmlspecialchars($editing['logo_url'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn-primary">Save Studio</button>
      </form>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="data-table-wrap">
        <table class="data-table">
          <thead><tr><th>#</th><th>Name</th><th>Country</th><th>Founded</th><th>Anime</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if ($studios): foreach ($studios as $s): ?>
              <tr>
                <td class="text-muted"><?= $s['id'] ?></td>
                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                <td><?= htmlspecialchars($s['country'] ?? '—') ?></td>
                <td><?= $s['founded_year'] ?? '—' ?></td>
                <td><span class="anime-card-genre"><?= $s['anime_count'] ?></span></td>
                <td>
                  <div class="td-actions">
                    <a href="?edit=<?= $s['id'] ?>" class="btn-ghost btn-sm">Edit</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this studio?')">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="delete_studio" value="1">
                      <input type="hidden" name="studio_id" value="<?= $s['id'] ?>">
                      <button type="submit" class="btn-danger btn-sm">Del</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6" class="text-center text-muted" style="padding:2rem;">No studios yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
