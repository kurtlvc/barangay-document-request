<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_once __DIR__ . '/../includes/Enums/PasswordResetStatus.php';
require_once __DIR__ . '/../includes/DTO/SubmitPasswordResetRequestDTO.php';
require_once __DIR__ . '/../includes/DTO/AdminResetPasswordDTO.php';
require_once __DIR__ . '/../includes/Repositories/PasswordResetRepositoryInterface.php';
require_once __DIR__ . '/../includes/Repositories/PdoPasswordResetRepository.php';
require_once __DIR__ . '/../includes/Services/PasswordResetService.php';

use App\DTO\AdminResetPasswordDTO;
use App\DTO\SubmitPasswordResetRequestDTO;
use App\Enums\PasswordResetStatus;
use App\Repositories\PdoPasswordResetRepository;
use App\Services\PasswordResetService;

bdr_start_session();

$repository = new PdoPasswordResetRepository($pdo);
$service = new PasswordResetService($repository);
$method = $_SERVER['REQUEST_METHOD'];

// Public action: Resident submits offline password reset request
if ($method === 'POST' && ($_GET['action'] ?? '') === 'request') {
    bdr_require_write_csrf();
    $data = bdr_request_data();
    $dto = SubmitPasswordResetRequestDTO::fromArray($data);

    try {
        $result = $service->submitResetRequest($dto);
        bdr_json_response($result, 201);
    } catch (InvalidArgumentException $e) {
        bdr_json_response(['status' => 'error', 'message' => $e->getMessage()], 422);
    } catch (Throwable $e) {
        bdr_json_response(['status' => 'error', 'message' => 'Unable to submit reset request.'], 500);
    }
}

// Admin / Staff protected endpoints
$adminUser = bdr_require_auth(['admin', 'staff']);

if ($method === 'GET') {
    $statusParam = $_GET['status'] ?? null;
    $statusEnum = null;
    if ($statusParam !== null && $statusParam !== 'all' && $statusParam !== '') {
        $statusEnum = PasswordResetStatus::tryFrom((string) $statusParam);
    }

    $requests = $repository->listRequests($statusEnum);
    bdr_json_response(['status' => 'ok', 'requests' => $requests]);
}

// Only admin can perform state mutations (reset password or reject request)
if ($adminUser['role'] !== 'admin') {
    bdr_json_response(['status' => 'error', 'message' => 'Admin privileges required.'], 403);
}

bdr_require_write_csrf();
$data = bdr_request_data();
$action = $data['action'] ?? '';

if ($method === 'POST' || $method === 'PATCH' || $method === 'PUT') {
    if ($action === 'generate-temp') {
        bdr_json_response([
            'status' => 'ok',
            'temp_password' => PasswordResetService::generateTempPassword(),
        ]);
    }

    if ($action === 'admin-reset') {
        try {
            $dto = AdminResetPasswordDTO::fromArray($data);
            $result = $service->adminResetPassword($dto, (int) $adminUser['id']);
            bdr_json_response($result);
        } catch (InvalidArgumentException $e) {
            bdr_json_response(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            bdr_json_response(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    if ($action === 'reject') {
        $requestId = (int) ($data['request_id'] ?? 0);
        $reason = trim((string) ($data['reason'] ?? ''));
        if ($requestId <= 0) {
            bdr_json_response(['status' => 'error', 'message' => 'Valid request ID required.'], 422);
        }

        try {
            $service->rejectRequest($requestId, (int) $adminUser['id'], $reason ?: null);
            bdr_json_response(['status' => 'ok', 'message' => 'Reset request marked as rejected.']);
        } catch (Throwable $e) {
            bdr_json_response(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    bdr_json_response(['status' => 'error', 'message' => 'Invalid action.'], 422);
}

bdr_json_response(['status' => 'error', 'message' => 'Method not allowed.'], 405);
