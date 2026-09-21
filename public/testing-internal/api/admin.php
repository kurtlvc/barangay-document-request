<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/database.php';

start_secure_session();
$admin = require_admin(); // blocks non-admins server-side, not just in the UI

$action = $_GET['action'] ?? 'list-users';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    case 'list-users':
        $stmt = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
        json_response(['users' => $stmt->fetchAll()]);
        break;

    case 'set-role':
        if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
        require_csrf();

        $input = get_json_input();
        $userId = (int)($input['user_id'] ?? 0);
        $role = $input['role'] ?? '';

        if (!in_array($role, ['user', 'admin'], true) || $userId <= 0) {
            json_response(['error' => 'Invalid user_id or role.'], 422);
        }
        if ($userId === (int)$admin['id'] && $role !== 'admin') {
            json_response(['error' => "You can't remove your own admin role."], 422);
        }

        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $userId]);

        json_response(['message' => 'Role updated.']);
        break;

    case 'delete-user':
        if ($method !== 'POST') json_response(['error' => 'Method not allowed.'], 405);
        require_csrf();

        $input = get_json_input();
        $userId = (int)($input['user_id'] ?? 0);

        if ($userId === (int)$admin['id']) {
            json_response(['error' => "You can't delete your own account here."], 422);
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);

        json_response(['message' => 'User deleted.']);
        break;

    default:
        json_response(['error' => 'Unknown action.'], 404);
}
