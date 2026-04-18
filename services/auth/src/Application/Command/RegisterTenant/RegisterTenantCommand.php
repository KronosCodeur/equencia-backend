<?php

declare(strict_types=1);

namespace App\Application\Command\RegisterTenant;

final readonly class RegisterTenantCommand
{
    public function __construct(
        public readonly string $companyName,
        public readonly string $sector,
        public readonly string $adminEmail,
        public readonly string $adminPassword,
        public readonly string $adminFirstName,
        public readonly string $adminLastName,
        public readonly ?string $adminPhone = null,
    ) {}
}
