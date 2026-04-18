<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\AttendanceSource;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'attendances')]
#[ORM\Index(columns: ['employee_id', 'checked_in_at'], name: 'idx_attendance_employee_date')]
#[ORM\HasLifecycleCallbacks]
class Attendance
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(type: 'uuid')]
    private Uuid $employeeId;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $shiftId;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $checkedInAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $checkedOutAt;

    #[ORM\Column(type: 'string', enumType: AttendanceSource::class)]
    private AttendanceSource $source;

    #[ORM\Column(type: 'decimal', precision: 9, scale: 6, nullable: true)]
    private ?string $latitude;

    #[ORM\Column(type: 'decimal', precision: 9, scale: 6, nullable: true)]
    private ?string $longitude;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $lateMinutes;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        Uuid $employeeId,
        DateTimeImmutable $checkedInAt,
        AttendanceSource $source,
        ?Uuid $shiftId,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->employeeId = $employeeId;
        $this->checkedInAt = $checkedInAt;
        $this->source = $source;
        $this->shiftId = $shiftId;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function record(
        Uuid $tenantId,
        Uuid $employeeId,
        DateTimeImmutable $checkedInAt,
        AttendanceSource $source,
        ?Uuid $shiftId = null,
    ): self {
        return new self(Uuid::v7(), $tenantId, $employeeId, $checkedInAt, $source, $shiftId);
    }

    public function checkOut(DateTimeImmutable $at): void
    {
        $this->checkedOutAt = $at;
    }

    public function setCoordinates(string $latitude, string $longitude): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function setLateMinutes(int $minutes): void
    {
        $this->lateMinutes = max(0, $minutes);
    }

    public function getDurationMinutes(): ?int
    {
        if ($this->checkedOutAt === null) {
            return null;
        }

        return (int) (($this->checkedOutAt->getTimestamp() - $this->checkedInAt->getTimestamp()) / 60);
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getEmployeeId(): Uuid { return $this->employeeId; }
    public function getShiftId(): ?Uuid { return $this->shiftId; }
    public function getCheckedInAt(): DateTimeImmutable { return $this->checkedInAt; }
    public function getCheckedOutAt(): ?DateTimeImmutable { return $this->checkedOutAt; }
    public function getSource(): AttendanceSource { return $this->source; }
    public function getLatitude(): ?string { return $this->latitude; }
    public function getLongitude(): ?string { return $this->longitude; }
    public function getLateMinutes(): ?int { return $this->lateMinutes; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function isCheckedOut(): bool { return $this->checkedOutAt !== null; }
}
