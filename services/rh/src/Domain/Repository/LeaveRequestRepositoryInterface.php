<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\LeaveRequest;
use App\Domain\Entity\Enum\LeaveStatus;
use Symfony\Component\Uid\Uuid;

interface LeaveRequestRepositoryInterface
{
    public function save(LeaveRequest $leaveRequest): void;

    public function findById(Uuid $id, Uuid $tenantId): ?LeaveRequest;

    /** @return LeaveRequest[] */
    public function findPendingByTenant(Uuid $tenantId): array;

    /** @return LeaveRequest[] */
    public function findByEmployee(Uuid $employeeId, Uuid $tenantId): array;

    public function hasOverlap(Uuid $employeeId, Uuid $tenantId, \DateTimeImmutable $from, \DateTimeImmutable $to): bool;
}
