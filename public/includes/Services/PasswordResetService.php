<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\AdminResetPasswordDTO;
use App\DTO\SubmitPasswordResetRequestDTO;
use App\Enums\PasswordResetStatus;
use App\Repositories\PasswordResetRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

final class PasswordResetService
{
    public function __construct(
        private readonly PasswordResetRepositoryInterface $repository,
    ) {}

    /**
     * Submit an offline reset request from resident.
     *
     * @return array{status: string, message: string, request_id: int}
     */
    public function submitResetRequest(SubmitPasswordResetRequestDTO $dto): array
    {
        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please provide a valid email address.');
        }

        $user = $this->repository->findUserByEmail($dto->email);
        if ($user === null) {
            // Return generic message to prevent email enumeration
            return [
                'status' => 'ok',
                'message' => 'If this email belongs to an account, a reset request has been queued for barangay staff review.',
                'request_id' => 0,
            ];
        }

        $pending = $this->repository->findPendingRequestForUser((int) $user['id']);
        if ($pending !== null) {
            return [
                'status' => 'ok',
                'message' => 'You already have a pending password reset request under review.',
                'request_id' => (int) $pending['id'],
            ];
        }

        $requestId = $this->repository->createResetRequest((int) $user['id'], $dto->reason);

        return [
            'status' => 'ok',
            'message' => 'Password reset request submitted. Please visit or contact barangay hall staff with your ID.',
            'request_id' => $requestId,
        ];
    }

    /**
     * Admin directly resets resident password.
     *
     * @return array{status: string, message: string, temp_password?: string}
     */
    public function adminResetPassword(AdminResetPasswordDTO $dto, int $adminUserId): array
    {
        if ($dto->userId <= 0) {
            throw new InvalidArgumentException('Valid user ID is required.');
        }

        $user = $this->repository->findUserById($dto->userId);
        if ($user === null) {
            throw new RuntimeException('User account not found.');
        }

        if (strlen($dto->newPassword) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters.');
        }

        $passwordHash = password_hash($dto->newPassword, PASSWORD_DEFAULT);
        $updated = $this->repository->updateUserPassword($dto->userId, $passwordHash);
        if (!$updated) {
            throw new RuntimeException('Failed to update password.');
        }

        // If linked to an open reset request, close it as approved
        if ($dto->requestId !== null) {
            $this->repository->updateRequestStatus(
                $dto->requestId,
                PasswordResetStatus::Approved,
                $adminUserId,
                $dto->adminNotes ?? 'Password reset by administrator'
            );
        } else {
            // Check if there is an active pending request for this user
            $pending = $this->repository->findPendingRequestForUser($dto->userId);
            if ($pending !== null) {
                $this->repository->updateRequestStatus(
                    (int) $pending['id'],
                    PasswordResetStatus::Approved,
                    $adminUserId,
                    $dto->adminNotes ?? 'Direct password reset by administrator'
                );
            }
        }

        return [
            'status' => 'ok',
            'message' => "Password for {$user['name']} has been reset successfully.",
        ];
    }

    /**
     * Admin rejects a pending password reset request.
     */
    public function rejectRequest(int $requestId, int $adminUserId, ?string $reason): bool
    {
        $request = $this->repository->findRequestById($requestId);
        if ($request === null) {
            throw new RuntimeException('Reset request not found.');
        }

        return $this->repository->updateRequestStatus(
            $requestId,
            PasswordResetStatus::Rejected,
            $adminUserId,
            $reason ?? 'Request rejected by administrator'
        );
    }

    /**
     * Generate secure random temporary password.
     */
    public static function generateTempPassword(int $length = 10): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%';
        $pw = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $pw .= $chars[random_int(0, $max)];
        }
        return $pw;
    }
}
