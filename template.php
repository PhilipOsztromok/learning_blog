<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($html_title) ?></title>
    <style>
        /* ── Reset & base ─────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --c-bg:        #0f1117;
            --c-surface:   #1a1d27;
            --c-border:    #2a2d3a;
            --c-accent:    #4f8ef7;
            --c-accent2:   #7c5cbf;
            --c-text:      #e2e4ec;
            --c-muted:     #7a7f96;
            --c-active-bg: #1e2235;
            --radius:      6px;
            --nav-w:       260px;
            --top-h:       56px;
            --font-body:   'Georgia', serif;
            --font-ui:     system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--c-bg);
            color: var(--c-text);
            font-family: var(--font-body);
            font-size: 17px;
            line-height: 1.75;
            min-height: 100vh;
        }

        a { color: var(--c-accent); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* ── Top navigation bar ───────────────────────────────────── */
        .topnav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--top-h);
            background: var(--c-surface);
            border-bottom: 1px solid var(--c-border);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            gap: 0.25rem;
            z-index: 100;
            font-family: var(--font-ui);
            font-size: 0.82rem;
            letter-spacing: 0.03em;
            overflow-x: auto;
        }

        .topnav__home {
            font-weight: 700;
            color: var(--c-text);
            white-space: nowrap;
            margin-right: 1rem;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .topnav__home:hover { text-decoration: none; color: var(--c-accent); }

        .topnav__divider {
            width: 1px;
            height: 18px;
            background: var(--c-border);
            margin: 0 0.75rem;
            flex-shrink: 0;
        }

        .topnav__link {
            color: var(--c-muted);
            padding: 0.3rem 0.65rem;
            border-radius: var(--radius);
            white-space: nowrap;
            flex-shrink: 0;
            transition: color 0.15s, background 0.15s;
        }
        .topnav__link:hover { color: var(--c-text); background: var(--c-active-bg); text-decoration: none; }
        .topnav__link--active { color: var(--c-text); background: var(--c-active-bg); font-weight: 500; }

        /* ── Page shell ───────────────────────────────────────────── */
        .shell {
            display: flex;
            padding-top: var(--top-h);
            min-height: 100vh;
        }

        /* ── Sidebar ──────────────────────────────────────────────── */
        .sidebar {
            width: var(--nav-w);
            flex-shrink: 0;
            position: fixed;
            top: var(--top-h);
            bottom: 0;
            left: 0;
            overflow-y: auto;
            background: var(--c-surface);
            border-right: 1px solid var(--c-border);
            padding: 1.5rem 0 2rem;
            font-family: var(--font-ui);
            font-size: 0.85rem;
        }

        .sidebar__section-title {
            color: var(--c-muted);
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 1rem 1.25rem 0.4rem;
        }

        .sidebar__link {
            display: block;
            color: var(--c-muted);
            padding: 0.35rem 1.25rem;
            border-left: 2px solid transparent;
            transition: color 0.15s, border-color 0.15s, background 0.15s;
            line-height: 1.4;
        }
        .sidebar__link:hover {
            color: var(--c-text);
            background: var(--c-active-bg);
            text-decoration: none;
        }
        .sidebar__link--active {
            color: var(--c-text);
            border-left-color: var(--c-accent);
            background: var(--c-active-bg);
            font-weight: 500;
        }

        /* ── Main content area ────────────────────────────────────── */
        .main {
            flex: 1;
            margin-left: var(--nav-w);
            padding: 3rem 4rem 5rem;
            max-width: 900px;
        }

        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 2rem 1.5rem 4rem; }
        }

        /* ── Typography ───────────────────────────────────────────── */
        h1 { font-size: 2rem; font-weight: 700; line-height: 1.2; margin-bottom: 1rem; color: #fff; }
        h2 { font-size: 1.45rem; font-weight: 600; margin: 2rem 0 0.75rem; color: #fff; }
        h3 { font-size: 1.15rem; font-weight: 600; margin: 1.5rem 0 0.5rem; color: var(--c-text); }

        p  { margin-bottom: 1rem; }
        ul, ol { padding-left: 1.5rem; margin-bottom: 1rem; }
        li { margin-bottom: 0.3rem; }

        code {
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            background: var(--c-border);
            padding: 0.15em 0.4em;
            border-radius: 3px;
        }

        pre {
            background: #12141e;
            border: 1px solid var(--c-border);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            overflow-x: auto;
            margin-bottom: 1.5rem;
        }
        pre code { background: none; padding: 0; font-size: 0.88em; }

        blockquote {
            border-left: 3px solid var(--c-accent2);
            padding: 0.5rem 0 0.5rem 1.25rem;
            margin: 1.5rem 0;
            color: var(--c-muted);
            font-style: italic;
        }

        hr { border: none; border-top: 1px solid var(--c-border); margin: 2rem 0; }

        /* ── Home page grid ───────────────────────────────────────── */
        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-top: 2rem;
        }

        .subject-card {
            display: block;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.2s, transform 0.15s;
        }
        .subject-card:hover {
            border-color: var(--c-accent);
            transform: translateY(-2px);
            text-decoration: none;
        }
        .subject-card__img {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            display: block;
        }
        .subject-card__body { padding: 0.9rem 1rem 1rem; }
        .subject-card__title { font-family: var(--font-ui); font-weight: 600; font-size: 1rem; color: var(--c-text); }
        .subject-card__desc  { font-family: var(--font-ui); font-size: 0.8rem; color: var(--c-muted); margin-top: 0.3rem; line-height: 1.5; }

        /* ── Subtopic list (subject page) ─────────────────────────── */
        .subtopic-list { list-style: none; padding: 0; margin-top: 1.5rem; }
        .subtopic-list li {
            border-bottom: 1px solid var(--c-border);
        }
        .subtopic-list li:first-child { border-top: 1px solid var(--c-border); }
        .subtopic-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 0;
            color: var(--c-text);
            font-family: var(--font-ui);
            font-size: 0.95rem;
            transition: color 0.15s;
        }
        .subtopic-list a:hover { color: var(--c-accent); text-decoration: none; }
        .subtopic-list__arrow { color: var(--c-muted); font-size: 1rem; }

        /* ── Page list (subtopic page) ────────────────────────────── */
        .page-list { list-style: none; padding: 0; margin-top: 1.5rem; }
        .page-list li {
            counter-increment: pages;
            border-bottom: 1px solid var(--c-border);
        }
        .page-list li:first-child { border-top: 1px solid var(--c-border); }
        .page-list a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 0;
            color: var(--c-text);
            font-family: var(--font-ui);
            font-size: 0.95rem;
            transition: color 0.15s;
        }
        .page-list a:hover { color: var(--c-accent); text-decoration: none; }
        .page-list__num {
            font-size: 0.75rem;
            color: var(--c-muted);
            min-width: 1.5rem;
            font-family: var(--font-ui);
        }

        /* ── Breadcrumb ───────────────────────────────────────────── */
        .breadcrumb {
            font-family: var(--font-ui);
            font-size: 0.78rem;
            color: var(--c-muted);
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
        }
        .breadcrumb a { color: var(--c-muted); }
        .breadcrumb a:hover { color: var(--c-accent); }
        .breadcrumb__sep { opacity: 0.4; }

        /* ── Prev / Next navigation ───────────────────────────────── */
        .page-nav {
            display: flex;
            gap: 1rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--c-border);
            font-family: var(--font-ui);
            font-size: 0.85rem;
        }
        .page-nav__btn {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            padding: 0.9rem 1.1rem;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius);
            color: var(--c-text);
            transition: border-color 0.15s;
        }
        .page-nav__btn:hover { border-color: var(--c-accent); text-decoration: none; }
        .page-nav__btn--next { text-align: right; }
        .page-nav__label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--c-muted); }
        .page-nav__title { font-weight: 500; color: var(--c-text); }

        /* ── 404 ──────────────────────────────────────────────────── */
        .not-found { text-align: center; padding: 5rem 2rem; }
        .not-found__code { font-size: 5rem; font-weight: 800; color: var(--c-border); line-height: 1; margin-bottom: 1rem; }
    </style>
</head>
<body>

<!-- ════════════════════════════════════════════════════════════
     TOP NAVIGATION BAR  (subject areas)
═════════════════════════════════════════════════════════════ -->
<nav class="topnav">
    <a href="/" class="topnav__home"><?= htmlspecialchars(SITE_TITLE) ?></a>
    <div class="topnav__divider"></div>
    <?php foreach ($all_subjects as $subj): ?>
        <a href="/<?= $subj['slug'] ?>"
           class="topnav__link<?= ($current_subject && $current_subject['id'] === $subj['id']) ? ' topnav__link--active' : '' ?>">
            <?= htmlspecialchars($subj['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<div class="shell">

    <!-- ══════════════════════════════════════════════════════════
         SIDEBAR  (subtopics + pages — only shown inside a subject)
    ═══════════════════════════════════════════════════════════ -->
    <?php if ($current_subject && $page_type !== '404'): ?>
    <aside class="sidebar">

        <div class="sidebar__section-title">
            <?= htmlspecialchars($current_subject['name']) ?>
        </div>

        <?php foreach ($current_subtopics as $st): ?>
            <?php
                $st_active = $current_subtopic && $current_subtopic['id'] === $st['id'];
                $st_url    = '/' . $current_subject['slug'] . '/' . $st['slug'];
            ?>
            <a href="<?= $st_url ?>"
               class="sidebar__link<?= $st_active ? ' sidebar__link--active' : '' ?>">
                <?= htmlspecialchars($st['name']) ?>
            </a>

            <?php if ($st_active && $current_pages): ?>
                <?php foreach ($current_pages as $pg): ?>
                    <?php $pg_url = $st_url . '/' . $pg['slug']; ?>
                    <a href="<?= $pg_url ?>"
                       class="sidebar__link<?= ($current_page && $current_page['id'] === $pg['id']) ? ' sidebar__link--active' : '' ?>"
                       style="padding-left:2rem; font-size:0.8rem;">
                        <?= htmlspecialchars($pg['title']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php endforeach; ?>
    </aside>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════════════════════════ -->
    <main class="main">

        <?php if ($page_type === 'home'): ?>
        <!-- ── HOME PAGE ─────────────────────────────────────── -->
            <h1>Welcome</h1>
            <p>A collection of notes and tutorials on the topics I'm learning.</p>
            <div class="subject-grid">
                <?php foreach ($all_subjects as $subj): ?>
                    <a href="/<?= $subj['slug'] ?>" class="subject-card">
                        <?php if (!empty($subj['image_url'])): ?>
                            <img src="<?= htmlspecialchars($subj['image_url']) ?>"
                                 alt="<?= htmlspecialchars($subj['name']) ?>"
                                 class="subject-card__img">
                        <?php endif; ?>
                        <div class="subject-card__body">
                            <div class="subject-card__title"><?= htmlspecialchars($subj['name']) ?></div>
                            <?php if (!empty($subj['description'])): ?>
                                <div class="subject-card__desc"><?= htmlspecialchars($subj['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

        <?php elseif ($page_type === 'subject'): ?>
        <!-- ── SUBJECT PAGE (list of subtopics) ──────────────── -->
            <nav class="breadcrumb">
                <a href="/">Home</a>
                <span class="breadcrumb__sep">›</span>
                <span><?= htmlspecialchars($current_subject['name']) ?></span>
            </nav>
            <h1><?= htmlspecialchars($current_subject['name']) ?></h1>
            <?php if (!empty($current_subject['description'])): ?>
                <p><?= htmlspecialchars($current_subject['description']) ?></p>
            <?php endif; ?>
            <ul class="subtopic-list">
                <?php foreach ($current_subtopics as $st): ?>
                    <li>
                        <a href="/<?= $current_subject['slug'] ?>/<?= $st['slug'] ?>">
                            <?= htmlspecialchars($st['name']) ?>
                            <span class="subtopic-list__arrow">›</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

        <?php elseif ($page_type === 'subtopic'): ?>
        <!-- ── SUBTOPIC PAGE (list of pages) ─────────────────── -->
            <nav class="breadcrumb">
                <a href="/">Home</a>
                <span class="breadcrumb__sep">›</span>
                <a href="/<?= $current_subject['slug'] ?>"><?= htmlspecialchars($current_subject['name']) ?></a>
                <span class="breadcrumb__sep">›</span>
                <span><?= htmlspecialchars($current_subtopic['name']) ?></span>
            </nav>
            <h1><?= htmlspecialchars($current_subtopic['name']) ?></h1>
            <?php if (!empty($current_subtopic['description'])): ?>
                <p><?= htmlspecialchars($current_subtopic['description']) ?></p>
            <?php endif; ?>
            <ol class="page-list">
                <?php foreach ($current_pages as $i => $pg): ?>
                    <li>
                        <a href="/<?= $current_subject['slug'] ?>/<?= $current_subtopic['slug'] ?>/<?= $pg['slug'] ?>">
                            <span class="page-list__num"><?= $i + 1 ?>.</span>
                            <?= htmlspecialchars($pg['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>

        <?php elseif ($page_type === 'page'): ?>
        <!-- ── CONTENT PAGE ───────────────────────────────────── -->
            <nav class="breadcrumb">
                <a href="/">Home</a>
                <span class="breadcrumb__sep">›</span>
                <a href="/<?= $current_subject['slug'] ?>"><?= htmlspecialchars($current_subject['name']) ?></a>
                <span class="breadcrumb__sep">›</span>
                <a href="/<?= $current_subject['slug'] ?>/<?= $current_subtopic['slug'] ?>"><?= htmlspecialchars($current_subtopic['name']) ?></a>
                <span class="breadcrumb__sep">›</span>
                <span><?= htmlspecialchars($current_page['title']) ?></span>
            </nav>
            <h1><?= htmlspecialchars($current_page['title']) ?></h1>

            <!-- Page body — stored as HTML in the database -->
            <div class="page-body">
                <?= $current_page['body'] ?>
            </div>

            <!-- Prev / Next navigation -->
            <?php if ($adjacent['prev'] || $adjacent['next']): ?>
            <nav class="page-nav">
                <?php if ($adjacent['prev']): ?>
                    <a href="/<?= $current_subject['slug'] ?>/<?= $current_subtopic['slug'] ?>/<?= $adjacent['prev']['slug'] ?>"
                       class="page-nav__btn page-nav__btn--prev">
                        <span class="page-nav__label">← Previous</span>
                        <span class="page-nav__title"><?= htmlspecialchars($adjacent['prev']['title']) ?></span>
                    </a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <?php if ($adjacent['next']): ?>
                    <a href="/<?= $current_subject['slug'] ?>/<?= $current_subtopic['slug'] ?>/<?= $adjacent['next']['slug'] ?>"
                       class="page-nav__btn page-nav__btn--next">
                        <span class="page-nav__label">Next →</span>
                        <span class="page-nav__title"><?= htmlspecialchars($adjacent['next']['title']) ?></span>
                    </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

        <?php elseif ($page_type === '404'): ?>
        <!-- ── 404 ────────────────────────────────────────────── -->
            <div class="not-found">
                <div class="not-found__code">404</div>
                <h1>Page not found</h1>
                <p><a href="/">Return to home</a></p>
            </div>

        <?php endif; ?>

    </main>
</div>

</body>
</html>
