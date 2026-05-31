<?php
// ============================================================
// ANIME VAULT - Admin: Add / Edit Anime
// File: /var/www/html/anime/admin/edit_anime.php
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo    = getDB();
$user   = currentUser();
$animeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit  = $animeId > 0;

// Load existing record
$show = null;
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
    $stmt->execute([$animeId]);
    $show = $stmt->fetch();
    if (!$show) { header('Location: /anime/admin/anime.php'); exit; }
}

$studios = $pdo->query("SELECT id, name FROM studios ORDER BY name")->fetchAll();
$genres  = $pdo->query("SELECT id, name, slug FROM genres ORDER BY name")->fetchAll();

// Current genres for this anime
$currentGenres = [];
if ($isEdit) {
    $gstmt = $pdo->prepare("SELECT genre_id FROM anime_genres WHERE anime_id = ?");
    $gstmt->execute([$animeId]);
    $currentGenres = array_column($gstmt->fetchAll(), 'genre_id');
}

$msg = '';

// ── Handle SAVE ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_anime'])) {
    verifyCsrf();

    $title      = trim($_POST['title'] ?? '');
    $titleJp    = trim($_POST['title_japanese'] ?? '');
    $titleEn    = trim($_POST['title_english'] ?? '');
    $synopsis   = trim($_POST['synopsis'] ?? '');
    $studioId   = $_POST['studio_id'] ? (int)$_POST['studio_id'] : null;
    $type       = $_POST['type'] ?? 'TV';
    $status     = $_POST['status'] ?? 'Completed';
    $episodes   = $_POST['episodes'] ? (int)$_POST['episodes'] : null;
    $duration   = $_POST['duration_min'] ? (int)$_POST['duration_min'] : null;
    $season     = $_POST['premiered_season'] ?: null;
    $year       = $_POST['premiered_year'] ? (int)$_POST['premiered_year'] : null;
    $finYear    = $_POST['finished_year'] ? (int)$_POST['finished_year'] : null;
    $ageRating  = trim($_POST['age_rating'] ?? '');
    $source     = trim($_POST['source'] ?? '');
    $posterUrl  = trim($_POST['poster_url'] ?? '');
    $bannerUrl  = trim($_POST['banner_url'] ?? '');
    $trailerUrl = trim($_POST['trailer_url'] ?? '');
    $imdbId     = trim($_POST['imdb_id'] ?? '');
    $selGenres  = array_map('intval', (array)($_POST['genres'] ?? []));

    if (!$title) { $msg = 'error:Title is required.'; }
    else {
        // Generate slug
        $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $baseSlug = trim($baseSlug, '-');
        $slug     = $baseSlug;
        // Ensure unique slug
        $n = 1;
        while (true) {
            $check = $pdo->prepare("SELECT id FROM anime WHERE slug = ? AND id != ?");
            $check->execute([$slug, $animeId]);
            if (!$check->fetch()) break;
            $slug = $baseSlug . '-' . (++$n);
        }

        if ($isEdit) {
            $pdo->prepare(
                "UPDATE anime SET title=?,title_japanese=?,title_english=?,slug=?,synopsis=?,studio_id=?,
                 type=?,status=?,episodes=?,duration_min=?,premiered_season=?,premiered_year=?,finished_year=?,
                 age_rating=?,source=?,poster_url=?,banner_url=?,trailer_url=?,imdb_id=?,updated_at=NOW()
                 WHERE id=?"
            )->execute([$title,$titleJp,$titleEn,$slug,$synopsis,$studioId,$type,$status,$episodes,$duration,
                        $season,$year,$finYear,$ageRating,$source,$posterUrl,$bannerUrl,$trailerUrl,$imdbId,$animeId]);
        } else {
            $pdo->prepare(
                "INSERT INTO anime (title,title_japanese,title_english,slug,synopsis,studio_id,type,status,
                 episodes,duration_min,premiered_season,premiered_year,finished_year,age_rating,source,
                 poster_url,banner_url,trailer_url,imdb_id,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([$title,$titleJp,$titleEn,$slug,$synopsis,$studioId,$type,$status,$episodes,$duration,
                        $season,$year,$finYear,$ageRating,$source,$posterUrl,$bannerUrl,$trailerUrl,$imdbId,$user['id']]);
            $animeId = (int)$pdo->lastInsertId();
            $isEdit  = true;
        }

        // Update genres
        $pdo->prepare("DELETE FROM anime_genres WHERE anime_id = ?")->execute([$animeId]);
        if ($selGenres) {
            $ins = $pdo->prepare("INSERT IGNORE INTO anime_genres (anime_id, genre_id) VALUES (?, ?)");
            foreach ($selGenres as $gid) $ins->execute([$animeId, $gid]);
        }

        // Reload
        $stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
        $stmt->execute([$animeId]);
        $show = $stmt->fetch();
        $gstmt = $pdo->prepare("SELECT genre_id FROM anime_genres WHERE anime_id = ?");
        $gstmt->execute([$animeId]);
        $currentGenres = array_column($gstmt->fetchAll(), 'genre_id');

        $msg = 'success:Anime saved successfully!';
    }
}

// ── IMDB / JIKAN (MyAnimeList) Scraper ───────────────────────
// We use the free Jikan API v4 (jikan.moe) – no key needed
// Called via AJAX from the page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'scrape') {
    header('Content-Type: application/json');
    verifyCsrf();

    $titleToSearch = trim($_POST['title'] ?? '');
    if (!$titleToSearch) { echo json_encode(['error' => 'No title provided']); exit; }

    $encoded = urlencode($titleToSearch);
    $apiUrl  = "https://api.jikan.moe/v4/anime?q={$encoded}&limit=1&sfw";

    $ctx = stream_context_create(['http' => [
        'timeout'       => 10,
        'method'        => 'GET',
        'header'        => "User-Agent: AnimeVault/1.0 (osztromok.com)\r\n",
        'ignore_errors' => true,
    ]]);

    $raw = @file_get_contents($apiUrl, false, $ctx);
    if ($raw === false) { echo json_encode(['error' => 'Failed to reach Jikan API']); exit; }

    $data = json_decode($raw, true);
    if (empty($data['data'][0])) { echo json_encode(['error' => 'No results found']); exit; }

    $a = $data['data'][0];

    // Extract English title
    $titleEn = '';
    foreach (($a['titles'] ?? []) as $t) {
        if ($t['type'] === 'English') { $titleEn = $t['title']; break; }
    }

    $result = [
        'title'           => $a['title'] ?? '',
        'title_japanese'  => $a['title_japanese'] ?? '',
        'title_english'   => $titleEn,
        'synopsis'        => $a['synopsis'] ?? '',
        'type'            => $a['type'] ?? '',
        'episodes'        => $a['episodes'] ?? '',
        'status'          => match($a['status'] ?? '') {
                                'Currently Airing' => 'Airing',
                                'Finished Airing'  => 'Completed',
                                'Not yet aired'    => 'Upcoming',
                                default            => 'Completed',
                             },
        'premiered_season'=> ucfirst(strtolower($a['season'] ?? '')),
        'premiered_year'  => $a['year'] ?? '',
        'duration_min'    => (int) preg_replace('/[^0-9]/', '', $a['duration'] ?? ''),
        'age_rating'      => $a['rating'] ?? '',
        'source'          => $a['source'] ?? '',
        'poster_url'      => $a['images']['jpg']['large_image_url'] ?? $a['images']['jpg']['image_url'] ?? '',
        'trailer_url'     => $a['trailer']['url'] ?? '',
        'mal_id'          => $a['mal_id'] ?? '',
        'score'           => $a['score'] ?? '',
        'genres'          => array_column($a['genres'] ?? [], 'name'),
        'studios'         => array_column($a['studios'] ?? [], 'name'),
    ];

    echo json_encode(['ok' => true, 'data' => $result]);
    exit;
}

$pageTitle = $isEdit ? 'Edit: ' . ($show['title'] ?? '') : 'Add Anime';
include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>

  <div class="admin-content">
    <div class="admin-header">
      <h2><?= $isEdit ? 'Edit Anime' : 'Add New Anime' ?></h2>
      <div style="display:flex;gap:0.5rem;">
        <?php if ($isEdit): ?>
          <a href="/anime/show.php?slug=<?= htmlspecialchars($show['slug'] ?? '') ?>" class="btn-ghost btn-sm" target="_blank">View Page</a>
        <?php endif; ?>
        <a href="/anime/admin/anime.php" class="btn-ghost btn-sm">← Back</a>
      </div>
    </div>

    <?php if ($msg): [$msgType, $msgText] = explode(':', $msg, 2); ?>
      <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msgText) ?></div>
    <?php endif; ?>

    <!-- ── JIKAN SCRAPER PANEL ── -->
    <div class="scraper-panel mb-3">
      <h4>🔍 Auto-fill from MyAnimeList (Jikan API)</h4>
      <p class="text-muted text-sm mb-2">Enter the anime title and click Fetch to automatically populate fields from the free Jikan/MyAnimeList API. You can then review and save.</p>
      <div style="display:flex;gap:0.75rem;align-items:flex-end;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
          <label class="form-label">Anime Title to Search</label>
          <input type="text" id="scrape-title" class="form-control"
                 value="<?= htmlspecialchars($show['title'] ?? '') ?>"
                 placeholder="e.g. Fullmetal Alchemist Brotherhood">
        </div>
        <button type="button" class="btn-secondary" onclick="doScrape()" id="scrape-btn">⬇ Fetch Data</button>
      </div>
      <div id="scrape-status" style="margin-top:0.75rem;"></div>
      <div id="scrape-result" class="scrape-result hidden"></div>
      <div id="scrape-apply-bar" class="hidden" style="margin-top:0.75rem;display:flex;gap:0.5rem;">
        <button type="button" class="btn-primary btn-sm" onclick="applyScrapedData()">✓ Apply to Form</button>
        <button type="button" class="btn-ghost btn-sm" onclick="clearScrapeResult()">Dismiss</button>
      </div>
    </div>

    <!-- ── MAIN FORM ── -->
    <form method="post" id="anime-form">
      <input type="hidden" name="save_anime" value="1">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="card mb-2">
        <div class="card-header"><h3>Core Information</h3></div>

        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" id="f-title" class="form-control" required
                 value="<?= htmlspecialchars($show['title'] ?? '') ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Japanese Title</label>
            <input type="text" name="title_japanese" id="f-title_japanese" class="form-control"
                   value="<?= htmlspecialchars($show['title_japanese'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">English Title</label>
            <input type="text" name="title_english" id="f-title_english" class="form-control"
                   value="<?= htmlspecialchars($show['title_english'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Synopsis</label>
          <textarea name="synopsis" id="f-synopsis" class="form-control" rows="5"><?= htmlspecialchars($show['synopsis'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="card mb-2">
        <div class="card-header"><h3>Details</h3></div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" id="f-type" class="form-control">
              <?php foreach (['TV','Movie','OVA','ONA','Special','Music'] as $t): ?>
                <option value="<?= $t ?>" <?= ($show['type'] ?? 'TV') === $t ? 'selected' : '' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="f-status" class="form-control">
              <?php foreach (['Airing','Completed','Upcoming','Hiatus'] as $s): ?>
                <option value="<?= $s ?>" <?= ($show['status'] ?? 'Completed') === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Studio</label>
            <select name="studio_id" class="form-control">
              <option value="">— None —</option>
              <?php foreach ($studios as $st): ?>
                <option value="<?= $st['id'] ?>" <?= ($show['studio_id'] ?? '') == $st['id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Episodes</label>
            <input type="number" name="episodes" id="f-episodes" class="form-control" min="1"
                   value="<?= $show['episodes'] ?? '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Duration (min)</label>
            <input type="number" name="duration_min" id="f-duration_min" class="form-control" min="1"
                   value="<?= $show['duration_min'] ?? '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Age Rating</label>
            <input type="text" name="age_rating" id="f-age_rating" class="form-control"
                   placeholder="PG-13, R17+…" value="<?= htmlspecialchars($show['age_rating'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Season</label>
            <select name="premiered_season" id="f-premiered_season" class="form-control">
              <option value="">—</option>
              <?php foreach (['Winter','Spring','Summer','Fall'] as $ss): ?>
                <option value="<?= $ss ?>" <?= ($show['premiered_season'] ?? '') === $ss ? 'selected' : '' ?>><?= $ss ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Premiered Year</label>
            <input type="number" name="premiered_year" id="f-premiered_year" class="form-control" min="1960" max="2035"
                   value="<?= $show['premiered_year'] ?? '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Finished Year</label>
            <input type="number" name="finished_year" class="form-control" min="1960" max="2035"
                   value="<?= $show['finished_year'] ?? '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Source Material</label>
            <input type="text" name="source" id="f-source" class="form-control"
                   placeholder="Manga, Light Novel…" value="<?= htmlspecialchars($show['source'] ?? '') ?>">
          </div>
        </div>
      </div>

      <div class="card mb-2">
        <div class="card-header"><h3>Genres</h3></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:0.5rem;" id="genres-checkboxes">
          <?php foreach ($genres as $g): ?>
            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;cursor:pointer;">
              <input type="checkbox" name="genres[]" value="<?= $g['id'] ?>"
                     <?= in_array($g['id'], $currentGenres) ? 'checked' : '' ?>>
              <?= htmlspecialchars($g['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card mb-2">
        <div class="card-header"><h3>Media &amp; Links</h3></div>
        <div class="form-group">
          <label class="form-label">Poster URL</label>
          <input type="url" name="poster_url" id="f-poster_url" class="form-control"
                 value="<?= htmlspecialchars($show['poster_url'] ?? '') ?>" placeholder="https://…">
        </div>
        <div class="form-group">
          <label class="form-label">Banner / Background URL</label>
          <input type="url" name="banner_url" class="form-control"
                 value="<?= htmlspecialchars($show['banner_url'] ?? '') ?>" placeholder="https://…">
        </div>
        <div class="form-group">
          <label class="form-label">Trailer URL (YouTube etc.)</label>
          <input type="url" name="trailer_url" id="f-trailer_url" class="form-control"
                 value="<?= htmlspecialchars($show['trailer_url'] ?? '') ?>" placeholder="https://…">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">IMDB ID <span class="text-muted" style="text-transform:none;">(e.g. tt0421463)</span></label>
            <input type="text" name="imdb_id" class="form-control"
                   value="<?= htmlspecialchars($show['imdb_id'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">MyAnimeList ID</label>
            <input type="number" name="mal_id" class="form-control"
                   value="<?= $show['mal_id'] ?? '' ?>">
          </div>
        </div>
      </div>

      <div style="display:flex;gap:0.75rem;padding-top:0.5rem;">
        <button type="submit" class="btn-primary btn-lg">💾 Save Anime</button>
        <a href="/anime/admin/anime.php" class="btn-ghost btn-lg">Cancel</a>
        <?php if ($isEdit): ?>
          <a href="/anime/admin/delete_anime.php?id=<?= $animeId ?>&csrf=<?= csrfToken() ?>"
             class="btn-danger btn-lg"
             onclick="return confirm('Delete this anime? This cannot be undone.')">🗑 Delete</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<script>
// ── Jikan scraper ────────────────────────────────────────────
let scrapedData = null;

async function doScrape() {
  const title = document.getElementById('scrape-title').value.trim();
  if (!title) { alert('Please enter a title to search.'); return; }

  const btn    = document.getElementById('scrape-btn');
  const status = document.getElementById('scrape-status');
  btn.disabled = true;
  btn.textContent = '⏳ Fetching…';
  status.innerHTML = '<span class="text-muted text-sm">Contacting Jikan API…</span>';

  try {
    const fd = new FormData();
    fd.append('action', 'scrape');
    fd.append('title', title);
    fd.append('csrf_token', '<?= csrfToken() ?>');

    const res  = await fetch(window.location.href, { method: 'POST', body: fd });
    const json = await res.json();

    if (json.error) {
      status.innerHTML = '<div class="alert alert-error">' + json.error + '</div>';
    } else {
      scrapedData = json.data;
      renderScrapeResult(json.data);
      status.innerHTML = '<div class="alert alert-success">✓ Data found! Review below, then click Apply to Form.</div>';
    }
  } catch (e) {
    status.innerHTML = '<div class="alert alert-error">Network error: ' + e.message + '</div>';
  }

  btn.disabled = false;
  btn.textContent = '⬇ Fetch Data';
}

function renderScrapeResult(d) {
  const r = document.getElementById('scrape-result');
  r.classList.remove('hidden');
  document.getElementById('scrape-apply-bar').classList.remove('hidden');
  document.getElementById('scrape-apply-bar').style.display = 'flex';

  const rows = [
    ['Title', d.title],
    ['Japanese Title', d.title_japanese],
    ['English Title', d.title_english],
    ['Type', d.type],
    ['Status', d.status],
    ['Episodes', d.episodes],
    ['Duration', d.duration_min ? d.duration_min + ' min' : ''],
    ['Season', d.premiered_season + ' ' + (d.premiered_year || '')],
    ['Source', d.source],
    ['Age Rating', d.age_rating],
    ['Score', d.score],
    ['Genres', (d.genres || []).join(', ')],
    ['Studios', (d.studios || []).join(', ')],
    ['Poster URL', d.poster_url ? '<a href="' + d.poster_url + '" target="_blank">Preview</a>' : ''],
  ].filter(([,v]) => v);

  r.innerHTML = rows.map(([k,v]) =>
    `<div class="scrape-result-item"><span class="scrape-key">${k}</span><span class="scrape-val">${v}</span></div>`
  ).join('');
}

function applyScrapedData() {
  if (!scrapedData) return;
  const d = scrapedData;
  const set = (id, val) => { const el = document.getElementById(id); if (el && val) el.value = val; };

  set('f-title', d.title);
  set('f-title_japanese', d.title_japanese);
  set('f-title_english', d.title_english);
  set('f-synopsis', d.synopsis);
  set('f-type', d.type);
  set('f-status', d.status);
  set('f-episodes', d.episodes);
  set('f-duration_min', d.duration_min);
  set('f-premiered_season', d.premiered_season);
  set('f-premiered_year', d.premiered_year);
  set('f-source', d.source);
  set('f-age_rating', d.age_rating);
  set('f-poster_url', d.poster_url);
  set('f-trailer_url', d.trailer_url);
  set('scrape-title', d.title);

  // Auto-check matching genres
  if (d.genres && d.genres.length) {
    const checkboxes = document.querySelectorAll('#genres-checkboxes input[type=checkbox]');
    checkboxes.forEach(cb => {
      const label = cb.closest('label').textContent.trim().toLowerCase();
      if (d.genres.some(g => g.toLowerCase() === label)) cb.checked = true;
    });
  }

  document.getElementById('scrape-status').innerHTML = '<div class="alert alert-success">✓ Form populated! Review the data and click Save.</div>';
}

function clearScrapeResult() {
  document.getElementById('scrape-result').classList.add('hidden');
  document.getElementById('scrape-apply-bar').style.display = 'none';
  document.getElementById('scrape-status').innerHTML = '';
  scrapedData = null;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
