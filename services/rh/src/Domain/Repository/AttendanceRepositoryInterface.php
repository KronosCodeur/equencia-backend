<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Attendance;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

interface AttendanceRepositoryInterface
{
    public function save(Attendance $attendance): void;

    public function findById(Uuid $id, Uuid $tenantId): ?Attendance;

    public function findOpenCheckIn(Uuid $employeeId, Uuid $tenantId): ?Attendance;

    /** @return Attendance[] */
    public function findByEmployeeBetween(
        Uuid $employeeId,
        Uuid $tenantId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): array;

    /** @return Attendance[] */
    public function findByTenantOnDate(Uuid $tenantId, DateTimeImmutable $date): array;
}
