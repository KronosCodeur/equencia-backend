<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Shift;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

interface ShiftRepositoryInterface
{
    public function save(Shift $shift): void;

    public function findById(Uuid $id, Uuid $tenantId): ?Shift;

    /** @return Shift[] */
    public function findByEmployeeBetween(
        Uuid $employeeId,
        Uuid $tenantId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): array;

    /** @return Shift[] */
    public function findPublishedByTenantBetween(
        Uuid $tenantId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): array;

    public function remove(Shift $shift): void;
}
