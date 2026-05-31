<?php
// ============================================================
// ANIME VAULT - Auth Helper
// File: /var/www/html/anime/includes/auth.php
// ============================================================

require_once __DIR__ . '/db.php';

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'cookie_secure'   => isset($_SERVER['HTTPS']),
]);

define('SESSION_LIFETIME', 60 * 60 * 24 * 7); // 7 days

// ── Get currently logged-in user (from session) ──────────────
function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id, username, email, role, avatar_url FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        if (!$user) { session_destroy(); }
    }
    return $user;
}

function isLoggedIn(): bool { return currentUser() !== null; }

function isAdmin(): bool {
    $u = currentUser();
    return $u && $u['role'] === 'admin';
}

function requireLogin(string $redirect = '/anime/login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect?next=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
}

// ── Register ─────────────────────────────────────────────────
function registerUser(string $username, string $email, string $password): array {
    $pdo = getDB();

    if (strlen($username) < 3 || strlen($username) > 50)
        return ['ok' => false, 'error' => 'Username must be 3–50 characters.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['ok' => false, 'error' => 'Invalid email address.'];
    if (strlen($password) < 8)
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username))
        return ['ok' => false, 'error' => 'Username may only contain letters, numbers, and underscores.'];

    // Check uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) return ['ok' => false, 'error' => 'Email or username already in use.'];

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hash]);
    $id = (int) $pdo->lastInsertId();

    $_SESSION['user_id'] = $id;
    return ['ok' => true, 'id' => $id];
}

// ── Login ────────────────────────────────────────────────────
function loginUser(string $email, string $password): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id, password, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $row  = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password']))
        return ['ok' => false, 'error' => 'Incorrect email or password.'];
    if (!$row['is_active'])
        return ['ok' => false, 'error' => 'This account has been disabled.'];

    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['id'];

    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$row['id']]);
    return ['ok' => true, 'id' => $row['id']];
}

// ── Logout ───────────────────────────────────────────────────
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ── CSRF ─────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}
