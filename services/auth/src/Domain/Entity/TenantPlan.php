<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum TenantPlan: string
{
    case Starter    = 'starter';
    case Business   = 'business';
    case Pro        = 'pro';
    case Enterprise = 'enterprise';

    public function employeeLimit(): int
    {
        return match($this) {
            self::Starter    => 10,
            self::Business   => 50,
            self::Pro        => 200,
            self::Enterprise => PHP_INT_MAX,
        };
    }

    public function monthlyPriceFcfa(): int
    {
        return match($this) {
            self::Starter    => 15_000,
            self::Business   => 45_000,
            self::Pro        => 120_000,
            self::Enterprise => 0,
        };
    }
}
