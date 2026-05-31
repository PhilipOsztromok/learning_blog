<?php
// ============================================================
// ANIME VAULT - Admin: Users
// File: /var/www/html/anime/admin/users.php
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo     = getDB();
$me      = currentUser();
$msg     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['toggle_role'])) {
        $uid = (int)$_POST['user_id'];
        if ($uid !== $me['id']) {
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
            $stmt->execute([$uid]);
            $row = $stmt->fetch();
            $newRole = $row['role'] === 'admin' ? 'user' : 'admin';
            $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$newRole, $uid]);
            $msg = 'success:User role updated.';
        }
    }

    if (isset($_POST['toggle_active'])) {
        $uid = (int)$_POST['user_id'];
        if ($uid !== $me['id']) {
            $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id=?")->execute([$uid]);
            $msg = 'success:User status toggled.';
        }
    }

    if (isset($_POST['reset_password'])) {
        $uid  = (int)$_POST['user_id'];
        $pass = trim($_POST['new_password'] ?? '');
        if (strlen($pass) < 8) {
            $msg = 'error:Password must be at least 8 characters.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
            $msg = 'success:Password reset successfully.';
        }
    }
}

$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($q) { $where[] = '(username LIKE ? OR email LIKE ?)'; $params[] = '%'.$q.'%'; $params[] = '%'.$q.'%'; }
$wSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $wSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->prepare(
    "SELECT u.*, 
            (SELECT COUNT(*) FROM reviews WHERE user_id = u.id) AS review_count,
            (SELECT COUNT(*) FROM watchlist WHERE user_id = u.id) AS watchlist_count
     FROM users u WHERE $wSQL ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="admin-content">
    <div class="admin-header">
      <h2>Users (<?= number_format($total) ?>)</h2>
    </div>

    <?php if ($msg): [$t,$m] = explode(':',$msg,2); ?>
      <div class="alert alert-<?=$t?>"><?=htmlspecialchars($m)?></div>
    <?php endif; ?>

    <form method="get" style="display:flex;gap:0.5rem;margin-bottom:1.5rem;">
      <input type="search" name="q" class="form-control" placeholder="Search username or email…" value="<?= htmlspecialchars($q) ?>" style="max-width:320px;">
      <button type="submit" class="btn-primary btn-sm">Search</button>
      <?php if ($q): ?><a href="/anime/admin/users.php" class="btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>

    <div class="card">
      <div class="data-table-wrap">
        <table class="data-table">
          <thead>
            <tr><th>#</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Reviews</th><th>Watchlist</th><th>Joined</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if ($users): foreach ($users as $u): ?>
              <tr>
                <td class="text-muted"><?= $u['id'] ?></td>
                <td>
                  <strong><?= htmlspecialchars($u['username']) ?></strong>
                  <?php if ($u['id'] === $me['id']): ?>
                    <span class="anime-card-genre" style="font-size:0.65rem;">You</span>
                  <?php endif; ?>
                </td>
                <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                <td>
                  <span class="status-badge <?= $u['role'] === 'admin' ? 'status-upcoming' : 'status-completed' ?>">
                    <?= $u['role'] ?>
                  </span>
                </td>
                <td>
                  <span class="status-badge <?= $u['is_active'] ? 'status-airing' : 'status-hiatus' ?>">
                    <?= $u['is_active'] ? 'Active' : 'Disabled' ?>
                  </span>
                </td>
                <td><?= $u['review_count'] ?></td>
                <td><?= $u['watchlist_count'] ?></td>
                <td class="text-muted text-sm"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <?php if ($u['id'] !== $me['id']): ?>
                    <div class="td-actions" style="flex-wrap:wrap;gap:0.25rem;">
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="toggle_role" value="1">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button class="btn-ghost btn-sm"><?= $u['role'] === 'admin' ? 'Demote' : 'Promote' ?></button>
                      </form>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="toggle_active" value="1">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button class="btn-<?= $u['is_active'] ? 'danger' : 'ghost' ?> btn-sm">
                          <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                        </button>
                      </form>
                      <button class="btn-ghost btn-sm" onclick="showResetForm(<?= $u['id'] ?>)">Reset PW</button>
                    </div>
                    <div id="reset-form-<?= $u['id'] ?>" class="hidden" style="margin-top:0.5rem;">
                      <form method="post" style="display:flex;gap:0.25rem;align-items:flex-end;">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="reset_password" value="1">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="password" name="new_password" class="form-control" placeholder="New password" style="width:160px;" minlength="8" required>
                        <button class="btn-primary btn-sm">Set</button>
                      </form>
                    </div>
                  <?php else: ?>
                    <span class="text-muted text-sm">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="9" class="text-center text-muted" style="padding:2rem;">No users found.</td></tr>
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
function showResetForm(id) {
  document.querySelectorAll('[id^="reset-form-"]').forEach(el => {
    if (el.id !== 'reset-form-' + id) el.classList.add('hidden');
  });
  document.getElementById('reset-form-' + id).classList.toggle('hidden');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
