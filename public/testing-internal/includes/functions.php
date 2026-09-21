<?php
// Shared helpers used by every API endpoint.

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,   // JS can't read the cookie -> mitigates XSS session theft
            'samesite' => 'Lax',  // basic CSRF mitigation for cross-site requests
            // 'secure' => true,   // uncomment once you're serving over HTTPS
        ]);
        session_start();
    }
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function get_json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Not authenticated.'], 401);
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        json_response(['error' => 'Admins only.'], 403);
    }
    return $user;
}

// CSRF: a token is generated per session and must be echoed back on every
// state-changing request (anything other than GET).
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        json_response(['error' => 'Invalid or missing CSRF token.'], 403);
    }
}
