<?php
// ============================================================
// ANIME VAULT - Admin: People
// File: /var/www/html/anime/admin/people.php
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['save_person'])) {
        $id          = (int)($_POST['person_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $nameJp      = trim($_POST['name_japanese'] ?? '');
        $birthDate   = $_POST['birth_date'] ?: null;
        $nationality = trim($_POST['nationality'] ?? '');
        $photoUrl    = trim($_POST['photo_url'] ?? '');
        $bio         = trim($_POST['bio'] ?? '');

        if (!$name) {
            $msg = 'error:Name is required.';
        } elseif ($id) {
            $pdo->prepare("UPDATE people SET name=?,name_japanese=?,birth_date=?,nationality=?,photo_url=?,bio=? WHERE id=?")
                ->execute([$name, $nameJp, $birthDate, $nationality, $photoUrl, $bio, $id]);
            $msg = 'success:Person updated.';
        } else {
            $pdo->prepare("INSERT INTO people (name,name_japanese,birth_date,nationality,photo_url,bio) VALUES (?,?,?,?,?,?)")
                ->execute([$name, $nameJp, $birthDate, $nationality, $photoUrl, $bio]);
            $msg = 'success:Person added.';
        }
    }

    if (isset($_POST['delete_person'])) {
        $pdo->prepare("DELETE FROM people WHERE id = ?")->execute([(int)$_POST['person_id']]);
        $msg = 'success:Person deleted.';
    }

    // Add cast entry
    if (isset($_POST['add_cast'])) {
        $animeId   = (int)$_POST['anime_id'];
        $personId  = (int)$_POST['person_id_cast'];
        $charName  = trim($_POST['character_name'] ?? '');
        $role      = $_POST['role'] ?? 'Main';
        $lang      = $_POST['language'] ?? 'Japanese';
        $pdo->prepare("INSERT IGNORE INTO cast_members (anime_id,person_id,character_name,role,language) VALUES (?,?,?,?,?)")
            ->execute([$animeId, $personId, $charName, $role, $lang]);
        $msg = 'success:Cast member added.';
    }

    // Add staff entry
    if (isset($_POST['add_staff'])) {
        $animeId  = (int)$_POST['anime_id_staff'];
        $personId = (int)$_POST['person_id_staff'];
        $role     = trim($_POST['staff_role'] ?? '');
        $pdo->prepare("INSERT IGNORE INTO staff (anime_id,person_id,role) VALUES (?,?,?)")
            ->execute([$animeId, $personId, $role]);
        $msg = 'success:Staff member added.';
    }
}

// Search
$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($q) { $where[] = 'p.name LIKE ?'; $params[] = '%'.$q.'%'; }
$wSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM people p WHERE $wSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare("SELECT * FROM people p WHERE $wSQL ORDER BY p.name LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$people = $stmt->fetchAll();

$editing = null;
if (isset($_GET['edit'])) {
    $stmt2 = $pdo->prepare("SELECT * FROM people WHERE id = ?");
    $stmt2->execute([(int)$_GET['edit']]);
    $editing = $stmt2->fetch();
}

$animeList = $pdo->query("SELECT id, title FROM anime ORDER BY title")->fetchAll();

$pageTitle = 'Manage People';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-header">
      <h2>People <span class="text-muted text-sm">(Voice Actors, Directors…)</span></h2>
      <button class="btn-primary" onclick="togglePanel('person-form')">+ Add Person</button>
    </div>

    <?php if ($msg): [$t,$m] = explode(':',$msg,2); ?>
      <div class="alert alert-<?=$t?>"><?=htmlspecialchars($m)?></div>
    <?php endif; ?>

    <!-- Person Form -->
    <div id="person-form" class="card mb-3 <?= $editing ? '' : 'hidden' ?>">
      <div class="card-header">
        <h3><?= $editing ? 'Edit Person' : 'Add Person' ?></h3>
        <button class="btn-ghost btn-sm" onclick="togglePanel('person-form')">✕</button>
      </div>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="save_person" value="1">
        <input type="hidden" name="person_id" value="<?= $editing['id'] ?? 0 ?>">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Japanese Name</label>
            <input type="text" name="name_japanese" class="form-control" value="<?= htmlspecialchars($editing['name_japanese'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Birth Date</label>
            <input type="date" name="birth_date" class="form-control" value="<?= $editing['birth_date'] ?? '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Nationality</label>
            <input type="text" name="nationality" class="form-control" value="<?= htmlspecialchars($editing['nationality'] ?? '') ?>" placeholder="Japanese">
          </div>
          <div class="form-group">
            <label class="form-label">Photo URL</label>
            <input type="url" name="photo_url" class="form-control" value="<?= htmlspecialchars($editing['photo_url'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Bio</label>
          <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($editing['bio'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn-primary">Save Person</button>
      </form>
    </div>

    <!-- Assign Cast -->
    <div class="card mb-3">
      <div class="card-header">
        <h3>Assign Cast Member to Anime</h3>
        <button class="btn-ghost btn-sm" onclick="togglePanel('cast-form')">Toggle</button>
      </div>
      <div id="cast-form" class="hidden">
        <form method="post" style="margin-top:1rem;">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="add_cast" value="1">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Anime</label>
              <select name="anime_id" class="form-control" required>
                <option value="">— Select Anime —</option>
                <?php foreach ($animeList as $a): ?>
                  <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Person</label>
              <select name="person_id_cast" class="form-control" required>
                <option value="">— Select Person —</option>
                <?php foreach ($people as $p): ?>
                  <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Character Name</label>
              <input type="text" name="character_name" class="form-control" placeholder="e.g. Edward Elric">
            </div>
            <div class="form-group">
              <label class="form-label">Role</label>
              <select name="role" class="form-control">
                <option>Main</option><option>Supporting</option><option>Background</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Language</label>
              <select name="language" class="form-control">
                <option>Japanese</option><option>English</option><option>Other</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn-primary btn-sm">Add to Cast</button>
        </form>
      </div>
    </div>

    <!-- Assign Staff -->
    <div class="card mb-3">
      <div class="card-header">
        <h3>Assign Staff to Anime</h3>
        <button class="btn-ghost btn-sm" onclick="togglePanel('staff-form')">Toggle</button>
      </div>
      <div id="staff-form" class="hidden">
        <form method="post" style="margin-top:1rem;">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="add_staff" value="1">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Anime</label>
              <select name="anime_id_staff" class="form-control" required>
                <option value="">— Select Anime —</option>
                <?php foreach ($animeList as $a): ?>
                  <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Person</label>
              <select name="person_id_staff" class="form-control" required>
                <option value="">— Select Person —</option>
                <?php foreach ($people as $p): ?>
                  <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Role / Position</label>
              <input type="text" name="staff_role" class="form-control" placeholder="e.g. Director, Composer" required>
            </div>
          </div>
          <button type="submit" class="btn-primary btn-sm">Add to Staff</button>
        </form>
      </div>
    </div>

    <!-- Search & Table -->
    <form method="get" style="display:flex;gap:0.5rem;margin-bottom:1.5rem;">
      <input type="search" name="q" class="form-control" placeholder="Search by name…" value="<?= htmlspecialchars($q) ?>" style="max-width:300px;">
      <button type="submit" class="btn-primary btn-sm">Search</button>
      <?php if ($q): ?><a href="/anime/admin/people.php" class="btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>

    <div class="card">
      <div class="data-table-wrap">
        <table class="data-table">
          <thead><tr><th>#</th><th>Name</th><th>Japanese Name</th><th>Nationality</th><th>Birth Date</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if ($people): foreach ($people as $p): ?>
              <tr>
                <td class="text-muted"><?= $p['id'] ?></td>
                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                <td><?= htmlspecialchars($p['name_japanese'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['nationality'] ?? '—') ?></td>
                <td><?= $p['birth_date'] ? date('M j, Y', strtotime($p['birth_date'])) : '—' ?></td>
                <td>
                  <div class="td-actions">
                    <a href="?edit=<?= $p['id'] ?>&<?= $q ? 'q='.urlencode($q) : '' ?>" class="btn-ghost btn-sm">Edit</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this person?')">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="delete_person" value="1">
                      <input type="hidden" name="person_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="btn-danger btn-sm">Del</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6" class="text-center text-muted" style="padding:2rem;">No people found.</td></tr>
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

<script>
function togglePanel(id) {
  document.getElementById(id).classList.toggle('hidden');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
