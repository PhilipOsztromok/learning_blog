<?php
// ============================================================
// ANIME VAULT - Admin: Delete Anime
// File: /var/www/html/anime/admin/delete_anime.php
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);

// Verify CSRF (passed via GET for link-based delete, or POST)
$token = $_GET['csrf'] ?? $_POST['csrf_token'] ?? '';
if (!hash_equals(csrfToken(), $token)) {
    http_response_code(403);
    die('Invalid CSRF token');
}

if ($id) {
    $pdo->prepare("DELETE FROM anime WHERE id = ?")->execute([$id]);
}

header('Location: /anime/admin/anime.php?msg=deleted');
exit;
