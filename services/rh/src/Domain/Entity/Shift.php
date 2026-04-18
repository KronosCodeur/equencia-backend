<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\InvalidShiftException;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'shifts')]
#[ORM\Index(columns: ['tenant_id', 'starts_at'], name: 'idx_shift_tenant_date')]
#[ORM\HasLifecycleCallbacks]
class Shift
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(type: 'uuid')]
    private Uuid $employeeId;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startsAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $endsAt;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $site;

    #[ORM\Column(type: 'boolean')]
    private bool $published;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        Uuid $employeeId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->employeeId = $employeeId;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->published = false;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        Uuid $tenantId,
        Uuid $employeeId,
        DateTimeImmutable $startsAt,
        DateTimeImmutable $endsAt,
        ?string $label = null,
        ?string $site = null,
    ): self {
        if ($endsAt <= $startsAt) {
            throw new InvalidShiftException('Shift end time must be after start time.');
        }

        $shift = new self(Uuid::v7(), $tenantId, $employeeId, $startsAt, $endsAt);
        $shift->label = $label;
        $shift->site = $site;

        return $shift;
    }

    public function publish(): void
    {
        $this->published = true;
        $this->touch();
    }

    public function reschedule(DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): void
    {
        if ($endsAt <= $startsAt) {
            throw new InvalidShiftException('Shift end time must be after start time.');
        }

        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->touch();
    }

    public function getDurationMinutes(): int
    {
        return (int) (($this->endsAt->getTimestamp() - $this->startsAt->getTimestamp()) / 60);
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getEmployeeId(): Uuid { return $this->employeeId; }
    public function getLabel(): ?string { return $this->label; }
    public function getStartsAt(): DateTimeImmutable { return $this->startsAt; }
    public function getEndsAt(): DateTimeImmutable { return $this->endsAt; }
    public function getSite(): ?string { return $this->site; }
    public function isPublished(): bool { return $this->published; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
