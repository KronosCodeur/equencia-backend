<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin      = 'admin';
    case Manager    = 'manager';
    case HR         = 'hr';
    case Agent      = 'agent';
    case Reader     = 'reader';

    public function toSymfonyRole(): string
    {
        return 'ROLE_' . strtoupper($this->value);
    }

    public function canManageUsers(): bool
    {
        return match($this) {
            self::SuperAdmin, self::Admin => true,
            default => false,
        };
    }

    public function canManageEmployees(): bool
    {
        return match($this) {
            self::SuperAdmin, self::Admin, self::Manager, self::HR => true,
            default => false,
        };
    }

    public function canTriggerInspection(): bool
    {
        return match($this) {
            self::SuperAdmin, self::Admin, self::Manager => true,
            default => false,
        };
    }

    public function canGeneratePayroll(): bool
    {
        return match($this) {
            self::SuperAdmin, self::Admin, self::HR => true,
            default => false,
        };
    }
}
