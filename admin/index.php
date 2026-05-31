<?php
// ═══════════════════════════════════════════════════════════════════════════
// ADMIN INTERFACE — Learning Blog
// Single-file admin for managing subjects, subtopics, pages and content.
// Place in /admin/index.php — protected by a simple session password.
// ═══════════════════════════════════════════════════════════════════════════

require_once dirname(__DIR__) . '/config.php';

// ── Password protection ────────────────────────────────────────────────────
// Change this to a strong password of your choice.
define('ADMIN_PASSWORD', 'K1m3tsuN0a@1bAPO');

session_start();

// Handle login / logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_auth'] = true;
    } else {
        $login_error = 'Incorrect password.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin/');
    exit;
}

// Show login form if not authenticated
if (empty($_SESSION['admin_auth'])) {
    render_login(isset($login_error) ? $login_error : null);
    exit;
}

// ── CSRF helpers ───────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }
}

// ── Slug generator ─────────────────────────────────────────────────────────
function make_slug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// ── Flash messages ─────────────────────────────────────────────────────────
function set_flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ═══════════════════════════════════════════════════════════════════════════
// ACTIONS — handle POST requests
// ═══════════════════════════════════════════════════════════════════════════

$action = $_GET['action'] ?? '';
$db     = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // ── Subjects ───────────────────────────────────────────────────────────
    if ($action === 'save_subject') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $slug  = trim($_POST['slug'] ?? '') ?: make_slug($name);
        $desc  = trim($_POST['description'] ?? '');
        $img   = trim($_POST['image_url'] ?? '');
        $order = (int)($_POST['sort_order'] ?? 0);

        if ($id) {
            $db->prepare('UPDATE subjects SET name=?, slug=?, description=?, image_url=?, sort_order=? WHERE id=?')
               ->execute([$name, $slug, $desc, $img, $order, $id]);
            set_flash('Subject updated.');
        } else {
            $db->prepare('INSERT INTO subjects (name, slug, description, image_url, sort_order) VALUES (?,?,?,?,?)')
               ->execute([$name, $slug, $desc, $img, $order]);
            set_flash('Subject created.');
        }
        redirect('/admin/?section=subjects');
    }

    if ($action === 'delete_subject') {
        $db->prepare('DELETE FROM subjects WHERE id=?')->execute([(int)$_POST['id']]);
        set_flash('Subject deleted.', 'warning');
        redirect('/admin/?section=subjects');
    }

    // ── Subtopics ──────────────────────────────────────────────────────────
    if ($action === 'save_subtopic') {
        $id         = (int)($_POST['id'] ?? 0);
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $slug       = trim($_POST['slug'] ?? '') ?: make_slug($name);
        $desc       = trim($_POST['description'] ?? '');
        $order      = (int)($_POST['sort_order'] ?? 0);

        if ($id) {
            $db->prepare('UPDATE subtopics SET subject_id=?, name=?, slug=?, description=?, sort_order=? WHERE id=?')
               ->execute([$subject_id, $name, $slug, $desc, $order, $id]);
            set_flash('Subtopic updated.');
        } else {
            $db->prepare('INSERT INTO subtopics (subject_id, name, slug, description, sort_order) VALUES (?,?,?,?,?)')
               ->execute([$subject_id, $name, $slug, $desc, $order]);
            set_flash('Subtopic created.');
        }
        redirect('/admin/?section=subtopics&subject_id=' . $subject_id);
    }

    if ($action === 'delete_subtopic') {
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $db->prepare('DELETE FROM subtopics WHERE id=?')->execute([(int)$_POST['id']]);
        set_flash('Subtopic deleted.', 'warning');
        redirect('/admin/?section=subtopics&subject_id=' . $subject_id);
    }

    // ── Pages ──────────────────────────────────────────────────────────────
    if ($action === 'save_page') {
        $id          = (int)($_POST['id'] ?? 0);
        $subtopic_id = (int)($_POST['subtopic_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $slug        = trim($_POST['slug'] ?? '') ?: make_slug($title);
        $order       = (int)($_POST['sort_order'] ?? 0);
        $body        = $_POST['body'] ?? '';

        if ($id) {
            $db->prepare('UPDATE pages SET subtopic_id=?, title=?, slug=?, sort_order=? WHERE id=?')
               ->execute([$subtopic_id, $title, $slug, $order, $id]);
            $db->prepare('UPDATE page_content SET body=?, updated_at=NOW() WHERE page_id=?')
               ->execute([$body, $id]);
            set_flash('Page updated.');
        } else {
            $db->prepare('INSERT INTO pages (subtopic_id, title, slug, sort_order) VALUES (?,?,?,?)')
               ->execute([$subtopic_id, $title, $slug, $order]);
            $new_id = (int)$db->lastInsertId();
            $db->prepare('INSERT INTO page_content (page_id, body) VALUES (?,?)')
               ->execute([$new_id, $body]);
            set_flash('Page created.');
        }
        redirect('/admin/?section=pages&subtopic_id=' . $subtopic_id);
    }

    if ($action === 'delete_page') {
        $subtopic_id = (int)($_POST['subtopic_id'] ?? 0);
        $db->prepare('DELETE FROM pages WHERE id=?')->execute([(int)$_POST['id']]);
        set_flash('Page deleted.', 'warning');
        redirect('/admin/?section=pages&subtopic_id=' . $subtopic_id);
    }
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// DATA HELPERS
// ═══════════════════════════════════════════════════════════════════════════

function all_subjects(): array {
    return get_db()->query('SELECT * FROM subjects ORDER BY sort_order, name')->fetchAll();
}
function get_subject_by_id(int $id): ?array {
    $s = get_db()->prepare('SELECT * FROM subjects WHERE id=?');
    $s->execute([$id]); return $s->fetch() ?: null;
}
function all_subtopics(int $subject_id): array {
    $s = get_db()->prepare('SELECT * FROM subtopics WHERE subject_id=? ORDER BY sort_order, name');
    $s->execute([$subject_id]); return $s->fetchAll();
}
function get_subtopic_by_id(int $id): ?array {
    $s = get_db()->prepare('SELECT * FROM subtopics WHERE id=?');
    $s->execute([$id]); return $s->fetch() ?: null;
}
function all_pages(int $subtopic_id): array {
    $s = get_db()->prepare('SELECT * FROM pages WHERE subtopic_id=? ORDER BY sort_order, title');
    $s->execute([$subtopic_id]); return $s->fetchAll();
}
function get_page_by_id(int $id): ?array {
    $s = get_db()->prepare(
        'SELECT p.*, pc.body FROM pages p
         LEFT JOIN page_content pc ON pc.page_id = p.id
         WHERE p.id=?'
    );
    $s->execute([$id]); return $s->fetch() ?: null;
}

// ═══════════════════════════════════════════════════════════════════════════
// DETERMINE CURRENT VIEW
// ═══════════════════════════════════════════════════════════════════════════

$section     = $_GET['section']     ?? 'subjects';
$subject_id  = (int)($_GET['subject_id']  ?? 0);
$subtopic_id = (int)($_GET['subtopic_id'] ?? 0);
$edit_id     = (int)($_GET['edit']        ?? 0);

$flash = get_flash();

// ═══════════════════════════════════════════════════════════════════════════
// RENDER FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════

function render_login(?string $error): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&display=swap');
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg: #0a0c10; --surface: #13161e; --border: #1f2230;
    --accent: #00d4aa; --text: #e0e2ea; --muted: #6b7080;
  }
  body { background: var(--bg); color: var(--text); font-family: 'JetBrains Mono', monospace;
         min-height: 100vh; display: grid; place-items: center; }
  .box { background: var(--surface); border: 1px solid var(--border); border-radius: 10px;
         padding: 2.5rem 2.5rem 2rem; width: 340px; }
  .box__title { font-size: 0.7rem; letter-spacing: 0.2em; text-transform: uppercase;
                color: var(--accent); margin-bottom: 1.75rem; }
  label { display: block; font-size: 0.7rem; color: var(--muted); margin-bottom: 0.4rem;
          letter-spacing: 0.1em; text-transform: uppercase; }
  input[type=password] { width: 100%; background: var(--bg); border: 1px solid var(--border);
    color: var(--text); font-family: inherit; font-size: 0.9rem; padding: 0.6rem 0.9rem;
    border-radius: 5px; outline: none; transition: border-color 0.15s; }
  input[type=password]:focus { border-color: var(--accent); }
  button { margin-top: 1.25rem; width: 100%; background: var(--accent); color: #000;
           font-family: inherit; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.1em;
           text-transform: uppercase; padding: 0.7rem; border: none; border-radius: 5px;
           cursor: pointer; transition: opacity 0.15s; }
  button:hover { opacity: 0.85; }
  .error { font-size: 0.78rem; color: #f87171; margin-top: 0.9rem; }
</style>
</head>
<body>
<div class="box">
  <div class="box__title">// Admin Access</div>
  <form method="post">
    <label for="pw">Password</label>
    <input type="password" id="pw" name="password" autofocus>
    <button type="submit">Enter</button>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </form>
</div>
</body>
</html>
<?php }

// ── Main layout wrapper ────────────────────────────────────────────────────
function layout_start(string $title, ?array $flash, int $subject_id = 0, int $subtopic_id = 0): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?> — Admin</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Lora:wght@400;600&display=swap');
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg:       #0a0c10;
    --surface:  #13161e;
    --surface2: #1a1e2a;
    --border:   #1f2230;
    --border2:  #2a2f42;
    --accent:   #00d4aa;
    --accent2:  #f59e0b;
    --danger:   #f87171;
    --text:     #e0e2ea;
    --muted:    #6b7080;
    --mono:     'JetBrains Mono', monospace;
    --serif:    'Lora', Georgia, serif;
    --top-h:    52px;
    --side-w:   220px;
    --radius:   6px;
  }
  body { background: var(--bg); color: var(--text); font-family: var(--mono); font-size: 14px;
         min-height: 100vh; }
  a { color: var(--accent); text-decoration: none; }
  a:hover { text-decoration: underline; }

  /* Topbar */
  .topbar { position: fixed; top: 0; left: 0; right: 0; height: var(--top-h);
            background: var(--surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 1.25rem; gap: 1rem; z-index: 100; }
  .topbar__brand { font-size: 0.72rem; letter-spacing: 0.15em; text-transform: uppercase;
                   color: var(--accent); font-weight: 600; flex-shrink: 0; }
  .topbar__spacer { flex: 1; }
  .topbar__logout { font-size: 0.7rem; color: var(--muted); letter-spacing: 0.05em; }
  .topbar__logout:hover { color: var(--danger); text-decoration: none; }

  /* Sidebar */
  .sidebar { position: fixed; top: var(--top-h); left: 0; bottom: 0; width: var(--side-w);
             background: var(--surface); border-right: 1px solid var(--border);
             padding: 1.5rem 0; overflow-y: auto; }
  .sidebar__label { font-size: 0.62rem; letter-spacing: 0.15em; text-transform: uppercase;
                    color: var(--muted); padding: 0 1.1rem 0.5rem; }
  .sidebar__link { display: block; padding: 0.45rem 1.1rem; color: var(--muted); font-size: 0.82rem;
                   border-left: 2px solid transparent; transition: all 0.12s; white-space: nowrap;
                   overflow: hidden; text-overflow: ellipsis; }
  .sidebar__link:hover { color: var(--text); background: var(--surface2); text-decoration: none; }
  .sidebar__link--active { color: var(--text); border-left-color: var(--accent); background: var(--surface2); }
  .sidebar__divider { height: 1px; background: var(--border); margin: 0.75rem 0; }

  /* Main */
  .main { margin-left: var(--side-w); padding-top: var(--top-h); min-height: 100vh; }
  .content { padding: 2rem 2.5rem; max-width: 900px; }

  /* Flash */
  .flash { padding: 0.65rem 1rem; border-radius: var(--radius); font-size: 0.8rem;
           margin-bottom: 1.5rem; border: 1px solid; }
  .flash--success { background: #0a2e27; border-color: var(--accent); color: var(--accent); }
  .flash--warning { background: #2e1a0a; border-color: var(--accent2); color: var(--accent2); }

  /* Page header */
  .page-header { margin-bottom: 1.75rem; display: flex; align-items: center;
                 justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
  .page-header h1 { font-family: var(--serif); font-size: 1.5rem; font-weight: 600;
                    color: var(--text); line-height: 1.2; }
  .page-header p  { font-size: 0.78rem; color: var(--muted); margin-top: 0.3rem; }

  /* Breadcrumb */
  .breadcrumb { font-size: 0.72rem; color: var(--muted); margin-bottom: 1.5rem;
                display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
  .breadcrumb a { color: var(--muted); }
  .breadcrumb a:hover { color: var(--accent); text-decoration: none; }

  /* Buttons */
  .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem;
         border-radius: var(--radius); font-family: var(--mono); font-size: 0.78rem;
         font-weight: 500; letter-spacing: 0.04em; cursor: pointer; border: 1px solid transparent;
         transition: all 0.15s; text-decoration: none; }
  .btn:hover { text-decoration: none; }
  .btn--primary  { background: var(--accent); color: #000; }
  .btn--primary:hover { opacity: 0.85; }
  .btn--outline  { background: transparent; border-color: var(--border2); color: var(--muted); }
  .btn--outline:hover { border-color: var(--accent); color: var(--accent); }
  .btn--danger   { background: transparent; border-color: var(--danger); color: var(--danger); }
  .btn--danger:hover { background: var(--danger); color: #fff; }
  .btn--sm { padding: 0.3rem 0.65rem; font-size: 0.72rem; }

  /* Table */
  .table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
  thead th { text-align: left; font-size: 0.65rem; text-transform: uppercase;
             letter-spacing: 0.12em; color: var(--muted); padding: 0 0.75rem 0.6rem;
             border-bottom: 1px solid var(--border); }
  tbody td { padding: 0.7rem 0.75rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
  tbody tr:hover td { background: var(--surface2); }
  .td-actions { display: flex; gap: 0.5rem; }
  .slug-badge { font-size: 0.72rem; color: var(--muted); background: var(--bg);
                padding: 0.15rem 0.45rem; border-radius: 3px; border: 1px solid var(--border); }

  /* Form */
  .form-card { background: var(--surface); border: 1px solid var(--border); border-radius: 8px;
               padding: 1.75rem 2rem 2rem; }
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
  .form-group { display: flex; flex-direction: column; gap: 0.4rem; }
  .form-group--full { grid-column: 1 / -1; }
  .form-group label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.12em;
                      color: var(--muted); }
  .form-group input, .form-group select, .form-group textarea {
    background: var(--bg); border: 1px solid var(--border2); color: var(--text);
    font-family: var(--mono); font-size: 0.85rem; padding: 0.55rem 0.8rem;
    border-radius: var(--radius); outline: none; transition: border-color 0.15s; width: 100%; }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--accent); }
  .form-group select option { background: var(--bg); }
  .form-hint { font-size: 0.68rem; color: var(--muted); }
  textarea { resize: vertical; min-height: 80px; }
  textarea.editor { font-family: var(--mono); min-height: 380px; line-height: 1.6; font-size: 0.82rem; }
  .form-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; align-items: center; }

  /* Empty state */
  .empty { padding: 3rem; text-align: center; color: var(--muted); font-size: 0.82rem;
           border: 1px dashed var(--border); border-radius: 8px; }

  /* Tabs (for subjects sidebar) */
  .count-badge { font-size: 0.65rem; background: var(--surface2); border: 1px solid var(--border2);
                 color: var(--muted); padding: 0.1rem 0.4rem; border-radius: 10px; }
</style>
</head>
<body>

<header class="topbar">
  <div class="topbar__brand">// Blog Admin</div>
  <div class="topbar__spacer"></div>
  <a href="/" target="_blank" class="topbar__logout" style="color:var(--muted)">↗ View site</a>
  &nbsp;
  <a href="/admin/?logout=1" class="topbar__logout">Sign out</a>
</header>

<nav class="sidebar">
  <div class="sidebar__label">Manage</div>
  <a href="/admin/?section=subjects" class="sidebar__link <?= (!$subject_id && $subtopic_id === 0 && ($GLOBALS['section'] ?? '') === 'subjects' ? 'sidebar__link--active' : '') ?>">Subjects</a>
  <div class="sidebar__divider"></div>
  <div class="sidebar__label">Subjects</div>
  <?php foreach (all_subjects() as $s): ?>
    <a href="/admin/?section=subtopics&subject_id=<?= $s['id'] ?>"
       class="sidebar__link <?= ($subject_id === $s['id'] ? 'sidebar__link--active' : '') ?>"
       title="<?= htmlspecialchars($s['name']) ?>">
      <?= htmlspecialchars($s['name']) ?>
    </a>
  <?php endforeach; ?>
</nav>

<div class="main">
<div class="content">

<?php if ($flash): ?>
  <div class="flash flash--<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>
<?php }

function layout_end(): void { ?>
</div></div>
</body></html>
<?php }

// ═══════════════════════════════════════════════════════════════════════════
// VIEWS
// ═══════════════════════════════════════════════════════════════════════════

// ── SUBJECTS list / edit ───────────────────────────────────────────────────
if ($section === 'subjects') {
    $editing = $edit_id ? get_subject_by_id($edit_id) : null;
    layout_start($editing ? 'Edit Subject' : 'Subjects', $flash);

    if ($editing) {
        // ── Edit form ──────────────────────────────────────────────────────
        echo '<div class="breadcrumb"><a href="/admin/?section=subjects">Subjects</a> › Edit</div>';
        echo '<div class="page-header"><div><h1>Edit Subject</h1></div></div>';
        echo '<div class="form-card">';
        echo '<form method="post" action="/admin/?action=save_subject">';
        echo csrf_field();
        echo '<input type="hidden" name="id" value="' . $editing['id'] . '">';
        echo '<div class="form-grid">';
        echo '<div class="form-group"><label>Name</label><input type="text" name="name" value="' . htmlspecialchars($editing['name']) . '" required></div>';
        echo '<div class="form-group"><label>Slug <span class="form-hint">(URL-safe, auto-generated if empty)</span></label><input type="text" name="slug" value="' . htmlspecialchars($editing['slug']) . '"></div>';
        echo '<div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="' . $editing['sort_order'] . '"></div>';
        echo '<div class="form-group"><label>Image URL</label><input type="text" name="image_url" value="' . htmlspecialchars($editing['image_url'] ?? '') . '"></div>';
        echo '<div class="form-group form-group--full"><label>Description</label><textarea name="description">' . htmlspecialchars($editing['description'] ?? '') . '</textarea></div>';
        echo '</div>';
        echo '<div class="form-actions"><button type="submit" class="btn btn--primary">Save changes</button><a href="/admin/?section=subjects" class="btn btn--outline">Cancel</a></div>';
        echo '</form></div>';
    } else {
        // ── List ───────────────────────────────────────────────────────────
        echo '<div class="page-header"><div><h1>Subjects</h1><p>Top-level categories on your site.</p></div>';
        echo '<a href="/admin/?section=subjects&edit=new" class="btn btn--primary">+ Add subject</a></div>';
        $subjects = all_subjects();
        if ($subjects) {
            echo '<div class="table-wrap"><table><thead><tr><th>Name</th><th>Slug</th><th>Order</th><th></th></tr></thead><tbody>';
            foreach ($subjects as $s) {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($s['name']) . '</strong></td>';
                echo '<td><span class="slug-badge">' . htmlspecialchars($s['slug']) . '</span></td>';
                echo '<td>' . $s['sort_order'] . '</td>';
                echo '<td><div class="td-actions">';
                echo '<a href="/admin/?section=subjects&edit=' . $s['id'] . '" class="btn btn--outline btn--sm">Edit</a>';
                echo '<a href="/admin/?section=subtopics&subject_id=' . $s['id'] . '" class="btn btn--outline btn--sm">Subtopics →</a>';
                echo '<form method="post" action="/admin/?action=delete_subject" style="display:inline" onsubmit="return confirm(\'Delete this subject and ALL its content?\');">';
                echo csrf_field() . '<input type="hidden" name="id" value="' . $s['id'] . '">';
                echo '<button type="submit" class="btn btn--danger btn--sm">Delete</button></form>';
                echo '</div></td></tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="empty">No subjects yet. Add your first one above.</div>';
        }

        // ── Inline new subject form ────────────────────────────────────────
        if (isset($_GET['edit']) && $_GET['edit'] === 'new') {
            echo '<br><div class="form-card">';
            echo '<div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.12em;color:var(--muted);margin-bottom:1.25rem">New Subject</div>';
            echo '<form method="post" action="/admin/?action=save_subject">';
            echo csrf_field();
            echo '<div class="form-grid">';
            echo '<div class="form-group"><label>Name</label><input type="text" name="name" required autofocus></div>';
            echo '<div class="form-group"><label>Slug <span class="form-hint">(auto-generated)</span></label><input type="text" name="slug"></div>';
            echo '<div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="0"></div>';
            echo '<div class="form-group"><label>Image URL</label><input type="text" name="image_url"></div>';
            echo '<div class="form-group form-group--full"><label>Description</label><textarea name="description"></textarea></div>';
            echo '</div>';
            echo '<div class="form-actions"><button type="submit" class="btn btn--primary">Create subject</button><a href="/admin/?section=subjects" class="btn btn--outline">Cancel</a></div>';
            echo '</form></div>';
        }
    }
    layout_end();
}

// ── SUBTOPICS list / edit ──────────────────────────────────────────────────
elseif ($section === 'subtopics') {
    $subject  = $subject_id ? get_subject_by_id($subject_id) : null;
    $editing  = $edit_id    ? get_subtopic_by_id($edit_id)   : null;
    $subjects = all_subjects();

    if (!$subject && $subjects) {
        // No subject chosen — redirect to first subject
        redirect('/admin/?section=subtopics&subject_id=' . $subjects[0]['id']);
    }

    layout_start($editing ? 'Edit Subtopic' : 'Subtopics', $flash, $subject_id);

    echo '<div class="breadcrumb">';
    echo '<a href="/admin/?section=subjects">Subjects</a> ›';
    echo '<a href="/admin/?section=subtopics&subject_id=' . $subject_id . '">' . htmlspecialchars($subject['name'] ?? '') . '</a>';
    if ($editing) echo ' › Edit';
    echo '</div>';

    if ($editing) {
        echo '<div class="page-header"><div><h1>Edit Subtopic</h1></div></div>';
        echo '<div class="form-card">';
        echo '<form method="post" action="/admin/?action=save_subtopic">';
        echo csrf_field();
        echo '<input type="hidden" name="id" value="' . $editing['id'] . '">';
        echo '<input type="hidden" name="subject_id" value="' . $subject_id . '">';
        echo '<div class="form-grid">';
        echo '<div class="form-group"><label>Name</label><input type="text" name="name" value="' . htmlspecialchars($editing['name']) . '" required></div>';
        echo '<div class="form-group"><label>Slug</label><input type="text" name="slug" value="' . htmlspecialchars($editing['slug']) . '"></div>';
        echo '<div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="' . $editing['sort_order'] . '"></div>';
        echo '<div class="form-group form-group--full"><label>Description</label><textarea name="description">' . htmlspecialchars($editing['description'] ?? '') . '</textarea></div>';
        echo '</div>';
        echo '<div class="form-actions"><button type="submit" class="btn btn--primary">Save changes</button>';
        echo '<a href="/admin/?section=subtopics&subject_id=' . $subject_id . '" class="btn btn--outline">Cancel</a></div>';
        echo '</form></div>';
    } else {
        echo '<div class="page-header"><div><h1>' . htmlspecialchars($subject['name'] ?? '') . '</h1><p>Subtopics within this subject.</p></div>';
        echo '<a href="/admin/?section=subtopics&subject_id=' . $subject_id . '&edit=new" class="btn btn--primary">+ Add subtopic</a></div>';

        $subtopics = $subject ? all_subtopics($subject['id']) : [];
        if ($subtopics) {
            echo '<div class="table-wrap"><table><thead><tr><th>Name</th><th>Slug</th><th>Order</th><th></th></tr></thead><tbody>';
            foreach ($subtopics as $st) {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($st['name']) . '</strong></td>';
                echo '<td><span class="slug-badge">' . htmlspecialchars($st['slug']) . '</span></td>';
                echo '<td>' . $st['sort_order'] . '</td>';
                echo '<td><div class="td-actions">';
                echo '<a href="/admin/?section=subtopics&subject_id=' . $subject_id . '&edit=' . $st['id'] . '" class="btn btn--outline btn--sm">Edit</a>';
                echo '<a href="/admin/?section=pages&subtopic_id=' . $st['id'] . '&subject_id=' . $subject_id . '" class="btn btn--outline btn--sm">Pages →</a>';
                echo '<form method="post" action="/admin/?action=delete_subtopic" style="display:inline" onsubmit="return confirm(\'Delete this subtopic and all its pages?\');">';
                echo csrf_field() . '<input type="hidden" name="id" value="' . $st['id'] . '">';
                echo '<input type="hidden" name="subject_id" value="' . $subject_id . '">';
                echo '<button type="submit" class="btn btn--danger btn--sm">Delete</button></form>';
                echo '</div></td></tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="empty">No subtopics yet for this subject.</div>';
        }

        if (isset($_GET['edit']) && $_GET['edit'] === 'new') {
            echo '<br><div class="form-card">';
            echo '<div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.12em;color:var(--muted);margin-bottom:1.25rem">New Subtopic</div>';
            echo '<form method="post" action="/admin/?action=save_subtopic">';
            echo csrf_field();
            echo '<input type="hidden" name="subject_id" value="' . $subject_id . '">';
            echo '<div class="form-grid">';
            echo '<div class="form-group"><label>Name</label><input type="text" name="name" required autofocus></div>';
            echo '<div class="form-group"><label>Slug <span class="form-hint">(auto-generated)</span></label><input type="text" name="slug"></div>';
            echo '<div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="0"></div>';
            echo '<div class="form-group form-group--full"><label>Description</label><textarea name="description"></textarea></div>';
            echo '</div>';
            echo '<div class="form-actions"><button type="submit" class="btn btn--primary">Create subtopic</button>';
            echo '<a href="/admin/?section=subtopics&subject_id=' . $subject_id . '" class="btn btn--outline">Cancel</a></div>';
            echo '</form></div>';
        }
    }
    layout_end();
}

// ── PAGES list / edit ──────────────────────────────────────────────────────
elseif ($section === 'pages') {
    $subtopic = $subtopic_id ? get_subtopic_by_id($subtopic_id) : null;
    $subject  = $subtopic    ? get_subject_by_id($subtopic['subject_id']) : ($subject_id ? get_subject_by_id($subject_id) : null);
    $editing  = $edit_id     ? get_page_by_id($edit_id) : null;

    layout_start($editing ? 'Edit Page' : 'Pages', $flash, $subject ? $subject['id'] : 0, $subtopic_id);

    echo '<div class="breadcrumb">';
    echo '<a href="/admin/?section=subjects">Subjects</a> ›';
    if ($subject) echo '<a href="/admin/?section=subtopics&subject_id=' . $subject['id'] . '">' . htmlspecialchars($subject['name']) . '</a> ›';
    if ($subtopic) echo '<a href="/admin/?section=pages&subtopic_id=' . $subtopic_id . '&subject_id=' . ($subject['id'] ?? 0) . '">' . htmlspecialchars($subtopic['name']) . '</a>';
    if ($editing) echo ' › Edit';
    echo '</div>';

    if ($editing) {
        echo '<div class="page-header"><div><h1>Edit Page</h1></div></div>';
        echo '<div class="form-card">';
        echo '<form method="post" action="/admin/?action=save_page">';
        echo csrf_field();
        echo '<input type="hidden" name="id" value="' . $editing['id'] . '">';
        echo '<input type="hidden" name="subtopic_id" value="' . $subtopic_id . '">';
        echo '<div class="form-grid">';
        echo '<div class="form-group form-group--full"><label>Title</label><input type="text" name="title" value="' . htmlspecialchars($editing['title']) . '" required></div>';
        echo '<div class="form-group"><label>Slug</label><input type="text" name="slug" value="' . htmlspecialchars($editing['slug']) . '"></div>';
        echo '<div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="' . $editing['sort_order'] . '"></div>';
        echo '<div class="form-group form-group--full">';
        echo '<label>Page Content <span class="form-hint">(HTML — write directly or paste from an editor)</span></label>';
        echo '<textarea name="body" class="editor">' . htmlspecialchars($editing['body'] ?? '') . '</textarea>';
        echo '</div>';
        echo '</div>';
        echo '<div class="form-actions"><button type="submit" class="btn btn--primary">Save changes</button>';
        echo '<a href="/admin/?section=pages&subtopic_id=' . $subtopic_id . '&subject_id=' . ($subject['id'] ?? 0) . '" class="btn btn--outline">Cancel</a></div>';
        echo '</form></div>';
    } else {
        echo '<div class="page-header"><div><h1>' . htmlspecialchars($subtopic['name'] ?? '') . '</h1><p>Pages within this subtopic.</p></div>';
        echo '<a href="/admin/?section=pages&subtopic_id=' . $subtopic_id . '&subject_id=' . ($subject['id'] ?? 0) . '&edit=new" class="btn btn--primary">+ Add page</a></div>';

        $pages = $subtopic ? all_pages($subtopic['id']) : [];
        if ($pages) {
            echo '<div class="table-wrap"><table><thead><tr><th>#</th><th>Title</th><th>Slug</th><th></th></tr></thead><tbody>';
            foreach ($pages as $pg) {
                echo '<tr>';
                echo '<td style="color:var(--muted)">' . $pg['sort_order'] . '</td>';
                echo '<td><strong>' . htmlspecialchars($pg['title']) . '</strong></td>';
                echo '<td><span class="slug-badge">' . htmlspecialchars($pg['slug']) . '</span></td>';
                echo '<td><div class="td-actions">';
                echo '<a href="/admin/?section=pages&subtopic_id=' . $subtopic_id . '&subject_id=' . ($subject['id'] ?? 0) . '&edit=' . $pg['id'] . '" class="btn btn--outline btn--sm">Edit</a>';
                echo '<form method="post" action="/admin/?action=delete_page" style="display:inline" onsubmit="return confirm(\'Delete this page and its content?\');">';
                echo csrf_field() . '<input type="hidden" name="id" value="' . $pg['id'] . '">';
                echo '<input type="hidden" name="subtopic_id" value="' . $subtopic_id . '">';
                echo '<button type="submit" class="btn btn--danger btn--sm">Delete</button></form>';
                echo '</div></td></tr>';
            }
            echo '</tbody></table></div>';
        } else {
            echo '<div class="empty">No pages yet in this subtopic.</div>';
        }

        if (isset($_GET['edit']) && $_GET['edit'] === 'new') {
            echo '<br><div class="form-card">';
            echo '<div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.12em;color:var(--muted);margin-bottom:1.25rem">New Page</div>';
            echo '<form method="post" action="/admin/?action=save_page">';
            echo csrf_field();
            echo '<input type="hidden" name="subtopic_id" value="' . $subtopic_id . '">';
            echo '<div class="form-grid">';
            echo '<div class="form-group form-group--full"><label>Title</label><input type="text" name="title" required autofocus></div>';
            echo '<div class="form-group"><label>Slug <span class="form-hint">(auto-generated)</span></label><input type="text" name="slug"></div>';
            echo '<div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="0"></div>';
            echo '<div class="form-group form-group--full">';
            echo '<label>Page Content <span class="form-hint">(HTML)</span></label>';
            echo '<textarea name="body" class="editor"></textarea>';
            echo '</div>';
            echo '</div>';
            echo '<div class="form-actions"><button type="submit" class="btn btn--primary">Create page</button>';
            echo '<a href="/admin/?section=pages&subtopic_id=' . $subtopic_id . '&subject_id=' . ($subject['id'] ?? 0) . '" class="btn btn--outline">Cancel</a></div>';
            echo '</form></div>';
        }
    }
    layout_end();
}
