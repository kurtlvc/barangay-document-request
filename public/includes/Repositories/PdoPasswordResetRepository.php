<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\PasswordResetStatus;
use PDO;

final class PdoPasswordResetRepository implements PasswordResetRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    public function findUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateUserPassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        return $stmt->execute([$passwordHash, $userId]);
    }

    public function createResetRequest(int $userId, ?string $reason): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, status, reason, requested_at) VALUES (?, "pending", ?, NOW())'
        );
        $stmt->execute([$userId, $reason]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findPendingRequestForUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, status, reason, requested_at FROM password_resets WHERE user_id = ? AND status = "pending" ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function listRequests(?PasswordResetStatus $status = null): array
    {
        $sql = 'SELECT pr.id, pr.user_id, pr.status, pr.reason, pr.admin_notes, pr.requested_at, pr.resolved_at,
                       u.name AS user_name, u.email AS user_email, u.role AS user_role,
                       r.resident_id, r.contact_number,
                       admin_user.name AS resolved_by_name
                FROM password_resets pr
                INNER JOIN users u ON u.id = pr.user_id
                LEFT JOIN residents r ON r.user_id = u.id
                LEFT JOIN users admin_user ON admin_user.id = pr.resolved_by';

        $params = [];
        if ($status !== null) {
            $sql .= ' WHERE pr.status = ?';
            $params[] = $status->value;
        }

        $sql .= ' ORDER BY pr.requested_at DESC, pr.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findRequestById(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pr.id, pr.user_id, pr.status, pr.reason, pr.admin_notes, pr.requested_at, pr.resolved_at,
                    u.name AS user_name, u.email AS user_email, u.role AS user_role
             FROM password_resets pr
             INNER JOIN users u ON u.id = pr.user_id
             WHERE pr.id = ?
             LIMIT 1'
        );
        $stmt->execute([$requestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function updateRequestStatus(
        int $requestId,
        PasswordResetStatus $status,
        int $resolvedBy,
        ?string $adminNotes = null
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets SET status = ?, resolved_by = ?, admin_notes = ?, resolved_at = NOW() WHERE id = ?'
        );
        return $stmt->execute([$status->value, $resolvedBy, $adminNotes, $requestId]);
    }
}
