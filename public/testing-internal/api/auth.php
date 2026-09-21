<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/database.php';

start_secure_session();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    case 'csrf-token':
        // The frontend fetches this once on load and attaches it to every
        // POST/PUT/DELETE request via the X-CSRF-Token header.
        json_response(['csrf_token' => csrf_token()]);
        break;

    case 'signup':
        if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
        require_csrf();

        $input = get_json_input();
        $name = trim($input['name'] ?? '');
        $email = trim(strtolower($input['email'] ?? ''));
        $password = $input['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            json_response(['error' => 'Name, email and password are all required.'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['error' => 'Please enter a valid email address.'], 422);
        }
        if (strlen($password) < 8) {
            json_response(['error' => 'Password must be at least 8 characters.'], 422);
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            json_response(['error' => 'An account with that email already exists.'], 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, 'user']);

        json_response(['message' => 'Account created. You can now log in.']);
        break;

    case 'login':
        if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
        require_csrf();

        $input = get_json_input();
        $email = trim(strtolower($input['email'] ?? ''));
        $password = $input['password'] ?? '';

        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Same generic error whether the email or the password was wrong,
        // so we don't leak which accounts exist.
        if (!$user || !password_verify($password, $user['password_hash'])) {
            json_response(['error' => 'Invalid email or password.'], 401);
        }

        session_regenerate_id(true); // prevent session fixation on privilege change
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        json_response(['user' => $_SESSION['user']]);
        break;

    case 'logout':
        if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
        require_csrf();

        $_SESSION = [];
        session_destroy();

        // Destroying the session also destroyed the CSRF token, so the
        // client's copy is now stale. Start a fresh session and hand back
        // a new token so the very next request (e.g. a login) still works.
        session_start();
        $newToken = csrf_token();

        json_response(['message' => 'Logged out.', 'csrf_token' => $newToken]);
        break;

    case 'me':
        json_response(['user' => current_user()]);
        break;

    default:
        json_response(['error' => 'Unknown action.'], 404);
}
