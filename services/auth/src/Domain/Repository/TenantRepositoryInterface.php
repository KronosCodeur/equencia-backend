<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Tenant;
use Equencia\Shared\ValueObject\TenantId;

interface TenantRepositoryInterface
{
    public function save(Tenant $tenant): void;

    public function findById(TenantId $id): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;

    public function slugExists(string $slug): bool;
}
