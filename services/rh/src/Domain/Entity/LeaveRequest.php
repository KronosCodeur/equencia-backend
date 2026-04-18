<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\LeaveStatus;
use App\Domain\Entity\Enum\LeaveType;
use App\Domain\Exception\InvalidLeaveRequestException;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'leave_requests')]
#[ORM\Index(columns: ['employee_id', 'starts_on'], name: 'idx_leave_employee_date')]
#[ORM\HasLifecycleCallbacks]
class LeaveRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(type: 'uuid')]
    private Uuid $employeeId;

    #[ORM\Column(type: 'string', enumType: LeaveType::class)]
    private LeaveType $type;

    #[ORM\Column(type: 'string', enumType: LeaveStatus::class)]
    private LeaveStatus $status;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $startsOn;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $endsOn;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $approvedBy;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $approvedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        Uuid $employeeId,
        LeaveType $type,
        DateTimeImmutable $startsOn,
        DateTimeImmutable $endsOn,
        ?string $reason,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->employeeId = $employeeId;
        $this->type = $type;
        $this->startsOn = $startsOn;
        $this->endsOn = $endsOn;
        $this->reason = $reason;
        $this->status = LeaveStatus::Pending;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function submit(
        Uuid $tenantId,
        Uuid $employeeId,
        LeaveType $type,
        DateTimeImmutable $startsOn,
        DateTimeImmutable $endsOn,
        ?string $reason = null,
    ): self {
        if ($endsOn < $startsOn) {
            throw new InvalidLeaveRequestException('Leave end date must be on or after start date.');
        }

        return new self(Uuid::v7(), $tenantId, $employeeId, $type, $startsOn, $endsOn, $reason);
    }

    public function approve(Uuid $approvedBy): void
    {
        $this->status = LeaveStatus::Approved;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new DateTimeImmutable();
        $this->touch();
    }

    public function reject(Uuid $rejectedBy, string $reason): void
    {
        $this->status = LeaveStatus::Rejected;
        $this->approvedBy = $rejectedBy;
        $this->rejectionReason = $reason;
        $this->touch();
    }

    public function cancel(): void
    {
        if (!in_array($this->status, [LeaveStatus::Pending, LeaveStatus::Approved], true)) {
            throw new InvalidLeaveRequestException('Only pending or approved leaves can be cancelled.');
        }

        $this->status = LeaveStatus::Cancelled;
        $this->touch();
    }

    public function getWorkingDays(): int
    {
        $days = 0;
        $current = $this->startsOn;

        while ($current <= $this->endsOn) {
            $dayOfWeek = (int) $current->format('N');
            if ($dayOfWeek < 6) {
                ++$days;
            }
            $current = $current->modify('+1 day');
        }

        return $days;
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getEmployeeId(): Uuid { return $this->employeeId; }
    public function getType(): LeaveType { return $this->type; }
    public function getStatus(): LeaveStatus { return $this->status; }
    public function getStartsOn(): DateTimeImmutable { return $this->startsOn; }
    public function getEndsOn(): DateTimeImmutable { return $this->endsOn; }
    public function getReason(): ?string { return $this->reason; }
    public function getApprovedBy(): ?Uuid { return $this->approvedBy; }
    public function getApprovedAt(): ?DateTimeImmutable { return $this->approvedAt; }
    public function getRejectionReason(): ?string { return $this->rejectionReason; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }
    public function isPending(): bool { return $this->status === LeaveStatus::Pending; }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
