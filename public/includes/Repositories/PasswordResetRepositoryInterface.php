<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\PasswordResetStatus;

interface PasswordResetRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findUserById(int $userId): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findUserByEmail(string $email): ?array;

    public function updateUserPassword(int $userId, string $passwordHash): bool;

    public function createResetRequest(int $userId, ?string $reason): int;

    /**
     * @return array<string, mixed>|null
     */
    public function findPendingRequestForUser(int $userId): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRequests(?PasswordResetStatus $status = null): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findRequestById(int $requestId): ?array;

    public function updateRequestStatus(
        int $requestId,
        PasswordResetStatus $status,
        int $resolvedBy,
        ?string $adminNotes = null
    ): bool;
}
