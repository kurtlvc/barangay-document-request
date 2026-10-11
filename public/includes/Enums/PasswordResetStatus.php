<?php

declare(strict_types=1);

namespace App\Enums;

enum PasswordResetStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Approved => 'Approved / Reset',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'status-pending',
            self::Approved => 'status-claimed',
            self::Rejected => 'status-inactive',
        };
    }
}
