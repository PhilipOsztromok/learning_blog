<?php
// ============================================================
// ANIME VAULT - Show Detail Page
// File: /var/www/html/anime/show.php
// ============================================================

require_once __DIR__ . '/includes/auth.php';

$pdo  = getDB();
$user = currentUser();

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: /anime/'); exit; }

// Fetch show
$stmt = $pdo->prepare(
    "SELECT a.*, s.name AS studio_name, s.id AS studio_id2
     FROM anime a LEFT JOIN studios s ON a.studio_id = s.id
     WHERE a.slug = ?"
);
$stmt->execute([$slug]);
$show = $stmt->fetch();

if (!$show) { http_response_code(404); include __DIR__ . '/404.php'; exit; }

// Genres
$genres = $pdo->prepare(
    "SELECT g.name, g.slug FROM genres g JOIN anime_genres ag ON g.id = ag.genre_id WHERE ag.anime_id = ? ORDER BY g.name"
);
$genres->execute([$show['id']]);
$genres = $genres->fetchAll();

// Cast
$cast = $pdo->prepare(
    "SELECT p.name, p.photo_url, cm.character_name, cm.role, cm.language
     FROM cast_members cm JOIN people p ON cm.person_id = p.id
     WHERE cm.anime_id = ? ORDER BY cm.role, p.name"
);
$cast->execute([$show['id']]);
$cast = $cast->fetchAll();

// Staff
$staff = $pdo->prepare(
    "SELECT p.name, p.photo_url, st.role
     FROM staff st JOIN people p ON st.person_id = p.id
     WHERE st.anime_id = ? ORDER BY st.role"
);
$staff->execute([$show['id']]);
$staff = $staff->fetchAll();

// Reviews
$reviews = $pdo->prepare(
    "SELECT r.*, u.username, u.avatar_url FROM reviews r JOIN users u ON r.user_id = u.id
     WHERE r.anime_id = ? ORDER BY r.helpful_count DESC, r.created_at DESC LIMIT 20"
);
$reviews->execute([$show['id']]);
$reviews = $reviews->fetchAll();

// Average rating
$avgRating = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE anime_id = ?");
$avgRating->execute([$show['id']]);
$avgRating = round((float)$avgRating->fetchColumn(), 1);

// User's watchlist entry
$watchlistEntry = null;
if ($user) {
    $wl = $pdo->prepare("SELECT * FROM watchlist WHERE user_id = ? AND anime_id = ?");
    $wl->execute([$user['id'], $show['id']]);
    $watchlistEntry = $wl->fetch();
}

// User's review
$userReview = null;
if ($user) {
    $ur = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND anime_id = ?");
    $ur->execute([$user['id'], $show['id']]);
    $userReview = $ur->fetch();
}

// Handle POST: add/update review
$reviewMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit'])) {
    if (!$user) { header('Location: /anime/login.php'); exit; }
    verifyCsrf();
    $rating = (int)($_POST['rating'] ?? 0);
    $body   = trim($_POST['body'] ?? '');
    $title  = trim($_POST['review_title'] ?? '');
    $spoil  = isset($_POST['spoilers']) ? 1 : 0;

    if ($rating < 1 || $rating > 10)        $reviewMsg = 'error:Please choose a rating (1–10).';
    elseif (strlen($body) < 10)             $reviewMsg = 'error:Review must be at least 10 characters.';
    else {
        if ($userReview) {
            $pdo->prepare("UPDATE reviews SET rating=?,title=?,body=?,contains_spoilers=?,updated_at=NOW() WHERE id=?")
                ->execute([$rating, $title, $body, $spoil, $userReview['id']]);
        } else {
            $pdo->prepare("INSERT INTO reviews (anime_id,user_id,rating,title,body,contains_spoilers) VALUES (?,?,?,?,?,?)")
                ->execute([$show['id'], $user['id'], $rating, $title, $body, $spoil]);
        }
        // Recalculate average
        $pdo->prepare("UPDATE anime SET rating = (SELECT AVG(rating) FROM reviews WHERE anime_id=?) WHERE id=?")
            ->execute([$show['id'], $show['id']]);
        header("Location: /anime/show.php?slug=$slug#reviews");
        exit;
    }
}

// Handle POST: watchlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['watchlist_submit'])) {
    if (!$user) { header('Location: /anime/login.php'); exit; }
    verifyCsrf();
    $wlStatus   = $_POST['wl_status'] ?? 'plan_to_watch';
    $wlProgress = (int)($_POST['wl_progress'] ?? 0);
    $wlScore    = ($_POST['wl_score'] !== '') ? (int)$_POST['wl_score'] : null;
    if ($watchlistEntry) {
        $pdo->prepare("UPDATE watchlist SET status=?,progress=?,score=? WHERE user_id=? AND anime_id=?")
            ->execute([$wlStatus, $wlProgress, $wlScore, $user['id'], $show['id']]);
    } else {
        $pdo->prepare("INSERT INTO watchlist (user_id,anime_id,status,progress,score) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $show['id'], $wlStatus, $wlProgress, $wlScore]);
    }
    header("Location: /anime/show.php?slug=$slug");
    exit;
}

$pageTitle = $show['title'];
$metaDesc  = substr(strip_tags($show['synopsis'] ?? ''), 0, 155);
include __DIR__ . '/includes/header.php';

$starsHtml = function(float $r): string {
    $full = floor($r / 2); $out = '';
    for ($i = 0; $i < 5; $i++) $out .= $i < $full ? '★' : '☆';
    return $out;
};
?>

<!-- Hero -->
<div class="anime-detail-hero">
  <?php if ($show['banner_url']): ?>
  <div style="position:absolute;inset:0;background:url('<?= htmlspecialchars($show['banner_url']) ?>')center/cover no-repeat;opacity:0.12;"></div>
  <?php endif; ?>

  <div class="container">
    <div class="anime-detail-layout">
      <div>
        <?php if ($show['poster_url']): ?>
          <img src="<?= htmlspecialchars($show['poster_url']) ?>" alt="<?= htmlspecialchars($show['title']) ?>" class="anime-detail-poster">
        <?php else: ?>
          <div class="anime-detail-poster flex-center" style="background:var(--bg-card);font-size:4rem;">⛩</div>
        <?php endif; ?>
      </div>

      <div class="anime-detail-info">
        <h1><?= htmlspecialchars($show['title']) ?></h1>
        <?php if ($show['title_japanese']): ?>
          <p class="anime-detail-tagline"><?= htmlspecialchars($show['title_japanese']) ?></p>
        <?php endif; ?>

        <!-- Genres -->
        <div class="anime-detail-genres">
          <?php foreach ($genres as $g): ?>
            <a href="/anime/browse.php?genre=<?= htmlspecialchars($g['slug']) ?>" class="genre-tag"><?= htmlspecialchars($g['name']) ?></a>
          <?php endforeach; ?>
          <span class="status-badge status-<?= strtolower($show['status']) ?>"><?= htmlspecialchars($show['status']) ?></span>
        </div>

        <!-- Rating -->
        <div class="rating-display mb-2">
          <span class="stars"><?= $starsHtml($avgRating ?: (float)($show['rating'] ?? 0)) ?></span>
          <span style="font-size:1.4rem;font-weight:700;font-family:var(--font-display);"><?= $avgRating ?: ($show['rating'] ?? '—') ?></span>
          <span class="text-muted text-sm">/ 10 &nbsp;(<?= count($reviews) ?> review<?= count($reviews) !== 1 ? 's' : '' ?>)</span>
        </div>

        <!-- Meta grid -->
        <div class="anime-meta-grid">
          <?php if ($show['studio_name']): ?>
          <div class="meta-item">
            <span class="meta-label">Studio</span>
            <span class="meta-value"><?= htmlspecialchars($show['studio_name']) ?></span>
          </div>
          <?php endif; ?>
          <div class="meta-item">
            <span class="meta-label">Type</span>
            <span class="meta-value"><?= htmlspecialchars($show['type']) ?></span>
          </div>
          <?php if ($show['episodes']): ?>
          <div class="meta-item">
            <span class="meta-label">Episodes</span>
            <span class="meta-value"><?= $show['episodes'] ?></span>
          </div>
          <?php endif; ?>
          <?php if ($show['duration_min']): ?>
          <div class="meta-item">
            <span class="meta-label">Duration</span>
            <span class="meta-value"><?= $show['duration_min'] ?> min</span>
          </div>
          <?php endif; ?>
          <?php if ($show['premiered_season'] && $show['premiered_year']): ?>
          <div class="meta-item">
            <span class="meta-label">Premiered</span>
            <span class="meta-value"><?= htmlspecialchars($show['premiered_season']) ?> <?= $show['premiered_year'] ?></span>
          </div>
          <?php endif; ?>
          <?php if ($show['source']): ?>
          <div class="meta-item">
            <span class="meta-label">Source</span>
            <span class="meta-value"><?= htmlspecialchars($show['source']) ?></span>
          </div>
          <?php endif; ?>
          <?php if ($show['age_rating']): ?>
          <div class="meta-item">
            <span class="meta-label">Rating</span>
            <span class="meta-value"><?= htmlspecialchars($show['age_rating']) ?></span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:0.5rem;">
          <?php if ($show['trailer_url']): ?>
            <a href="<?= htmlspecialchars($show['trailer_url']) ?>" target="_blank" rel="noopener" class="btn-secondary btn-sm">▶ Trailer</a>
          <?php endif; ?>
          <?php if ($user): ?>
            <button class="btn-primary btn-sm" onclick="openWatchlistModal()">
              <?= $watchlistEntry ? '✏ Edit List' : '+ Add to List' ?>
            </button>
          <?php else: ?>
            <a href="/anime/login.php" class="btn-primary btn-sm">+ Add to List</a>
          <?php endif; ?>
          <?php if (isAdmin()): ?>
            <a href="/anime/admin/edit_anime.php?id=<?= $show['id'] ?>" class="btn-ghost btn-sm">✏ Edit</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Tabs -->
<div class="container" style="padding-top:2rem;">
  <div class="tabs">
    <button class="tab-btn active" onclick="showTab('overview',this)">Overview</button>
    <button class="tab-btn" onclick="showTab('cast',this)">Cast &amp; Staff</button>
    <button class="tab-btn" onclick="showTab('reviews',this)" id="reviews-tab">
      Reviews <span style="font-size:0.75rem;opacity:0.7;">(<?= count($reviews) ?>)</span>
    </button>
  </div>

  <!-- Overview -->
  <div id="tab-overview" class="tab-panel active">
    <?php if ($show['synopsis']): ?>
      <div class="card mb-2">
        <h3 style="margin-bottom:0.75rem;">Synopsis</h3>
        <p style="color:var(--text-secondary);line-height:1.8;"><?= nl2br(htmlspecialchars($show['synopsis'])) ?></p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Cast & Staff -->
  <div id="tab-cast" class="tab-panel">
    <?php if ($cast): ?>
      <h3 style="margin-bottom:1rem;">Voice Cast</h3>
      <div class="cast-grid mb-3">
        <?php foreach ($cast as $c): ?>
          <div class="cast-card">
            <div class="cast-avatar">
              <?php if ($c['photo_url']): ?>
                <img src="<?= htmlspecialchars($c['photo_url']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                👤
              <?php endif; ?>
            </div>
            <div class="cast-name"><?= htmlspecialchars($c['name']) ?></div>
            <div class="cast-role"><?= htmlspecialchars($c['character_name'] ?? $c['role']) ?></div>
            <?php if ($c['language'] !== 'Japanese'): ?>
              <div class="cast-role" style="color:var(--neon-blue)"><?= htmlspecialchars($c['language']) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($staff): ?>
      <h3 style="margin-bottom:1rem;">Staff</h3>
      <div class="cast-grid">
        <?php foreach ($staff as $s): ?>
          <div class="cast-card">
            <div class="cast-avatar">
              <?php if ($s['photo_url']): ?>
                <img src="<?= htmlspecialchars($s['photo_url']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                🎬
              <?php endif; ?>
            </div>
            <div class="cast-name"><?= htmlspecialchars($s['name']) ?></div>
            <div class="cast-role"><?= htmlspecialchars($s['role']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (!$cast && !$staff): ?>
      <p class="text-muted">No cast or staff information yet.</p>
    <?php endif; ?>
  </div>

  <!-- Reviews -->
  <div id="tab-reviews" class="tab-panel">
    <?php
    [$msgType, $msgText] = $reviewMsg ? explode(':', $reviewMsg, 2) : ['',''];
    if ($msgText): ?>
      <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
    <?php endif; ?>

    <!-- Write review -->
    <?php if ($user && !$userReview): ?>
    <div class="review-form mb-3" id="reviews">
      <h3>Write a Review</h3>
      <form method="post">
        <input type="hidden" name="review_submit" value="1">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
          <label class="form-label">Your Rating (1–10)</label>
          <div class="star-rating-input">
            <?php for ($i = 10; $i >= 1; $i--): ?>
              <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>">
              <label for="star<?= $i ?>">★</label>
            <?php endfor; ?>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Review Title (optional)</label>
          <input type="text" name="review_title" class="form-control" placeholder="Sum it up…">
        </div>
        <div class="form-group">
          <label class="form-label">Review</label>
          <textarea name="body" class="form-control" rows="4" placeholder="What did you think?" required></textarea>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:0.5rem;">
          <input type="checkbox" name="spoilers" id="spoilers">
          <label for="spoilers" style="font-size:0.85rem;color:var(--text-secondary);">Contains spoilers</label>
        </div>
        <button type="submit" class="btn-primary">Submit Review</button>
      </form>
    </div>
    <?php elseif (!$user): ?>
      <div class="alert alert-info"><a href="/anime/login.php">Log in</a> to write a review.</div>
    <?php endif; ?>

    <!-- Existing reviews -->
    <?php if ($reviews): ?>
      <?php foreach ($reviews as $r):
        $initials = strtoupper(substr($r['username'], 0, 1));
        $stars = str_repeat('★', (int)round($r['rating'] / 2)) . str_repeat('☆', 5 - (int)round($r['rating'] / 2));
      ?>
      <div class="review-card">
        <div class="review-header">
          <div class="reviewer-info">
            <div class="reviewer-avatar"><?= $initials ?></div>
            <div>
              <div class="reviewer-name"><?= htmlspecialchars($r['username']) ?></div>
              <div class="review-date"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="color:var(--neon-gold);letter-spacing:0.05em;"><?= $stars ?></div>
            <div style="font-weight:700;font-size:0.9rem;"><?= $r['rating'] ?>/10</div>
          </div>
        </div>
        <?php if ($r['title']): ?>
          <h4 style="margin-bottom:0.5rem;"><?= htmlspecialchars($r['title']) ?></h4>
        <?php endif; ?>
        <?php if ($r['contains_spoilers']): ?>
          <div class="alert alert-info" style="margin-bottom:0.5rem;padding:0.3rem 0.6rem;font-size:0.8rem;">⚠ Contains spoilers</div>
        <?php endif; ?>
        <p class="review-text"><?= nl2br(htmlspecialchars($r['body'])) ?></p>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted">No reviews yet. Be the first!</p>
    <?php endif; ?>
  </div>
</div>

<!-- Watchlist Modal -->
<?php if ($user): ?>
<div class="modal-overlay" id="watchlist-modal">
  <div class="modal">
    <div class="modal-header">
      <h3>Add to Watchlist</h3>
      <button class="modal-close" onclick="closeWatchlistModal()">✕</button>
    </div>
    <form method="post">
      <input type="hidden" name="watchlist_submit" value="1">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="wl_status" class="form-control">
          <?php foreach (['watching'=>'Watching','completed'=>'Completed','plan_to_watch'=>'Plan to Watch','dropped'=>'Dropped','on_hold'=>'On Hold'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= ($watchlistEntry['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Episodes Watched</label>
        <input type="number" name="wl_progress" class="form-control" min="0" max="<?= $show['episodes'] ?? 9999 ?>" value="<?= $watchlistEntry['progress'] ?? 0 ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Your Score (1–10)</label>
        <select name="wl_score" class="form-control">
          <option value="">—</option>
          <?php for ($i = 10; $i >= 1; $i--): ?>
            <option value="<?= $i ?>" <?= ($watchlistEntry['score'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:0.5rem;">
        <button type="button" class="btn-ghost" onclick="closeWatchlistModal()">Cancel</button>
        <button type="submit" class="btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<script>
function openWatchlistModal()  { document.getElementById('watchlist-modal').classList.add('open'); }
function closeWatchlistModal() { document.getElementById('watchlist-modal').classList.remove('open'); }
document.getElementById('watchlist-modal').addEventListener('click', function(e){ if(e.target===this) closeWatchlistModal(); });
</script>
<?php endif; ?>

<script>
function showTab(id, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  btn.classList.add('active');
}
// Auto-switch to reviews if URL has #reviews
if (location.hash === '#reviews') {
  showTab('reviews', document.getElementById('reviews-tab'));
}
</script>

<?php
$paddingBottom = '<div style="padding-bottom:3rem;"></div>';
echo $paddingBottom;
include __DIR__ . '/includes/footer.php';
?>
