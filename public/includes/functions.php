<?php

function bdr_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function bdr_requre_csrf()
{
    // PHP validation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'CSRF token validation failed']);
            exit;
        }
    }
}

function bdr_start_session()
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
        bdr_csrf_token();
    }
}

function bdr_end_session()
{
    session_destroy();
}

function bdr_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function bdr_request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    return $_POST;
}

function bdr_require_auth(?array $roles = null): array
{
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        bdr_json_response(['status' => 'error', 'message' => 'Authentication is required.'], 401);
    }

    $user = [
        'id' => (int) $_SESSION['user_id'],
        'name' => $_SESSION['name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'],
        'resident_id' => $_SESSION['resident_id'] ?? null,
        'resident_status' => $_SESSION['resident_status'] ?? null,
    ];

    if ($roles !== null && !in_array($user['role'], $roles, true)) {
        bdr_json_response(['status' => 'error', 'message' => 'You do not have permission for this action.'], 403);
    }

    return $user;
}

function bdr_require_method(array $methods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        bdr_json_response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
    }
}

function bdr_require_write_csrf(): void
{
    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        bdr_requre_csrf();
    }
}

function bdr_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $publicDir = realpath(__DIR__ . '/..');
    $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
    if ($publicDir && $scriptDir && str_starts_with($scriptDir, $publicDir)) {
        $rel = trim(substr($scriptDir, strlen($publicDir)), '/\\');
        $depth = $rel === '' ? 0 : substr_count($rel, DIRECTORY_SEPARATOR) + 1;
        $base = $depth === 0 ? '.' : rtrim(str_repeat('../', $depth), '/');
    } else {
        $base = '.';
    }
    return $base;
}

