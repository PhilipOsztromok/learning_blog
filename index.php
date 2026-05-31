<?php
// ─── index.php ────────────────────────────────────────────────────────────
// Single entry point for the entire site.
// Apache rewrites all requests here via .htaccess.
// ──────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/config.php';

// ═══════════════════════════════════════════════════════════════════════════
// 1. ROUTING — parse the URL into subject / subtopic / page segments
// ═══════════════════════════════════════════════════════════════════════════

$request_uri  = $_SERVER['REQUEST_URI'];
$path         = parse_url($request_uri, PHP_URL_PATH);
$path         = trim($path, '/');
$segments     = $path !== '' ? explode('/', $path) : [];

$subject_slug  = $segments[0] ?? null;
$subtopic_slug = $segments[1] ?? null;
$page_slug     = $segments[2] ?? null;

// ═══════════════════════════════════════════════════════════════════════════
// 2. DATA QUERIES
// ═══════════════════════════════════════════════════════════════════════════

// ── Always loaded (needed for the top navigation bar) ─────────────────────
function get_all_subjects(): array {
    $stmt = get_db()->query(
        'SELECT id, name, slug FROM subjects ORDER BY sort_order, name'
    );
    return $stmt->fetchAll();
}

// ── Load the current subject ───────────────────────────────────────────────
function get_subject(string $slug): ?array {
    $stmt = get_db()->prepare(
        'SELECT * FROM subjects WHERE slug = ?'
    );
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

// ── Load all subtopics for the current subject (used in sidebar) ───────────
function get_subtopics(int $subject_id): array {
    $stmt = get_db()->prepare(
        'SELECT id, name, slug FROM subtopics
         WHERE subject_id = ?
         ORDER BY sort_order, name'
    );
    $stmt->execute([$subject_id]);
    return $stmt->fetchAll();
}

// ── Load the current subtopic ─────────────────────────────────────────────
function get_subtopic(int $subject_id, string $slug): ?array {
    $stmt = get_db()->prepare(
        'SELECT * FROM subtopics WHERE subject_id = ? AND slug = ?'
    );
    $stmt->execute([$subject_id, $slug]);
    return $stmt->fetch() ?: null;
}

// ── Load all pages in a subtopic (used for the subtopic page list) ─────────
function get_pages(int $subtopic_id): array {
    $stmt = get_db()->prepare(
        'SELECT id, title, slug FROM pages
         WHERE subtopic_id = ?
         ORDER BY sort_order, title'
    );
    $stmt->execute([$subtopic_id]);
    return $stmt->fetchAll();
}

// ── Load a single page with its content ───────────────────────────────────
function get_page(int $subtopic_id, string $slug): ?array {
    $stmt = get_db()->prepare(
        'SELECT p.id, p.title, p.slug, p.sort_order, pc.body
         FROM pages p
         LEFT JOIN page_content pc ON pc.page_id = p.id
         WHERE p.subtopic_id = ? AND p.slug = ?'
    );
    $stmt->execute([$subtopic_id, $slug]);
    return $stmt->fetch() ?: null;
}

// ── Load prev / next pages within the same subtopic ───────────────────────
function get_adjacent_pages(int $subtopic_id, int $current_sort_order): array {
    $db = get_db();

    $prev = $db->prepare(
        'SELECT title, slug FROM pages
         WHERE subtopic_id = ? AND sort_order < ?
         ORDER BY sort_order DESC LIMIT 1'
    );
    $prev->execute([$subtopic_id, $current_sort_order]);

    $next = $db->prepare(
        'SELECT title, slug FROM pages
         WHERE subtopic_id = ? AND sort_order > ?
         ORDER BY sort_order ASC LIMIT 1'
    );
    $next->execute([$subtopic_id, $current_sort_order]);

    return [
        'prev' => $prev->fetch() ?: null,
        'next' => $next->fetch() ?: null,
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// 3. RESOLVE WHAT TO DISPLAY
// ═══════════════════════════════════════════════════════════════════════════

$all_subjects  = get_all_subjects();
$current_subject  = null;
$current_subtopics = [];
$current_subtopic  = null;
$current_pages     = [];
$current_page      = null;
$adjacent          = ['prev' => null, 'next' => null];
$page_type         = 'home';      // home | subject | subtopic | page | 404

if ($subject_slug) {
    $current_subject = get_subject($subject_slug);

    if (!$current_subject) {
        $page_type = '404';
    } else {
        $current_subtopics = get_subtopics($current_subject['id']);

        if ($subtopic_slug) {
            $current_subtopic = get_subtopic($current_subject['id'], $subtopic_slug);

            if (!$current_subtopic) {
                $page_type = '404';
            } else {
                $current_pages = get_pages($current_subtopic['id']);

                if ($page_slug) {
                    $current_page = get_page($current_subtopic['id'], $page_slug);

                    if (!$current_page) {
                        $page_type = '404';
                    } else {
                        $adjacent  = get_adjacent_pages(
                            $current_subtopic['id'],
                            $current_page['sort_order']
                        );
                        $page_type = 'page';
                    }
                } else {
                    $page_type = 'subtopic';
                }
            }
        } else {
            $page_type = 'subject';
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// 4. BUILD PAGE TITLE
// ═══════════════════════════════════════════════════════════════════════════

$html_title = SITE_TITLE;
if ($current_subject)  $html_title = $current_subject['name']  . ' — ' . SITE_TITLE;
if ($current_subtopic) $html_title = $current_subtopic['name'] . ' — ' . $html_title;
if ($current_page)     $html_title = $current_page['title']    . ' — ' . $html_title;

// ═══════════════════════════════════════════════════════════════════════════
// 5. HAND OFF TO TEMPLATE
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/template.php';
