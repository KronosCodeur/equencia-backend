<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Employee;
use Symfony\Component\Uid\Uuid;

interface EmployeeRepositoryInterface
{
    public function save(Employee $employee): void;

    public function findById(Uuid $id, Uuid $tenantId): ?Employee;

    public function findByEmail(string $email, Uuid $tenantId): ?Employee;

    public function findByQrCodeHash(string $hash, Uuid $tenantId): ?Employee;

    /** @return Employee[] */
    public function findAllByTenant(Uuid $tenantId, int $page = 1, int $perPage = 50): array;

    public function countByTenant(Uuid $tenantId): int;

    public function emailExists(string $email, Uuid $tenantId): bool;

    public function remove(Employee $employee): void;
}
