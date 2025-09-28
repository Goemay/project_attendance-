<?php
// Start session with secure defaults
if (session_status() === PHP_SESSION_NONE) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/db.php';

function current_user() {
    if (!isset($_SESSION['user_id'])) return null;
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

// CSRF helpers
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
