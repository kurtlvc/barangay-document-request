<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AdminResetPasswordDTO
{
    public function __construct(
        public int $userId,
        public string $newPassword,
        public ?int $requestId = null,
        public ?string $adminNotes = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) ($data['user_id'] ?? 0),
            newPassword: (string) ($data['new_password'] ?? ''),
            requestId: !empty($data['request_id']) ? (int) $data['request_id'] : null,
            adminNotes: isset($data['admin_notes']) && trim((string) $data['admin_notes']) !== '' ? trim((string) $data['admin_notes']) : null,
        );
    }
}
