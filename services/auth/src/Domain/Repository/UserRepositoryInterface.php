<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\User;
use Equencia\Shared\ValueObject\TenantId;
use Equencia\Shared\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(UserId $id, TenantId $tenantId): ?User;

    public function findByEmail(string $email): ?User;

    public function emailExists(string $email): bool;
}
