<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SubmitPasswordResetRequestDTO
{
    public function __construct(
        public string $email,
        public ?string $reason = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: strtolower(trim((string) ($data['email'] ?? ''))),
            reason: isset($data['reason']) && trim((string) $data['reason']) !== '' ? trim((string) $data['reason']) : null,
        );
    }
}
